<?php

namespace Database\Factories;

use App\Models\Advertisement;
use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Advertisement>
 */
class AdvertisementFactory extends Factory
{
    protected $model = Advertisement::class;

    public function definition(): array
    {
        $status = fake()->randomElement([
            'draft',
            'active',
            'inactive',
            'expired',
        ]);

        $startDate = fake()->optional(0.8)
            ->dateTimeBetween('-6 months', '+1 month');

        $endDate = match ($status) {
            'expired' => fake()->dateTimeBetween('-6 months', '-1 day'),
            'active' => fake()->optional(0.8)
                ->dateTimeBetween('now', '+6 months'),
            default => fake()->optional()
                ->dateTimeBetween('+1 day', '+1 year'),
        };

        return [
            'uuid' => (string) Str::uuid(),

            'image_id' => Media::query()
                ->where('type', 'image')
                ->inRandomOrder()
                ->value('id'),

            'title' => fake()->sentence(
                fake()->numberBetween(3, 6)
            ),

            'destination_url' => fake()->url(),

            'position' => fake()->randomElement([
                'homepage_top',
                'homepage_sidebar',
                'article_top',
                'article_bottom',
                'category_sidebar',
                'footer',
            ]),

            'display_order' => fake()->numberBetween(0, 100),

            'start_date' => $startDate,

            'end_date' => $endDate,

            'is_internal' => fake()->boolean(30),

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
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (): array => [
            'status' => 'active',
            'start_date' => fake()
                ->dateTimeBetween('-1 month', 'now'),
            'end_date' => fake()
                ->dateTimeBetween('now', '+6 months'),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'status' => 'inactive',
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (): array => [
            'status' => 'expired',
            'start_date' => fake()
                ->dateTimeBetween('-1 year', '-2 months'),
            'end_date' => fake()
                ->dateTimeBetween('-2 months', '-1 day'),
        ]);
    }

    public function internal(): static
    {
        return $this->state(fn (): array => [
            'is_internal' => true,
            'destination_url' => fake()->randomElement([
                '/articles',
                '/categories',
                '/about',
                '/contact',
            ]),
        ]);
    }

    public function external(): static
    {
        return $this->state(fn (): array => [
            'is_internal' => false,
            'destination_url' => fake()->url(),
        ]);
    }

    public function forPosition(string $position): static
    {
        return $this->state(fn (): array => [
            'position' => $position,
        ]);
    }
}