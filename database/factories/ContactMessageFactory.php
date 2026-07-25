<?php

namespace Database\Factories;

use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ContactMessage>
 */
class ContactMessageFactory extends Factory
{
    protected $model = ContactMessage::class;

    public function definition(): array
    {
        $status = fake()->randomElement([
            'new',
            'read',
            'replied',
            'archived',
        ]);

        return [
            'uuid' => (string) Str::uuid(),

            'full_name' => fake()->name(),

            'email' => fake()->safeEmail(),

            'phone' => fake()
                ->optional(0.7)
                ->phoneNumber(),

            'subject' => fake()
                ->optional(0.8)
                ->sentence(fake()->numberBetween(3, 7)),

            'message' => fake()->paragraphs(
                fake()->numberBetween(2, 5),
                true
            ),

            'status' => $status,

            'handled_by' => in_array($status, ['read', 'replied', 'archived'], true)
                ? User::query()->inRandomOrder()->value('id')
                : null,

            'replied_at' => $status === 'replied'
                ? fake()->dateTimeBetween('-1 year', 'now')
                : null,
        ];
    }

    public function newMessage(): static
    {
        return $this->state(fn (): array => [
            'status' => 'new',
            'handled_by' => null,
            'replied_at' => null,
        ]);
    }

    public function read(): static
    {
        return $this->state(fn (): array => [
            'status' => 'read',
            'handled_by' => User::query()
                ->inRandomOrder()
                ->value('id'),
            'replied_at' => null,
        ]);
    }

    public function replied(): static
    {
        return $this->state(fn (): array => [
            'status' => 'replied',
            'handled_by' => User::query()
                ->inRandomOrder()
                ->value('id'),
            'replied_at' => fake()
                ->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (): array => [
            'status' => 'archived',
            'handled_by' => User::query()
                ->inRandomOrder()
                ->value('id'),
            'replied_at' => fake()
                ->optional()
                ->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    public function withoutPhone(): static
    {
        return $this->state(fn (): array => [
            'phone' => null,
        ]);
    }

    public function withoutSubject(): static
    {
        return $this->state(fn (): array => [
            'subject' => null,
        ]);
    }
}