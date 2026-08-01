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
    'article_id',
    'parent_id',
    'user_id',
    'guest_name',
    'guest_email',
    'guest_website',
    'content',
    'ip_address',
    'user_agent',
    'status',
    'approved_by',
    'approved_at',
])]
class Comment extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Comment $comment) {
            $comment->uuid ??= (string) Str::uuid();
        });
    }

    // Scopes

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    // Accessors

    public function getAuthorNameAttribute(): ?string
    {
        return $this->user?->full_name ?? $this->guest_name;
    }

    // Relationships

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
