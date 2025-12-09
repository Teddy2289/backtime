<?php

namespace Modules\User\Domain\Entities;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Modules\User\Domain\Enums\UserRole;

class User extends Authenticatable implements JWTSubject
{
    use SoftDeletes, HasRoles, Notifiable;
    protected $guard_name = 'api';


    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'role',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        // REMOVE THIS: 'role' => UserRole::class, // CAUSE OF THE ERROR
    ];

    protected $appends = ['avatar_url', 'initials'];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->role, // This will use the getRoleAttribute() accessor
            'name' => $this->name,
            'email' => $this->email,
        ];
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if (!$this->avatar) {
            return null;
        }

        if (filter_var($this->avatar, FILTER_VALIDATE_URL)) {
            return $this->avatar;
        }

        return asset('storage/avatars/' . $this->avatar);
    }

    public function getInitialsAttribute(): string
    {
        $words = explode(' ', trim($this->name));
        $initials = '';

        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper($word[0]);
            }
        }

        return substr($initials, 0, 2);
    }

    protected function getDefaultGuardName(): string
    {
        return 'api';
    }

    /**
     * Get the user's role as UserRole enum
     */
    public function getRoleAttribute($value): ?UserRole
    {
        if ($value === null) {
            return UserRole::USER; // Default value
        }

        if ($value instanceof UserRole) {
            return $value;
        }

        try {
            return UserRole::from($value);
        } catch (\ValueError $e) {
            // If value doesn't exist in enum, return default
            return UserRole::USER;
        }
    }

    /**
     * Set the user's role - store as string value
     */
    public function setRoleAttribute($value): void
    {
        if ($value instanceof UserRole) {
            $this->attributes['role'] = $value->value;
        } elseif (is_string($value) && in_array($value, UserRole::values())) {
            $this->attributes['role'] = $value;
        } else {
            // Default value
            $this->attributes['role'] = UserRole::USER->value;
        }
    }

    /**
     * Check if user has specific role
     */
    public function hasRoleEnum(UserRole $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if user has admin role
     */
    public function isAdmin(): bool
    {
        return $this->hasRoleEnum(UserRole::ADMIN);
    }

    /**
     * Check if user has manager role
     */
    public function isManager(): bool
    {
        return $this->hasRoleEnum(UserRole::MANAGER);
    }

    /**
     * Check if user is regular user
     */
    public function isUser(): bool
    {
        return $this->hasRoleEnum(UserRole::USER);
    }
}
