<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="StockProductRequest",
 *     required={"product_id", "user_id", "quantity"},
 *     @OA\Property(property="product_id", type="integer", format="int64", example=1),
 *     @OA\Property(property="user_id", type="integer", format="int64", example=1),
 *     @OA\Property(property="quantity", type="integer", minimum=0, example=100),
 *     @OA\Property(property="note", type="string", maxLength=255, nullable=true),
 *     @OA\Property(property="image", type="string", maxLength=255, nullable=true)
 * )
 */
class StockProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'product_id' => 'required|exists:products,id',
            'user_id' => 'required|exists:users,id',
            'quantity' => 'required|integer|min:0',
            'note' => 'nullable|string|max:255',
            'image' => 'nullable|string|max:255'
        ];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules = array_map(function ($rule) {
                return 'sometimes|' . $rule;
            }, $rules);
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'The product ID is required.',
            'product_id.exists' => 'The selected product does not exist.',
            'user_id.required' => 'The user ID is required.',
            'user_id.exists' => 'The selected user does not exist.',
            'quantity.required' => 'The quantity is required.',
            'quantity.integer' => 'The quantity must be an integer.',
            'quantity.min' => 'The quantity must be at least 0.',
            'note.string' => 'The note must be a string.',
            'note.max' => 'The note may not be greater than 255 characters.',
            'image.string' => 'The image must be a string.',
            'image.max' => 'The image may not be greater than 255 characters.'
        ];
    }
}