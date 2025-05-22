<?php

namespace App\Http\Requests;

use App\Models\ShiftHistory;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="ShiftHistoryRequest",
 *     required={"description", "type", "amount"},
 *     @OA\Property(property="description", type="string", example="Cash deposit"),
 *     @OA\Property(property="type", type="integer", minimum=0, enum={0,1}, description="0: income, 1: outcome", example=0),
 *     @OA\Property(property="amount", type="number", format="float", minimum=0, example="100.00")
 * )
 */
class ShiftHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => ['required', 'string'],
            'type' => ['required', 'in:' . ShiftHistory::TYPE_INCOME . ',' . ShiftHistory::TYPE_OUTCOME],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}