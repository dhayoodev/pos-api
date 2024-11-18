<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductCategory extends Model
{
    use HasFactory;
    
    protected $primaryKey = 'product_category_id';
    
    protected $fillable = [
        'category_name',
    ];
} 