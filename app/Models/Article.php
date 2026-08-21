<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'author_id',
    'category_id',
    'featured_image_id',
    'title',
    'slug',
    'excerpt',
    'content',
    'meta_title',
    'meta_description',
    'reading_time',
    'is_featured',
    'allow_comments',
    'published_at',
    'status',
    'created_by',
    'updated_by',
    'submitted_for_review_by',
    'submitted_for_review_at',
    'reviewed_by',
    'reviewed_at',
])]
class Article extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'reading_time' => 'integer',
            'views_count' => 'integer',
            'is_featured' => 'boolean',
            'allow_comments' => 'boolean',
            'published_at' => 'datetime',
            'submitted_for_review_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Article $article) {
            $article->uuid ??= (string) Str::uuid();
        });
    }

    // Scopes

    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    // Scope لإخفاء مقالات المستخدمين أو الكتّاب المعطلين
    public function scopeWithActiveAuthorOrCreator($query)
    {
        return $query->where(function($q) {
            $q->whereNull('created_by')
              ->orWhereHas('creator', function($creatorQuery) {
                  $creatorQuery->where('status', 'active');
              });
        })->where(function($q2) {
            $q2->whereNull('author_id')
               ->orWhereHas('author', function($authorQuery) {
                   $authorQuery->where('status', 'active');
               });
        });
    }

    // Relationships

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function featuredImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_image_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'article_tag')
            ->withPivot('created_at');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
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

    public function submittedForReviewBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_for_review_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}