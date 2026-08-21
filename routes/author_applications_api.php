<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthorApplicationController;

// مسارات المستخدم المسجل
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/author-applications', [AuthorApplicationController::class, 'store']);
    Route::get('/author-applications/my', [AuthorApplicationController::class, 'mine']);
});

// مسارات الأدمن المحمية بـ Spatie Role
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/author-applications', [AuthorApplicationController::class, 'index']);
    Route::get('/author-applications/{application}', [AuthorApplicationController::class, 'show']);
    Route::patch('/author-applications/{application}/approve', [AuthorApplicationController::class, 'approve']);
    Route::patch('/author-applications/{application}/reject', [AuthorApplicationController::class, 'reject']);
});