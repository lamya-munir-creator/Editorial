<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'uuid',
    'name',
    'slug',
    'description',
    'status',
    'guard_name',
])]
class Role extends SpatieRole
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

    public function customUsers(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
