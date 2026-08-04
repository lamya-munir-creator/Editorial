<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'admin',  'slug' => 'admin',  'description' => 'مدير النظام', 'status' => 'active'],
            ['name' => 'editor', 'slug' => 'editor', 'description' => 'محرر',        'status' => 'active'],
            ['name' => 'author', 'slug' => 'author', 'description' => 'كاتب',        'status' => 'active'],
            ['name' => 'user',   'slug' => 'user',   'description' => 'مستخدم',      'status' => 'active'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['name' => $role['name']],
                [
                    'uuid'        => (string) Str::uuid(),
                    'slug'        => $role['slug'],
                    'description' => $role['description'],
                    'status'      => $role['status'],
                ]
            );
        }
    }
}