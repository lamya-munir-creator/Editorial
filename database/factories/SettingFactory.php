<?php

namespace Database\Factories;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Setting>
 */
class SettingFactory extends Factory
{
    protected $model = Setting::class;

    public function definition(): array
    {
        $valueType = fake()->randomElement([
            'string',
            'integer',
            'boolean',
            'json',
        ]);

        return [
            'setting_key' => fake()->unique()->randomElement([
                'site_name',
                'site_description',
                'site_email',
                'site_phone',
                'site_logo',
                'default_language',
                'default_timezone',
                'maintenance_mode',
                'posts_per_page',
                'allow_comments',
            ]) . '_' . Str::lower(Str::random(5)),

            'setting_value' => $this->generateValue($valueType),

            'group_name' => fake()->randomElement([
                'general',
                'appearance',
                'contact',
                'content',
                'seo',
                'security',
            ]),

            'value_type' => $valueType,

            'is_public' => fake()->boolean(40),

            'updated_by' => fake()->optional(0.5)->passthrough(
                User::query()
                    ->inRandomOrder()
                    ->value('id')
            ),
        ];
    }

    private function generateValue(string $valueType): ?string
    {
        return match ($valueType) {
            'integer' => (string) fake()->numberBetween(1, 100),
            'boolean' => fake()->boolean() ? '1' : '0',
            'json' => json_encode([
                'enabled' => fake()->boolean(),
                'label' => fake()->words(2, true),
            ]),
            default => fake()->sentence(),
        };
    }

    public function public(): static
    {
        return $this->state(fn (): array => [
            'is_public' => true,
        ]);
    }

    public function private(): static
    {
        return $this->state(fn (): array => [
            'is_public' => false,
        ]);
    }

    public function general(): static
    {
        return $this->state(fn (): array => [
            'group_name' => 'general',
        ]);
    }

    public function seo(): static
    {
        return $this->state(fn (): array => [
            'group_name' => 'seo',
        ]);
    }

    public function boolean(): static
    {
        return $this->state(fn (): array => [
            'value_type' => 'boolean',
            'setting_value' => fake()->boolean() ? '1' : '0',
        ]);
    }

    public function integer(): static
    {
        return $this->state(fn (): array => [
            'value_type' => 'integer',
            'setting_value' => (string) fake()->numberBetween(1, 100),
        ]);
    }

    public function json(): static
    {
        return $this->state(fn (): array => [
            'value_type' => 'json',
            'setting_value' => json_encode([
                'enabled' => true,
                'options' => fake()->words(3),
            ]),
        ]);
    }
}