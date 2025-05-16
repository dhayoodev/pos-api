<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
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
     *     summary="List all transactions",
     *     tags={"Transactions"},
     *     @OA\Response(
     *         response=200,
     *         description="List of transactions",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Transaction")
     *         )
     *     ),
     *     security={{"sanctum": {}}}
     * )
     */
    public function index()
    {
        return TransactionResource::collection(
            Transaction::with(['user', 'details.product', 'creator'])->get()
        );
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