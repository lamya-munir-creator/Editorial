<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Category;
use App\Models\Article;
use App\Models\Author;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticlesPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(RoleSeeder::class);
    }

    /**
     * اختبار 1: المستخدم العادي لا يستطيع إنشاء مقال ويحصل على 403 Forbidden
     */
    public function test_regular_user_cannot_create_article(): void
    {
        $user = User::factory()->create(['role_id' => 1]);
        $user->assignRole('user');
        $category = Category::factory()->create(['created_by' => $user->id, 'status' => 'active']);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/articles', [
                'title'       => 'مقال محظور من مستخدم عادي',
                'content'     => 'محتوى تجريبي غير مصرح',
                'category_id' => $category->id,
                'status'      => 'published',
            ]);

        $response->assertStatus(403);
    }

    /**
     * اختبار 2: الكاتب يستطيع إنشاء مقال بنجاح ويحصل على 201 Created
     */
    public function test_author_can_create_article(): void
    {
        $authorUser = User::factory()->create(['role_id' => 2]);
        $authorUser->assignRole('author');
        $category = Category::factory()->create(['created_by' => $authorUser->id, 'status' => 'active']);

        $response = $this->actingAs($authorUser, 'sanctum')
            ->postJson('/api/articles', [
                'title'       => 'مقال صحفي معتمد من الكاتب',
                'content'     => 'محتوى المقال الصحفي المعتمد لاختبار الصلاحية',
                'category_id' => $category->id,
                'status'      => 'published',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', true);
    }

    /**
     * اختبار 3: المحرر يستطيع تعديل أي مقال ويحصل على 200 OK
     */
    public function test_editor_can_update_any_article(): void
    {
        $authorUser = User::factory()->create(['role_id' => 2]);
        $authorUser->assignRole('author');
        $author = Author::factory()->create(['user_id' => $authorUser->id]);
        $category = Category::factory()->create(['created_by' => $authorUser->id, 'status' => 'active']);

        $article = Article::factory()->create([
            'author_id'   => $author->id,
            'category_id' => $category->id,
            'created_by'  => $authorUser->id,
            'status'      => 'published',
        ]);

        $editorUser = User::factory()->create(['role_id' => 4]);
        $editorUser->assignRole('editor');

        $response = $this->actingAs($editorUser, 'sanctum')
            ->putJson("/api/articles/{$article->id}", [
                'title' => 'عنوان معدل بوسطة المحرر العام',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', true);
    }

    /**
     * اختبار 4: المحرر يستطيع إنشاء قسم جديد ويحصل على 201 Created
     */
    public function test_editor_can_create_category(): void
    {
        $editorUser = User::factory()->create(['role_id' => 4]);
        $editorUser->assignRole('editor');

        $response = $this->actingAs($editorUser, 'sanctum')
            ->postJson('/api/categories', [
                'name'   => 'قسم التكنولوجيا والاقتصاد',
                'slug'   => 'tech-economy-section',
                'status' => 'active',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', true);
    }
}
