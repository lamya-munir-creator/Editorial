<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\NewsletterSubscriberController;

// 1. مسار الاشتراك في النشرة البريدية (متاح للجميع - للزوار)
Route::post('newsletter/subscribe', [NewsletterSubscriberController::class, 'store']);

// 2. مسارات إدارة المشتركين في النشرة البريدية (محمية للأدمن فقط)
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('admin/newsletter/subscribers', [NewsletterSubscriberController::class, 'index']);
    Route::get('admin/newsletter/subscribers/{id}', [NewsletterSubscriberController::class, 'show']);
    Route::put('admin/newsletter/subscribers/{id}', [NewsletterSubscriberController::class, 'update']);
    Route::delete('admin/newsletter/subscribers/{id}', [NewsletterSubscriberController::class, 'destroy']);
});

// 3. إلغاء الاشتراك السريع
Route::post('newsletter/unsubscribe/{id}', [NewsletterSubscriberController::class, 'unsubscribe']);