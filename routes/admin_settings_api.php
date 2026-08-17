<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\ContactMessageController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\MenuItemController;
use App\Http\Controllers\Api\NotificationController;

/*
|--------------------------------------------------------------------------
| Admin Settings & RBAC Routes
|--------------------------------------------------------------------------
*/

// استقبال رسالة جديدة متاح للزائر بدون تسجيل دخول
Route::post('/contact-messages', [ContactMessageController::class, 'store']);

<<<<<<< Updated upstream
Route::middleware(['auth:sanctum', 'role:admin|editor|moderator'])->group(function () {
=======
// الحصول على إعدادات الموقع العامة المتاحة للجمهور بدون مصادقة
Route::get('/public/settings', [SettingController::class, 'publicSettings']);

// مسارات لوحة التحكم: تحتاج توكن صالح ودور admin
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
>>>>>>> Stashed changes
    // إحصائيات وبحث لوحة التحكم
    Route::get('/dashboard/stats', [\App\Http\Controllers\Api\DashboardController::class, 'stats']);
    Route::get('/search', [\App\Http\Controllers\Api\GlobalSearchController::class, 'search']);
});

// مسارات لوحة التحكم: تحتاج توكن صالح ودور admin
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    
    // الإشعارات
    Route::get('/notifications/test', function (\Illuminate\Http\Request $request) {
        $request->user()->notifications()->create([
            'id' => \Illuminate\Support\Str::uuid(),
            'type' => 'App\Notifications\SystemAlert',
            'data' => [
                'message' => 'مرحباً بك في نظام الإشعارات الجديد! هذا إشعار تجريبي.',
                'type' => 'success'
            ]
        ]);
        return response()->json(['message' => 'Test notification sent']);
    });
   
    // إدارة المستخدمين
    Route::apiResource('users', UserController::class);

    // تغيير دور المستخدم
    Route::patch(
        '/users/{user}/role',
        [UserController::class, 'changeRole']
    );

    // تغيير حالة حساب المستخدم
    Route::patch(
        '/users/{user}/status',
        [UserController::class, 'changeStatus']
    );

    // إدارة الأدوار
    Route::apiResource('roles', RoleController::class);

    // إدارة إعدادات النظام
    Route::apiResource('settings', SettingController::class);

    // إدارة رسائل التواصل، باستثناء إنشاء الرسالة العامة
    Route::apiResource(
        'contact-messages',
        ContactMessageController::class
    )->except(['store']);

    Route::post(
        '/contact-messages/{id}/reply',
        [ContactMessageController::class, 'reply']
    );

    // إدارة القوائم
    Route::apiResource('menus', MenuController::class);
    Route::apiResource('menu-items', MenuItemController::class);

    // مسارات الـ SEO
    Route::get('/seo-settings', [\App\Http\Controllers\Api\SeoController::class, 'index']);
    Route::post('/seo-settings', [\App\Http\Controllers\Api\SeoController::class, 'update']);
});
