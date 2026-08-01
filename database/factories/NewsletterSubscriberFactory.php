<?php

namespace Database\Factories;

use App\Models\NewsletterSubscriber;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<NewsletterSubscriber>
 */
class NewsletterSubscriberFactory extends Factory
{
    protected $model = NewsletterSubscriber::class;

    public function definition(): array
    {
        $isActive = fake()->boolean(85);

        return [
            'uuid' => (string) Str::uuid(),

            'email' => fake()->unique()->safeEmail(),

            'full_name' => fake()->optional(0.8)->name(),

            'is_active' => $isActive,

            'subscribed_at' => fake()->dateTimeBetween('-2 years', 'now'),

            'unsubscribed_at' => $isActive
                ? null
                : fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (): array => [
            'is_active' => true,
            'unsubscribed_at' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
            'unsubscribed_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    public function withoutName(): static
    {
        return $this->state(fn (): array => [
            'full_name' => null,
        ]);
    }

    public function subscribedToday(): static
    {
        return $this->state(fn (): array => [
            'subscribed_at' => now(),
            'is_active' => true,
            'unsubscribed_at' => null,
        ]);
    }
}