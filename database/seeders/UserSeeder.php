<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $usersData = [
            [
                'email'      => 'admin@example.com',
                'first_name' => 'الأدمن',
                'last_name'  => 'الرئيسي',
                'username'   => 'admin',
                'role_name'  => 'admin',
            ],
            [
                'email'      => 'editor@example.com',
                'first_name' => 'المحرر',
                'last_name'  => 'العام',
                'username'   => 'editor',
                'role_name'  => 'editor',
            ],
            [
                'email'      => 'moderator@example.com',
                'first_name' => 'المشرف',
                'last_name'  => 'التفاعلي',
                'username'   => 'moderator',
                'role_name'  => 'moderator',
            ],
            [
                'email'      => 'author@example.com',
                'first_name' => 'الكاتب',
                'last_name'  => 'الصحفي',
                'username'   => 'author',
                'role_name'  => 'author',
            ],
            [
                'email'      => 'user@example.com',
                'first_name' => 'المستخدم',
                'last_name'  => 'العادي',
                'username'   => 'user',
                'role_name'  => 'user',
            ],
        ];

        foreach ($usersData as $data) {
            $role = Role::where('name', $data['role_name'])->first();

            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'uuid'       => (string) Str::uuid(),
                    'role_id'    => $role?->id ?? 2,
                    'first_name' => $data['first_name'],
                    'last_name'  => $data['last_name'],
                    'username'   => $data['username'],
                    'password'   => Hash::make('password'),
                    'status'     => 'active',
                ]
            );

            // إسناد دور Spatie للمستخدم
            $user->syncRoles([$data['role_name']]);
        }
    }
}