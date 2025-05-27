<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="TransactionRequest",
 *     required={
 *         "user_id", "shift_id", "payment_method", "date", "payment_status", "details"
 *     },
 *     @OA\Property(property="user_id", type="integer", format="int64"),
 *     @OA\Property(property="shift_id", type="integer", format="int64"),
 *     @OA\Property(property="discount_id", type="integer", format="int64"),
 *     @OA\Property(property="payment_method", type="string", enum={"bank_transfer", "e_wallet", "qris", "cash", "card"}),
 *     @OA\Property(property="date", type="string", format="date-time"),
 *     @OA\Property(property="total_price", type="number", format="float"),
 *     @OA\Property(property="total_payment", type="number", format="float"),
 *     @OA\Property(property="total_tax", type="number", format="float"),
 *     @OA\Property(property="type_discount", type="integer", enum={0, 1, 2}),
 *     @OA\Property(property="amount_discount", type="integer"),
 *     @OA\Property(property="payment_status", type="string", enum={"pending", "paid", "failed", "refunded"}),
 *     @OA\Property(
 *         property="details",
 *         type="array",
 *         minItems=1,
 *         @OA\Items(
 *             type="object",
 *             required={"product_id", "quantity"},
 *             @OA\Property(property="product_id", type="integer", format="int64"),
 *             @OA\Property(property="quantity", type="integer", minimum=1)
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
            'shift_id' => ['required', 'integer', 'exists:shifts,id'],
            'discount_id' => ['nullable', 'integer', 'exists:discounts,id'],
            'payment_method' => ['required', 'in:bank_transfer,e_wallet,qris,cash,card'],
            'date' => ['required', 'date'],
            'total_price' => ['required', 'numeric'],
            'total_payment' => ['required', 'numeric'],
            'total_tax' => ['required', 'numeric'],
            'type_discount' => ['required', 'in:0,1,2'],
            'amount_discount' => ['required', 'integer'],
            'payment_status' => ['required', 'in:pending,paid,failed,refunded'],

            'details' => ['required', 'array', 'min:1'],
            'details.*.product_id' => ['required', 'exists:products,id'],
            'details.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
