<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdminUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('edit users') ?? false;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name'       => ['sometimes', 'string', 'max:255'],
            'email'      => ['sometimes', 'email', 'max:255', "unique:users,email,{$userId}"],
            'company_id' => ['sometimes', 'nullable', 'integer', 'exists:companies,id'],
            'is_active'  => ['sometimes', 'boolean'],
            'note'       => ['nullable', 'string', 'max:500'],
        ];
    }
}
