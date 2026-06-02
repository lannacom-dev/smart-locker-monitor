<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('view issues') ?? false;
    }

    public function rules(): array
    {
        return [
            'body'        => ['required', 'string', 'min:1'],
            'is_internal' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => 'Comment body cannot be empty.',
        ];
    }
}
