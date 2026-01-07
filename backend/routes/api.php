<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Http\Request;
use App\Http\Controllers\UserController;

Route::get('/hello', function () {
    return response()->json([
        'message' => 'Halo dari Laravel'
    ]);
});

Route::get('/test-db', function () {
    return [
        'db' => config('database.default'),
        'status' => 'MySQL connected'
    ];
});

Route::get('/announcements', [AnnouncementController::class, 'index']);

Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum', 'operator'])->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);


    Route::middleware('operator')->group(function () {
        Route::get('/dashboard/operator', [DashboardController::class, 'operator']);
    });
    Route::middleware(['auth:sanctum', 'role.operator'])->group(function () {
    Route::get('/users', [UserController::class, 'index']);
});
});
