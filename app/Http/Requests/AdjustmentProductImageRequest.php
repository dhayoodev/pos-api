<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="AdjustmentProductImageRequest",
 *     required={"image"},
 *     @OA\Property(property="image", type="string", maxLength=255, nullable=true)
 * )
 */
class AdjustmentProductImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image' => ['required', 'file', 'image', 'mimes:jpeg,png,jpg', 'max:2048']
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => 'The image is required.',
        ];
    }
}