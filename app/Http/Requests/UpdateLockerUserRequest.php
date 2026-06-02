<?php

namespace App\Http\Requests;

use App\Models\LockerUser;
use App\Models\UserType;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLockerUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('edit locker users') ?? false;
    }

    public function rules(): array
    {
        return [
            'user_type_id'      => ['sometimes', 'integer', 'exists:user_types,id'],
            'full_name'         => ['sometimes', 'string', 'max:255'],
            'email'             => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone'             => ['sometimes', 'nullable', 'string', 'max:50'],
            'employee_id'       => ['sometimes', 'nullable', 'string', 'max:100'],
            'organization'      => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_active'         => ['sometimes', 'boolean'],
            'access_start_date' => ['sometimes', 'nullable', 'date'],
            'access_end_date'   => ['sometimes', 'nullable', 'date', 'after_or_equal:access_start_date'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            // Resolve type: use incoming user_type_id or fall back to existing
            $typeId = $this->input('user_type_id');

            if ($typeId) {
                $type = UserType::find($typeId);
            } else {
                /** @var LockerUser $lockerUser */
                $lockerUser = $this->route('lockerUser');
                $type = $lockerUser?->userType;
            }

            if (! $type) return;

            // Only enforce required-field rules when the field is present in the update
            if ($this->has('employee_id') && $type->requiresEmployeeId() && empty($this->input('employee_id'))) {
                $v->errors()->add('employee_id', 'Employee ID is required for the Employee user type.');
            }

            if ($this->has('organization') && $type->requiresOrganization() && empty($this->input('organization'))) {
                $v->errors()->add('organization', "Organization is required for the {$type->name} user type.");
            }
        });
    }
}
