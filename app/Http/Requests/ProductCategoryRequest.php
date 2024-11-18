<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('product_category')?->product_category_id;
        
        return [
            'category_name' => [
                'required',
                'string',
                'max:255',
                'unique:product_categories,category_name,' . $categoryId . ',product_category_id'
            ]
        ];
    }
} 