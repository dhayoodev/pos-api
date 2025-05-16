<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="UserRequest",
 *     required={"name", "email", "role"},
 *     @OA\Property(property="name", type="string", maxLength=255),
 *     @OA\Property(property="email", type="string", format="email", maxLength=255),
 *     @OA\Property(property="password", type="string", minLength=8, nullable=true),
 *     @OA\Property(property="role", type="string", enum={"admin", "cashier"})
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
        $passwordRule = $this->isMethod('PUT') ? 'nullable' : 'required';

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email,' . $userId . ',id'
            ],
            'password' => [$passwordRule, 'string', 'min:8'],
            'role' => ['required', 'string', 'in:admin,cashier']
        ];
    }
}