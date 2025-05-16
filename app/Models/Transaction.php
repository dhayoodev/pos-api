<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @OA\Schema(
 *     schema="Transaction",
 *     required={"user_id", "date", "total_price", "payment_status"},
 *     @OA\Property(property="trans_id", type="integer", format="int64"),
 *     @OA\Property(property="user_id", type="integer", format="int64"),
 *     @OA\Property(property="date", type="string", format="date-time"),
 *     @OA\Property(property="total_price", type="number", format="float"),
 *     @OA\Property(property="payment_status", type="string"),
 *     @OA\Property(property="is_hide", type="boolean", nullable=true),
 *     @OA\Property(property="note_hide", type="string", nullable=true),
 *     @OA\Property(property="is_deleted", type="boolean", nullable=true),
 *     @OA\Property(property="note_deleted", type="string", nullable=true),
 *     @OA\Property(property="created_by", type="integer", format="int64", nullable=true),
 *     @OA\Property(property="updated_by", type="integer", format="int64", nullable=true),
 *     @OA\Property(property="deleted_by", type="integer", format="int64", nullable=true),
 *     @OA\Property(property="created_date", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="updated_date", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="deleted_date", type="string", format="date-time", nullable=true)
 * )
 */
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