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
    'full_name',
    'email',
    'phone',
    'subject',
    'message',
    'status',
    'handled_by',
    'replied_at',
])]
class ContactMessage extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'replied_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (ContactMessage $message) {
            $message->uuid ??= (string) Str::uuid();
        });
    }

    // Relationships

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
