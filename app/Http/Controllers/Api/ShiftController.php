<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\ShiftHistory;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\ShiftRequest;
use App\Http\Requests\ShiftHistoryRequest;
use App\Http\Resources\ShiftResource;
use App\Http\Resources\ShiftHistoryResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @OA\Tag(
 *     name="Shifts",
 *     description="API Endpoints for managing shifts"
 * )
 */
class ShiftController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/shifts",
     *     summary="List all shifts with pagination",
     *     tags={"Shifts"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="Filter by user ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="date_from",
     *         in="query",
     *         description="Filter transactions from this date (Y-m-d format)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="date_to",
     *         in="query",
     *         description="Filter transactions to this date (Y-m-d format)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Paginated list of shifts",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Shift")),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    /* public function index(): AnonymousResourceCollection
    {
        return ShiftResource::collection(Shift::all());
    } */
    public function index(Request $request): AnonymousResourceCollection
    {
        // First, get the shifts with basic relations
        $shifts = Shift::with(['user', 'histories'])
            ->when($request->has('status') && $request->status !== '', function($q) use ($request) {
                $q->where('payment_status', $request->status);
            })
            ->when($request->has('user_id') && $request->user_id !== '', function($q) use ($request) {
                $q->where('user_id', $request->user_id);
            })
            ->when($request->has('date_from') && $request->date_from !== '', function($q) use ($request) {
                $q->whereDate('created_at', '>=', $request->date_from);
            })
            ->when($request->has('date_to') && $request->date_to !== '', function($q) use ($request) {
                $q->whereDate('created_at', '<=', $request->date_to);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        // Get the shift IDs
        $shiftIds = $shifts->pluck('id')->toArray();

        // Get product sales for these shifts
        $productSales = DB::table('transactions')
            ->select(
                'transactions.shift_id',
                'transaction_details.product_id',
                'products.name as product_name',
                'products.price',
                DB::raw('SUM(CASE WHEN transactions.payment_status = "paid" THEN transaction_details.quantity ELSE 0 END) as sold_quantity'),
                DB::raw('SUM(CASE WHEN transactions.payment_status = "refunded" THEN transaction_details.quantity ELSE 0 END) as refunded_quantity'),
                DB::raw('SUM(CASE 
                    WHEN transactions.payment_status = "paid" THEN transaction_details.subtotal 
                    WHEN transactions.payment_status = "refunded" THEN -transaction_details.subtotal 
                    ELSE 0 
                END) as net_sales')
            )
            ->join('transaction_details', 'transactions.id', '=', 'transaction_details.trans_id')
            ->join('products', 'transaction_details.product_id', '=', 'products.id')
            ->whereIn('transactions.shift_id', $shiftIds)
            ->whereIn('transactions.payment_status', ['paid', 'refunded'])
            ->groupBy(
                'transactions.shift_id',
                'transaction_details.product_id',
                'products.name',
                'products.price'
            )
            ->get()
            ->groupBy('shift_id');

        // Map the product sales to each shift
        $shifts->getCollection()->transform(function ($shift) use ($productSales) {
            $shift->products = $productSales->get($shift->id, collect())->map(function ($item) {
                return [
                    'id' => $item->product_id,
                    'name' => $item->product_name,
                    'price' => $item->price,
                    'sold_quantity' => $item->sold_quantity,
                    'refunded_quantity' => $item->refunded_quantity,
                    'total_sales' => $item->net_sales
                ];
            })->values();
            return $shift;
        });

        return ShiftResource::collection($shifts);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/shifts/user/{user_id}",
     *     summary="Get shifts by user ID",
     *     tags={"Shifts"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="user_id",
     *         in="path",
     *         required=true,
     *         description="ID of the user",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Shift"))
     *     ),
     *     @OA\Response(response=404, description="User not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    /* public function getByUserId(int $user_id): JsonResponse
    {
        $shifts = Shift::with(['user', 'creator', 'updater'])
            ->where('user_id', $user_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($shifts);
    } */
    public function getByUserId(int $user_id)
    {
        $shift = Shift::with(['user', 'creator', 'updater'])
            ->where('user_id', $user_id)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$shift) {
            return response()->json(['message' => 'No shift found for this user'], 404);
        }

        // Calculate paid in
        $paidIn = ShiftHistory::where('shift_id', $shift->id)
            ->where('type', 0)
            ->orderBy('created_at', 'desc')
            ->sum('amount');
        // Calculate paid out
        $paidOut = ShiftHistory::where('shift_id', $shift->id)
            ->where('type', 1)
            ->orderBy('created_at', 'desc')
            ->sum('amount');
        // Calculate cash payments
        $cashPayments = Transaction::where('shift_id', $shift->id)
            ->where('payment_method', 'cash')
            ->where('payment_status', 'paid')
            ->orderBy('created_at', 'desc')
            ->get();
        // Calculate cash refunds
        $cashRefunds = Transaction::where('shift_id', $shift->id)
            ->where('payment_method', 'cash')
            ->where('payment_status', 'refunded')
            ->orderBy('created_at', 'desc')
            ->sum('total_price');

        $cashChanges = (float) $cashPayments->sum('total_payment') - $cashPayments->sum('total_price');
        $expectedCash = (float) $shift->cash_balance + $paidIn - $paidOut + $cashPayments->sum('total_payment') - $cashChanges - $cashRefunds;

        // Calculate gross sales (sum of subtotal from transaction_details)
        $grossSales = Transaction::where('transactions.shift_id', $shift->id)
            ->leftJoin('transaction_details', 'transactions.id', '=', 'transaction_details.trans_id')
            ->where('transactions.payment_status', 'paid')
            ->orderBy('transactions.created_at', 'desc')
            ->sum('transaction_details.subtotal');
        // Calculate refund sales (sum of subtotal from transaction_details)
        $refundSales = Transaction::where('transactions.shift_id', $shift->id)
            ->leftJoin('transaction_details', 'transactions.id', '=', 'transaction_details.trans_id')
            ->where('transactions.payment_status', 'refunded')
            ->orderBy('transactions.created_at', 'desc')
            ->sum('transaction_details.subtotal');
        // Calculate discounts (sum of amount_discount from transactions)
        $discountSales = Transaction::where('shift_id', $shift->id)
            ->where('payment_status', 'paid')
            ->selectRaw('transactions.*, (SELECT SUM(subtotal) FROM transaction_details WHERE transaction_details.trans_id = transactions.id) as total_subtotal')
            ->get()
            ->sum(function ($transaction) {
                if ($transaction->type_discount === 1) { // Fixed discount
                    return $transaction->amount_discount;
                } elseif ($transaction->type_discount === 2) { // Percentage discount
                    return ($transaction->total_subtotal * $transaction->amount_discount) / 100;
                }
                return 0;
            });
        // Calculate discounts refund (sum of amount_discount from transactions)
        $discountRefund = Transaction::where('shift_id', $shift->id)
            ->where('payment_status', 'refunded')
            ->selectRaw('transactions.*, (SELECT SUM(subtotal) FROM transaction_details WHERE transaction_details.trans_id = transactions.id) as total_subtotal')
            ->get()
            ->sum(function ($transaction) {
                if ($transaction->type_discount === 1) { // Fixed discount
                    return $transaction->amount_discount;
                } elseif ($transaction->type_discount === 2) { // Percentage discount
                    return ($transaction->total_subtotal * $transaction->amount_discount) / 100;
                }
                return 0;
            });
        // Calculate net sales (gross sales - refund sales - discount sales)
        $netSales = (float) $grossSales - $refundSales - $discountSales;
        // Calculate tax sales (sum of total_tax from transactions)
        $taxSales = Transaction::where('shift_id', $shift->id)
            ->where('payment_status', 'paid')
            ->orderBy('created_at', 'desc')
            ->sum('total_tax');
        // Calculate refund tax sales (sum of total_tax from transactions)
        $taxRefund = Transaction::where('shift_id', $shift->id)
            ->where('payment_status', 'refunded')
            ->orderBy('created_at', 'desc')
            ->sum('total_tax');

        $array = [
            'cash_payments' => (float) $cashPayments->sum('total_payment'),
            'cash_refunds' => (float) $cashRefunds,
            'cash_changes' => $cashChanges,
            'paid_in' => (float) $paidIn,
            'paid_out' => (float) $paidOut,
            'expected_cash' => $expectedCash,
            'gross_sales' => (float) $grossSales,
            'refunds' => (float) $refundSales,
            'discounts' => (float) $discountSales - $discountRefund,
            'net_sales' => $netSales,
            'tax_sales' => (float) $taxSales - $taxRefund,
        ];

        return response()->json([
            'message' => 'Shift retrieved successfully',
            'data' => array_merge(
                (new ShiftResource($shift))->resolve(),
                $array
            )
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/shifts/{shift}",
     *     summary="Get shift details",
     *     tags={"Shifts"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="shift",
     *         in="path",
     *         required=true,
     *         description="ID of the shift",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/Shift")
     *     ),
     *     @OA\Response(response=404, description="Shift not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function show(Shift $shift): JsonResponse
    {
        $shift->load(['user', 'creator', 'updater' => function ($query) {
            $query->orderBy('created_at', 'desc');
        }]);

        if (!$shift) {
            return response()->json(['message' => 'No shift found for this user'], 404);
        }

        // Calculate paid in
        $paidIn = ShiftHistory::where('shift_id', $shift->id)
            ->where('type', 0)
            ->orderBy('created_at', 'desc')
            ->sum('amount');
        // Calculate paid out
        $paidOut = ShiftHistory::where('shift_id', $shift->id)
            ->where('type', 1)
            ->orderBy('created_at', 'desc')
            ->sum('amount');
        // Calculate cash payments
        $cashPayments = Transaction::where('shift_id', $shift->id)
            ->where('payment_method', 'cash')
            ->where('payment_status', 'paid')
            ->orderBy('created_at', 'desc')
            ->get();
        // Calculate cash refunds
        $cashRefunds = Transaction::where('shift_id', $shift->id)
            ->where('payment_method', 'cash')
            ->where('payment_status', 'refunded')
            ->orderBy('created_at', 'desc')
            ->sum('total_price');

        $cashChanges = (float) $cashPayments->sum('total_payment') - $cashPayments->sum('total_price');
        $expectedCash = (float) $shift->cash_balance + $paidIn - $paidOut + $cashPayments->sum('total_payment') - $cashChanges - $cashRefunds;
        $cashTotal = $cashPayments->sum('total_payment') - $cashRefunds;

        // Calculate gross sales (sum of subtotal from transaction_details)
        $grossSales = Transaction::where('transactions.shift_id', $shift->id)
            ->leftJoin('transaction_details', 'transactions.id', '=', 'transaction_details.trans_id')
            ->where('transactions.payment_status', 'paid')
            ->orderBy('transactions.created_at', 'desc')
            ->sum('transaction_details.subtotal');
        // Calculate refund sales (sum of subtotal from transaction_details)
        $refundSales = Transaction::where('transactions.shift_id', $shift->id)
            ->leftJoin('transaction_details', 'transactions.id', '=', 'transaction_details.trans_id')
            ->where('transactions.payment_status', 'refunded')
            ->orderBy('transactions.created_at', 'desc')
            ->sum('transaction_details.subtotal');
        // Calculate discounts (sum of amount_discount from transactions)
        $discountSales = Transaction::where('shift_id', $shift->id)
            ->where('payment_status', 'paid')
            ->selectRaw('transactions.*, (SELECT SUM(subtotal) FROM transaction_details WHERE transaction_details.trans_id = transactions.id) as total_subtotal')
            ->get()
            ->sum(function ($transaction) {
                if ($transaction->type_discount === 1) { // Fixed discount
                    return $transaction->amount_discount;
                } elseif ($transaction->type_discount === 2) { // Percentage discount
                    return ($transaction->total_subtotal * $transaction->amount_discount) / 100;
                }
                return 0;
            });
        // Calculate discounts refund (sum of amount_discount from transactions)
        $discountRefund = Transaction::where('shift_id', $shift->id)
            ->where('payment_status', 'refunded')
            ->selectRaw('transactions.*, (SELECT SUM(subtotal) FROM transaction_details WHERE transaction_details.trans_id = transactions.id) as total_subtotal')
            ->get()
            ->sum(function ($transaction) {
                if ($transaction->type_discount === 1) { // Fixed discount
                    return $transaction->amount_discount;
                } elseif ($transaction->type_discount === 2) { // Percentage discount
                    return ($transaction->total_subtotal * $transaction->amount_discount) / 100;
                }
                return 0;
            });
        // Calculate net sales (gross sales - refund sales - discount sales)
        $netSales = (float) $grossSales - $refundSales - $discountSales;
        // Calculate tax sales (sum of total_tax from transactions)
        $taxSales = Transaction::where('shift_id', $shift->id)
            ->where('payment_status', 'paid')
            ->orderBy('created_at', 'desc')
            ->sum('total_tax');
        // Calculate refund tax sales (sum of total_tax from transactions)
        $taxRefund = Transaction::where('shift_id', $shift->id)
            ->where('payment_status', 'refunded')
            ->orderBy('created_at', 'desc')
            ->sum('total_tax');

        // Calculate bank transfer payments
        $bankTransferPayments = Transaction::where('shift_id', $shift->id)
            ->where('payment_method', 'bank_transfer')
            ->where('payment_status', 'paid')
            ->orderBy('created_at', 'desc')
            ->sum('total_payment');
        $bankTransferRefunds = Transaction::where('shift_id', $shift->id)
            ->where('payment_method', 'bank_transfer')
            ->where('payment_status', 'refunded')
            ->orderBy('created_at', 'desc')
            ->sum('total_price');
        
        $bankTransferTotal = $bankTransferPayments - $bankTransferRefunds;
        
        // Calculate e-wallet payments
        $ewalletPayments = Transaction::where('shift_id', $shift->id)
            ->where('payment_method', 'e_wallet')
            ->where('payment_status', 'paid')
            ->orderBy('created_at', 'desc')
            ->sum('total_payment');
        $ewalletRefunds = Transaction::where('shift_id', $shift->id)
            ->where('payment_method', 'e_wallet')
            ->where('payment_status', 'refunded')
            ->orderBy('created_at', 'desc')
            ->sum('total_price');
        
        $ewalletTotal = $ewalletPayments - $ewalletRefunds;

        // Calculate qris payments
        $qrisPayments = Transaction::where('shift_id', $shift->id)
            ->where('payment_method', 'qris')
            ->where('payment_status', 'paid')
            ->orderBy('created_at', 'desc')
            ->sum('total_payment');
        $qrisRefunds = Transaction::where('shift_id', $shift->id)
            ->where('payment_method', 'qris')
            ->where('payment_status', 'refunded')
            ->orderBy('created_at', 'desc')
            ->sum('total_price');
        
        $qrisTotal = $qrisPayments - $qrisRefunds;

        // List transactions products
        $products = TransactionDetail::select(
                'products.id',
                'products.name',
                'products.price',
                \DB::raw('SUM(CASE WHEN transactions.payment_status = "paid" THEN transaction_details.quantity ELSE 0 END) as sold_quantity'),
                \DB::raw('SUM(CASE WHEN transactions.payment_status = "refunded" THEN transaction_details.quantity ELSE 0 END) as refunded_quantity'),
                \DB::raw('SUM(CASE 
                    WHEN transactions.payment_status = "paid" THEN transaction_details.subtotal 
                    WHEN transactions.payment_status = "refunded" THEN -transaction_details.subtotal 
                    ELSE 0 
                END) as total_sales')
            )
            ->join('transactions', 'transaction_details.trans_id', '=', 'transactions.id')
            ->join('products', 'transaction_details.product_id', '=', 'products.id')
            ->where('transactions.shift_id', $shift->id)
            ->whereIn('transactions.payment_status', ['paid', 'refunded'])
            ->groupBy('products.id', 'products.name', 'products.price')
            ->orderBy('total_sales', 'desc')
            ->get();
        

        $array = [
            'cash_payments' => (float) $cashPayments->sum('total_payment'),
            'cash_refunds' => (float) $cashRefunds,
            'cash_changes' => $cashChanges,
            'paid_in' => (float) $paidIn,
            'paid_out' => (float) $paidOut,
            'expected_cash' => $expectedCash,
            'gross_sales' => (float) $grossSales,
            'refunds' => (float) $refundSales,
            'discounts' => (float) $discountSales - $discountRefund,
            'net_sales' => $netSales,
            'tax_sales' => (float) $taxSales - $taxRefund,
            'total_tendered' => (float) $cashTotal + $bankTransferTotal + $ewalletTotal + $qrisTotal,
            'bank_transfer_total' => (float) $bankTransferTotal,
            'ewallet_total' => (float) $ewalletTotal,
            'qris_total' => (float) $qrisTotal,
            'cash_total' => (float) $cashTotal,
            'products' => $products,
        ];

        return response()->json([
            'message' => 'Shift retrieved successfully',
            'data' => array_merge(
                (new ShiftResource($shift))->resolve(),
                $array
            )
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/shifts",
     *     summary="Create a new shift",
     *     tags={"Shifts"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/ShiftRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Shift created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Shift")
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function store(ShiftRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $shift = DB::transaction(function () use ($validated, $request) {
            $shift = new Shift($validated);
            $shift->created_by = $request->user()->id;
            $shift->created_at = now();
            $shift->save();

            // Create initial balance history
            /* ShiftHistory::create([
                'shift_id' => $shift->id,
                'description' => 'Initial cash balance',
                'type' => ShiftHistory::TYPE_INCOME,
                'amount' => $validated['cash_balance'],
                'created_by' => $request->user()->id,
                'created_at' => now()
            ]); */

            return $shift;
        });

        $shift->load(['user', 'creator']);
        return response()->json($shift, 201);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/shifts/{shift}",
     *     summary="Update shift details",
     *     tags={"Shifts"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="shift",
     *         in="path",
     *         required=true,
     *         description="ID of the shift",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/ShiftRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Shift updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Shift")
     *     ),
     *     @OA\Response(response=404, description="Shift not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function update(ShiftRequest $request, Shift $shift): JsonResponse
    {
        $validated = $request->validated();

        $shift->final_cash_balance = $validated['final_cash_balance'];
        $shift->updated_by = $request->user()->id;
        $shift->updated_at = now();
        $shift->save();

        $shift->load(['user', 'creator', 'updater']);
        return response()->json(new ShiftResource($shift));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/shifts/{shift}/histories",
     *     summary="Add shift history",
     *     tags={"Shifts"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="shift",
     *         in="path",
     *         required=true,
     *         description="ID of the shift",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/ShiftHistoryRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="History added successfully",
     *         @OA\JsonContent(ref="#/components/schemas/ShiftHistory")
     *     ),
     *     @OA\Response(response=404, description="Shift not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function addHistory(ShiftHistoryRequest $request, Shift $shift): JsonResponse
    {
        $validated = $request->validated();

        $history = DB::transaction(function () use ($validated, $request, $shift) {
            $history = new ShiftHistory($validated);
            $history->shift_id = $shift->id;
            $history->created_by = $request->user()->id;
            $history->created_at = now();
            $history->save();

            // Update shift balance
            $shift->expected_cash_balance += $validated['type'] == ShiftHistory::TYPE_INCOME 
                ? $validated['amount'] 
                : -$validated['amount'];
            $shift->save();

            return $history;
        });

        $history->load(['creator']);
        return response()->json($history, 201);
    }
}