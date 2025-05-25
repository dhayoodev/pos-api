<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @OA\Schema(
 *     schema="AdjustmentProduct",
 *     required={"product_id", "user_id", "stock_id", "type", "quantity", "created_by"},
 *     @OA\Property(property="id", type="integer", format="int64"),
 *     @OA\Property(property="product_id", type="integer"),
 *     @OA\Property(property="user_id", type="integer"),
 *     @OA\Property(property="stock_id", type="integer"),
 *     @OA\Property(property="type", type="integer", description="0: plus, 1: minus"),
 *     @OA\Property(property="quantity", type="integer"),
 *     @OA\Property(property="note", type="string", nullable=true),
 *     @OA\Property(property="image", type="string", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="created_by", type="integer")
 * )
 */
class AdjustmentProduct extends Model
{
    const TYPE_PLUS = 0;
    const TYPE_MINUS = 1;

    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'user_id',
        'stock_id',
        'type',
        'quantity',
        'note',
        'image',
        'created_by',
        'created_at'
    ];

    protected $casts = [
        'type' => 'integer',
        'quantity' => 'integer',
        'created_at' => 'datetime'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(StockProduct::class, 'stock_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}