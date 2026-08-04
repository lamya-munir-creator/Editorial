<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SitemapController;

Route::get('/', function () {
    return view('welcome');
});

// إتاحة خريطة الموقع على النطاق الرئيسي مباشرة /sitemap.xml
Route::get('sitemap.xml', [SitemapController::class, 'index']);
