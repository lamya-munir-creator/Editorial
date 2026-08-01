<?php

namespace Database\Factories;

use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
{
    protected $model = Media::class;

    public function definition(): array
    {
        $type = fake()->randomElement([
            'image',
            'video',
            'audio',
            'document',
        ]);

        $file = $this->fileData($type);

        return [
            // الـ Media Model ينشئ UUID تلقائيًا،
            // لكن وجوده هنا أيضًا صحيح للـFactory.
            'uuid' => (string) Str::uuid(),

            // ينشئ مستخدمًا تلقائيًا عند استخدام create()
            'uploaded_by' => User::factory(),

            'file_name' => $file['file_name'],
            'original_name' => $file['original_name'],
            'disk' => 'public',
            'path' => $file['path'],
            'mime_type' => $file['mime_type'],
            'extension' => $file['extension'],
            'file_size' => fake()->numberBetween(10_000, 10_000_000),

            'width' => $type === 'image'
                ? fake()->numberBetween(300, 2500)
                : null,

            'height' => $type === 'image'
                ? fake()->numberBetween(300, 2500)
                : null,

            'duration' => in_array($type, ['video', 'audio'], true)
                ? fake()->numberBetween(10, 3600)
                : null,

            'alt_text' => $type === 'image'
                ? fake()->optional()->sentence(4)
                : null,

            'caption' => fake()->optional()->sentence(),

            'type' => $type,
            'visibility' => fake()->randomElement([
                'public',
                'private',
            ]),

            'created_by' => null,
            'updated_by' => null,
        ];
    }

    private function fileData(string $type): array
    {
        $files = [
            'image' => [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'webp' => 'image/webp',
            ],

            'video' => [
                'mp4' => 'video/mp4',
                'mov' => 'video/quicktime',
                'webm' => 'video/webm',
            ],

            'audio' => [
                'mp3' => 'audio/mpeg',
                'wav' => 'audio/wav',
                'ogg' => 'audio/ogg',
            ],

            'document' => [
                'pdf' => 'application/pdf',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
        ];

        $extension = fake()->randomElement(
            array_keys($files[$type])
        );

        $fileName = Str::uuid() . '.' . $extension;

        return [
            'file_name' => $fileName,
            'original_name' => fake()->slug(3) . '.' . $extension,
            'path' => "media/{$type}/{$fileName}",
            'mime_type' => $files[$type][$extension],
            'extension' => $extension,
        ];
    }

    public function image(): static
    {
        return $this->state(function (): array {
            $file = $this->fileData('image');

            return [
                ...$file,
                'type' => 'image',
                'width' => fake()->numberBetween(300, 2500),
                'height' => fake()->numberBetween(300, 2500),
                'duration' => null,
                'alt_text' => fake()->sentence(4),
            ];
        });
    }

    public function video(): static
    {
        return $this->state(function (): array {
            $file = $this->fileData('video');

            return [
                ...$file,
                'type' => 'video',
                'width' => null,
                'height' => null,
                'duration' => fake()->numberBetween(10, 3600),
                'alt_text' => null,
            ];
        });
    }

    public function audio(): static
    {
        return $this->state(function (): array {
            $file = $this->fileData('audio');

            return [
                ...$file,
                'type' => 'audio',
                'width' => null,
                'height' => null,
                'duration' => fake()->numberBetween(10, 3600),
                'alt_text' => null,
            ];
        });
    }

    public function document(): static
    {
        return $this->state(function (): array {
            $file = $this->fileData('document');

            return [
                ...$file,
                'type' => 'document',
                'width' => null,
                'height' => null,
                'duration' => null,
                'alt_text' => null,
            ];
        });
    }

    public function public(): static
    {
        return $this->state(fn (): array => [
            'visibility' => 'public',
        ]);
    }

    public function private(): static
    {
        return $this->state(fn (): array => [
            'visibility' => 'private',
        ]);
    }
}