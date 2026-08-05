<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\AuthorController;
use App\Http\Controllers\Api\PageController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\SitemapController;

/*
|--------------------------------------------------------------------------
| مسارات الشخص الأول: إدارة المحتوى والأقسام (Articles & Categories)
|--------------------------------------------------------------------------
*/

// مسارات الصفحة الرئيسية وخريطة الموقع والروابط اللطيفة (Public Slug Routes)
Route::get('/sitemap.xml', [SitemapController::class, 'index']);
Route::get('/articles/{article}/related', [ArticleController::class, 'related']);
Route::get('/categories/slug/{slug}', [CategoryController::class, 'showBySlug']);
Route::get('/authors/slug/{slug}', [AuthorController::class, 'showBySlug']);
Route::get('/pages/homepage', [PageController::class, 'homepage']);
Route::get('/pages/slug/{slug}', [PageController::class, 'showBySlug']);

// مسارات الموارد العامة والمحمية لاحقاً بالصلاحيات
Route::apiResource('categories', CategoryController::class);
Route::apiResource('tags', TagController::class);
Route::apiResource('articles', ArticleController::class);
Route::apiResource('authors', AuthorController::class);
Route::apiResource('pages', PageController::class);
Route::apiResource('media', MediaController::class);
