<?php

use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\StudentController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login');

    Route::middleware(['auth:sanctum', 'installed'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/user', [AuthController::class, 'user'])->name('user');

        Route::apiResource('students', StudentController::class)->only(['index', 'show', 'store', 'update']);

        Route::prefix('attendance')->name('attendance.')->group(function () {
            Route::get('/', [AttendanceController::class, 'index'])->name('index');
            Route::post('/scan', [AttendanceController::class, 'scan'])->name('scan');
            Route::get('/stats', [AttendanceController::class, 'stats'])->name('stats');
            Route::get('/inside', [AttendanceController::class, 'inside'])->name('inside');
        });
    });
});
