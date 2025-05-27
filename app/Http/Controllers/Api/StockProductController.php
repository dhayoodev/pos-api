<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StockProductResource;
use App\Models\StockProduct;
use App\Models\AdjustmentProduct;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\StockProductRequest;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * @OA\Tag(
 *     name="Stock Products",
 *     description="API Endpoints for managing stock products"
 * )
 */
class StockProductController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/stock-products",
     *     summary="List all stock products with optional filters",
     *     tags={"Stock Products"},
     *     @OA\Parameter(
     *         name="product_id",
     *         in="query",
     *         description="Filter by product ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="Filter by user ID (branch)",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of stock products",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/StockProduct")
     *         )
     *     ),
     *     security={{"sanctum": {}}}
     * )
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = StockProduct::with(['product', 'user']);

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $stockProducts = $query->get();
        return StockProductResource::collection($stockProducts);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/stock-products",
     *     summary="Create a new stock product",
     *     tags={"Stock Products"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/StockProductRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Stock product created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/StockProduct")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     security={{"sanctum": {}}}
     * )
     */
    public function store(StockProductRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Check if stock already exists for this product and user
        $existingStock = StockProduct::where('product_id', $validated['product_id'])
            ->where('user_id', $validated['user_id'])
            ->first();

        if ($existingStock) {
            return response()->json([
                'message' => 'Stock already exists for this product and user',
                'data' => new StockProductResource($existingStock)
            ], 409);
        }

        $stockProduct = StockProduct::create([
            'product_id' => $validated['product_id'],
            'user_id' => $validated['user_id'],
            'quantity' => $validated['quantity'],
            'created_by' => auth()->id(),
            'created_at' => Carbon::now(),
        ]);

        // Create adjustment record for new stock
        $note = $validated['note'] ?? 'Initial stock';
        AdjustmentProduct::create([
            'product_id' => $validated['product_id'],
            'user_id' => $validated['user_id'],
            'stock_id' => $stockProduct->id,
            'type' => AdjustmentProduct::TYPE_PLUS,
            'quantity' => $validated['quantity'],
            'note' => $note . ' (Initial Stock: 0, Actual Stock: ' . $validated['quantity'] . ')',
            'image' => $validated['image'] ?? '',
            'created_by' => auth()->id(),
            'created_at' => Carbon::now(),
        ]);

        return response()->json(new StockProductResource($stockProduct), 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/stock-products/{id}",
     *     summary="Get a specific stock product",
     *     tags={"Stock Products"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Stock product details",
     *         @OA\JsonContent(ref="#/components/schemas/StockProduct")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Stock product not found"
     *     ),
     *     security={{"sanctum": {}}}
     * )
     */
    public function show(StockProduct $stockProduct): StockProductResource
    {
        return new StockProductResource($stockProduct->load(['product', 'user']));
    }

    /**
     * @OA\Put(
     *     path="/api/v1/stock-products/{id}",
     *     summary="Update a stock product",
     *     tags={"Stock Products"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/StockProduct")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Stock product updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/StockProduct")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Stock product not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     security={{"sanctum": {}}}
     * )
     */
    public function update(StockProductRequest $request, StockProduct $stockProduct): StockProductResource
    {
        $validated = $request->validated();
        $oldQuantity = $stockProduct->quantity;
        $newQuantity = $validated['quantity'];

        $stockProduct->update($validated);

        // Create adjustment record for stock update
        if ($oldQuantity !== $newQuantity) {
            $type = $newQuantity > $oldQuantity ? AdjustmentProduct::TYPE_PLUS : AdjustmentProduct::TYPE_MINUS;
            $quantity = abs($newQuantity - $oldQuantity);

            $note = $validated['note'] ?? 'Stock quantity update';

            AdjustmentProduct::create([
                'product_id' => $stockProduct->product_id,
                'user_id' => $stockProduct->user_id,
                'stock_id' => $stockProduct->id,
                'type' => $type,
                'quantity' => $quantity,
                'note' => $note . ' (Initial Stock: ' . $oldQuantity . ', Actual Stock: ' . $newQuantity . ')',
                'image' => $validated['image'] ?? '',
                'created_by' => auth()->id(),
                'created_at' => Carbon::now(),
            ]);
        }

        return new StockProductResource($stockProduct->load(['product', 'user']));
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/stock-products/{id}",
     *     summary="Delete a stock product",
     *     tags={"Stock Products"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Stock product deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Stock product not found"
     *     ),
     *     security={{"sanctum": {}}}
     * )
     */
    public function destroy(StockProduct $stockProduct): JsonResponse
    {
        $stockProduct->delete();
        return response()->json(null, 204);
    }
}