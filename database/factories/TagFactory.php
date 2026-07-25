<?php

namespace Database\Factories;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tag>
 */
class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(
            fake()->numberBetween(1, 2),
            true
        );

        return [
            'uuid' => (string) Str::uuid(),

            'name' => ucfirst($name),

            'slug' => Str::slug($name)
                . '-'
                . Str::lower(Str::random(6)),

            'description' => fake()
                ->optional()
                ->sentence(),

            'color' => fake()
                ->optional(0.8)
                ->hexColor(),

            'status' => fake()->randomElement([
                'active',
                'inactive',
            ]),

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

    public function withColor(): static
    {
        return $this->state(fn (): array => [
            'color' => fake()->hexColor(),
        ]);
    }

    public function withoutColor(): static
    {
        return $this->state(fn (): array => [
            'color' => null,
        ]);
    }
}