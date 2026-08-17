<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthorArticleController;

/*
|--------------------------------------------------------------------------
| Author Articles Routes
|--------------------------------------------------------------------------
|
| إدارة مقالات الكاتب داخل لوحة تحكم الكاتب.
|
| جميع المسارات محمية بالمصادقة.
| يتم تحديد الكاتب من المستخدم الحالي.
|
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // جلب جميع مقالات الكاتب الحالي
    Route::get(
        '/author/articles',
        [AuthorArticleController::class, 'index']
    )->name('author.articles.index');

    // إنشاء مقال جديد
    Route::post(
        '/author/articles',
        [AuthorArticleController::class, 'store']
    )->name('author.articles.store');
Route::post(
    '/author/articles/{article}/submit-review',
    [AuthorArticleController::class, 'submitForReview']
)->name('author.articles.submit-review');
    // عرض مقال محدد للكاتب
    Route::get(
        '/author/articles/{article}',
        [AuthorArticleController::class, 'show']
    )->name('author.articles.show');

    // تعديل مقال الكاتب
    Route::match(
        ['put', 'patch'],
        '/author/articles/{article}',
        [AuthorArticleController::class, 'update']
    )->name('author.articles.update');

    // حذف مقال الكاتب
    Route::delete(
        '/author/articles/{article}',
        [AuthorArticleController::class, 'destroy']
    )->name('author.articles.destroy');
});