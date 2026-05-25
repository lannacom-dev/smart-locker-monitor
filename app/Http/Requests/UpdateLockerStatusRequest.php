<?php

namespace App\Http\Requests;

use App\Models\Locker;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLockerStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && ($user->isSuperAdmin() || $user->isTenantAdmin());
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(array_keys(Locker::statusOptions()))],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'กรุณาระบุสถานะ',
            'status.in'       => 'สถานะไม่ถูกต้อง',
            'reason.max'      => 'เหตุผลต้องไม่เกิน 500 ตัวอักษร',
        ];
    }
}
