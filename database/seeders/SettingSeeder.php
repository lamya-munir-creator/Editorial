<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;
use Illuminate\Support\Facades\Schema;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        // تعطيل فحص المفاتيح الخارجية مؤقتاً لتنظيف الجدول بأمان
        Schema::disableForeignKeyConstraints();
        Setting::truncate();
        Schema::enableForeignKeyConstraints();

        $settings = [
            // 1. Basic Info
            ['setting_key' => 'site_name', 'setting_value' => 'مجلة لومين - LUMEN', 'group_name' => 'basic', 'value_type' => 'string', 'is_public' => true],
            ['setting_key' => 'site_description', 'setting_value' => 'منصة رائدة لنشر المحتوى العربي', 'group_name' => 'basic', 'value_type' => 'text', 'is_public' => true],
            ['setting_key' => 'timezone', 'setting_value' => 'Asia/Aden', 'group_name' => 'basic', 'value_type' => 'string', 'is_public' => true], // تم تحديثها لتوقيت اليمن
            ['setting_key' => 'date_format', 'setting_value' => 'Y-m-d H:i:s', 'group_name' => 'basic', 'value_type' => 'string', 'is_public' => true],

            // 2. Branding (Defaults - can be updated via UI)
            ['setting_key' => 'site_logo', 'setting_value' => null, 'group_name' => 'branding', 'value_type' => 'image', 'is_public' => true],
            ['setting_key' => 'site_favicon', 'setting_value' => null, 'group_name' => 'branding', 'value_type' => 'image', 'is_public' => true],

            // 3. Contact & Social
            ['setting_key' => 'contact_email', 'setting_value' => 'contact@lumen.com', 'group_name' => 'contact', 'value_type' => 'string', 'is_public' => true],
            ['setting_key' => 'contact_phone', 'setting_value' => '+966500000000', 'group_name' => 'contact', 'value_type' => 'string', 'is_public' => true],
            ['setting_key' => 'contact_address', 'setting_value' => 'الرياض، المملكة العربية السعودية', 'group_name' => 'contact', 'value_type' => 'string', 'is_public' => true],
            ['setting_key' => 'social_media', 'setting_value' => json_encode([
                ['platform' => 'twitter', 'url' => 'https://twitter.com/lumen'],
                ['platform' => 'linkedin', 'url' => 'https://linkedin.com/company/lumen']
            ]), 'group_name' => 'contact', 'value_type' => 'json', 'is_public' => true],

            // 4. Footer
            ['setting_key' => 'footer_copyright_text', 'setting_value' => 'جميع الحقوق محفوظة لمجلة لومين © 2026', 'group_name' => 'footer', 'value_type' => 'string', 'is_public' => true],
            ['setting_key' => 'footer_extra_info', 'setting_value' => 'تم تطوير المنصة بأحدث التقنيات لتقديم أفضل تجربة.', 'group_name' => 'footer', 'value_type' => 'text', 'is_public' => true],
            ['setting_key' => 'footer_quick_links', 'setting_value' => json_encode([
                ['title' => 'من نحن', 'url' => '/about-us'],
                ['title' => 'الشروط والأحكام', 'url' => '/terms']
            ]), 'group_name' => 'footer', 'value_type' => 'json', 'is_public' => true],
        ];

        foreach ($settings as $setting) {
            Setting::create($setting);
        }
    }
}