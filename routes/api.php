<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\AuthorController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\AdvertisementController;
use App\Http\Controllers\ContactMessageController;

// 1. مسار يجلب بيانات المستخدم الحالي عند تسجيل الدخول
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// 2. مسارات الـ API العامة (Public API Routes)
Route::apiResource('categories', CategoryController::class);
Route::apiResource('tags', TagController::class);
Route::apiResource('articles', ArticleController::class);
Route::apiResource('authors', AuthorController::class);
Route::apiResource('comments', CommentController::class);
Route::apiResource('advertisements', AdvertisementController::class);
Route::apiResource('contact-messages', ContactMessageController::class);