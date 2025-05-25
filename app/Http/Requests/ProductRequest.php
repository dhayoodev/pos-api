<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="ProductRequest",
 *     required={"name", "price", "status"},
 *     @OA\Property(property="name", type="string", maxLength=255),
 *     @OA\Property(
 *         property="image",
 *         type="string",
 *         format="binary",
 *         nullable=true,
 *         description="Product image file (jpeg, png, jpg, gif up to 2MB)"
 *     ),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="price", type="number", format="float", minimum=0),
 *     @OA\Property(property="status", type="integer", enum={"0", "1", "2"}, description="0: active, 1: disabled, 2: deleted")
 * )
 */
class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['string', 'max:255'],
            'image' => ['nullable', 'file', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'description' => ['nullable', 'string'],
            'price' => ['numeric', 'min:0'],
            'status' => ['in:0,1,2']
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Product name is required',
            'name.max' => 'Product name cannot exceed 255 characters',
            'price.required' => 'Product price is required',
            'price.numeric' => 'Product price must be a number',
            'price.min' => 'Product price cannot be negative',
            'status.required' => 'Product status is required',
            'status.in' => 'Invalid product status'
        ];
    }
}