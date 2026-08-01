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
    'title',
    'destination_url',
    'image_id',
    'position',
    'display_order',
    'start_date',
    'end_date',
    'is_internal',
    'status',
    'created_by',
    'updated_by',
])]
class Advertisement extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'is_internal' => 'boolean',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Advertisement $ad) {
            $ad->uuid ??= (string) Str::uuid();
        });
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('start_date')->orWhere('start_date', '<=', now()))
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()));
    }

    public function scopeForPosition($query, string $position)
    {
        return $query->where('position', $position)->orderBy('display_order');
    }

    // Relationships

    public function image(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'image_id');
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
