<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AdvertisementController;
use App\Http\Controllers\Api\CommentController;

// مسارات عامة (عرض التعليقات أو الإعلانات المتاحة للجميع)
Route::get('ads', [AdvertisementController::class, 'index']);
Route::get('articles/{article}/comments', [CommentController::class, 'index']);

// مسارات محمية تسجيل الدخول (يتطلب Bearer Token)
Route::middleware(['auth:sanctum'])->group(function () {

    // إضافة تعليق (متاح للمستخدم المسجل)
    Route::post('articles/{article}/comments', [CommentController::class, 'store']);

    // مسارات الإعلانات (خاصة بالـ Admin فقط -> ترجع 403 للمستخدم العادي)
    Route::middleware(['role:admin'])->group(function () {
        Route::apiResource('admin/ads', AdvertisementController::class)->except(['index']);
    });

    // مسارات إدارة التعليقات (للأدمن، المحرر، والمشرف)
    Route::middleware(['role:admin|editor|moderator'])->group(function () {
        Route::patch('comments/{comment}/status', [CommentController::class, 'updateStatus']);
        Route::match(['put', 'patch'], 'comments/{comment}', [CommentController::class, 'update']);
        Route::delete('comments/{comment}', [CommentController::class, 'destroy']);
    });
    });
