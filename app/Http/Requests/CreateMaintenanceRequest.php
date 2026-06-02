<?php

namespace App\Http\Requests;

use App\Models\CorrectiveMaintenance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create maintenance') ?? false;
    }

    public function rules(): array
    {
        return [
            'locker_id'      => ['required', 'integer', 'exists:lockers,id'],
            'issue_id'       => ['nullable', 'integer', 'exists:issues,id'],
            'technician_id'  => ['nullable', 'integer', 'exists:users,id'],
            'title'          => ['required', 'string', 'max:255'],
            'description'    => ['required', 'string'],
            'priority'       => ['required', Rule::in(array_keys(CorrectiveMaintenance::priorityOptions()))],
            'scheduled_date' => ['nullable', 'date'],
            'cost_estimate'  => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'notes'          => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'locker_id.required' => 'Please select the affected locker.',
            'locker_id.exists'   => 'Selected locker does not exist.',
            'title.required'     => 'Maintenance title is required.',
            'description.required' => 'Please describe what needs to be done.',
            'priority.in'        => 'Invalid priority. Allowed: ' . implode(', ', array_keys(CorrectiveMaintenance::priorityOptions())),
        ];
    }
}
