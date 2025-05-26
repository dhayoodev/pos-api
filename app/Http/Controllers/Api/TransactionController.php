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
        $query = Transaction::query()->with(['user', 'details.product']);

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
        $query->orderBy('created_date', 'desc');

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
            
            // Calculate totals and create details
            $details = collect($validated['details'])->map(function ($detail) {
                $product = Product::findOrFail($detail['product_id']);
                
                // Verify stock availability
                if ($product->stock < $detail['qty']) {
                    throw ValidationException::withMessages([
                        'details' => ["Insufficient stock for product: {$product->product_name}"]
                    ]);
                }
                
                // Calculate price and subtotal
                $price = $product->price;
                $subtotal = $price * $detail['qty'];
                
                // Decrease stock
                $product->decrement('stock', $detail['qty']);
                
                return [
                    'product_id' => $detail['product_id'],
                    'qty' => $detail['qty'],
                    'price' => $price,
                    'subtotal' => $subtotal,
                    'created_by' => auth()->id(),
                    'created_date' => now()
                ];
            });
            
            // Create transaction
            $transaction = Transaction::create([
                'user_id' => $validated['user_id'],
                'date' => $validated['date'],
                'payment_status' => $validated['payment_status'],
                'total_price' => $details->sum('subtotal'),
                'created_by' => auth()->id(),
                'created_date' => now()
            ]);
            
            // Create transaction details
            $transaction->details()->createMany($details->toArray());
            
            // Load relationships for response
            $transaction->load(['user', 'details.product', 'creator']);
            
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
            'is_deleted' => true,
            'deleted_by' => auth()->id(),
            'deleted_date' => now(),
        ]);
        
        return response()->json(null, 204);
    }
} 