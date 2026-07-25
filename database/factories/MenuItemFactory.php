<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\Category;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MenuItem>
 */
class MenuItemFactory extends Factory
{
    protected $model = MenuItem::class;

    public function definition(): array
    {
        return [
            'menu_id' => Menu::query()
                ->inRandomOrder()
                ->value('id'),

            'parent_id' => null,

            'page_id' => fake()->optional(0.3)->passthrough(
                Page::query()
                    ->inRandomOrder()
                    ->value('id')
            ),

            'article_id' => fake()->optional(0.3)->passthrough(
                Article::query()
                    ->inRandomOrder()
                    ->value('id')
            ),

            'category_id' => fake()->optional(0.3)->passthrough(
                Category::query()
                    ->inRandomOrder()
                    ->value('id')
            ),

            'title' => fake()->words(
                fake()->numberBetween(1, 3),
                true
            ),

            'url' => fake()->optional(0.4)->url(),

            'target' => fake()->randomElement([
                '_self',
                '_blank',
            ]),

            'icon' => fake()->optional(0.4)->randomElement([
                'home',
                'article',
                'folder',
                'settings',
                'info',
                'mail',
                'user',
            ]),

            'sort_order' => fake()->numberBetween(0, 100),

            'is_active' => fake()->boolean(90),
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }

    public function withParent(MenuItem $parent): static
    {
        return $this->state(fn () => [
            'parent_id' => $parent->id,
        ]);
    }

    public function pageLink(): static
    {
        return $this->state(fn () => [
            'page_id' => Page::query()->inRandomOrder()->value('id'),
            'article_id' => null,
            'category_id' => null,
            'url' => null,
        ]);
    }

    public function articleLink(): static
    {
        return $this->state(fn () => [
            'page_id' => null,
            'article_id' => Article::query()->inRandomOrder()->value('id'),
            'category_id' => null,
            'url' => null,
        ]);
    }

    public function categoryLink(): static
    {
        return $this->state(fn () => [
            'page_id' => null,
            'article_id' => null,
            'category_id' => Category::query()->inRandomOrder()->value('id'),
            'url' => null,
        ]);
    }

    public function customUrl(): static
    {
        return $this->state(fn () => [
            'page_id' => null,
            'article_id' => null,
            'category_id' => null,
            'url' => fake()->url(),
        ]);
    }
}