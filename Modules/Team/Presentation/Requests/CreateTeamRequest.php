<?php

namespace Modules\Team\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'owner_id' => 'required|exists:users,id',
            'is_public' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Team name is required.',
            'owner_id.required' => 'Team owner is required.',
            'owner_id.exists' => 'The selected owner does not exist.',
        ];
    }
}