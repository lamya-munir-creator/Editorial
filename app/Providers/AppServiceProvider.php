<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use App\Models\Setting;
use Illuminate\Support\Facades\Config;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });

        // تفعيل قراءة المنطقة الزمنية ديناميكياً من قاعدة البيانات
        if (Schema::hasTable('settings')) {
            try {
                $timezoneSetting = Setting::where('setting_key', 'timezone')->first();
                if ($timezoneSetting && $timezoneSetting->setting_value) {
                    $timezone = $timezoneSetting->setting_value; // مثل Asia/Aden
                    
                    Config::set('app.timezone', $timezone);
                    date_default_timezone_set($timezone);
                }
            } catch (\Exception $e) {
                // تجاهل الخطأ مؤقتاً في حال لم تكن الجداول جاهزة بعد
            }
        }
    }
}