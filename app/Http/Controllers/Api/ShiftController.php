<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\ShiftHistory;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
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
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Shift::query()->active()->with(['user', 'histories']);

        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            $query->where('payment_status', $request->status);
        }

        // Filter by user_id
        if ($request->has('user_id') && $request->user_id !== '') {
            $query->where('user_id', $request->user_id);
        }

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
        $shift = $query->paginate($request->per_page ?? 15);

        return ShiftResource::collection($shift);
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

        $cashPayments = Transaction::where('shift_id', $shift->id)
            ->where('payment_method', 'cash')
            ->where('payment_status', 'paid')
            ->orderBy('created_at', 'desc')
            ->get();

        $cashRefunds = Transaction::where('shift_id', $shift->id)
            ->where('payment_method', 'cash')
            ->where('payment_status', 'refunded')
            ->orderBy('created_at', 'desc')
            ->get();

        $cashChanges = (float) $cashPayments->sum('total_payment') - $cashPayments->sum('total_price');
        $expectedCash = (float) $shift->cash_balance + (float) $paidIn->sum('amount') - (float) $paidOut->sum('amount') + (float) $cashPayments->sum('total_payment') - $cashChanges - (float) $cashRefunds->sum('total_price');

        $array = [
            'cash_payments' => (float) $cashPayments->sum('total_payment'),
            'cash_refunds' => (float) $cashRefunds->sum('total_price'),
            'cash_changes' => $cashChanges,
            'paid_in' => $paidIn->sum('amount'),
            'paid_out' => $paidOut->sum('amount'),
            'expected_cash' => $expectedCash,
            'gross_sales' => 0,
            'refunds' => 0,
            'discounts' => 0,
            'net_sales' => 0,
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

        $array = [
            'cash_payments' => 0,
            'cash_refunds' => 0,
            'paid_in' => $paidIn->sum('amount'),
            'paid_out' => $paidOut->sum('amount'),
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
            $shift->expected_cash_balance += $validated['type'] == ShiftHistory::TYPE_INCOME 
                ? $validated['amount'] 
                : -$validated['amount'];
            $shift->save();

            return $history;
        });

        $history->load(['creator']);
        return response()->json($history, 201);
    }
}