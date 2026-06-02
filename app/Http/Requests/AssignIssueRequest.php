<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('assign issues') ?? false;
    }

    public function rules(): array
    {
        return [
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'note'        => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'assigned_to.exists' => 'Selected user does not exist.',
        ];
    }
}
