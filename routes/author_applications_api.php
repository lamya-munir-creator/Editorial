<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthorApplicationController;

/*
|--------------------------------------------------------------------------
| Author Applications Routes
|--------------------------------------------------------------------------
|
| مسارات طلبات الانضمام ككاتب.
|
| المستخدم المسجل:
| - يستطيع إرسال طلب
| - يستطيع مشاهدة طلباته
|
| الأدمن:
| - يستطيع مشاهدة جميع الطلبات
| - يستطيع قبول أو رفض الطلب
|
|--------------------------------------------------------------------------
*/

// ========================================================================
// المستخدم المسجل
// ========================================================================

Route::middleware('auth:sanctum')->group(function () {

    // إرسال طلب جديد ليصبح المستخدم كاتبًا
    Route::post(
        '/author-applications',
        [AuthorApplicationController::class, 'store']
    );

    // عرض طلبات المستخدم الحالي
   Route::get(
    '/author-applications/my',
    [AuthorApplicationController::class, 'mine']
);
});


// ========================================================================
// إدارة طلبات الكتاب - Admin
// ========================================================================

Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {

    // عرض جميع طلبات الكتاب
    Route::get(
        '/author-applications',
        [AuthorApplicationController::class, 'index']
    );

    // عرض طلب محدد
    Route::get(
    '/author-applications/{application}',
    [AuthorApplicationController::class, 'show']
);

Route::patch(
    '/author-applications/{application}/approve',
    [AuthorApplicationController::class, 'approve']
);

Route::patch(
    '/author-applications/{application}/reject',
    [AuthorApplicationController::class, 'reject']
);
});