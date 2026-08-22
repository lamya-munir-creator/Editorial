<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $creator = User::first();

        if (!$creator) {
            $this->command->error('No user found. Seed users first.');
            return;
        }

        $categories = [
            [
                'name' => 'تقنية',
                'slug' => 'technology',
                'description' => 'أحدث المقالات حول التقنية والذكاء الاصطناعي والبرمجيات والتحول الرقمي.',
                'status' => 'active',
                'sort_order' => 1,
            ],
            [
                'name' => 'رياضة وصحة',
                'slug' => 'sports-health',
                'description' => 'مقالات حول الرياضة والصحة واللياقة ونمط الحياة.',
                'status' => 'active',
                'sort_order' => 2,
            ],
            [
                'name' => 'أعمال',
                'slug' => 'business',
                'description' => 'مقالات ورؤى حول ريادة الأعمال والإدارة والشركات وسوق العمل.',
                'status' => 'active',
                'sort_order' => 3,
            ],
            [
                'name' => 'ثقافة',
                'slug' => 'culture',
                'description' => 'موضوعات ثقافية حول الفكر والأدب والمعرفة والمجتمع.',
                'status' => 'active',
                'sort_order' => 4,
            ],
            [
                'name' => 'فن',
                'slug' => 'art',
                'description' => 'مقالات حول الفن والسينما والموسيقى والتصميم والإبداع.',
                'status' => 'active',
                'sort_order' => 5,
            ],
            [
                'name' => 'علوم',
                'slug' => 'science',
                'description' => 'مقالات وأخبار واكتشافات في مختلف المجالات العلمية.',
                'status' => 'active',
                'sort_order' => 6,
            ],
            [
                'name' => 'تعليم',
                'slug' => 'education',
                'description' => 'مقالات حول التعليم والتعلم وتطوير المهارات.',
                'status' => 'active',
                'sort_order' => 7,
            ],
            [
                'name' => 'اقتصاد',
                'slug' => 'economy',
                'description' => 'تحليلات وموضوعات حول الاقتصاد والأسواق والاتجاهات المالية.',
                'status' => 'active',
                'sort_order' => 8,
            ],
            [
                'name' => 'مجتمع',
                'slug' => 'society',
                'description' => 'موضوعات تناقش المجتمع والحياة والتغيرات المعاصرة.',
                'status' => 'active',
                'sort_order' => 9,
            ],
            [
                'name' => 'ابتكار',
                'slug' => 'innovation',
                'description' => 'أفكار واتجاهات حديثة في الابتكار والإبداع.',
                'status' => 'active',
                'sort_order' => 10,
            ],
            [
                'name' => 'سفر',
                'slug' => 'travel',
                'description' => 'وجهات وتجارب ونصائح حول السفر واستكشاف أماكن وثقافات مختلفة.',
                'status' => 'inactive',
                'sort_order' => 11,
            ],
            [
                'name' => 'بيئة',
                'slug' => 'environment',
                'description' => 'موضوعات حول البيئة والاستدامة والمناخ والحفاظ على الموارد.',
                'status' => 'inactive',
                'sort_order' => 12,
            ],
        ];

        foreach ($categories as $data) {
            Category::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'status' => $data['status'],
                    'sort_order' => $data['sort_order'],
                    'created_by' => $creator->id,
                    'updated_by' => $creator->id,
                ]
            );
        }

        $this->command->info('Categories seeded successfully.');
    }
}