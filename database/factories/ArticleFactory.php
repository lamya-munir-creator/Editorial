<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(
            fake()->numberBetween(4, 8)
        );

        $status = fake()->randomElement([
            'draft',
            'published',
            'archived',
        ]);

        return [
            'uuid' => (string) Str::uuid(),

            'author_id' => Author::query()
                ->inRandomOrder()
                ->value('id'),

            'category_id' => Category::query()
                ->inRandomOrder()
                ->value('id'),

            'featured_image_id' => fake()->optional(0.6)->passthrough(
                Media::query()
                    ->where('type', 'image')
                    ->inRandomOrder()
                    ->value('id')
            ),

            'title' => $title,

            'slug' => Str::slug($title)
                . '-'
                . Str::lower(Str::random(6)),

            'excerpt' => fake()
                ->optional()
                ->paragraph(),

            'content' => fake()->paragraphs(
                fake()->numberBetween(5, 12),
                true
            ),

            'meta_title' => fake()
                ->optional()
                ->sentence(6),

            'meta_description' => fake()
                ->optional()
                ->text(300),

            'reading_time' => fake()->numberBetween(1, 20),

            'views_count' => fake()->numberBetween(0, 50_000),

            'is_featured' => fake()->boolean(20),

            'allow_comments' => fake()->boolean(85),

            'published_at' => $status === 'published'
                ? fake()->dateTimeBetween('-1 year', 'now')
                : null,

            'status' => $status,

            'created_by' => User::query()
                ->inRandomOrder()
                ->value('id'),

            'updated_by' => fake()->optional(0.5)->passthrough(
                User::query()
                    ->inRandomOrder()
                    ->value('id')
            ),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => 'published',
            'published_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (): array => [
            'status' => 'archived',
            'published_at' => fake()->optional()->dateTimeBetween('-2 years', 'now'),
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (): array => [
            'is_featured' => true,
        ]);
    }

    public function withoutFeaturedImage(): static
    {
        return $this->state(fn (): array => [
            'featured_image_id' => null,
        ]);
    }

    public function commentsDisabled(): static
    {
        return $this->state(fn (): array => [
            'allow_comments' => false,
        ]);
    }
}