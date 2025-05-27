<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @OA\Schema(
 *     schema="TransactionDetail",
 *     required={"trans_id", "product_id", "quantity", "price", "subtotal"},
 *     @OA\Property(property="id", type="integer", format="int64"),
 *     @OA\Property(property="trans_id", type="integer", format="int64"),
 *     @OA\Property(property="product_id", type="integer", format="int64"),
 *     @OA\Property(property="quantity", type="integer", minimum=1),
 *     @OA\Property(property="price", type="number", format="float"),
 *     @OA\Property(property="subtotal", type="number", format="float")
 * )
 */
class TransactionDetail extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'trans_id',
        'product_id',
        'quantity',
        'price',
        'subtotal'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'subtotal' => 'decimal:2'
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'trans_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
