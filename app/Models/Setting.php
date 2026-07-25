<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

#[Fillable([
    'setting_key',
    'setting_value',
    'group_name',
    'value_type',
    'is_public',
    'updated_by',
])]
class Setting extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    // Relationships

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Helpers

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever("setting:{$key}", function () use ($key, $default) {
            return static::where('setting_key', $key)->value('setting_value') ?? $default;
        });
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['setting_key' => $key], ['setting_value' => $value]);
        Cache::forget("setting:{$key}");
    }
}
