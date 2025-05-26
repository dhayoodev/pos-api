<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="TransactionRequest",
 *     required={"user_id", "date", "payment_status", "details"},
 *     @OA\Property(property="user_id", type="integer", format="int64"),
 *     @OA\Property(property="date", type="string", format="date-time"),
 *     @OA\Property(property="payment_status", type="string", enum={"pending", "paid", "failed", "refunded"}),
 *     @OA\Property(
 *         property="details",
 *         type="array",
 *         minItems=1,
 *         @OA\Items(
 *             type="object",
 *             required={"product_id", "qty"},
 *             @OA\Property(property="product_id", type="integer", format="int64"),
 *             @OA\Property(property="qty", type="integer", minimum=1)
 *         )
 *     )
 * )
 */
class TransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'date' => ['required', 'date'],
            'payment_status' => ['required', 'in:pending,paid,failed,refunded'],
            'details' => ['required', 'array', 'min:1'],
            'details.*.id' => ['required', 'exists:products,id'],
            'details.*.qty' => ['required', 'integer', 'min:1'],
        ];
    }
}