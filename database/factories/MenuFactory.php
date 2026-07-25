<?php

namespace Database\Factories;

use App\Models\Menu;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Menu>
 */
class MenuFactory extends Factory
{
    protected $model = Menu::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),

            'name' => fake()->randomElement([
                'القائمة الرئيسية',
                'روابط التذييل',
                'القائمة الجانبية',
                'روابط سريعة',
                'قائمة الصفحات',
            ]) . ' ' . Str::lower(Str::random(4)),

            'location' => fake()->randomElement([
                'header',
                'footer',
                'sidebar',
            ]),

            'status' => fake()->randomElement([
                'active',
                'inactive',
            ]),

            'created_by' => fake()->optional(0.8)->passthrough(
                User::query()
                    ->inRandomOrder()
                    ->value('id')
            ),

            'updated_by' => fake()->optional(0.4)->passthrough(
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

    public function header(): static
    {
        return $this->state(fn (): array => [
            'location' => 'header',
        ]);
    }

    public function footer(): static
    {
        return $this->state(fn (): array => [
            'location' => 'footer',
        ]);
    }

    public function sidebar(): static
    {
        return $this->state(fn (): array => [
            'location' => 'sidebar',
        ]);
    }
}