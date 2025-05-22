<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="ShiftRequest",
 *     required={"user_id", "cash_balance"},
 *     @OA\Property(property="user_id", type="integer", format="int64", example=1),
 *     @OA\Property(property="cash_balance", type="number", format="decimal", example="100.00"),
 *     @OA\Property(property="expected_cash_balance", type="number", format="decimal", example="100.00"),
 *     @OA\Property(property="final_cash_balance", type="number", format="decimal", example="100.00")
 * )
 */
class ShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'cash_balance' => ['required', 'numeric', 'min:0'],
            'expected_cash_balance' => ['numeric', 'min:0'],
            'final_cash_balance' => ['numeric', 'min:0'],
        ];
    }
}