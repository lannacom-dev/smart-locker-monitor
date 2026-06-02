<?php

namespace App\Http\Requests;

use App\Models\Issue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIssueStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) return false;

        // Must be able to edit issues OR close issues (for the close transition)
        return $user->can('edit issues') || $user->can('close issues');
    }

    public function rules(): array
    {
        return [
            'to_status' => [
                'required',
                'string',
                Rule::in(array_keys(Issue::statusOptions())),
            ],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'to_status.required' => 'Target status is required.',
            'to_status.in'       => 'Invalid status value. Allowed: ' .
                                    implode(', ', array_keys(Issue::statusOptions())),
        ];
    }
}
