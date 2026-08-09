<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdsCommentsPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // تجهيز الصلاحيات من الـ Seeder الأساسي
        $this->seed(RoleSeeder::class);
    }

    /**
     * اختبار 1: المستخدم العادي لا يستطيع الوصول للإعلانات
     */
    public function test_regular_user_cannot_access_ads(): void
    {
        $regularUser = User::factory()->create();
        $regularUser->assignRole('user');

        $response = $this->actingAs($regularUser, 'sanctum')
            ->getJson('/api/advertisements');

        $response->assertStatus(403); // Forbidden
    }

    /**
     * اختبار 2: المدير (Admin) يستطيع الوصول للإعلانات
     */
    public function test_admin_can_access_ads(): void
    {
        $adminUser = User::factory()->create();
        $adminUser->assignRole('admin');

        $response = $this->actingAs($adminUser, 'sanctum')
            ->getJson('/api/advertisements');

        // نتوقع 200 لأن الصلاحيات سليمة (حتى لو كانت قاعدة البيانات فارغة سيعود بمصفوفة فارغة)
        $response->assertStatus(200)
            ->assertJsonPath('status', true);
    }

    /**
     * اختبار 3: المستخدم العادي لا يستطيع الوصول للتعليقات
     */
    public function test_regular_user_cannot_access_comments(): void
    {
        $regularUser = User::factory()->create();
        $regularUser->assignRole('user');

        $response = $this->actingAs($regularUser, 'sanctum')
            ->getJson('/api/comments');

        $response->assertStatus(403);
    }

    /**
     * اختبار 4: المحرر (Editor) يستطيع الوصول للتعليقات
     */
    public function test_editor_can_access_comments(): void
    {
        $editorUser = User::factory()->create();
        $editorUser->assignRole('editor');

        $response = $this->actingAs($editorUser, 'sanctum')
            ->getJson('/api/comments');

        $response->assertStatus(200)
            ->assertJsonPath('status', true);
    }

    /**
     * اختبار 5: المستخدم العادي لا يستطيع رؤية مشتركي النشرة البريدية
     */
    public function test_regular_user_cannot_access_newsletter_subscribers(): void
    {
        $regularUser = User::factory()->create();
        $regularUser->assignRole('user');

        $response = $this->actingAs($regularUser, 'sanctum')
            ->getJson('/api/newsletter-subscribers');

        $response->assertStatus(403);
    }

    /**
     * اختبار 6: الزائر (غير المسجل) يستطيع التسجيل في النشرة البريدية
     */
    public function test_guest_can_subscribe_to_newsletter(): void
    {
        $response = $this->postJson('/api/newsletter-subscribers', [
            'email' => 'test@example.com',
            'full_name' => 'Test User'
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', true);
    }
}
