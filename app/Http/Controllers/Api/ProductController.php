<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\StockProduct;
use App\Models\AdjustmentProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Products",
 *     description="API Endpoints for managing products"
 * )
 */
class ProductController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/products",
     *     summary="List all products with pagination",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status (0: active, 1: disabled, 2: deleted)",
     *         required=false,
     *         @OA\Schema(type="integer", default=0)
     *     ),
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="Filter stock quantity by user/branch ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by product name",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="stock",
     *         in="query",
     *         description="Filter stock quantity",
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
     *         description="Paginated list of products",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 allOf={
     *                     @OA\Schema(ref="#/components/schemas/Product"),
     *                     @OA\Schema(
     *                         @OA\Property(property="total_stock", type="integer", description="Total stock quantity across all branches")
     *                     )
     *                 }
     *             )),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     ),
     *     security={{"sanctum": {}}}
     * )
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Product::query()
            ->select('products.*')
            ->selectRaw('COALESCE(SUM(CASE WHEN ' . ($request->has('user_id') ? 'stock_products.user_id = ' . $request->user_id : '1=1') . ' THEN stock_products.quantity ELSE 0 END), 0) as total_stock')
            ->leftJoin('stock_products', 'products.id', '=', 'stock_products.product_id')
            ->groupBy('products.id');

        if ($request->has('stock')) {
            $query->having('total_stock', '>=', $request->stock);
        }

        if ($request->has('status')) {
            $query->where('products.status', $request->status);
        } else {
            $query->where('products.status', '!=' , 2);
        }

        if ($request->has('search')) {
            $query->where('products.name', 'like', '%' . $request->search . '%');
        }

        return ProductResource::collection($query->paginate($request->per_page ?? 15));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/products",
     *     summary="Create a new product",
     *     tags={"Products"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(ref="#/components/schemas/ProductRequest")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Product created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Product")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     security={{"sanctum": {}}}
     * )
     */
    public function store(ProductRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('products', $filename, 'public');
            //$path = 'products/' . $filename;
            //Storage::disk('public')->putFileAs('products', $file, $filename);
            $data['image'] = $path;
        }

        $product = Product::create([
            ...$data,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id()
        ]);

        return response()->json(new ProductResource($product), 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/products/{id}",
     *     summary="Get a specific product",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Product ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product details",
     *         @OA\JsonContent(ref="#/components/schemas/Product")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found"
     *     ),
     *     security={{"sanctum": {}}}
     * )
     */
    public function show(Product $product): ProductResource
    {
        return new ProductResource($product->load(['createdBy', 'updatedBy']));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/products/{id}",
     *     summary="Update a product",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Product ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(ref="#/components/schemas/ProductRequest")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Product")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     security={{"sanctum": {}}}
     * )
     */
    public function update(ProductRequest $request, Product $product): ProductResource
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }

            // Upload new image
            $file = $request->file('image');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('products', $filename, 'public');
            $data['image'] = $path;
        }

        if ($request->has('status') && $request->status == 2) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
        }

        $product->update([
            ...$data,
            'updated_by' => auth()->id(),
            'updated_date' => now(),
        ]);

        return new ProductResource($product->load(['creator']));
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/products/{id}",
     *     summary="Delete a product",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Product ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Product deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found"
     *     ),
     *     security={{"sanctum": {}}}
     * )
     */
    public function destroy(Product $product): ProductResource
    {
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->update(['status' => 2]);
        return new ProductResource($product);

        /* $product->update([
            'deleted_by' => auth()->id(),
            'deleted_date' => now(),
        ]);
        
        $product->delete();
        return response()->json(null, 204); */
    }
}