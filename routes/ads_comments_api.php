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
Route::apiResource('comments', CommentController::class);
Route::apiResource('advertisements', AdvertisementController::class);
Route::apiResource('newsletter-subscribers', NewsletterSubscriberController::class);
Route::patch('/newsletter-subscribers/{id}/unsubscribe', [NewsletterSubscriberController::class, 'unsubscribe']);
