<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\User;
use Faker\Factory as Faker;

class AuthorApplicationSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('author_applications')->truncate();

        $faker = Faker::create('ar_SA');

        $users = User::whereDoesntHave('roles', function ($q) {
            $q->whereIn('name', ['admin', 'author']);
        })->inRandomOrder()->take(10)->get();

        if ($users->isEmpty()) {
            $users = User::inRandomOrder()->take(10)->get();
        }

        $statuses = ['pending', 'approved', 'rejected'];
        $applications = [];

        foreach ($users as $user) {
            $status = $statuses[array_rand($statuses)];
            $isRejected = $status === 'rejected';
            
            $applications[] = [
                'uuid' => (string) Str::uuid(),
                'user_id' => $user->id,
                'display_name' => $user->first_name . ' ' . $user->last_name,
                'job_title' => $faker->jobTitle(),
                'biography' => $faker->realText(180),
                'website' => rand(0, 1) ? $faker->url() : null,
                'avatar_id' => null,
                'application_message' => $faker->realText(150),
                'status' => $status,
                'admin_notes' => $isRejected ? 'عذراً، نبذة المحتوى المقدمة لا تتطابق مع الشروط والمعايير الحالية للمنصة.' : null,
                'reviewed_by' => ($status !== 'pending') ? 1 : null,
                'reviewed_at' => ($status !== 'pending') ? now()->subDays(rand(1, 5)) : null,
                'created_at' => now()->subDays(rand(6, 15)),
                'updated_at' => now()->subDays(rand(1, 5)),
            ];
        }

        DB::table('author_applications')->insert($applications);
    }
}