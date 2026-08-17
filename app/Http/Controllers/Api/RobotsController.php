<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Response;

class RobotsController extends Controller
{
    /**
     * خدمة ملف robots.txt
     */
    public function index()
    {
        $defaultRobots = "User-agent: *\nAllow: /\nDisallow: /admin/\nDisallow: /search/";
        $robotsTxt = Setting::where('setting_key', 'seo_robots_txt')->value('setting_value') ?? $defaultRobots;

        return response($robotsTxt, 200, [
            'Content-Type' => 'text/plain',
        ]);
    }
}
