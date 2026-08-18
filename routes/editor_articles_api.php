<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EditorArticleController;

Route::middleware([
    'auth:sanctum',
    'role:admin|editor',
])
->prefix('editor')
->group(function () {

    // جميع المقالات
    Route::get(
        '/articles',
        [EditorArticleController::class, 'index']
    );

    // إنشاء مقال
    Route::post(
        '/articles',
        [EditorArticleController::class, 'store']
    );

    // مقال محدد
    Route::get(
        '/articles/{article}',
        [EditorArticleController::class, 'show']
    );

    // تعديل المقال
    Route::match(
        ['put', 'patch'],
        '/articles/{article}',
        [EditorArticleController::class, 'update']
    );

    // نشر مقال بعد المراجعة
    Route::patch(
        '/articles/{article}/publish',
        [EditorArticleController::class, 'publish']
    );

    // إعادة المقال للكاتب
    Route::patch(
        '/articles/{article}/request-changes',
        [EditorArticleController::class, 'requestChanges']
    );

    // أرشفة
    Route::patch(
        '/articles/{article}/archive',
        [EditorArticleController::class, 'archive']
    );

    // حذف
    Route::delete(
        '/articles/{article}',
        [EditorArticleController::class, 'destroy']
    );
});
