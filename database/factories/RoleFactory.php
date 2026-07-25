<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
{
    $roles = [
        'Admin',
        'Editor',
        'Author',
        'Moderator',
        'User',
    ];

    $name = fake()->unique()->randomElement($roles);

    return [
        'uuid' => (string) Str::uuid(),
        'name' => $name,
        'slug' => Str::slug($name),
        'description' => fake()->optional()->sentence(),
        'status' => 'active',
    ];
}
}
