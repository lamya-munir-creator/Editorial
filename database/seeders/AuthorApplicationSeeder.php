<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\User;

class AuthorApplicationSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('author_applications')->truncate();

        $users = User::whereDoesntHave('roles', function ($q) {
            $q->whereIn('name', ['admin', 'author']);
        })->inRandomOrder()->take(10)->get();

        if ($users->isEmpty()) {
            $users = User::inRandomOrder()->take(10)->get();
        }

        $jobTitles = ['صحفي مستقل', 'مدون تقني', 'كاتب محتوى إبداعي', 'باحث أكاديمي', 'مترجم وصانع محتوى'];
        $messages = [
            'لدي خبرة واسعة في كتابة المقالات وأرغب بمشاركة معرفتي مع جمهوركم.',
            'أتابع منصتكم منذ فترة طويلة وأعتقد أن أسلوبي في الكتابة يتناسب تماماً مع رؤيتكم.',
            'أود الانضمام لفريق الكتاب لديكم لنشر وعي أكبر حول المواضيع التقنية الحديثة.',
            'أرفقت لكم بعض أعمالي السابقة كعينة لمستوى الجودة الذي أقدمه.',
            'الكتابة هي شغفي الأول، وأسعى لبناء مسيرة مهنية قوية من خلال منصتكم الرائدة.'
        ];
        $statuses = ['pending', 'pending', 'pending', 'pending', 'approved', 'approved', 'rejected', 'rejected'];

        $applications = [];

        foreach ($users as $index => $user) {
            $status = $statuses[array_rand($statuses)];
            $isRejected = $status === 'rejected';
            
            $applications[] = [
                'uuid' => Str::uuid(),
                'user_id' => $user->id,
                'display_name' => $user->first_name . ' ' . $user->last_name,
                'job_title' => $jobTitles[array_rand($jobTitles)],
                'biography' => 'أنا كاتب شغوف بالمحتوى العربي. أمتلك خبرة جيدة في إعداد المقالات الحصرية، تدقيق النصوص، وتقديم المعلومات بأسلوب سلس يجذب القارئ ويلبي احتياجاته المعرفية.',
                'website' => rand(0, 1) ? 'https://portfolio-' . $user->username . '.com' : null,
                'application_message' => $messages[array_rand($messages)],
                'status' => $status,
                'admin_notes' => $isRejected ? 'المحتوى المرفق لا يتوافق مع معايير المنصة الحالية. يرجى تطوير الأسلوب والمحاولة لاحقا.' : null,
                'reviewed_by' => ($status !== 'pending') ? 1 : null,
                'reviewed_at' => ($status !== 'pending') ? now()->subDays(rand(1, 5)) : null,
                'created_at' => now()->subDays(rand(6, 15)),
                'updated_at' => now()->subDays(rand(1, 5)),
            ];
        }

        DB::table('author_applications')->insert($applications);
    }
}