<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthorCommentController;

/*
|--------------------------------------------------------------------------
| Author Comments Routes
|--------------------------------------------------------------------------
|
| تعليقات المقالات الخاصة بالكاتب الحالي.
|
*/

Route::middleware('auth:sanctum')->group(function () {

    // جلب التعليقات الموجودة على مقالات الكاتب الحالي
    Route::get(
        '/author/comments',
        [AuthorCommentController::class, 'index']
    )->name('author.comments.index');

});