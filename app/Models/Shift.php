<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
//use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @OA\Schema(
 *     schema="Shift",
 *     required={"user_id", "cash_balance", "created_at", "created_by"},
 *     @OA\Property(property="id", type="integer", format="int64"),
 *     @OA\Property(property="user_id", type="integer", format="int64"),
 *     @OA\Property(property="cash_balance", type="number", format="decimal"),
 *     @OA\Property(property="expected_cash_balance", type="number", format="decimal"),
 *     @OA\Property(property="final_cash_balance", type="number", format="decimal"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="created_by", type="integer"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="updated_by", type="integer", nullable=true),
 *     @OA\Property(
 *         property="user",
 *         ref="#/components/schemas/User",
 *         type="object"
 *     ),
 *     @OA\Property(
 *         property="creator",
 *         ref="#/components/schemas/User",
 *         type="object"
 *     ),
 *     @OA\Property(
 *         property="updater",
 *         ref="#/components/schemas/User",
 *         type="object",
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="histories",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/ShiftHistory"),
 *         nullable=true
 *     )
 * )
 */
class Shift extends Authenticatable
{
    use HasFactory, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'cash_balance',
        'expected_cash_balance',
        'final_cash_balance',
        'created_by',
        'updated_by'
    ];

    public $timestamps = false;

    /**
     * Scope a query to only include active shifts.
     */
    public function scopeActive($query)
    {
        return $query->whereNotNull('updated_at')->orderBy('created_at', 'desc');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cash_balance' => 'decimal:2',
            'expected_cash_balance' => 'decimal:2',
            'final_cash_balance' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime'
        ];
    }
    /* protected $casts = [
        'cash_balance' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ]; */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ShiftHistory::class);
    }
}