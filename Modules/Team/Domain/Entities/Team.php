<?php

namespace Modules\Team\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Modules\ProjectsTeams\Domain\Entities\ProjectsTeams;
use Modules\User\Domain\Entities\User;


class Team extends Model
{

    protected $fillable = [
        'name',
        'description',
        'owner_id',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_members', 'team_id', 'user_id')
            ->withTimestamps()
            ->withPivot('created_at');
    }

    public function projects()
    {
        return $this->hasMany(ProjectsTeams::class);
    }

    public function isOwner($userId): bool
    {
        return $this->owner_id == $userId;
    }

    public function isMember($userId): bool
    {
        return $this->members()->where('user_id', $userId)->exists();
    }
}