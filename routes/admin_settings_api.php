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
| مسارات الشخص الثالث: إعدادات النظام والمستخدمين (RBAC & Settings)
|--------------------------------------------------------------------------
*/

// مسارات رسائل اتصل بنا والإجابة عليها
Route::apiResource('contact-messages', ContactMessageController::class);
Route::post('contact-messages/{id}/reply', [ContactMessageController::class, 'reply']);

// مسارات الإدارة العليا (الأدوار، المستخدمين، الإعدادات، القوائم)
Route::apiResource('users', UserController::class);
Route::apiResource('roles', RoleController::class);
Route::apiResource('settings', SettingController::class);
Route::apiResource('menus', MenuController::class);
Route::apiResource('menu-items', MenuItemController::class);
