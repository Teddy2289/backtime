<?php

namespace Modules\Team\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Modules\User\Domain\Entities\User;


class Team extends Model
{
    public $incrementing = false;  
    protected $keyType = 'string';  

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (! $model->getKey()) {
                $model->setAttribute($model->getKeyName(), Str::uuid()->toString());
            }
        });
    }
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
        return $this->belongsToMany(User::class, 'team_members')
            ->withTimestamps();
    }

    public function projects()
    {
        return $this->hasMany(\Modules\Project\Domain\Entities\Project::class);
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