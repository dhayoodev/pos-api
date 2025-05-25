<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @OA\Schema(
 *     schema="StockProduct",
 *     required={"product_id", "user_id", "quantity"},
 *     @OA\Property(property="id", type="integer", format="int64"),
 *     @OA\Property(property="product_id", type="integer"),
 *     @OA\Property(property="user_id", type="integer"),
 *     @OA\Property(property="quantity", type="integer"),
 *     @OA\Property(property="created_at", type="string", format="date-time")
 * )
 */
class StockProduct extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'user_id',
        'quantity',
        'created_by',
        'created_at'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'created_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(AdjustmentProduct::class, 'stock_id');
    }
}