<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage user types') ?? false;
    }

    public function rules(): array
    {
        $typeId = $this->route('userType')?->id ?? $this->route('userType');

        return [
            'name'        => ['sometimes', 'string', 'max:100'],
            'slug'        => ['sometimes', 'string', 'max:50', 'alpha_dash',
                              Rule::unique('user_types', 'slug')->ignore($typeId)],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'is_active'   => ['sometimes', 'boolean'],
        ];
    }
}
