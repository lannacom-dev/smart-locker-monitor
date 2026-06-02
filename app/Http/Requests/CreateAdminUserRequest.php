<?php

namespace App\Http\Requests;

use App\Services\UserManagementService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class CreateAdminUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create users') ?? false;
    }

    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'   => ['required', 'string', 'min:8'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'role'       => ['required', 'string', 'exists:roles,name'],
            'is_active'  => ['boolean'],
            'note'       => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $role = $this->input('role');
            if ($role && ! app(UserManagementService::class)->canAssignRole($this->user(), $role)) {
                $v->errors()->add('role', "You cannot assign the '{$role}' role.");
            }
        });
    }
}
