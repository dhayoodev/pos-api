<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model
{
    use HasFactory;
    
    protected $primaryKey = 'trans_id';
    
    public $timestamps = false;
    
    protected $fillable = [
        'user_id',
        'date',
        'total_price',
        'payment_status',
        'is_hide',
        'note_hide',
        'is_deleted',
        'note_deleted',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $dates = [
        'date',
        'created_date',
        'updated_date',
        'deleted_date'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function details()
    {
        return $this->hasMany(TransactionDetail::class, 'trans_id', 'trans_id');
    }
} 