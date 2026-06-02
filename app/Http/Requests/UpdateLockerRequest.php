<?php

namespace App\Http\Requests;

use App\Models\Locker;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLockerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $locker = $this->route('locker');
        return $this->user()->can('update', $locker);
    }

    public function rules(): array
    {
        $user  = $this->user();
        $locker = $this->route('locker');

        $rules = [
            // ── Editable by everyone with edit lockers ────────────
            'name'        => ['sometimes', 'string', 'max:255'],
            'code'        => ['sometimes', 'nullable', 'string', 'max:50'],
            'zone'        => ['sometimes', 'nullable', 'string', 'max:100'],
            'floor'       => ['sometimes', 'nullable', 'string', 'max:50'],
            'location_id' => ['sometimes', 'nullable', 'integer', 'exists:locations,id'],
            'tenant_id'   => ['sometimes', 'nullable', 'integer', 'exists:companies,id'],
            'status'      => ['sometimes', 'string', 'in:available,in_use,fault,offline,disabled'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'is_active'   => ['sometimes', 'boolean'],
            'ip_address'  => ['sometimes', 'nullable', 'ip'],
            // ── Audit note (not a locker column) ─────────────────
            'note'        => ['sometimes', 'nullable', 'string', 'max:500'],
        ];

        // ── Sensitive fields: super_admin only ────────────────────
        if ($user?->isSuperAdmin()) {
            $rules['serial_number']      = ['sometimes', 'nullable', 'string', 'max:100'];
            $rules['firmware_version']   = ['sometimes', 'nullable', 'string', 'max:50'];
            $rules['heartbeat_interval'] = ['sometimes', 'integer', 'min:10', 'max:3600'];
            $rules['offline_after']      = ['sometimes', 'integer', 'min:30', 'max:86400'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'status.in'           => 'Status must be one of: available, in_use, fault, offline, disabled.',
            'location_id.exists'  => 'The selected location does not exist.',
            'tenant_id.exists'    => 'The selected tenant company does not exist.',
            'heartbeat_interval.min' => 'Heartbeat interval must be at least 10 seconds.',
            'offline_after.min'      => 'Offline after must be at least 30 seconds.',
        ];
    }

    /**
     * Fields that should be saved to the locker (excludes 'note' which is audit metadata).
     */
    public function lockerFields(): array
    {
        return $this->except('note');
    }
}
