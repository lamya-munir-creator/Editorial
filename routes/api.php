<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ActivityLogController;


/*
|--------------------------------------------------------------------------
| API Routes Configuration
|--------------------------------------------------------------------------
*/

// إدراج مسارات Breeze المصادقة الأساسية
require __DIR__.'/auth.php';

// مسارات أعضاء الفريق (التقسيم العمودي)
require __DIR__ . '/articles_api.php';
require __DIR__ . '/ads_comments_api.php';
require __DIR__ . '/admin_settings_api.php';
require __DIR__ . '/newsletter_api.php';
require __DIR__ . '/author_applications_api.php';
require __DIR__ . '/authors_api.php';
require __DIR__ . '/author_articles_api.php';
require __DIR__ . '/author_comments_api.php';
require __DIR__ . '/author_notifications_api.php';
require __DIR__.'/editor_articles_api.php';

// 1. مسارات المصادقة العامة والخاصة بـ API (عبر AuthController)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/auth/send-otp', [AuthController::class, 'sendOtp']);
Route::post('/verify-email-otp', [AuthController::class, 'verifyEmailOtp']);
Route::post('/verify-otp', [AuthController::class, 'verifyEmailOtp']);
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/activity-logs', [ActivityLogController::class, 'index']);
});

// =========================================================================
// 2. الملفات الموُزعة على الفريق لتفادي تعارضات Git (Modular Route Files)
// =========================================================================

// الشخص الأول: المقالات والأقسام (Articles & Categories Feature)
require __DIR__.'/articles_api.php';

// الشخص الثاني: النظام التفاعلي والإعلانات (Comments & Advertisements Feature)
require __DIR__.'/ads_comments_api.php';

// الشخص الثالث: إعدادات النظام والمستخدمين (System Settings & RBAC Management)
require __DIR__.'/admin_settings_api.php';

require __DIR__ . '/media_api.php';


// Public SEO Routes
Route::get('/robots.txt', [\App\Http\Controllers\Api\RobotsController::class, 'index']);
Route::get('/sitemap.xml', [\App\Http\Controllers\Api\SitemapController::class, 'index']);
