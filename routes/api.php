<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\AuthorController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\AdvertisementController;
use App\Http\Controllers\Api\ContactMessageController;
use App\Http\Controllers\Api\NewsletterSubscriberController;
use App\Http\Controllers\Api\PageController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\SettingController;

// 1. مسار يجلب بيانات المستخدم الحالي عند تسجيل الدخول
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post(
    'newsletter-subscribers/unsubscribe',
    [NewsletterSubscriberController::class, 'unsubscribe']
);
Route::get('pages/homepage', [PageController::class, 'homepage']);

Route::get(
    'pages/slug/{slug}',
    [PageController::class, 'showBySlug']
);


// 2. مسارات الـ API العامة (Public API Routes)
Route::apiResource('categories', CategoryController::class);
Route::apiResource('tags', TagController::class);
Route::apiResource('articles', ArticleController::class);
Route::apiResource('authors', AuthorController::class);
Route::apiResource('comments', CommentController::class);
Route::apiResource('advertisements', AdvertisementController::class);
Route::apiResource('contact-messages', ContactMessageController::class);
Route::post('contact-messages/{id}/reply', [ContactMessageController::class, 'reply']);
Route::apiResource('newsletter-subscribers', NewsletterSubscriberController::class);
Route::apiResource('pages', PageController::class);
Route::apiResource('menus', MenuController::class);
Route::apiResource('settings', SettingController::class);