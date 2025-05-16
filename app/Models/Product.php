<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @OA\Schema(
 *     schema="Product",
 *     required={"product_category_id", "product_name", "stock", "price"},
 *     @OA\Property(property="product_id", type="integer", format="int64"),
 *     @OA\Property(property="product_category_id", type="integer", format="int64"),
 *     @OA\Property(property="product_name", type="string", maxLength=255),
 *     @OA\Property(property="picture", type="string", nullable=true),
 *     @OA\Property(property="stock", type="integer"),
 *     @OA\Property(property="price", type="number", format="float"),
 *     @OA\Property(property="desc_product", type="string", nullable=true),
 *     @OA\Property(property="discount_type", type="string", nullable=true),
 *     @OA\Property(property="discount_amount", type="number", format="float", nullable=true),
 *     @OA\Property(property="start_date_disc", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="end_date_disc", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="created_by", type="integer", nullable=true),
 *     @OA\Property(property="updated_by", type="integer", nullable=true),
 *     @OA\Property(property="deleted_by", type="integer", nullable=true),
 *     @OA\Property(property="created_date", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="updated_date", type="string", format="date-time", nullable=true)
 * )
 */
class Product extends Model
{
    use HasFactory;
    
    protected $primaryKey = 'product_id';
    
    // Disable Laravel's default timestamps
    public $timestamps = false;
    
    protected $fillable = [
        'product_category_id',
        'product_name',
        'picture',
        'stock',
        'price',
        'desc_product',
        'discount_type',
        'discount_amount',
        'start_date_disc',
        'end_date_disc',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $dates = [
        'start_date_disc',
        'end_date_disc',
        'created_date',
        'updated_date',
        'deleted_date'
    ];

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id', 'product_category_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}