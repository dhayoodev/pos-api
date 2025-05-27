<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Discount;

/**
 * @OA\Schema(
 *     schema="DiscountRequest",
 *     required={"name", "type", "amount"},
 *     @OA\Property(property="name", type="string", maxLength=255),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(
 *         property="type",
 *         type="integer",
 *         enum={1, 2},
 *         description="Discount type (1: fixed, 2: percent)"
 *     ),
 *     @OA\Property(
 *         property="amount",
 *         type="integer",
 *         description="Discount amount (for percentage: 1-100, for fixed: positive integer)"
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="integer",
 *         enum={0, 1, 2},
 *         description="Discount status (0: active, 1: disabled, 2: deleted)",
 *         default=0
 *     )
 * )
 */
class DiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required','string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'integer', 'in:' . Discount::TYPE_FIXED . ',' . Discount::TYPE_PERCENT],
            'amount' => ['required', 'integer', 'min:0', function ($attribute, $value, $fail) {
                if ($this->input('type') === Discount::TYPE_PERCENT && $value > 100) {
                    $fail('The percentage discount cannot be greater than 100.');
                }
            }],
            'status' => ['sometimes', 'integer', 'in:' . Discount::STATUS_ACTIVE . ',' . Discount::STATUS_DISABLED . ','. Discount::STATUS_DELETED]
        ];
    }
}