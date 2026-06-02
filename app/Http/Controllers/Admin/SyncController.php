<?php

namespace App\Http\Controllers\Admin;

use App\Services\SmartLockerSyncService;
use Illuminate\Http\RedirectResponse;

class SyncController extends Controller
{
    public function store(SmartLockerSyncService $sync): RedirectResponse
    {
        $this->authorizePermission('view lockers');

        $run = $sync->sync();

        if ($run->status === 'failed') {
            return back()->with('error', 'Sync failed: ' . $run->error_message);
        }

        return back()->with('success', sprintf(
            'Synced from API — updated %d locker(s).',
            $run->lockers_updated
        ));
    }
}
