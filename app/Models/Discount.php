<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @OA\Schema(
 *     schema="Discount",
 *     required={"name", "type", "amount", "status", "created_by"},
 *     @OA\Property(property="id", type="integer", format="int64"),
 *     @OA\Property(property="name", type="string", maxLength=255),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="type", type="integer", description="1: fixed, 2: percent"),
 *     @OA\Property(property="amount", type="integer"),
 *     @OA\Property(property="status", type="integer", minimum=0, description="0 = active, 1 = disaled, 2 = deleted"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="created_by", type="integer")
 * )
 */
class Discount extends Model
{
    protected $fillable = [
        'name',
        'description',
        'type',
        'amount',
        'status',
        'created_by',
        'updated_by'
    ];

    public const TYPE_FIXED = 1;
    public const TYPE_PERCENT = 2;

    public const STATUS_ACTIVE = 0;
    public const STATUS_DISABLED = 1;
    public const STATUS_DELETED = 2;

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}