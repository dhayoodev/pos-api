<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShiftHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Resources\ShiftHistoryResource;

/**
 * @OA\Tag(
 *     name="Shift Histories",
 *     description="API Endpoints for managing shift histories / cash management"
 * )
 */

class ShiftHistoryController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/shift-histories",
     *     summary="Get all shift histories",
     *     tags={"Shift Histories"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="shift_id",
     *         in="query",
     *         required=false,
     *         description="Filter by shift ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         required=false,
     *         description="Filter by type (0: income, 1: outcome)",
     *         @OA\Schema(type="integer", enum={0, 1})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/ShiftHistory"))
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $histories = ShiftHistory::with(['shift', 'creator'])
            ->when($request->shift_id, function ($query, $shiftId) {
                return $query->where('shift_id', $shiftId);
            })
            ->when($request->type !== null, function ($query) use ($request) {
                return $query->where('type', $request->type);
            })
            ->orderBy('created_at', 'desc')
            ->get();
        
        //return response()->json(ShiftHistoryResource::collection($histories));
        return response()->json([
            'message' => 'Shift histories retrieved successfully',
            'data' => ShiftHistoryResource::collection($histories)
            ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/shift-histories/{history}",
     *     summary="Get a specific shift history",
     *     tags={"Shift Histories"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="history",
     *         in="path",
     *         required=true,
     *         description="Shift History ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/ShiftHistory")
     *     ),
     *     @OA\Response(response=404, description="Shift history not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function show(ShiftHistory $history): JsonResponse
    {
        $history->load(['shift', 'creator']);
        return response()->json(new ShiftHistoryResource($history));
    }
}