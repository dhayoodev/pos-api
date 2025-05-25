<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdjustmentProductResource;
use App\Models\AdjustmentProduct;
use App\Models\StockProduct;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\AdjustmentProductRequest;
use App\Http\Requests\AdjustmentProductImageRequest;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @OA\Tag(
 *     name="Adjustment Products",
 *     description="API Endpoints for managing adjustment products"
 * )
 */
class AdjustmentProductController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/adjustment-products/upload-image",
     *     summary="Upload image for an adjustment product",
     *     tags={"Adjustment Products"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="image",
     *                     type="file",
     *                     format="binary"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Image uploaded successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", ref="#/components/schemas/AdjustmentProduct")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Adjustment product not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function uploadImage(AdjustmentProductImageRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            // Delete old image if exists
           /*  if ($adjustmentProduct->image) {
                Storage::disk('public')->delete($adjustmentProduct->image);
            } */

            // Store new image
            $path = $request->file('image')->store('adjustment', 'public');

            return response()->json([
                'message' => 'Image uploaded successfully',
                'data' => $path
            ]);
        }

        return response()->json([
            'message' => 'No image file provided'
        ], 422);
    }
    /**
     * @OA\Get(
     *     path="/api/v1/adjustment-products",
     *     summary="List all adjustment products with optional filters and pagination",
     *     tags={"Adjustment Products"},
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
     *     @OA\Parameter(
     *         name="stock_id",
     *         in="query",
     *         description="Filter by stock ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filter by adjustment type (0: plus, 1: minus)",
     *         required=false,
     *         @OA\Schema(type="integer")
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
     *         description="Paginated list of adjustment products",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/AdjustmentProduct")),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     ),
     *     security={{"sanctum": {}}}
     * )
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = AdjustmentProduct::with(['product', 'user', 'stock', 'createdBy']);

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('stock_id')) {
            $query->where('stock_id', $request->stock_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $perPage = $request->input('per_page', 15);
        $adjustmentProducts = $query->latest('created_at')->paginate($perPage);
        
        return AdjustmentProductResource::collection($adjustmentProducts);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/adjustment-products",
     *     summary="Create a new adjustment product",
     *     tags={"Adjustment Products"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/AdjustmentProduct")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Adjustment product created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/AdjustmentProduct")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     security={{"sanctum": {}}}
     * )
     */
    public function store(AdjustmentProductRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $stockProduct = StockProduct::findOrFail($validated['stock_id']);

        // Calculate new quantity
        $newQuantity = $validated['type'] == AdjustmentProduct::TYPE_PLUS
            ? $stockProduct->quantity + $validated['quantity']
            : $stockProduct->quantity - $validated['quantity'];

        // Prevent negative stock
        if ($newQuantity < 0) {
            return response()->json([
                'message' => 'Insufficient stock quantity for adjustment',
                'errors' => [
                    'quantity' => ['The adjustment would result in negative stock quantity.']
                ]
            ], 422);
        }

        // Create adjustment record
        $adjustmentProduct = AdjustmentProduct::create([
            'product_id' => $validated['product_id'],
            'user_id' => $validated['user_id'],
            'stock_id' => $validated['stock_id'],
            'type' => $validated['type'],
            'quantity' => $validated['quantity'],
            'note' => $validated['note'] ?? null,
            'image' => $validated['image'] ?? null,
            'created_by' => auth()->id(),
            'created_at' => Carbon::now()
        ]);

        // Update stock quantity
        $stockProduct->update(['quantity' => $newQuantity]);

        return response()->json(
            new AdjustmentProductResource($adjustmentProduct->load(['product', 'user', 'stock', 'createdBy'])),
            201
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/adjustment-products/{id}",
     *     summary="Get a specific adjustment product",
     *     tags={"Adjustment Products"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Adjustment product details",
     *         @OA\JsonContent(ref="#/components/schemas/AdjustmentProduct")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Adjustment product not found"
     *     ),
     *     security={{"sanctum": {}}}
     * )
     */
    public function show(AdjustmentProduct $adjustmentProduct): AdjustmentProductResource
    {
        return new AdjustmentProductResource(
            $adjustmentProduct->load(['product', 'user', 'stock', 'createdBy'])
        );
    }
}