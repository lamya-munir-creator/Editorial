<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(
            fake()->numberBetween(1, 3),
            true
        );

        return [
            'uuid' => (string) Str::uuid(),

            // نخليه null افتراضيًا حتى ما يعمل تسلسل لا نهائي
            'parent_id' => null,

            // يختار صورة موجودة إن وُجدت
            'image_id' => fake()->optional(0.6)->passthrough(
                Media::query()
                    ->where('type', 'image')
                    ->inRandomOrder()
                    ->value('id')
            ),

            'name' => ucfirst($name),

            'slug' => Str::slug($name) . '-' . Str::lower(Str::random(6)),

            'description' => fake()->optional()->paragraph(),

            'meta_title' => fake()->optional()->sentence(6),

            'meta_description' => fake()->optional()->text(300),

            'sort_order' => fake()->numberBetween(0, 100),

            'status' => fake()->randomElement([
                'active',
                'inactive',
            ]),

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

    public function withParent(?Category $parent = null): static
    {
        return $this->state(fn (): array => [
            'parent_id' => $parent?->id
                ?? Category::query()->inRandomOrder()->value('id'),
        ]);
    }

    public function withoutParent(): static
    {
        return $this->state(fn (): array => [
            'parent_id' => null,
        ]);
    }

    public function withoutImage(): static
    {
        return $this->state(fn (): array => [
            'image_id' => null,
        ]);
    }
}