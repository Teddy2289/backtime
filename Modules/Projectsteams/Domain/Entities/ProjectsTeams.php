<?php

namespace Modules\ProjectsTeams\Domain\Entities;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Team\Domain\Entities\Team;

class ProjectsTeams extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'projects';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'team_id',
        'name',
        'description',
        'start_date',
        'end_date',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'active',
    ];

    /**
     * Get the team that owns the project.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Check if the project is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the project is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the project is on hold.
     */
    public function isOnHold(): bool
    {
        return $this->status === 'on_hold';
    }

    /**
     * Check if the project is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Get the project status options.
     */
    public static function getStatusOptions(): array
    {
        return [
            'active' => 'Active',
            'completed' => 'Completed',
            'on_hold' => 'On Hold',
            'cancelled' => 'Cancelled',
        ];
    }
}