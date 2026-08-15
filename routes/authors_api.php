<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthorController;

/*
|--------------------------------------------------------------------------
| Authors Routes
|--------------------------------------------------------------------------
|
| مسارات بروفايلات الكُتّاب.
|
| المستخدم المسجل الذي لديه بروفايل كاتب:
| - يستطيع مشاهدة بروفايله الخاص وتعديله (/authors/me)
|
| عام (بدون تسجيل دخول):
| - استعراض قائمة الكُتّاب وصفحاتهم العامة
|
| الأدمن:
| - إضافة/تعديل/حذف أي بروفايل كاتب
|
|--------------------------------------------------------------------------
*/

// ========================================================================
// المستخدم المسجل: بروفايله الشخصي كـ "كاتب"
// ملاحظة: يجب تعريف /authors/me قبل /authors/{author} حتى لا يفسَّر
// "me" كمعرّف Route Model Binding.
// ========================================================================

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/authors/me', [AuthorController::class, 'me']);

    Route::patch('/authors/me', [AuthorController::class, 'updateMe']);
});

// ========================================================================
// عام: استعراض الكُتّاب وصفحاتهم العامة
// ========================================================================

Route::get('/authors', [AuthorController::class, 'index'])
    ->name('authors.index');

Route::get('/authors/slug/{slug}', [AuthorController::class, 'showBySlug']);

Route::get('/authors/{author}', [AuthorController::class, 'show'])
    ->name('authors.show');

// ========================================================================
// إدارة الكُتّاب - Admin فقط
// ========================================================================

Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {

    Route::post('/authors', [AuthorController::class, 'store'])
        ->name('authors.store');

    Route::match(['put', 'patch'], '/authors/{author}', [AuthorController::class, 'update'])
        ->name('authors.update');

    Route::delete('/authors/{author}', [AuthorController::class, 'destroy'])
        ->name('authors.destroy');
});