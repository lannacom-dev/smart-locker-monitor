<?php

use App\Http\Controllers\Api\LockerMonitorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn(Request $request) => $request->user());

    Route::get('/lockers', [LockerMonitorController::class, 'index'])
        ->middleware('can:view lockers');

    Route::patch('/lockers/{locker}/status', [LockerMonitorController::class, 'updateStatus'])
        ->middleware('can:edit lockers');
});
