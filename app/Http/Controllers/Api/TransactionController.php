<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\Product;
use App\Models\StockProduct;
use App\Models\AdjustmentProduct;
use Illuminate\Support\Carbon;

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
    /* public function index()
    {
        return TransactionResource::collection(
            Transaction::with(['user', 'details.product', 'creator'])->get()
        );
    } */
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
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to !== '') {
            $query->whereDate('date', '<=', $request->date_to);
        }

        // Order by latest first
        $query->orderBy('created_at', 'desc');

        // Pagination
        $transactions = $query->paginate($request->per_page ?? 15);

        return TransactionResource::collection($transactions);
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
            if ($validated['trans_id']) {
                $reason .= 'Refund Transaction #' . $validated['trans_id'];
            }
            // type_reason 0: Produk Return, 1: Misplaced Transaction, 2:Order cancelation, 3: Others
            if ($validated['type_reason'] && $validated['type_reason'] === '0') {
                $reason.= ' (Produk Return)';
            } elseif ($validated['type_reason'] && $validated['type_reason'] === '1') {
                $reason.= ' (Misplaced Transaction)';
            } elseif ($validated['type_reason'] && $validated['type_reason'] === '2') {
                $reason.= ' (Order cancelation)';
            } elseif ($validated['type_reason'] && $validated['reason'] && $validated['type_reason'] === '3') {
                $reason .= ' ('. $validated['reason'] . ')';
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