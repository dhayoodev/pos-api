<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @OA\Schema(
 *     schema="TransactionDetail",
 *     required={"trans_id", "product_id", "qty", "price", "subtotal"},
 *     @OA\Property(property="trans_detail_id", type="integer", format="int64"),
 *     @OA\Property(property="trans_id", type="integer", format="int64"),
 *     @OA\Property(property="product_id", type="integer", format="int64"),
 *     @OA\Property(property="qty", type="integer", minimum=1),
 *     @OA\Property(property="price", type="number", format="float"),
 *     @OA\Property(property="subtotal", type="number", format="float"),
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