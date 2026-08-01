<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaultSettings = [
            [
                'setting_key'   => 'google_analytics_id',
                'setting_value' => 'G-XXXXXXXXXX',
                'group_name'    => 'seo',
                'value_type'    => 'string',
                'is_public'     => true,
            ],
            [
                'setting_key'   => 'google_search_console_code',
                'setting_value' => '',
                'group_name'    => 'seo',
                'value_type'    => 'string',
                'is_public'     => true,
            ],
            [
                'setting_key'   => 'google_adsense_client_id',
                'setting_value' => 'ca-pub-XXXXXXXXXXXXXXXX',
                'group_name'    => 'adsense',
                'value_type'    => 'string',
                'is_public'     => true,
            ],
            [
                'setting_key'   => 'google_adsense_auto_ads',
                'setting_value' => 'true',
                'group_name'    => 'adsense',
                'value_type'    => 'boolean',
                'is_public'     => true,
            ],
            [
                'setting_key'   => 'site_sitemap_url',
                'setting_value' => '/sitemap.xml',
                'group_name'    => 'general',
                'value_type'    => 'string',
                'is_public'     => true,
            ]
        ];

        foreach ($defaultSettings as $setting) {
            Setting::updateOrCreate(
                ['setting_key' => $setting['setting_key']],
                $setting
            );
        }
    }
}