<?php

namespace Modules\Team\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'owner_id' => 'sometimes|exists:users,id',
            'is_public' => 'boolean',
        ];
    }
}