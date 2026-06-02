<?php

namespace App\Http\Requests;

use App\Models\UserType;
use Illuminate\Foundation\Http\FormRequest;

class StoreLockerUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create locker users') ?? false;
    }

    public function rules(): array
    {
        return [
            'company_id'        => ['required', 'integer', 'exists:companies,id'],
            'user_type_id'      => ['required', 'integer', 'exists:user_types,id'],
            'full_name'         => ['required', 'string', 'max:255'],
            'email'             => ['nullable', 'email', 'max:255'],
            'phone'             => ['nullable', 'string', 'max:50'],
            'employee_id'       => ['nullable', 'string', 'max:100'],
            'organization'      => ['nullable', 'string', 'max:255'],
            'is_active'         => ['boolean'],
            'access_start_date' => ['nullable', 'date'],
            'access_end_date'   => ['nullable', 'date', 'after_or_equal:access_start_date'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $actor = $this->user();

            // Company access guard
            $companyId = (int) $this->input('company_id');
            if ($companyId && ! $actor->canAccessCompany($companyId)) {
                $v->errors()->add('company_id', 'You do not have access to this company.');
                return;
            }

            // Type-specific required fields
            $type = UserType::find($this->input('user_type_id'));
            if (! $type) return;

            if ($type->requiresEmployeeId() && empty($this->input('employee_id'))) {
                $v->errors()->add('employee_id', 'Employee ID is required for the Employee user type.');
            }

            if ($type->requiresOrganization() && empty($this->input('organization'))) {
                $v->errors()->add('organization', "Organization is required for the {$type->name} user type.");
            }
        });
    }
}
