<?php

namespace Database\Factories;

use App\Models\Author;
use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Author>
 */
class AuthorFactory extends Factory
{
    protected $model = Author::class;

    public function definition(): array
    {
        $displayName = fake()->name();

        return [
            'uuid' => (string) Str::uuid(),

            'user_id' => fake()->optional(0.8)->passthrough(
                User::query()->inRandomOrder()->value('id')
            ),

            'avatar_id' => fake()->optional(0.6)->passthrough(
                Media::query()
                    ->where('type', 'image')
                    ->inRandomOrder()
                    ->value('id')
            ),

            'display_name' => $displayName,

            'slug' => Str::slug($displayName) . '-' . Str::lower(Str::random(6)),

            'biography' => fake()->optional()->paragraph(),

            'job_title' => fake()->optional()->jobTitle(),

            'website' => fake()->optional()->url(),
            'facebook' => fake()->optional()->url(),
            'twitter' => fake()->optional()->url(),
            'linkedin' => fake()->optional()->url(),
            'instagram' => fake()->optional()->url(),
            'youtube' => fake()->optional()->url(),

            'gender' => fake()->randomElement([
                'male',
                'female',
                'other',
            ]),

            'status' => fake()->randomElement([
                'active',
                'inactive',
            ]),

            'created_by' => User::query()->inRandomOrder()->value('id'),

            'updated_by' => fake()->optional(0.5)->passthrough(
                User::query()->inRandomOrder()->value('id')
            ),
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

    public function male(): static
    {
        return $this->state(fn (): array => [
            'gender' => 'male',
        ]);
    }

    public function female(): static
    {
        return $this->state(fn (): array => [
            'gender' => 'female',
        ]);
    }

    public function withoutAvatar(): static
    {
        return $this->state(fn (): array => [
            'avatar_id' => null,
        ]);
    }

    public function withoutUser(): static
    {
        return $this->state(fn (): array => [
            'user_id' => null,
        ]);
    }
}