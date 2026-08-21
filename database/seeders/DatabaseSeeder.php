<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            AuthorSeeder::class,
            AuthorApplicationSeeder::class,
            CategorySeeder::class,
            TagSeeder::class,
            ArticleSeeder::class,
            PageSeeder::class,
            CommentSeeder::class,
            ContactMessageSeeder::class,
            NewsletterSubscriberSeeder::class,
            MenuSeeder::class,
            MenuItemSeeder::class,
            MediaSeeder::class,
            SettingSeeder::class,
            AdvertisementSeeder::class,
        ]);
    }
}
