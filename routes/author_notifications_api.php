<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\NotificationController;

/*
|--------------------------------------------------------------------------
| Author Notifications API Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum'])->prefix('author')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
});