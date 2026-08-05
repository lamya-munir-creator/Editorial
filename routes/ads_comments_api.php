<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AdvertisementController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\NewsletterSubscriberController;

/*
|--------------------------------------------------------------------------
| مسارات الشخص الثاني: النظام التفاعلي والإعلانات (Comments & Advertisements)
|--------------------------------------------------------------------------
*/

// مسارات إلغاء الاشتراك بالنشرة البريدية
Route::post('/newsletter-subscribers/unsubscribe', [NewsletterSubscriberController::class, 'unsubscribe']);

// مسارات الإعلانات والتعليقات والنشرة البريدية (سيتم وضع حماية الصلاحيات بها لاحقاً)
Route::apiResource('comments', CommentController::class)->middleware(['auth:sanctum', 'role:admin|editor']);
Route::apiResource('advertisements', AdvertisementController::class)->middleware(['auth:sanctum', 'role:admin']);
// مسارات النشرة البريدية
// 1. المسارات العامة (متاحة للجميع لكي يشتركوا)
Route::post('/newsletter-subscribers', [NewsletterSubscriberController::class, 'store']);
Route::patch('/newsletter-subscribers/{id}/unsubscribe', [NewsletterSubscriberController::class, 'unsubscribe']);

// 2. المسارات المحمية (خاصة بالمدير فقط لرؤية المشتركين وحذفهم)
Route::apiResource('newsletter-subscribers', NewsletterSubscriberController::class)
    ->only(['index', 'show', 'update', 'destroy'])
    ->middleware(['auth:sanctum', 'role:admin']);
