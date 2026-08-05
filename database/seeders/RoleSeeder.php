<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // إعادة ضبط الـ Cache الخاص بالصلاحيات
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. إنشاء قائمة الصلاحيات الأساسية للمشروع
        $permissions = [
            // صلاحيات الشخص الأول (المقالات والتصنيفات)
            'create-article',
            'edit-article',
            'publish-article',
            'delete-article',
            'manage-categories',

            // صلاحيات الشخص الثاني (الإعلانات والتعليقات)
            'manage-ads',
            'approve-comment',
            'delete-comment',

            // صلاحيات الشخص الثالث (الإعدادات والمستخدمين)
            'manage-settings',
            'change-user-role',
            'view-contact-messages',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name'       => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        // 2. إنشاء وتحديث الأدوار عبر Spatie
        $adminRole     = Role::firstOrCreate(['name' => 'admin',     'guard_name' => 'web']);
        $editorRole    = Role::firstOrCreate(['name' => 'editor',    'guard_name' => 'web']);
        $moderatorRole = Role::firstOrCreate(['name' => 'moderator', 'guard_name' => 'web']);
        $authorRole    = Role::firstOrCreate(['name' => 'author',    'guard_name' => 'web']);
        $userRole      = Role::firstOrCreate(['name' => 'user',      'guard_name' => 'web']);

        // 3. إسناد الصلاحيات المحددة لكل دور

        // الأدمن: كافة الصلاحيات
        $adminRole->syncPermissions(Permission::all());

        // المحرر: المقالات والتصنيفات والتعليقات
        $editorRole->syncPermissions([
            'create-article',
            'edit-article',
            'publish-article',
            'delete-article',
            'manage-categories',
            'approve-comment',
            'delete-comment',
        ]);

        // المشرف (Moderator): الإشراف على التعليقات والرسائل وتعديل المقالات
        $moderatorRole->syncPermissions([
            'approve-comment',
            'delete-comment',
            'view-contact-messages',
            'edit-article',
        ]);

        // الكاتب: إنشاء وتعديل مقالاته
        $authorRole->syncPermissions([
            'create-article',
            'edit-article',
        ]);

        // المستخدم: بدون صلاحيات إدارية
        $userRole->syncPermissions([]);

        // 4. تعبئة البيانات المكملة بجدول الأدوار (مثل uuid و slug والوصف)
        $rolesMetaData = [
            ['name' => 'admin',     'slug' => 'admin',     'description' => 'مدير النظام',            'status' => 'active'],
            ['name' => 'editor',    'slug' => 'editor',    'description' => 'محرر المحتوى',           'status' => 'active'],
            ['name' => 'moderator', 'slug' => 'moderator', 'description' => 'مشرف المحتوى والتعليقات', 'status' => 'active'],
            ['name' => 'author',    'slug' => 'author',    'description' => 'كاتب ومؤلف',            'status' => 'active'],
            ['name' => 'user',      'slug' => 'user',      'description' => 'مستخدم عادي',            'status' => 'active'],
        ];

        foreach ($rolesMetaData as $meta) {
            \App\Models\Role::updateOrCreate(
                ['name' => $meta['name']],
                [
                    'uuid'        => \App\Models\Role::where('name', $meta['name'])->value('uuid') ?? (string) Str::uuid(),
                    'slug'        => $meta['slug'],
                    'description' => $meta['description'],
                    'status'      => $meta['status'],
                    'guard_name'  => 'web',
                ]
            );
        }
    }
}