<?php

namespace Modules\User\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Modules\User\Domain\Enums\UserRole;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user') ?? $this->route('id');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,' . $userId],
            'password' => ['sometimes', 'string', 'min:8', 'confirmed'],
            'role' => ['sometimes', new Enum(UserRole::class)],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'], 
            'email_verified_at' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'avatar.image' => 'Le fichier doit être une image valide',
            'avatar.mimes' => 'Les formats acceptés sont : jpeg, png, jpg, gif, webp',
            'avatar.max' => 'L\'image ne doit pas dépasser 2MB',
        ];
    }
}
