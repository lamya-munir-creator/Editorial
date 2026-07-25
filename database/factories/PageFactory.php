<?php

namespace Database\Factories;

use App\Models\Media;
use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    protected $model = Page::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(
            fake()->numberBetween(2, 6)
        );

        $status = fake()->randomElement([
            'draft',
            'published',
            'archived',
        ]);

        return [
            'uuid' => (string) Str::uuid(),

            'featured_image_id' => fake()->optional(0.5)->passthrough(
                Media::query()
                    ->where('type', 'image')
                    ->inRandomOrder()
                    ->value('id')
            ),

            'title' => $title,

            'slug' => Str::slug($title)
                . '-'
                . Str::lower(Str::random(6)),

            'content' => fake()->paragraphs(
                fake()->numberBetween(4, 10),
                true
            ),

            'meta_title' => fake()
                ->optional()
                ->sentence(6),

            'meta_description' => fake()
                ->optional()
                ->text(300),

            'template' => fake()->randomElement([
                'default',
                'about',
                'contact',
                'privacy-policy',
                'terms',
            ]),

            'is_homepage' => false,

            'status' => $status,

            'published_at' => $status === 'published'
                ? fake()->dateTimeBetween('-1 year', 'now')
                : null,

            'created_by' => User::query()
                ->inRandomOrder()
                ->value('id'),

            'updated_by' => fake()
                ->optional(0.5)
                ->passthrough(
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
            'published_at' => fake()
                ->optional()
                ->dateTimeBetween('-2 years', 'now'),
        ]);
    }

    public function homepage(): static
    {
        return $this->state(fn (): array => [
            'is_homepage' => true,
            'status' => 'published',
            'published_at' => now(),
            'template' => 'default',
        ]);
    }

    public function withoutFeaturedImage(): static
    {
        return $this->state(fn (): array => [
            'featured_image_id' => null,
        ]);
    }

    public function withTemplate(string $template): static
    {
        return $this->state(fn (): array => [
            'template' => $template,
        ]);
    }
}