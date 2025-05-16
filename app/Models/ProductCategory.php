<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @OA\Schema(
 *     schema="ProductCategory",
 *     required={"category_name"},
 *     @OA\Property(property="product_category_id", type="integer", format="int64"),
 *     @OA\Property(property="category_name", type="string", maxLength=255),
 * )
 */
class ProductCategory extends Model
{
    use HasFactory;
    
    protected $primaryKey = 'product_category_id';
    
    protected $fillable = [
        'category_name',
    ];
} 