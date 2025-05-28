<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @OA\Schema(
 *     schema="Transaction",
 *     required={"user_id", "shift_id", "payment_method", "total_price", "total_payment", "total_tax", "type_discount", "amount_discount", "payment_status", "date", "created_by", "updated_by"},
 *     @OA\Property(property="id", type="integer", format="int64"),
 *     @OA\Property(property="user_id", type="integer"),
 *     @OA\Property(property="shift_id", type="integer"),
 *     @OA\Property(property="discount_id", type="integer"),
 *     @OA\Property(property="payment_method", type="string", enum={"bank_transfer", "e_wallet", "qris", "cash", "card"}),
 *     @OA\Property(property="total_price", type="number", format="float"),
 *     @OA\Property(property="total_payment", type="number", format="float"),
 *     @OA\Property(property="total_tax", type="number", format="float"),
 *     @OA\Property(property="type_discount", type="integer", enum={0, 1, 2}),
 *     @OA\Property(property="amount_discount", type="integer"),
 *     @OA\Property(property="payment_status", type="string", enum={"pending", "paid", "failed", "refunded"}),
 *     @OA\Property(property="date", type="string", format="date-time"),
 *     @OA\Property(property="is_deleted", type="integer", enum={0, 1}),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="created_by", type="integer"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_by", type="integer")
 * )
 */
class Transaction extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'shift_id',
        'discount_id',
        'payment_method',
        'total_price',
        'total_payment',
        'total_tax',
        'type_discount',
        'amount_discount',
        'payment_status',
        'date',
        'is_deleted',
        'type_reason',
        'reason',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by'
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
        'total_payment' => 'decimal:2',
        'total_tax' => 'decimal:2',
        'type_discount' => 'integer',
        'amount_discount' => 'integer',
        'is_deleted' => 'integer',
        'date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope a query to only include active transactions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_deleted', 0);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function discount()
    {
        return $this->belongsTo(Discount::class, 'discount_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function details()
    {
        return $this->hasMany(TransactionDetail::class, 'trans_id', 'id');
    }
}
