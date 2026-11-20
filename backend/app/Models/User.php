<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'is_staff',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_staff' => 'boolean',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isSuperAdmin(): bool
    {
        $this->loadMissing('roles');

        return $this->roles->contains(fn (Role $role) => $role->is_super);
    }

    public function hasPermission(string $permission): bool
    {
        $this->loadMissing('roles.permissions');

        if ($this->roles->contains(fn (Role $role) => $role->is_super)) {
            return true;
        }

        return $this->roles
            ->flatMap(fn (Role $role) => $role->permissions)
            ->contains(fn (Permission $item) => $item->slug === $permission);
    }

    public function permissionSlugs(): array
    {
        $this->loadMissing('roles.permissions');

        if ($this->isSuperAdmin()) {
            return Permission::query()->orderBy('slug')->pluck('slug')->all();
        }

        return $this->roles
            ->flatMap(fn (Role $role) => $role->permissions)
            ->pluck('slug')
            ->unique()
            ->values()
            ->all();
    }

    public function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'is_staff' => $this->is_staff,
            'email_verified' => $this->hasVerifiedEmail(),
        ];
    }

    public function toStaffArray(): array
    {
        $this->loadMissing('roles');

        return [
            ...$this->toPublicArray(),
            'roles' => $this->roles->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
                'is_super' => $role->is_super,
            ])->values(),
            'permissions' => $this->permissionSlugs(),
            'is_super_admin' => $this->isSuperAdmin(),
        ];
    }
}
