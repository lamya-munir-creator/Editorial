<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        $status = fake()->randomElement([
            'pending',
            'approved',
            'spam',
            'rejected',
        ]);

        $isGuest = fake()->boolean(50);

        return [
            'uuid' => (string) Str::uuid(),

            'article_id' => Article::query()
                ->inRandomOrder()
                ->value('id'),

            'parent_id' => null,

            'user_id' => $isGuest
                ? null
                : User::query()
                    ->inRandomOrder()
                    ->value('id'),

            'guest_name' => $isGuest
                ? fake()->name()
                : null,

            'guest_email' => $isGuest
                ? fake()->safeEmail()
                : null,

            'guest_website' => $isGuest
                ? fake()->optional(0.3)->url()
                : null,

            'content' => fake()->paragraph(
                fake()->numberBetween(2, 5)
            ),

            'ip_address' => fake()
                ->optional(0.8)
                ->ipv4(),

            'user_agent' => fake()
                ->optional(0.8)
                ->userAgent(),

            'status' => $status,

            'approved_by' => $status === 'approved'
                ? User::query()
                    ->inRandomOrder()
                    ->value('id')
                : null,

            'approved_at' => $status === 'approved'
                ? fake()->dateTimeBetween('-1 year', 'now')
                : null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'status' => 'pending',
            'approved_by' => null,
            'approved_at' => null,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (): array => [
            'status' => 'approved',
            'approved_by' => User::query()
                ->inRandomOrder()
                ->value('id'),
            'approved_at' => fake()
                ->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    public function spam(): static
    {
        return $this->state(fn (): array => [
            'status' => 'spam',
            'approved_by' => null,
            'approved_at' => null,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (): array => [
            'status' => 'rejected',
            'approved_by' => null,
            'approved_at' => null,
        ]);
    }

    public function guest(): static
    {
        return $this->state(fn (): array => [
            'user_id' => null,
            'guest_name' => fake()->name(),
            'guest_email' => fake()->safeEmail(),
            'guest_website' => fake()->optional(0.3)->url(),
        ]);
    }

    public function registeredUser(): static
    {
        return $this->state(fn (): array => [
            'user_id' => User::query()
                ->inRandomOrder()
                ->value('id'),
            'guest_name' => null,
            'guest_email' => null,
            'guest_website' => null,
        ]);
    }

    public function replyTo(Comment $parent): static
    {
        return $this->state(fn (): array => [
            'article_id' => $parent->article_id,
            'parent_id' => $parent->id,
        ]);
    }
}