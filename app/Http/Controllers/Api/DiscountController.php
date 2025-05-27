<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DiscountRequest;
use App\Http\Resources\DiscountResource;
use App\Models\Discount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @OA\Tag(
 *     name="Discounts",
 *     description="API Endpoints for managing discounts"
 * )
 */
class DiscountController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/discounts",
     *     summary="List all discounts with pagination",
     *     tags={"Discounts"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by discount name",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filter type of discount",
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
     *         description="Paginated list of discounts",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Discount")),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Discount::query()
            ->with(['createdBy', 'updatedBy'])
            ->where('status', '!=', Discount::STATUS_DELETED);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('amount', 'like', "%{$search}%");
            });
        }

        $discounts = $query->latest('created_at')->paginate($request->get('per_page', 15));

        return DiscountResource::collection($discounts);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/discounts",
     *     summary="Create a new discount",
     *     tags={"Discounts"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/DiscountRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Discount created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Discount")
     *     )
     * )
     */
    public function store(DiscountRequest $request): JsonResponse
    {
        $discount = new Discount($request->validated());
        $discount->created_by = $request->user()->id;
        $discount->updated_by = $request->user()->id;
        $discount->save();

        return response()->json(new DiscountResource($discount), 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/discounts/{id}",
     *     summary="Get discount details",
     *     tags={"Discounts"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/Discount")
     *     )
     * )
     */
    public function show(Discount $discount): DiscountResource
    {
        return new DiscountResource($discount->load(['createdBy', 'updatedBy']));
    }

    /**
     * @OA\Put(
     *     path="/api/v1/discounts/{id}",
     *     summary="Update a discount",
     *     tags={"Discounts"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/DiscountRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Discount updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Discount")
     *     )
     * )
     */
    public function update(DiscountRequest $request, Discount $discount): DiscountResource
    {
        $discount->fill($request->validated());
        $discount->updated_by = $request->user()->id;
        $discount->save();

        return new DiscountResource($discount->load(['createdBy', 'updatedBy']));
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/discounts/{id}",
     *     summary="Delete a discount",
     *     tags={"Discounts"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Discount deleted successfully"
     *     )
     * )
     */
    public function destroy(Request $request, Discount $discount): JsonResponse
    {
        $discount->status = Discount::STATUS_DELETED;
        $discount->updated_by = $request->user()->id;
        $discount->save();

        return response()->json(['message' => 'Discount deleted successfully']);
    }
}