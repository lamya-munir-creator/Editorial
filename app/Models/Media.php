<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'uploaded_by',
    'file_name',
    'original_name',
    'disk',
    'path',
    'mime_type',
    'extension',
    'file_size',
    'width',
    'height',
    'duration',
    'alt_text',
    'caption',
    'type',
    'visibility',
    'created_by',
    'updated_by',
])]
class Media extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'duration' => 'integer',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Media $media) {
            $media->uuid ??= (string) Str::uuid();
        });
    }

    // Accessors

    public function getUrlAttribute(): string
    {
        return \Illuminate\Support\Facades\Storage::disk($this->disk)->url($this->path);
    }

    // Relationships

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
