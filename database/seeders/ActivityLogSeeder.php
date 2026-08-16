<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Seeder;

class ActivityLogSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        $userId = $user ? $user->id : null;

        $samples = [
            [
                'user_id' => $userId,
                'action_type' => 'system_settings',
                'action_label' => 'قامت بتعديل إعدادات',
                'target_name' => 'إعدادات محركات البحث (SEO)',
                'target_url' => '/seo',
            ],
            [
                'user_id' => $userId,
                'action_type' => 'edit_article',
                'action_label' => 'قام بتعديل المقال',
                'target_name' => 'مستقبل الذكاء الاصطناعي في 2026',
                'target_url' => '/articles',
            ],
            [
                'user_id' => $userId,
                'action_type' => 'publish_article',
                'action_label' => 'قامت بنشر المقال',
                'target_name' => 'تحديثات جوجل الجديدة',
                'target_url' => '/articles',
            ],
        ];

        foreach ($samples as $sample) {
            ActivityLog::create($sample);
        }
    }
}