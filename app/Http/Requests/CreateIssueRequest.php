<?php

namespace App\Http\Requests;

use App\Models\Issue;
use Illuminate\Foundation\Http\FormRequest;

class CreateIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create issues') ?? false;
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'category'    => ['required', 'in:' . implode(',', array_keys(Issue::categoryOptions()))],
            'severity'    => ['required', 'in:' . implode(',', array_keys(Issue::severityOptions()))],
            'locker_id'   => ['nullable', 'integer', 'exists:lockers,id'],
            'due_date'    => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'       => 'Issue title is required.',
            'description.required' => 'Please describe the issue.',
            'category.in'          => 'Invalid category.',
            'severity.in'          => 'Invalid severity level.',
            'locker_id.exists'     => 'Selected locker does not exist.',
            'due_date.after_or_equal' => 'Due date cannot be in the past.',
        ];
    }
}
