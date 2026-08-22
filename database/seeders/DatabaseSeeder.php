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

    // الصور أولًا لأن Authors و Articles يعتمدون عليها
    MediaSeeder::class,

    CategorySeeder::class,
    TagSeeder::class,

    AuthorSeeder::class,
    AuthorApplicationSeeder::class,

    ArticleSeeder::class,
    CommentSeeder::class,

    PageSeeder::class,

    ContactMessageSeeder::class,
    NewsletterSubscriberSeeder::class,

    MenuSeeder::class,
    MenuItemSeeder::class,

    SettingSeeder::class,
    AdvertisementSeeder::class,
]);
    }
}
