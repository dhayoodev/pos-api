<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TransactionDetail extends Model
{
    use HasFactory;
    
    protected $primaryKey = 'trans_detail_id';
    
    public $timestamps = false;
    
    protected $fillable = [
        'trans_id',
        'product_id',
        'qty',
        'price',
        'subtotal',
        'is_hide',
        'note_hide',
        'is_deleted',
        'note_deleted',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $dates = [
        'created_date',
        'updated_date',
        'deleted_date'
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'trans_id', 'trans_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }
} 