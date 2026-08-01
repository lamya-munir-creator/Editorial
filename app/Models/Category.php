<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'parent_id',
    'image_id',
    'name',
    'slug',
    'description',
    'meta_title',
    'meta_description',
    'sort_order',
    'status',
    'created_by',
    'updated_by',
])]
class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Category $category) {
            $category->uuid ??= (string) Str::uuid();
        });
    }

    // Relationships

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'image_id');
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class);
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
