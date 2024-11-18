<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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