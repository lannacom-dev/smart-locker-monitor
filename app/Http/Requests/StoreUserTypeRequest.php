<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage user types') ?? false;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:100'],
            'slug'        => ['required', 'string', 'max:50', 'alpha_dash', 'unique:user_types,slug'],
            'description' => ['nullable', 'string', 'max:1000'],
            'company_id'  => ['nullable', 'integer', 'exists:companies,id'],
            'is_active'   => ['boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $companyId = $this->input('company_id');

            // Non-super-admin can only create types for their own company tree
            if ($companyId && ! $this->user()->isSuperAdmin()) {
                if (! $this->user()->canAccessCompany((int) $companyId)) {
                    $v->errors()->add('company_id', 'You do not have access to this company.');
                }
            }
        });
    }
}
