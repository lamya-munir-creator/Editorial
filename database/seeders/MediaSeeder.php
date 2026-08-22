<?php

namespace Database\Seeders;

use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MediaSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();

        if (!$user) {
            $this->command->error('No user found. Seed users first.');
            return;
        }

        $images = [];

        // Art
        for ($i = 1; $i <= 3; $i++) {
            $images[] = "art-$i.webp";
        }

        // Technology
        for ($i = 1; $i <= 7; $i++) {
            $images[] = "tech-$i.webp";
        }

        // Sports
        for ($i = 1; $i <= 8; $i++) {
            $images[] = "sports-$i.webp";
        }

        // Health
        for ($i = 1; $i <= 5; $i++) {
            $images[] = "health-$i.webp";
        }

        foreach ($images as $fileName) {
            $relativePath = "articles/$fileName";

            Media::updateOrCreate(
                ['path' => $relativePath],
                [
                    'uuid' => (string) Str::uuid(),

                    'uploaded_by' => $user->id,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,

                    'file_name' => $fileName,
                    'original_name' => $fileName,

                    'disk' => 'public',
                    'path' => $relativePath,
                    'webp_path' => $relativePath,

                    'mime_type' => 'image/webp',
                    'extension' => 'webp',

                    'file_size' => 0,
                    'width' => null,
                    'height' => null,
                    'duration' => null,

                    'alt_text' => pathinfo($fileName, PATHINFO_FILENAME),
                    'caption' => null,

                    'type' => 'image',
                    'visibility' => 'public',
                ]
            );
        }
    }
}