<?php

namespace Modules\Task\Domain\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Modules\ProjectsTeams\Domain\Entities\ProjectsTeams;
use Modules\TaskComment\Domain\Entities\TaskComment;
use Modules\Taskfiles\Domain\Entities\TaskFile;
use Modules\Tasktimelog\Domain\Entities\TaskTimeLog;
use Modules\Team\Domain\Entities\Team;
use Modules\User\Domain\Entities\User;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'title',
        'description',
        'assigned_to',
        'status',
        'priority',
        'start_date',
        'due_date',
        'estimated_time',
        'tags',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'tags' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $appends = ['progress', 'is_overdue'];


    /**
     * Relation avec les journaux de temps
     */
    public function timeLogs(): HasMany
    {
        return $this->hasMany(TaskTimeLog::class, 'task_id');
    }

    /**
     * Relation avec les fichiers
     */
    public function files(): HasMany
    {
        return $this->hasMany(TaskFile::class, 'task_id');
    }

    /**
     * Relation avec les commentaires
     */
    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class, 'task_id');
    }

    /**
     * Calculer le progrès de la tâche
     */
    public function getProgressAttribute(): float
    {
        switch ($this->status) {
            case 'backlog':
                return 0;
            case 'todo':
                return 0;
            case 'doing':
                return 50;
            case 'done':
                return 100;
            default:
                return 0;
        }
    }

    /**
     * Vérifier si la tâche est en retard
     */
    public function getIsOverdueAttribute(): bool
    {
        if (!$this->due_date || $this->status === 'done') {
            return false;
        }

        return $this->due_date->isPast();
    }

    /**
     * Calculer le temps total travaillé (en minutes)
     */
    public function getTotalWorkedTimeAttribute(): int
    {
        return $this->timeLogs->sum('duration') ?? 0;
    }

    /**
     * Récupérer les tags sous forme de tableau
     */
    public function getTagsArrayAttribute(): array
    {
        return $this->tags ?? [];
    }

    /**
     * Scope pour les tâches actives (non supprimées)
     */
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * Scope pour les tâches par statut
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope pour les tâches par priorité
     */
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope pour les tâches assignées à un utilisateur
     */
    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Scope pour les tâches d'un projet
     */
    public function scopeByProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Scope pour les tâches en retard
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->where('status', '!=', 'done')
            ->whereNotNull('due_date');
    }

    /**
     * Scope pour les tâches à venir
     */
    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>', now())
            ->where('start_date', '<=', now()->addDays(7))
            ->whereNotNull('start_date');
    }

    /**
     * Relation avec le projet
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(ProjectsTeams::class, 'project_id');
    }

    /**
     * Relation avec l'équipe via le projet
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    /**
     * Relation avec l'équipe via le projet (alternative)
     */
    public function teamThroughProject(): HasOneThrough
    {
        return $this->hasOneThrough(
            Team::class,
            ProjectsTeams::class,
            'id', // Foreign key on ProjectsTeams table
            'id', // Foreign key on Team table
            'project_id', // Local key on Task table
            'team_id' // Local key on ProjectsTeams table
        );
    }

    /**
     * Relation avec l'utilisateur assigné
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Relation avec les utilisateurs de l'équipe (via projet->équipe)
     */
    public function teamMembers()
    {
        return $this->hasManyThrough(
            User::class,
            ProjectsTeams::class,
            'id',
            'id',
            'project_id',
            'team_id'
        );
    }

    /**
     * Récupérer les utilisateurs assignables pour cette tâche
     */
    public function getAssignableUsers()
    {
        if ($this->project && $this->project->team) {
            return $this->project->team->members;
        }

        return collect();
    }

    /**
     * Vérifier si un utilisateur peut être assigné à cette tâche
     */
    public function canAssignUser($userId): bool
    {
        if (!$this->project) {
            return false;
        }

        return $this->project->isUserInTeam($userId);
    }
}