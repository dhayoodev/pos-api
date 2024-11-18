<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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