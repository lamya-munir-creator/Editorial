<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthorArticleController;

// مسارات مقالات الكاتب - يجب أن تكون محمية بصلاحية الكاتب (author) وليس الـ admin
Route::middleware(['auth:sanctum', 'role:author|admin'])->group(function () {
    Route::get('/author/articles', [AuthorArticleController::class, 'index']);
    Route::post('/author/articles', [AuthorArticleController::class, 'store']);
    Route::get('/author/articles/{article}', [AuthorArticleController::class, 'show']);
    Route::match(['put', 'patch'], '/author/articles/{article}', [AuthorArticleController::class, 'update']);
    Route::delete('/author/articles/{article}', [AuthorArticleController::class, 'destroy']);
    Route::post('/author/articles/{article}/submit-review', [AuthorArticleController::class, 'submitForReview']);
});