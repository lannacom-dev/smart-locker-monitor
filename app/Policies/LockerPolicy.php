<?php

namespace App\Policies;

use App\Models\Locker;
use App\Models\User;

class LockerPolicy
{
    /**
     * View a specific locker — requires 'view lockers' + company access.
     */
    public function view(User $user, Locker $locker): bool
    {
        return $user->can('view lockers')
            && $user->canAccessCompany($locker->company_id);
    }

    /**
     * Edit general fields — requires 'edit lockers' + company access.
     */
    public function update(User $user, Locker $locker): bool
    {
        return $user->can('edit lockers')
            && $user->canAccessCompany($locker->company_id);
    }

    /**
     * Only super_admin may edit sensitive hardware fields:
     * serial_number, api_token, external_locker_id, external_unit_id, company_id.
     */
    public function editSensitiveFields(User $user, Locker $locker): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Delete a locker.
     */
    public function delete(User $user, Locker $locker): bool
    {
        return $user->can('delete lockers')
            && $user->canAccessCompany($locker->company_id);
    }
}
