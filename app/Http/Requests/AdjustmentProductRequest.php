<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="AdjustmentProductRequest",
 *     required={"product_id", "user_id", "stock_id", "type", "quantity"},
 *     @OA\Property(property="product_id", type="integer", description="Product ID must exist in products table"),
 *     @OA\Property(property="user_id", type="integer", description="User ID must exist in users table"),
 *     @OA\Property(property="stock_id", type="integer", description="Stock ID must exist in stock_products table"),
 *     @OA\Property(property="type", type="integer", enum={"0", "1"}, description="0: minus, 1: plus"),
 *     @OA\Property(property="quantity", type="integer", minimum=1),
 *     @OA\Property(property="note", type="string", maxLength=255, nullable=true),
 *     @OA\Property(property="image", type="string", maxLength=255, nullable=true)
 * )
 */
class AdjustmentProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'stock_id' => ['required', 'integer', 'exists:stock_products,id'],
            'type' => ['required', 'integer', 'in:0,1'],
            'quantity' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'string', 'max:255']
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'The product ID is required.',
            'product_id.integer' => 'The product ID must be an integer.',
            'product_id.exists' => 'The selected product does not exist.',
            
            'user_id.required' => 'The user ID is required.',
            'user_id.integer' => 'The user ID must be an integer.',
            'user_id.exists' => 'The selected user does not exist.',
            
            'stock_id.required' => 'The stock ID is required.',
            'stock_id.integer' => 'The stock ID must be an integer.',
            'stock_id.exists' => 'The selected stock does not exist.',
            
            'type.required' => 'The adjustment type is required.',
            'type.integer' => 'The adjustment type must be an integer.',
            'type.in' => 'The adjustment type must be either 0 (plus) or 1 (minus).',
            
            'quantity.required' => 'The quantity is required.',
            'quantity.integer' => 'The quantity must be an integer.',
            'quantity.min' => 'The quantity must be at least 1.',
            
            'note.string' => 'The note must be a string.',
            'note.max' => 'The note may not be greater than 255 characters.',
            
            'image.string' => 'The image must be a string.',
            'image.max' => 'The image may not be greater than 255 characters.'
        ];
    }
}