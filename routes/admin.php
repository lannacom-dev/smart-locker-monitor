<?php

use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FloorPlanController;
use App\Http\Controllers\Admin\IssueController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\LockerController;
use App\Http\Controllers\Admin\LockerMonitorController;
use App\Http\Controllers\Admin\LockerUserController;
use App\Http\Controllers\Admin\MaintenanceController;
use App\Http\Controllers\Admin\RolePermissionController;
use App\Http\Controllers\Admin\SyncController;
use App\Http\Controllers\Admin\SystemHealthController;
use App\Http\Controllers\Admin\UsageController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\UserTypeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('/sync', [SyncController::class, 'store'])->name('sync');

        Route::get('/lockers', [LockerMonitorController::class, 'index'])->name('lockers.index');
        Route::patch('/lockers/{locker}/status', [LockerMonitorController::class, 'updateStatus'])->name('lockers.status');
        Route::get('/lockers/{locker}', [LockerController::class, 'show'])->name('lockers.show');
        Route::get('/lockers/{locker}/edit', [LockerController::class, 'edit'])->name('lockers.edit');
        Route::put('/lockers/{locker}', [LockerController::class, 'update'])->name('lockers.update');

        Route::get('/usage', [UsageController::class, 'index'])->name('usage.index');

        Route::get('/issues', [IssueController::class, 'index'])->name('issues.index');
        Route::get('/issues/create', [IssueController::class, 'create'])->name('issues.create');
        Route::post('/issues', [IssueController::class, 'store'])->name('issues.store');
        Route::get('/issues/{issue}', [IssueController::class, 'show'])->name('issues.show');
        Route::patch('/issues/{issue}/status', [IssueController::class, 'updateStatus'])->name('issues.status');
        Route::post('/issues/{issue}/assign', [IssueController::class, 'assign'])->name('issues.assign');
        Route::post('/issues/{issue}/comment', [IssueController::class, 'comment'])->name('issues.comment');

        Route::get('/maintenance', [MaintenanceController::class, 'index'])->name('maintenance.index');
        Route::get('/maintenance/create', [MaintenanceController::class, 'create'])->name('maintenance.create');
        Route::post('/maintenance', [MaintenanceController::class, 'store'])->name('maintenance.store');
        Route::get('/maintenance/{maintenance}', [MaintenanceController::class, 'show'])->name('maintenance.show');
        Route::patch('/maintenance/{maintenance}/transition', [MaintenanceController::class, 'transition'])->name('maintenance.transition');
        Route::post('/maintenance/{maintenance}/assign', [MaintenanceController::class, 'assign'])->name('maintenance.assign');
        Route::post('/maintenance/{maintenance}/fields', [MaintenanceController::class, 'updateFields'])->name('maintenance.fields');
        Route::post('/maintenance/{maintenance}/note', [MaintenanceController::class, 'addNote'])->name('maintenance.note');
        Route::post('/maintenance/{maintenance}/attachment', [MaintenanceController::class, 'uploadAttachment'])->name('maintenance.attachment');

        Route::get('/health', [SystemHealthController::class, 'index'])->name('health.index');
        Route::post('/health/alerts/{alert}/acknowledge', [SystemHealthController::class, 'acknowledge'])->name('health.acknowledge');

        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserManagementController::class, 'create'])->name('users.create');
        Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
        Route::get('/users/{user}', [UserManagementController::class, 'show'])->name('users.show');
        Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');

        Route::get('/roles', [RolePermissionController::class, 'index'])->name('roles.index');
        Route::post('/roles', [RolePermissionController::class, 'store'])->name('roles.store');
        Route::delete('/roles/{role}', [RolePermissionController::class, 'destroy'])->name('roles.destroy');
        Route::put('/roles/{role}', [RolePermissionController::class, 'update'])->name('roles.update');

        Route::get('/locker-users', [LockerUserController::class, 'index'])->name('locker-users.index');
        Route::get('/locker-users/{lockerUser}', [LockerUserController::class, 'show'])->name('locker-users.show');
        Route::put('/locker-users/{lockerUser}', [LockerUserController::class, 'update'])->name('locker-users.update');

        Route::get('/user-types', [UserTypeController::class, 'index'])->name('user-types.index');
        Route::post('/user-types', [UserTypeController::class, 'store'])->name('user-types.store');
        Route::put('/user-types/{userType}', [UserTypeController::class, 'update'])->name('user-types.update');

        Route::resource('companies', CompanyController::class)->except(['show', 'destroy']);
        Route::resource('locations', LocationController::class)->except(['show', 'destroy']);

        Route::get('/floor-plans', [FloorPlanController::class, 'index'])->name('floor-plans.index');
        Route::get('/floor-plans/{floorPlan}', [FloorPlanController::class, 'show'])->name('floor-plans.show');
    });
