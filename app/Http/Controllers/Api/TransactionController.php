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
     *         name="payment_method",
     *         in="query",
     *         description="Filter by transaction payment_method",
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

        // Filter by payment_method
        if ($request->has('payment_method') && $request->payment_method !== '') {
            $query->where('payment_method', $request->payment_method);
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
     *         name="group_by",
     *         in="query",
     *         description="Group by day or month",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="year",
     *         in="query",
     *         description="Filter by year",
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
        $groupBy = $request->input('group_by', 'day'); // 'day' or 'month'
        $year = $request->input('year', date('Y'));

        // Base query for transactions with filters
        $baseQuery = Transaction::query()
            ->active()
            ->whereIn('payment_status', ['paid', 'refunded'])
            ->when($request->has('user_id') && $request->user_id !== '', function ($q) use ($request) {
                $q->where('user_id', $request->user_id);
            })
            ->when($request->has('date_from') && $request->date_from !== '', function ($q) use ($request) {
                $q->whereDate('transactions.created_at', '>=', $request->date_from);
            })
            ->when($request->has('date_to') && $request->date_to !== '', function ($q) use ($request) {
                $q->whereDate('transactions.created_at', '<=', $request->date_to);
            })
            ->when($groupBy === 'month', function ($q) use ($year) {
                $q->whereYear('transactions.created_at', $year);
            });

        // --- Transaction-level aggregates ---
        $transactionAggregatesQuery = (clone $baseQuery)
            ->selectRaw(
                ($groupBy === 'month' ? 'DATE_FORMAT(created_at, "%Y-%m-01")' : 'DATE(created_at)') . ' as trans_date, 
                COUNT(id) as transaction_count,
                SUM(CASE WHEN payment_status = "paid" THEN 1 ELSE 0 END) as paid_count,
                SUM(CASE WHEN payment_status = "refunded" THEN 1 ELSE 0 END) as refunded_count,
                SUM(CASE WHEN payment_status = "paid" THEN total_price ELSE 0 END) as sum_paid_price,
                SUM(CASE WHEN payment_status = "refunded" THEN total_price ELSE 0 END) as sum_refunded_price,
                SUM(CASE WHEN payment_status = "paid" AND payment_method = "cash" THEN total_payment ELSE 0 END) as sum_cash_payment,
                SUM(CASE WHEN payment_status = "paid" AND payment_method = "bank_transfer" THEN total_payment ELSE 0 END) as sum_bank_transfer_payment,
                SUM(CASE WHEN payment_status = "paid" AND payment_method = "ewallet" THEN total_payment ELSE 0 END) as sum_ewallet_payment,
                SUM(CASE WHEN payment_status = "paid" AND payment_method = "qris" THEN total_payment ELSE 0 END) as sum_qris_payment,
                SUM(CASE WHEN payment_status = "refunded" AND payment_method = "cash" THEN total_price ELSE 0 END) as sum_cash_refunded,
                SUM(CASE WHEN payment_status = "refunded" AND payment_method = "bank_transfer" THEN total_price ELSE 0 END) as sum_bank_transfer_refunded,
                SUM(CASE WHEN payment_status = "refunded" AND payment_method = "ewallet" THEN total_price ELSE 0 END) as sum_ewallet_refunded,
                SUM(CASE WHEN payment_status = "refunded" AND payment_method = "qris" THEN total_price ELSE 0 END) as sum_qris_refunded,
                SUM(total_payment) as sum_payment,
                SUM(total_tax) as sum_tax,
                SUM(CASE WHEN discount_id IS NOT NULL AND type_discount = 1 AND payment_status = "paid" THEN amount_discount ELSE 0 END) as sum_paid_discount_fixed,
                SUM(CASE WHEN discount_id IS NOT NULL AND type_discount = 1 AND payment_status = "refunded" THEN amount_discount ELSE 0 END) as sum_refunded_discount_fixed'
            )
            ->groupBy('trans_date');

        // --- Detail-level aggregates ---
        $detailAggregatesQuery = (clone $baseQuery)
            ->join('transaction_details', 'transactions.id', '=', 'transaction_details.trans_id')
            ->selectRaw(
                ($groupBy === 'month' ? 'DATE_FORMAT(transactions.created_at, "%Y-%m-01")' : 'DATE(transactions.created_at)') . ' as trans_date,
                SUM(CASE WHEN transactions.payment_status = "paid" THEN transaction_details.subtotal ELSE 0 END) as sum_paid_subtotal,
                SUM(CASE WHEN transactions.payment_status = "refunded" THEN transaction_details.subtotal ELSE 0 END) as sum_refunded_subtotal,
                SUM(CASE WHEN transactions.discount_id IS NOT NULL AND transactions.type_discount = 2 AND transactions.payment_status = "paid" THEN (transaction_details.subtotal * transactions.amount_discount / 100) ELSE 0 END) as sum_paid_discount_percentage,
                SUM(CASE WHEN transactions.discount_id IS NOT NULL AND transactions.type_discount = 2 AND transactions.payment_status = "refunded" THEN (transaction_details.subtotal * transactions.amount_discount / 100) ELSE 0 END) as sum_refunded_discount_percentage'
            )
            ->groupBy('trans_date');

        // --- Products-level aggregates ---
        $productAggregatesQuery = (clone $baseQuery)
            ->join('transaction_details', 'transactions.id', '=', 'transaction_details.trans_id')
            ->join('products', 'transaction_details.product_id', '=', 'products.id')
            ->selectRaw(
                ($groupBy === 'month' ? 'DATE_FORMAT(transactions.created_at, "%Y-%m-01")' : 'DATE(transactions.created_at)') . ' as trans_date,
                products.id as product_id,
                products.name as product_name,
                products.price as product_price,
                COALESCE(SUM(CASE WHEN transactions.payment_status = "paid" THEN transaction_details.quantity ELSE 0 END), 0) as sold_quantity,
                COALESCE(SUM(CASE WHEN transactions.payment_status = "refunded" THEN transaction_details.quantity ELSE 0 END), 0) as refunded_quantity'
            )
            ->groupBy('trans_date', 'products.id', 'products.name', 'products.price');

        // --- Combine aggregates ---
        $transactionSql = $transactionAggregatesQuery->toSql();
        $detailSql = $detailAggregatesQuery->toSql();
        
        // Create a new query builder for dates with transaction and detail aggregates
        $datesQuery = DB::table(DB::raw("($transactionSql) as t_agg"))
            ->leftJoin(
                DB::raw("($detailSql) as d_agg"), 
                't_agg.trans_date', 
                '=', 
                'd_agg.trans_date'
            )
            ->selectRaw('t_agg.*, 
                         COALESCE(d_agg.sum_paid_subtotal, 0) as sum_paid_subtotal,
                         COALESCE(d_agg.sum_refunded_subtotal, 0) as sum_refunded_subtotal,
                         (t_agg.sum_paid_discount_fixed + COALESCE(d_agg.sum_paid_discount_percentage, 0)) as sum_paid_discount,
                         (t_agg.sum_refunded_discount_fixed + COALESCE(d_agg.sum_refunded_discount_percentage, 0)) as sum_refunded_discount'
            );
            
        // Manually add all bindings in the correct order
        $bindings = array_merge(
            $transactionAggregatesQuery->getBindings(),
            $detailAggregatesQuery->getBindings()
        );
        
        // Apply all bindings to the query
        foreach ($bindings as $binding) {
            $datesQuery->addBinding($binding);
        }
        
        // Get product data separately to avoid cartesian product
        $productResults = $productAggregatesQuery->get();
        
        // Group product data by date
        $productsByDate = $productResults->groupBy('trans_date');

        // First, get all the results
        $results = $datesQuery->get();
        
        // Then calculate the grand totals manually
        $grandTotals = (object)[
            'transaction_count' => $results->sum('transaction_count'),
            'paid_count' => $results->sum('paid_count'),
            'refunded_count' => $results->sum('refunded_count'),
            'sum_paid_subtotal' => $results->sum('sum_paid_subtotal'),
            'sum_refunded_subtotal' => $results->sum('sum_refunded_subtotal'),
            'sum_paid_price' => $results->sum('sum_paid_price'),
            'sum_refunded_price' => $results->sum('sum_refunded_price'),
            'sum_cash_payment' => $results->sum('sum_cash_payment'),
            'sum_bank_transfer_payment' => $results->sum('sum_bank_transfer_payment'),
            'sum_ewallet_payment' => $results->sum('sum_ewallet_payment'),
            'sum_qris_payment' => $results->sum('sum_qris_payment'),
            'sum_cash_refunded' => $results->sum('sum_cash_refunded'),
            'sum_bank_transfer_refunded' => $results->sum('sum_bank_transfer_refunded'),
            'sum_ewallet_refunded' => $results->sum('sum_ewallet_refunded'),
            'sum_qris_refunded' => $results->sum('sum_qris_refunded'),
            'sum_payment' => $results->sum('sum_payment'),
            'sum_tax' => $results->sum('sum_tax'),
            'sum_paid_discount' => $results->sum('sum_paid_discount'),
            'sum_refunded_discount' => $results->sum('sum_refunded_discount'),
        ];

        // Paginate the results
        $results = $datesQuery
            ->orderBy('t_agg.trans_date', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        // Process results to include product data
        $processedResults = collect($results->items())->map(function($item) use ($productsByDate) {
            $item->products = $productsByDate->get($item->trans_date, collect())->map(function($product) {
                return [
                    'product_id' => $product->product_id,
                    'name' => $product->product_name,
                    'price' => (float)$product->product_price,
                    'sold_quantity' => (int)$product->sold_quantity,
                    'refunded_quantity' => (int)$product->refunded_quantity,
                    'net_quantity' => (int)$product->sold_quantity - (int)$product->refunded_quantity,
                    'total_sales' => (float)($product->product_price * ($product->sold_quantity - $product->refunded_quantity))
                ];
            })->values();
            return $item;
        });

        // Calculate product-level totals
        $productTotals = [
            'total_products_sold' => $productResults->sum('sold_quantity'),
            'total_products_refunded' => $productResults->sum('refunded_quantity'),
            'unique_products' => $productResults->unique('product_id')->count(),
            'total_sales_value' => $productResults->sum(function($product) {
                return $product->product_price * ($product->sold_quantity - $product->refunded_quantity);
            })
        ];

        return response()->json([
            'data' => $processedResults,
            'totals' => (object) array_merge((array) $grandTotals, (array) $productTotals),
            'meta' => [
                'total' => $results->total(),
                'per_page' => $results->perPage(),
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'from' => $results->firstItem(),
                'to' => $results->lastItem(),
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
            $totalChange = $transaction->total_price - $transaction->total_payment;
            if ($transaction->payment_status === 'paid' && $transaction->payment_method === 'cash') {
                $shift->update(['expected_cash_balance' => $shift->expected_cash_balance + $transaction->total_payment + $totalChange]);
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