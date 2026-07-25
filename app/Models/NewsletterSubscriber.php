<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'email',
    'full_name',
    'is_active',
    'subscribed_at',
    'unsubscribed_at',
])]
class NewsletterSubscriber extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'subscribed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (NewsletterSubscriber $subscriber) {
            $subscriber->uuid ??= (string) Str::uuid();
            $subscriber->subscribed_at ??= now();
        });
    }

    // Helpers

    public function unsubscribe(): void
    {
        $this->update([
            'is_active' => false,
            'unsubscribed_at' => now(),
        ]);
    }
}
