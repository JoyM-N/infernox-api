<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
 // Only super_admin can register new operator accounts but regular users cannot sign themselves up
        return auth()->check() && auth()->user()->hasRole('super_admin');
    }

    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:100'],
            'email'     => ['required', 'string', 'email', 'unique:users,email'],
            'password'  => ['required', 'string', 'min:8', 'confirmed'],
            // confirmed means: request must also have 'password_confirmation' that matches the 'password'

            'role' => ['required', 'string', 'in:operator,viewer'],
            // super_admin can only create operators or viewers
            // cannot create another super_admin via API
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'        => 'An account with this email already exists.',
            'password.confirmed'  => 'Password confirmation does not match.',
            'password.min'        => 'Password must be at least 8 characters.',
            'role.in'             => 'Role must be either operator or viewer.',
        ];
    }
}