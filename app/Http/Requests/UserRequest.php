<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="UserRequest",
 *     required={"name", "email", "role"},
 *     @OA\Property(property="name", type="string", maxLength=255),
 *     @OA\Property(property="email", type="string", format="email", maxLength=255),
 *     @OA\Property(property="phone", type="string", minLength=8),
 *     @OA\Property(property="password", type="string", minLength=8, nullable=true),
 *     @OA\Property(property="role", type="integer", minimum=0)
 * )
 */
class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;
        $passwordRule = $this->isMethod('PATCH') ? 'nullable' : 'required';

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email,' . $userId . ',id'
            ],
            'phone' => [
                'required',
                'string',
                'min:8',
                'unique:users,phone,' . $userId . ',id'
            ],
            'password' => [$passwordRule, 'string', 'min:8'],
            'role' => ['required', 'integer', 'min:0', 'in:0,1']
        ];
    }
}