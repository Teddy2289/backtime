<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        $user = Auth::user();
        
        return [
            'name' => 'sometimes|string|between:2,100',
            'email' => 'sometimes|string|email|max:100|unique:users,email,' . $user->id,
            'current_password' => 'required_with:password',
            'password' => 'sometimes|string|min:6|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required_with' => 'Current password is required when changing password',
            'password.confirmed' => 'Password confirmation does not match',
        ];
    }
}