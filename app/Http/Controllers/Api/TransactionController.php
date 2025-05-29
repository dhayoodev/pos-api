<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Product;
use App\Models\StockProduct;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\AdjustmentProduct;

/**
 * @OA\Tag(
 *     name="Transactions",
 *     description="API Endpoints for managing transactions"
 * )
 */
class TransactionController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/transactions",
     *     summary="List all transactions with filters and pagination",
     *     tags={"Transactions"},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by transaction status",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="Filter by user ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search in transaction number or customer name",
     *         required=false,
     *         @OA\Schema(type="string")
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
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Transaction")),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Transaction::query()
            ->active()
            ->with(['user', 'details.product'])
            ->selectRaw('transactions.*, (SELECT SUM(subtotal) FROM transaction_details WHERE transaction_details.trans_id = transactions.id) as total_subtotal');

        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            $query->where('payment_status', $request->status);
        }

        // Filter by user_id
        if ($request->has('user_id') && $request->user_id !== '') {
            $query->where('user_id', $request->user_id);
        }

        // Search functionality
        /* if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('transaction_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        } */

        // Date range filter
        if ($request->has('date_from') && $request->date_from !== '') {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to !== '') {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Order by latest first
        $query->orderBy('created_at', 'desc');

        // Pagination
        $transactions = $query->paginate($request->per_page ?? 15);

        return TransactionResource::collection($transactions);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/transactions/report",
     *     summary="List all transactions with filters and pagination grouped by data",
     *     tags={"Transactions"},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by transaction status",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="Filter by user ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search in transaction number or customer name",
     *         required=false,
     *         @OA\Schema(type="string")
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
     *         description="Transactions grouped by date",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="date", type="string", format="date"),
     *                 @OA\Property(property="total_transactions", type="integer"),
     *                 @OA\Property(property="total_amount", type="number", format="float"),
     *                 @OA\Property(
     *                     property="transactions",
     *                     type="array",
     *                     @OA\Items(ref="#/components/schemas/Transaction")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function groupByData(Request $request)
    {
        // Get pagination parameters
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);
        
        // First, get the distinct dates with transaction data
        $datesQuery = Transaction::query()
            ->select([
                DB::raw('DATE(transactions.created_at) as trans_date'),
                DB::raw('COUNT(DISTINCT transactions.id) as transaction_count'),
                DB::raw('COALESCE(COUNT(DISTINCT(CASE WHEN transactions.payment_status = \'paid\' THEN transactions.id END)), 0) as paid_count'),
                DB::raw('COALESCE(COUNT(DISTINCT(CASE WHEN transactions.payment_status = \'refunded\' THEN transactions.id END)), 0) as refunded_count'),
                DB::raw('COALESCE(SUM(CASE WHEN transactions.payment_status = \'paid\' THEN transaction_details.subtotal ELSE 0 END), 0) as sum_paid_subtotal'),
                DB::raw('COALESCE(SUM(CASE WHEN transactions.payment_status = \'refunded\' THEN transaction_details.subtotal ELSE 0 END), 0) as sum_refunded_subtotal'),
                DB::raw('SUM(DISTINCT CASE WHEN transactions.payment_status = \'paid\' THEN transactions.total_price ELSE 0 END) as sum_paid_price'),
                DB::raw('SUM(DISTINCT CASE WHEN transactions.payment_status = \'refunded\' THEN transactions.total_price ELSE 0 END) as sum_refunded_price'),
                DB::raw('SUM(DISTINCT transactions.total_payment) as sum_payment'),
                DB::raw('SUM(DISTINCT transactions.total_tax) as sum_tax'),
                DB::raw('COALESCE(SUM(CASE 
                    WHEN transactions.discount_id IS NOT NULL AND transactions.type_discount = 1 AND transactions.payment_status = \'paid\'
                    THEN transactions.amount_discount 
                    WHEN transactions.discount_id IS NOT NULL AND transactions.type_discount = 2 AND transactions.payment_status = \'paid\'
                    THEN (transaction_details.subtotal * transactions.amount_discount / 100)
                    ELSE 0 
                END), 0) as sum_paid_discount'),
                DB::raw('COALESCE(SUM(CASE 
                    WHEN transactions.discount_id IS NOT NULL AND transactions.type_discount = 1 AND transactions.payment_status = \'refunded\'
                    THEN transactions.amount_discount 
                    WHEN transactions.discount_id IS NOT NULL AND transactions.type_discount = 2 AND transactions.payment_status = \'refunded\'
                    THEN (transaction_details.subtotal * transactions.amount_discount / 100)
                    ELSE 0 
                END), 0) as sum_refunded_discount')
            ])
            ->leftJoin('transaction_details', 'transactions.id', '=', 'transaction_details.trans_id')
            ->active()
            ->whereIn('payment_status', ['paid', 'refunded'])
            ->groupBy('trans_date')
            ->orderBy('trans_date', 'desc');

        // Apply user_id filter
        if ($request->has('user_id') && $request->user_id !== '') {
            $datesQuery->where('user_id', $request->user_id);
        }

        // Apply date range filter if needed
        if ($request->has('date_from') && $request->date_from !== '') {
            $datesQuery->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to !== '') {
            $datesQuery->whereDate('created_at', '<=', $request->date_to);
        }

        // Get paginated dates
        $dates = $datesQuery->paginate($perPage, ['*'], 'page', $page);

        // If no dates found, return empty paginated response
        if ($dates->isEmpty()) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'current_page' => $page,
                    'from' => null,
                    'last_page' => 1,
                    'per_page' => $perPage,
                    'to' => null,
                    'total' => 0
                ]
            ]);
        }

        // Get all dates for the current page
        $dateValues = $dates->pluck('trans_date');

        // Get transactions for these dates
        $transactions = Transaction::query()
            ->with(['user', 'details.product'])
            ->select([
                'transactions.*',
                DB::raw('(SELECT SUM(subtotal) FROM transaction_details WHERE transaction_details.trans_id = transactions.id) as total_subtotal'),
                DB::raw('DATE(transactions.created_at) as transaction_date')
            ])
            ->whereIn(DB::raw('DATE(created_at)'), $dateValues)
            ->orderBy('created_at', 'desc')
            ->get();

        // Group transactions by date and format the response
        $groupedTransactions = $transactions->groupBy('transaction_date')
            ->map(function ($dateTransactions, $date) use ($dates) {
                $dateInfo = $dates->firstWhere('trans_date', $date);
                
                return [
                    'date' => $date,
                    'total_transactions' => $dateInfo->transaction_count,
                    'paid_transactions' => (int) $dateInfo->paid_count,
                    'refunded_transactions' => (int) $dateInfo->refunded_count,
                    'paid' => [
                        'subtotal' => (float) $dateInfo->sum_paid_subtotal,
                        'total' => (float) $dateInfo->sum_paid_price,
                        'discount' => (float) $dateInfo->sum_paid_discount,
                    ],
                    'refunded' => [
                        'subtotal' => (float) $dateInfo->sum_refunded_subtotal,
                        'total' => (float) $dateInfo->sum_refunded_price,
                        'discount' => (float) $dateInfo->sum_refunded_discount,
                    ],
                    'total_tax' => (float) $dateInfo->sum_tax,
                    'transactions' => TransactionResource::collection($dateTransactions)
                ];
            })
            ->sortByDesc('date')
            ->values();

        // Create paginated response
        return response()->json([
            'data' => $groupedTransactions,
            'meta' => [
                'current_page' => $dates->currentPage(),
                'from' => $dates->firstItem(),
                'last_page' => $dates->lastPage(),
                'per_page' => $dates->perPage(),
                'to' => $dates->lastItem(),
                'total' => $dates->total()
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/transactions",
     *     summary="Create a new transaction",
     *     tags={"Transactions"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/TransactionRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Transaction created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Transaction")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     security={{"sanctum": {}}}
     * )
     */
    public function store(TransactionRequest $request)
    {
        // Start database transaction
        return DB::transaction(function () use ($request) {
            $validated = $request->validated();

            // Prepare details and validate stock
            $details = collect($validated['details'])->map(function ($detail) use ($validated) {
                // Fetch product
                $product = Product::findOrFail($detail['product_id']);
                // Check stock availability
                $stockProduct = StockProduct::where('product_id', $detail['product_id'])
                    ->where('user_id', $validated['user_id'])
                    ->orderBy('created_at', 'desc')
                    ->firstOrFail();

                // Check if there's enough stock for the transaction (excluding refunds
                if ($stockProduct->quantity < $detail['quantity'] && $validated['payment_status'] !== 'refunded') {
                    throw ValidationException::withMessages([
                        'details' => ["Insufficient stock for product: {$product->product_name}"]
                    ]);
                }

                $price = $product->price;
                $subtotal = $price * $detail['quantity'];

                return [
                    'product_id' => $detail['product_id'],
                    'quantity' => $detail['quantity'],
                    'price' => $price,
                    'subtotal' => $subtotal,
                ];
            });

            // Compute total price from details
            $totalPrice = $details->sum('subtotal');

            // Create reason refund
            $reason = "";
            if ($validated['payment_status'] === 'refunded') {
                if ($validated['trans_id']) {
                    $reason .= 'Refund Transaction #' . $validated['trans_id'];
                }
                // type_reason 0: Produk Return, 1: Misplaced Transaction, 2:Order cancelation, 3: Others
                /* if ($validated['type_reason'] && $validated['type_reason'] === '1') {
                    $reason .= ' (Misplaced Transaction)';
                } elseif ($validated['type_reason'] && $validated['type_reason'] === '2') {
                    $reason .= ' (Order cancelation)';
                } elseif ($validated['type_reason'] && $validated['reason'] && $validated['type_reason'] === '3') {
                    $reason .= ' ('. $validated['reason'] . ')';
                } elseif ($validated['type_reason']) {
                    $reason .= ' (Produk Return)';
                } */

                switch ($validated['type_reason']) {
                    case '0':
                        $reason .= ' (Produk Return)';
                        break;
                    case '1':
                        $reason .= ' (Misplaced Transaction)';
                        break;
                    case '2':
                        $reason .= ' (Order cancelation)';
                        break;
                    case '3':
                        $reason .= ' ('. $validated['reason'] . ')';
                        break;
                    default:
                        $reason .= ' (Produk Return)';
                        break;
                }
            }

            // Create transaction with updated schema fields
            $transaction = Transaction::create([
                'user_id' => $validated['user_id'],
                'shift_id' => $validated['shift_id'],
                'discount_id' => $validated['discount_id'],
                'payment_method' => $validated['payment_method'],
                'total_price' => $validated['total_price'],
                'total_payment' => $validated['total_payment'],
                'total_tax' => $validated['total_tax'],
                'type_discount' => $validated['type_discount'],
                'amount_discount' => $validated['amount_discount'],
                'payment_status' => $validated['payment_status'],
                'date' => $validated['date'],
                'type_reason' => $validated['type_reason'],
                'reason' => $reason,
                'created_by' => auth()->id(),
                'created_at' => now(),
            ]);

            // Save transaction details
            $transaction->details()->createMany($details->toArray());

            $transaction->load(['user', 'details.product', 'creator']);

            // Adjust stock and create adjustment product
            $details->each(function ($detail) use ($transaction) {
                // Adjust stock
                $stockProduct = StockProduct::where('product_id', $detail['product_id'])
                    ->where('user_id', $transaction->user_id)
                    ->orderBy('created_at', 'desc')
                    ->firstOrFail();

                // Create adjustment product
                $oldQuantity = $stockProduct->quantity;
                if ($transaction->payment_status === 'refunded') {
                    $type = AdjustmentProduct::TYPE_PLUS;
                    // Calculate new quantity after refunding the product
                    $newQuantity = $stockProduct->quantity + $detail['quantity'];
                } else {
                    $type = AdjustmentProduct::TYPE_MINUS;
                    // Calculate new quantity after selling the product
                    $newQuantity = $stockProduct->quantity - $detail['quantity'];
                }

                // Adjustment product
                $note = 'Transaction #' . $transaction->id;
                AdjustmentProduct::create([
                    'product_id' => $detail['product_id'],
                    'user_id' => $transaction->user_id,
                    'stock_id' => $stockProduct->id,
                    'type' => $type,
                    'quantity' => $detail['quantity'],
                    'note' => $note . ' (Initial Stock: ' . $oldQuantity . ', Actual Stock: ' . $newQuantity . ')',
                    'image' => '',
                    'created_by' => auth()->id(),
                    'created_at' => Carbon::now(),
                ]);

                // Update stock product
                $stockProduct->update(['quantity' => $newQuantity]);
            });

            // Adjustment shift expected cash balance
            $shift = $transaction->shift;
            if ($transaction->payment_status === 'paid' && $transaction->payment_method === 'cash') {
                $shift->update(['expected_cash_balance' => $shift->expected_cash_balance + $transaction->total_payment]);
            } elseif ($transaction->payment_status === 'refunded' && $transaction->payment_method === 'cash') {
                $shift->update(['expected_cash_balance' => $shift->expected_cash_balance - $transaction->total_payment]);
            }

            return new TransactionResource($transaction);
        });
    }

    /**
     * @OA\Get(
     *     path="/api/v1/transactions/{id}",
     *     summary="Get transaction details",
     *     tags={"Transactions"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Transaction ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transaction details",
     *         @OA\JsonContent(ref="#/components/schemas/Transaction")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Transaction not found"
     *     ),
     *     security={{"sanctum": {}}}
     * )
     */
    public function show(Transaction $transaction)
    {
        return new TransactionResource($transaction->load(['user', 'details.product', 'creator']));
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/transactions/{id}",
     *     summary="Delete a transaction",
     *     tags={"Transactions"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Transaction ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Transaction deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Transaction not found"
     *     ),
     *     security={{"sanctum": {}}}
     * )
     */
    public function destroy(Transaction $transaction)
    {
        $transaction->update([
            'is_deleted' => 1,
            'deleted_by' => auth()->id(),
            'deleted_date' => now(),
        ]);
        return new TransactionResource($transaction);
    }
}