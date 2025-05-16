<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="ProductRequest",
 *     required={"product_category_id", "product_name", "stock", "price"},
 *     @OA\Property(property="product_category_id", type="integer", format="int64"),
 *     @OA\Property(property="product_name", type="string", maxLength=255),
 *     @OA\Property(property="picture", type="string", nullable=true),
 *     @OA\Property(property="stock", type="integer", minimum=0),
 *     @OA\Property(property="price", type="number", format="float", minimum=0),
 *     @OA\Property(property="desc_product", type="string", nullable=true),
 *     @OA\Property(property="discount_type", type="string", enum={"percentage", "fixed"}, nullable=true),
 *     @OA\Property(property="discount_amount", type="number", format="float", minimum=0, nullable=true),
 *     @OA\Property(property="start_date_disc", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="end_date_disc", type="string", format="date-time", nullable=true)
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
            'product_category_id' => ['required', 'exists:product_categories,product_category_id'],
            'product_name' => ['required', 'string', 'max:255'],
            'picture' => ['nullable', 'string', 'max:255'],
            'stock' => ['required', 'integer', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
            'desc_product' => ['nullable', 'string'],
            'discount_type' => ['nullable', 'in:percentage,fixed'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'start_date_disc' => ['nullable', 'date'],
            'end_date_disc' => ['nullable', 'date', 'after_or_equal:start_date_disc'],
        ];
    }
}