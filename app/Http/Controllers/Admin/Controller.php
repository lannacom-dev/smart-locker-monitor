<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller as BaseController;
use App\Models\ApiSyncRun;

abstract class Controller extends BaseController
{
    protected function authorizePermission(string $permission): void
    {
        abort_unless(auth()->check() && auth()->user()->can($permission), 403);
    }

    protected function companiesForFilter()
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return \App\Models\Company::orderBy('name')->get(['id', 'name']);
        }

        return \App\Models\Company::whereIn('id', $user->accessibleCompanyIds())
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    protected function lastApiSync(): ?ApiSyncRun
    {
        return ApiSyncRun::latestSuccess();
    }
}
