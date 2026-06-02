<?php

use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\CorrectiveMaintenanceController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FloorPlanController;
use App\Http\Controllers\Api\HeartbeatController;
use App\Http\Controllers\Api\IssueController;
use App\Http\Controllers\Api\LockerController;
use App\Http\Controllers\Api\LockerMonitorController;
use App\Http\Controllers\Api\LockerUserController;
use App\Http\Controllers\Api\RolePermissionController;
use App\Http\Controllers\Api\SmartLockerProxyController;
use App\Http\Controllers\Api\UserTypeController;
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

    Route::get('/lockers/{locker}', [LockerController::class, 'show'])
        ->middleware('can:view lockers');

    Route::patch('/lockers/{locker}', [LockerController::class, 'update'])
        ->middleware('can:edit lockers');

    Route::get('/lockers/{locker}/edit-logs', [LockerController::class, 'editLogs'])
        ->middleware('can:view lockers');

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

    // ── Issue tracking ───────────────────────────────────────────
    Route::prefix('issues')->middleware('can:view issues')->group(function () {
        Route::get('/',                         [IssueController::class, 'index']);
        Route::get('/stats',                    [IssueController::class, 'stats']);
        Route::post('/',                        [IssueController::class, 'store'])->middleware('can:create issues');
        Route::get('/{issue}',                  [IssueController::class, 'show']);
        Route::patch('/{issue}/assign',         [IssueController::class, 'assign'])->middleware('can:assign issues');
        Route::post('/{issue}/comments',        [IssueController::class, 'addComment']);
        Route::patch('/{issue}/status',         [IssueController::class, 'updateStatus']);
        Route::get('/{issue}/status-history',   [IssueController::class, 'statusHistory']);
    });

    // ── Corrective Maintenance ───────────────────────────────────
    Route::prefix('maintenance')->middleware('can:view maintenance')->group(function () {
        Route::get('/',                             [CorrectiveMaintenanceController::class, 'index']);
        Route::get('/stats',                        [CorrectiveMaintenanceController::class, 'stats']);
        Route::post('/',                            [CorrectiveMaintenanceController::class, 'store'])->middleware('can:create maintenance');
        Route::get('/{maintenance}',                [CorrectiveMaintenanceController::class, 'show']);
        Route::patch('/{maintenance}',              [CorrectiveMaintenanceController::class, 'update'])->middleware('can:edit maintenance');
        Route::patch('/{maintenance}/transition',   [CorrectiveMaintenanceController::class, 'transition'])->middleware('can:edit maintenance');
        Route::patch('/{maintenance}/assign',       [CorrectiveMaintenanceController::class, 'assign'])->middleware('can:assign maintenance');
        Route::get('/{maintenance}/logs',           [CorrectiveMaintenanceController::class, 'logs']);
    });

    // ── Admin User Management ─────────────────────────────────────
    Route::prefix('admin-users')->middleware('can:view users')->group(function () {
        Route::get('/',                              [AdminUserController::class, 'index']);
        Route::post('/',                             [AdminUserController::class, 'store'])->middleware('can:create users');
        Route::get('/{user}',                        [AdminUserController::class, 'show']);
        Route::patch('/{user}',                      [AdminUserController::class, 'update'])->middleware('can:edit users');
        Route::patch('/{user}/roles',                [AdminUserController::class, 'assignRoles'])->middleware('can:edit users');
        Route::patch('/{user}/disable',              [AdminUserController::class, 'disable'])->middleware('can:edit users');
        Route::patch('/{user}/enable',               [AdminUserController::class, 'enable'])->middleware('can:edit users');
        Route::post('/{user}/reset-password',        [AdminUserController::class, 'resetPassword'])->middleware('can:edit users');
        Route::get('/{user}/audit-log',              [AdminUserController::class, 'auditLog']);
    });

    // ── Role-Permission Matrix (super_admin only) ─────────────────
    Route::prefix('role-permissions')->middleware('check.role:super_admin')->group(function () {
        Route::get('/',           [RolePermissionController::class, 'index']);
        Route::patch('/{role}',   [RolePermissionController::class, 'update']);
        Route::get('/audit-log',  [RolePermissionController::class, 'auditLog']);
    });

    // ── User Types ────────────────────────────────────────────────
    Route::prefix('user-types')->middleware('can:view locker users')->group(function () {
        Route::get('/',                         [UserTypeController::class, 'index']);
        Route::post('/',                        [UserTypeController::class, 'store'])->middleware('can:manage user types');
        Route::patch('/{userType}',             [UserTypeController::class, 'update'])->middleware('can:manage user types');
        Route::patch('/{userType}/disable',     [UserTypeController::class, 'disable'])->middleware('can:manage user types');
        Route::patch('/{userType}/enable',      [UserTypeController::class, 'enable'])->middleware('can:manage user types');
    });

    // ── Locker Users ──────────────────────────────────────────────
    Route::prefix('locker-users')->middleware('can:view locker users')->group(function () {
        Route::get('/',                              [LockerUserController::class, 'index']);
        Route::post('/',                             [LockerUserController::class, 'store'])->middleware('can:create locker users');
        Route::get('/{lockerUser}',                  [LockerUserController::class, 'show']);
        Route::patch('/{lockerUser}',                [LockerUserController::class, 'update'])->middleware('can:edit locker users');
        Route::patch('/{lockerUser}/disable',        [LockerUserController::class, 'disable'])->middleware('can:disable locker users');
        Route::patch('/{lockerUser}/enable',         [LockerUserController::class, 'enable'])->middleware('can:disable locker users');
        Route::get('/{lockerUser}/audit-log',        [LockerUserController::class, 'auditLog']);
    });

    // ── SmartLocker API proxy (Lannacom hardware control) ─────────
    Route::prefix('smartlocker')->group(function () {
        Route::post('/lockers/{locker}/unlock',           [SmartLockerProxyController::class, 'unlock']);
        Route::post('/lockers/{locker}/emergency-unlock', [SmartLockerProxyController::class, 'emergencyUnlock']);
        Route::post('/lockers/{locker}/disable',          [SmartLockerProxyController::class, 'disable']);
        Route::post('/lockers/{locker}/enable',           [SmartLockerProxyController::class, 'enable']);
        Route::post('/sync',                              [SmartLockerProxyController::class, 'sync']);
    });
});
