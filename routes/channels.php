<?php

use Illuminate\Support\Facades\Broadcast;

/*
 * Private channel per company — only users who can access the company receive its locker events.
 * Reuses User::canAccessCompany() which returns true for super_admin and for users with matching company_id.
 */
Broadcast::channel('company.{companyId}', function ($user, $companyId) {
    return $user->canAccessCompany((int) $companyId);
});
