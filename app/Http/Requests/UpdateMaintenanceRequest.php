<?php

namespace App\Http\Requests;

use App\Models\CorrectiveMaintenance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('edit maintenance') ?? false;
    }

    public function rules(): array
    {
        return [
            'title'          => ['sometimes', 'string', 'max:255'],
            'description'    => ['sometimes', 'string'],
            'root_cause'     => ['nullable', 'string'],
            'solution'       => ['nullable', 'string'],
            'notes'          => ['nullable', 'string', 'max:2000'],
            'priority'       => ['sometimes', Rule::in(array_keys(CorrectiveMaintenance::priorityOptions()))],
            'scheduled_date' => ['nullable', 'date'],
            'cost_estimate'  => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'cost_actual'    => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'technician_id'  => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
