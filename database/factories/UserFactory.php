<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),

            'role_id' => Role::query()->inRandomOrder()->value('id') ?? 1,

            'first_name' => fake()->firstName(),
            'last_name' => fake()->optional()->lastName(),

            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => fake()->optional(0.8)->dateTimeBetween('-1 year', 'now'),

            'password' => static::$password ??= Hash::make('password'),

            'phone' => fake()->optional()->numerify('#########'),

            'avatar_id' => null,

            'locale' => 'ar',
            'timezone' => fake()->optional()->timezone(),

            'remember_token' => Str::random(10),

            'last_login_at' => fake()
                ->optional()
                ->dateTimeBetween('-6 months', 'now'),

            'status' => fake()->randomElement([
                'active',
                'inactive',
                'suspended',
            ]),

            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (): array => [
            'status' => 'active',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'status' => 'inactive',
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (): array => [
            'status' => 'suspended',
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn (): array => [
            'email_verified_at' => null,
        ]);
    }

    public function withLastLogin(): static
    {
        return $this->state(fn (): array => [
            'last_login_at' => now(),
        ]);
    }
}