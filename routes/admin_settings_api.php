<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\ContactMessageController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\MenuItemController;

/*
|--------------------------------------------------------------------------
| Admin Settings & RBAC Routes
|--------------------------------------------------------------------------
*/

// استقبال رسالة جديدة متاح للزائر بدون تسجيل دخول
Route::post('/contact-messages', [ContactMessageController::class, 'store']);

// مسارات لوحة التحكم: تحتاج توكن صالح ودور admin
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {

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
});