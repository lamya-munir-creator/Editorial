<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthorSeeder extends Seeder
{
    public function run(): void
    {
        $creator = User::first();

        if (!$creator) {
            $this->command->error('No user found. Seed users first.');
            return;
        }

        // نحاول إيجاد Role مناسب للكاتب
        $authorRoleId = DB::table('roles')
            ->whereIn('name', ['author', 'writer'])
            ->value('id');

        if (!$authorRoleId) {
            $authorRoleId = $creator->role_id;
        }

        $authors = [
            [
                'first_name' => 'أحمد',
                'last_name' => 'السالمي',
                'display_name' => 'أحمد السالمي',
                'slug' => 'ahmed-al-salmi',
                'username' => 'ahmed.salmi',
                'email' => 'ahmed.salmi@example.com',
                'biography' => 'كاتب مهتم بالتقنية والابتكار والتحول الرقمي، ويكتب عن تأثير التكنولوجيا في الحياة والعمل.',
                'job_title' => 'كاتب في التقنية والابتكار',
                'gender' => 'male',
                'status' => 'active',
            ],
            [
                'first_name' => 'سارة',
                'last_name' => 'العريقي',
                'display_name' => 'سارة العريقي',
                'slug' => 'sara-al-oraiki',
                'username' => 'sara.oraiki',
                'email' => 'sara.oraiki@example.com',
                'biography' => 'كاتبة تهتم بالصحة ونمط الحياة والعادات اليومية التي تساعد على تحسين جودة الحياة.',
                'job_title' => 'كاتبة في الصحة ونمط الحياة',
                'gender' => 'female',
                'status' => 'active',
            ],
            [
                'first_name' => 'محمد',
                'last_name' => 'الحكيمي',
                'display_name' => 'محمد الحكيمي',
                'slug' => 'mohammed-al-hakimi',
                'username' => 'mohammed.hakimi',
                'email' => 'mohammed.hakimi@example.com',
                'biography' => 'كاتب رياضي يهتم باللياقة والصحة الرياضية وبناء العادات الرياضية المستمرة.',
                'job_title' => 'كاتب رياضي',
                'gender' => 'male',
                'status' => 'active',
            ],
            [
                'first_name' => 'ليان',
                'last_name' => 'القحطاني',
                'display_name' => 'ليان القحطاني',
                'slug' => 'layan-al-qahtani',
                'username' => 'layan.qahtani',
                'email' => 'layan.qahtani@example.com',
                'biography' => 'كاتبة في الثقافة والفنون تهتم بالسينما والتصميم والفنون البصرية.',
                'job_title' => 'كاتبة في الثقافة والفنون',
                'gender' => 'female',
                'status' => 'active',
            ],
            [
                'first_name' => 'عمر',
                'last_name' => 'الرفاعي',
                'display_name' => 'عمر الرفاعي',
                'slug' => 'omar-al-rifai',
                'username' => 'omar.rifai',
                'email' => 'omar.rifai@example.com',
                'biography' => 'كاتب يهتم بالتقنية والعلوم الحديثة والذكاء الاصطناعي والمستقبل الرقمي.',
                'job_title' => 'كاتب في التقنية والعلوم',
                'gender' => 'male',
                'status' => 'active',
            ],
            [
                'first_name' => 'نور',
                'last_name' => 'الحداد',
                'display_name' => 'نور الحداد',
                'slug' => 'noor-al-haddad',
                'username' => 'noor.haddad',
                'email' => 'noor.haddad@example.com',
                'biography' => 'كاتبة تهتم بالصحة النفسية والمجتمع ونمط الحياة.',
                'job_title' => 'كاتبة في الصحة والمجتمع',
                'gender' => 'female',
                'status' => 'active',
            ],
            [
                'first_name' => 'خالد',
                'last_name' => 'العمري',
                'display_name' => 'خالد العمري',
                'slug' => 'khaled-al-omari',
                'username' => 'khaled.omari',
                'email' => 'khaled.omari@example.com',
                'biography' => 'كاتب يهتم بالرياضة اليومية واللياقة وأثر النشاط البدني على الصحة.',
                'job_title' => 'كاتب في الرياضة واللياقة',
                'gender' => 'male',
                'status' => 'active',
            ],
            [
                'first_name' => 'ريم',
                'last_name' => 'الشامي',
                'display_name' => 'ريم الشامي',
                'slug' => 'reem-al-shami',
                'username' => 'reem.shami',
                'email' => 'reem.shami@example.com',
                'biography' => 'كاتبة في الفن والثقافة تهتم بالتصميم والسينما والإبداع.',
                'job_title' => 'كاتبة في الفن والثقافة',
                'gender' => 'female',
                'status' => 'active',
            ],

            // Inactive
            [
                'first_name' => 'ياسر',
                'last_name' => 'النعماني',
                'display_name' => 'ياسر النعماني',
                'slug' => 'yasser-al-numani',
                'username' => 'yasser.numani',
                'email' => 'yasser.numani@example.com',
                'biography' => 'كاتب يهتم بالأعمال والتقنية وريادة الأعمال.',
                'job_title' => 'كاتب في الأعمال والتقنية',
                'gender' => 'male',
                'status' => 'inactive',
            ],

            // inactive
            [
                'first_name' => 'هناء',
                'last_name' => 'الصبري',
                'display_name' => 'هناء الصبري',
                'slug' => 'hana-al-sabri',
                'username' => 'hana.sabri',
                'email' => 'hana.sabri@example.com',
                'biography' => 'كاتبة في العلوم والمعرفة وتهتم بتبسيط الموضوعات العلمية.',
                'job_title' => 'كاتبة في العلوم والمعرفة',
                'gender' => 'female',
                'status' => 'inactive',
            ],
        ];

        foreach ($authors as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'uuid' => (string) Str::uuid(),
                    'role_id' => $authorRoleId,

                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'username' => $data['username'],

                    'password' => Hash::make('password123'),
                    'email_verified_at' => now(),

                    'locale' => 'ar',
                    'timezone' => 'Asia/Aden',

                    // نخلي User بنفس حالة Author
                    'status' => $data['status'],

                    'created_by' => $creator->id,
                    'updated_by' => $creator->id,
                ]
            );

            Author::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'uuid' => (string) Str::uuid(),

                    'user_id' => $user->id,
                    'avatar_id' => null,

                    'display_name' => $data['display_name'],
                    'biography' => $data['biography'],
                    'job_title' => $data['job_title'],

                    'website' => null,
                    'facebook' => null,
                    'twitter' => null,
                    'linkedin' => null,
                    'instagram' => null,
                    'youtube' => null,

                    'gender' => $data['gender'],
                    'status' => $data['status'],

                    'created_by' => $creator->id,
                    'updated_by' => $creator->id,
                ]
            );
        }

        $this->command->info('Authors seeded successfully.');
    }
}