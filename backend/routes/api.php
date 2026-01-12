<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\Api\ComplaintController;
use App\Http\Controllers\Api\CounselingScheduleController;
use App\Http\Controllers\Api\CounselorScheduleController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\MaterialController;
use App\Http\Controllers\Api\ViolenceCategoryController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Models\Announcement;
use Illuminate\Http\Request;


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

Route::apiResource('announcements', AnnouncementController::class);
Route::get('/public/announcements', function () {
    return Announcement::whereNotNull('published_at')
        ->where('published_at', '<=', now())
        ->orderBy('published_at', 'desc')
        ->limit(3)
        ->get();
});


Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum', 'role:operator'])->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'role:operator,konselor'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});

// Materials API - Accessible by Operator & Konselor
Route::middleware(['auth:sanctum', 'role:operator,konselor'])->group(function () {
    Route::get('/materials', [MaterialController::class, 'index']);
    Route::post('/materials', [MaterialController::class, 'store']); // Konselor can upload
});

// Materials CRUD - Only Operator can modify
Route::middleware(['auth:sanctum', 'role:operator'])->group(function () {
    Route::put('/materials/{id}', [MaterialController::class, 'update']);
    Route::delete('/materials/{id}', [MaterialController::class, 'destroy']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource(
        'violence-categories',
        \App\Http\Controllers\Api\ViolenceCategoryController::class
    );
});

Route::middleware(['auth:sanctum', 'role:operator,konselor'])->group(function () {
    Route::get('/counseling-schedules', [CounselingScheduleController::class, 'index']);
});

Route::middleware(['auth:sanctum', 'role:operator'])->group(function () {
    Route::patch('/counseling-schedules/{id}/status', [CounselingScheduleController::class, 'updateStatus']);
});

Route::middleware(['auth:sanctum', 'role:konselor'])->group(function () {
    Route::post('/counseling-schedules/{id}/confirm', [CounselingScheduleController::class, 'confirm']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/counselor-schedules', [CounselorScheduleController::class, 'index']);
    Route::post('/counselor-schedules', [CounselorScheduleController::class, 'store']);
    Route::put('/counselor-schedules/{id}', [CounselorScheduleController::class, 'update']);
});

Route::middleware(['auth:sanctum', 'role:operator'])->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
    Route::delete('/announcements/{id}', [AnnouncementController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'role:pengguna'])->group(function () {
    Route::post('/complaints', [ComplaintController::class, 'store']);
    Route::get('/my-complaints', [ComplaintController::class, 'myComplaints']);
});

Route::middleware(['auth:sanctum', 'role:operator,konselor'])->group(function () {
    Route::get('/complaints', [ComplaintController::class, 'index']);
    Route::get('/complaints/{complaint}', [ComplaintController::class, 'show']);
});

Route::middleware(['auth:sanctum', 'role:operator'])->group(function () {
    Route::patch('/complaints/{complaint}/assign-counselor', [ComplaintController::class, 'assignCounselor']);
    Route::delete('/complaints/{complaint}', [ComplaintController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'role:operator,konselor'])->group(function () {
    Route::patch('/complaints/{complaint}/status', [ComplaintController::class, 'updateStatus']);
});
