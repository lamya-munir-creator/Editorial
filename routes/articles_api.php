<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\PageController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\SitemapController;

/*
|--------------------------------------------------------------------------
| مسارات الشخص الأول: إدارة المحتوى والأقسام (Articles & Categories Feature)
|--------------------------------------------------------------------------
|
| ملاحظة: مسارات authors.* انتقلت بالكامل إلى routes/authors_api.php
| لتفادي التعارض وتسهيل الصيانة (راجعي authors_api.php).
|
*/

// =========================================================================
// 1. المسارات العامة للقراءة (Public GET Routes)
// =========================================================================
Route::get('/sitemap.xml', [SitemapController::class, 'index']);
Route::get('/articles/{article}/related', [ArticleController::class, 'related']);
Route::get('/categories/slug/{slug}', [CategoryController::class, 'showBySlug']);
Route::get('/pages/homepage', [PageController::class, 'homepage']);
Route::get('/pages/slug/{slug}', [PageController::class, 'showBySlug']);

// مسارات استعراض البيانات (Index & Show)
Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);
Route::apiResource('tags', TagController::class)->only(['index', 'show']);
Route::apiResource('articles', ArticleController::class)->only(['index', 'show']);
Route::apiResource('pages', PageController::class)->only(['index', 'show']);
Route::apiResource('media', MediaController::class)->only(['index', 'show']);

// =========================================================================
// 2. المسارات المحمية للمصادقة والصلاحيات (Protected Routes)
// =========================================================================
Route::middleware('auth:sanctum')->group(function () {

    // إدارة الأقسام والوسوم (مقتصرة على المحرر والأدمن عبر can:manage-categories)
    Route::middleware('can:manage-categories')->group(function () {
        Route::apiResource('categories', CategoryController::class)->except(['index', 'show']);
        Route::apiResource('tags', TagController::class)->except(['index', 'show']);
    });

   
    // إدارة الصفحات (مقتصرة على الأدمن عبر role:admin)
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('pages', PageController::class)->except(['index', 'show']);
    });

    // إدارة الوسائط
    Route::apiResource('media', MediaController::class)->except(['index', 'show']);
});