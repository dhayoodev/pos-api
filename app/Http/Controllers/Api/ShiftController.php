<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\ShiftHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\ShiftRequest;
use App\Http\Requests\ShiftHistoryRequest;
use App\Http\Resources\ShiftResource;
use App\Http\Resources\ShiftHistoryResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @OA\Tag(
 *     name="Shifts",
 *     description="API Endpoints for managing shifts"
 * )
 */
class ShiftController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/shifts",
     *     summary="List all shifts with pagination",
     *     tags={"Shifts"},
     *     security={{"sanctum": {}}},
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
     *         description="Paginated list of shifts",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Shift")),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    /* public function index(): AnonymousResourceCollection
    {
        return ShiftResource::collection(Shift::all());
    } */
    /* public function index(): JsonResponse
    {
        $shifts = Shift::with(['user', 'creator', 'updater'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'message' => 'Shifts retrieved successfully',
            'data' => [
                'shifts' => $shifts->items(),
                'total' => $shifts->total(),
                'per_page' => $shifts->perPage(),
                'current_page' => $shifts->currentPage(),
                'last_page' => $shifts->lastPage(),
            ]
        ]);
    } */
    public function index()
    {
        $perPage = request('per_page', 15);
        return ShiftResource::collection(Shift::with('user')->active()->paginate($perPage));
        /* $shifts = Shift::with('user')->active()->paginate($perPage);

        $shiftsData = $shifts->map(function ($shift) {
            $data = (new ShiftResource($shift))->resolve();
            $data['cash_difference'] = $shift->expected_cash_balance - $shift->final_cash_balance;
            return $data;
        });

        return response()->json([
            'message' => 'Shifts retrieved successfully',
            'data' => [
                'shifts' => $shiftsData,
                'total' => $shifts->total(),
                'per_page' => $shifts->perPage(),
                'current_page' => $shifts->currentPage(),
                'last_page' => $shifts->lastPage(),
            ]
        ]); */
    }

    /**
     * @OA\Get(
     *     path="/api/v1/shifts/user/{user_id}",
     *     summary="Get shifts by user ID",
     *     tags={"Shifts"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="user_id",
     *         in="path",
     *         required=true,
     *         description="ID of the user",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Shift"))
     *     ),
     *     @OA\Response(response=404, description="User not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    /* public function getByUserId(int $user_id): JsonResponse
    {
        $shifts = Shift::with(['user', 'creator', 'updater'])
            ->where('user_id', $user_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($shifts);
    } */
    public function getByUserId(int $user_id)
    {
        $shift = Shift::with(['user', 'creator', 'updater'])
            ->where('user_id', $user_id)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$shift) {
            return response()->json(['message' => 'No shift found for this user'], 404);
        }

        $paidIn = ShiftHistory::where('shift_id', $shift->id)
            ->where('type', 0)
            ->orderBy('created_at', 'desc')
            ->get();

        $paidOut = ShiftHistory::where('shift_id', $shift->id)
            ->where('type', 1)
            ->orderBy('created_at', 'desc')
            ->get();

        $expectedCash = $shift->cash_balance + $paidIn->sum('amount') - $paidOut->sum('amount');

        $array = [
            'cash_payments' => 0,
            'cash_refunds' => 0,
            'paid_in' => $paidIn->sum('amount'),
            'paid_out' => $paidOut->sum('amount'),
            'expected_cash' => $expectedCash,
            'gross_sales' => 0,
            'refunds' => 0,
            'discounts' => 0,
            'net_sales' => 19,
        ];

        return response()->json([
            'message' => 'Shift retrieved successfully',
            'data' => array_merge(
                (new ShiftResource($shift))->resolve(),
                $array
            )
        ]);

        /* if ($shifts->isEmpty()) {
            return response()->json(['message' => 'No shifts found for this user'], 404);
        }

        return ShiftResource::collection($shifts); */
    }

    /**
     * @OA\Get(
     *     path="/api/v1/shifts/{shift}",
     *     summary="Get shift details",
     *     tags={"Shifts"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="shift",
     *         in="path",
     *         required=true,
     *         description="ID of the shift",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/Shift")
     *     ),
     *     @OA\Response(response=404, description="Shift not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function show(Shift $shift): JsonResponse
    {
        $shift->load(['user', 'creator', 'updater' => function ($query) {
            $query->orderBy('created_at', 'desc');
        }]);

        if (!$shift) {
            return response()->json(['message' => 'No shift found for this user'], 404);
        }

        $paidIn = ShiftHistory::where('shift_id', $shift->id)
            ->where('type', 0)
            ->orderBy('created_at', 'desc')
            ->get();

        $paidOut = ShiftHistory::where('shift_id', $shift->id)
            ->where('type', 1)
            ->orderBy('created_at', 'desc')
            ->get();

        $expectedCash = $shift->cash_balance + $paidIn->sum('amount') - $paidOut->sum('amount');

        $array = [
            'cash_payments' => 0,
            'cash_refunds' => 0,
            'paid_in' => $paidIn->sum('amount'),
            'paid_out' => $paidOut->sum('amount'),
            'expected_cash' => $expectedCash,
            'gross_sales' => 0,
            'refunds' => 0,
            'discounts' => 0,
            'net_sales' => 19,
        ];

        return response()->json([
            'message' => 'Shift retrieved successfully',
            'data' => array_merge(
                (new ShiftResource($shift))->resolve(),
                $array
            )
        ]);

        //return response()->json(new ShiftResource($shift));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/shifts",
     *     summary="Create a new shift",
     *     tags={"Shifts"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/ShiftRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Shift created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Shift")
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function store(ShiftRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $shift = DB::transaction(function () use ($validated, $request) {
            $shift = new Shift($validated);
            $shift->created_by = $request->user()->id;
            $shift->created_at = now();
            $shift->save();

            // Create initial balance history
            /* ShiftHistory::create([
                'shift_id' => $shift->id,
                'description' => 'Initial cash balance',
                'type' => ShiftHistory::TYPE_INCOME,
                'amount' => $validated['cash_balance'],
                'created_by' => $request->user()->id,
                'created_at' => now()
            ]); */

            return $shift;
        });

        $shift->load(['user', 'creator']);
        return response()->json($shift, 201);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/shifts/{shift}",
     *     summary="Update shift details",
     *     tags={"Shifts"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="shift",
     *         in="path",
     *         required=true,
     *         description="ID of the shift",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/ShiftRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Shift updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Shift")
     *     ),
     *     @OA\Response(response=404, description="Shift not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function update(ShiftRequest $request, Shift $shift): JsonResponse
    {
        $validated = $request->validated();

        $shift->final_cash_balance = $validated['final_cash_balance'];
        $shift->updated_by = $request->user()->id;
        $shift->updated_at = now();
        $shift->save();

        $shift->load(['user', 'creator', 'updater']);
        return response()->json(new ShiftResource($shift));
    }

    /**
     * @OA\Post(
     *     path="/api/v1/shifts/{shift}/histories",
     *     summary="Add shift history",
     *     tags={"Shifts"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="shift",
     *         in="path",
     *         required=true,
     *         description="ID of the shift",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/ShiftHistoryRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="History added successfully",
     *         @OA\JsonContent(ref="#/components/schemas/ShiftHistory")
     *     ),
     *     @OA\Response(response=404, description="Shift not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function addHistory(ShiftHistoryRequest $request, Shift $shift): JsonResponse
    {
        $validated = $request->validated();

        $history = DB::transaction(function () use ($validated, $request, $shift) {
            $history = new ShiftHistory($validated);
            $history->shift_id = $shift->id;
            $history->created_by = $request->user()->id;
            $history->created_at = now();
            $history->save();

            // Update shift balance
            /* $shift->cash_balance += $validated['type'] == ShiftHistory::TYPE_INCOME 
                ? $validated['amount'] 
                : -$validated['amount'];
            $shift->updated_by = $request->user()->id;
            $shift->updated_at = now();
            $shift->save(); */

            return $history;
        });

        $history->load(['creator']);
        return response()->json($history, 201);
    }
}