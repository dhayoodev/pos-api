<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @OA\Schema(
 *     schema="Product",
 *     required={"name", "price", "status", "created_by", "updated_by"},
 *     @OA\Property(property="id", type="integer", format="int64"),
 *     @OA\Property(property="name", type="string", maxLength=255),
 *     @OA\Property(property="image", type="string", nullable=true),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="price", type="number", format="float"),
 *     @OA\Property(property="status", type="integer", minimum=0, description="0: active, 1: disabled, 2: deleted"),
 *     @OA\Property(property="created_by", type="integer"),
 *     @OA\Property(property="updated_by", type="integer"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'image',
        'description',
        'price',
        'status',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function stocks(): HasMany
    {
        return $this->hasMany(StockProduct::class);
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(AdjustmentProduct::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    protected $dates = [
        'created_date',
        'updated_date'
    ];

    /* public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id', 'product_category_id');
    } */

    public function histories(): HasMany
    {
        return $this->hasMany(adjustmentProduct::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}