<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'uuid',
    'name',
    'slug',
    'description',
    'status',
])]
class Role extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'uuid' => 'string',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Role $role) {
            $role->uuid ??= (string) \Illuminate\Support\Str::uuid();
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
