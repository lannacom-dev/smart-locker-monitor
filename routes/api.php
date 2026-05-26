<?php

use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FloorPlanController;
use App\Http\Controllers\Api\HeartbeatController;
use App\Http\Controllers\Api\LockerMonitorController;
use App\Http\Controllers\Api\SmartLockerProxyController;
use App\Http\Middleware\AuthenticateLockerToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Device → Server (authenticated by locker api_token, no Sanctum)
|--------------------------------------------------------------------------
*/
Route::post('/heartbeat', HeartbeatController::class)
    ->middleware(AuthenticateLockerToken::class)
    ->name('api.heartbeat');

/*
|--------------------------------------------------------------------------
| Admin / App → Server (Sanctum token auth)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', fn(Request $request) => $request->user());

    // ── Locker operational status ────────────────────────────────
    Route::get('/lockers', [LockerMonitorController::class, 'index'])
        ->middleware('can:view lockers');

    Route::patch('/lockers/{locker}/status', [LockerMonitorController::class, 'updateStatus'])
        ->middleware('can:edit lockers');

    // ── Connection status ────────────────────────────────────────
    Route::get('/lockers/{locker}/connection-status', [FloorPlanController::class, 'lockerStatus'])
        ->middleware('can:view lockers');

    // ── Dashboard statistics ─────────────────────────────────────
    Route::get('/dashboard/stats', [DashboardController::class, 'stats'])
        ->middleware('can:view lockers');

    // ── Floor plans ──────────────────────────────────────────────
    Route::get('/floor-plans', [FloorPlanController::class, 'index'])
        ->middleware('can:view lockers');

    Route::get('/floor-plans/{floorPlan}', [FloorPlanController::class, 'show'])
        ->middleware('can:view lockers');

    // ── SmartLocker API proxy (Lannacom hardware control) ─────────
    Route::prefix('smartlocker')->group(function () {
        Route::post('/lockers/{locker}/unlock',           [SmartLockerProxyController::class, 'unlock']);
        Route::post('/lockers/{locker}/emergency-unlock', [SmartLockerProxyController::class, 'emergencyUnlock']);
        Route::post('/lockers/{locker}/disable',          [SmartLockerProxyController::class, 'disable']);
        Route::post('/lockers/{locker}/enable',           [SmartLockerProxyController::class, 'enable']);
        Route::post('/sync',                              [SmartLockerProxyController::class, 'sync']);
    });
});
