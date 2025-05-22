<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @OA\Schema(
 *     schema="ShiftHistory",
 *     required={"shift_id", "description", "type", "amount", "created_at", "created_by"},
 *     @OA\Property(property="id", type="integer", format="int64"),
 *     @OA\Property(property="shift_id", type="integer", format="int64"),
 *     @OA\Property(property="description", type="string"),
 *     @OA\Property(
 *         property="type",
 *         type="integer",
 *         enum={0, 1},
 *         description="0: pay-in, 1: pay-out"
 *     ),
 *     @OA\Property(property="amount", type="number", format="decimal"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="created_by", type="integer"),
 *     @OA\Property(
 *         property="shift",
 *         ref="#/components/schemas/Shift",
 *         type="object"
 *     ),
 *     @OA\Property(
 *         property="creator",
 *         ref="#/components/schemas/User",
 *         type="object"
 *     )
 * )
 */
class ShiftHistory extends Model
{
    protected $fillable = [
        'shift_id',
        'description',
        'type',
        'amount',
        'created_by'
    ];

    public $timestamps = false;

    protected $casts = [
        'type' => 'integer',
        'amount' => 'decimal:2',
        'created_at' => 'datetime'
    ];

    const TYPE_INCOME = 0;
    const TYPE_OUTCOME = 1;

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}