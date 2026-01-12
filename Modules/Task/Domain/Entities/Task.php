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
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;


class Task extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_BACKLOG = 'backlog';
    public const STATUS_TODO = 'todo';
    public const STATUS_DOING = 'doing';
    public const STATUS_DONE = 'done';
    public const STATUS_IN_PROGRESS = 'in_progress';

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

    public static function getValidStatuses(): array
    {
        return [
            self::STATUS_BACKLOG,
            self::STATUS_TODO,
            self::STATUS_DOING,
            self::STATUS_DONE,
        ];
    }

    // Scope pour vérifier les statuts
    public function scopeWithValidStatus($query)
    {
        return $query->whereIn('status', self::getValidStatuses());
    }

    protected $casts = [
        'status' => 'string',
        'start_date' => 'date',
        'due_date' => 'date',
        'start_date' => 'date',
        'due_date' => 'date',
        'tags' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
    protected $appends = ['progress', 'is_overdue', 'total_worked_time'];

    /* -------------------------------------------------------------------------- */
    /* Relations                                  */
    /* -------------------------------------------------------------------------- */

    /**
     * Relation avec les journaux de temps
     */
    public function timeLogs(): HasMany
    {
        // Les noms de clés étrangères par défaut sont généralement 'task_id' (si Task est le nom du modèle)
        return $this->hasMany(TaskTimeLog::class);
    }

    /**
     * Relation avec les fichiers
     */
    public function files(): HasMany
    {
        // Utilisation de la relation par défaut si possible
        return $this->hasMany(TaskFile::class)
            ->with('uploader:id,name,email,avatar');
    }

    /**
     * Relation avec les commentaires
     */
    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    /**
     * Relation avec l'utilisateur assigné
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Relation avec le projet
     */
    public function project(): BelongsTo
    {

        return $this->belongsTo(
            ProjectsTeams::class,
            'project_id',
            'id'
        );
    }

    /**
     * Relation avec l'équipe via le projet
     */
    public function teamThroughProject(): HasOneThrough
    {
        // Logique correcte pour HasOneThrough : Task -> ProjectsTeams -> Team
        return $this->hasOneThrough(
            Team::class,
            ProjectsTeams::class,
            'id',
            'id',
            'project_id',
            'team_id'
        );
    }

    /* -------------------------------------------------------------------------- */
    /* Accesseurs                                */
    /* -------------------------------------------------------------------------- */

    /**
     * Calculer le progrès de la tâche
     */
    public function getProgressAttribute(): int
    {
        return match ($this->status) {
            self::STATUS_DONE => 100,
            self::STATUS_DOING => 50,
            default => 0,
        };
    }

    /**
     * Vérifier si la tâche est en retard
     */
    public function getIsOverdueAttribute(): bool
    {
        // Grâce à 'protected $casts = ['due_date' => 'date'];', $this->due_date est soit Carbon, soit null.
        if (!$this->due_date) {
            return false;
        }

        if ($this->status === self::STATUS_DONE) {
            return false;
        }



        return $this->due_date->isPast();
    }

    /**
     * Calculer le temps total travaillé (en minutes)
     */
    public function getTotalWorkedTimeAttribute(): int
    {
        // La méthode `sum` sur une collection retourne null si la collection est vide, d'où le `?? 0`.
        return $this->timeLogs->sum('duration') ?? 0;
    }



    /* -------------------------------------------------------------------------- */
    /* Scopes                                   */
    /* -------------------------------------------------------------------------- */

    // J'ai laissé les scopes tels quels, ils sont corrects.

    public function scopeOverdue($query)
    {
        return $query->whereNotNull('due_date')
            ->where('due_date', '<', Carbon::now())
            ->where('due_date', '<', Carbon::now())
            ->where('status', '!=', self::STATUS_DONE);
    }

    /* -------------------------------------------------------------------------- */
    /* Méthodes Métier                               */
    /* -------------------------------------------------------------------------- */

    /**
     * Récupérer les utilisateurs assignables pour cette tâche
     */
    public function getAssignableUsers()
    {
        // Vérifie la relation `project` et la relation `team` du projet.
        // Si ProjectsTeams a une relation `team` et cette `team` a une relation `members`.
        return $this->project?->team?->members ?? collect();
    }

    /**
     * Vérifier si un utilisateur peut être assigné à cette tâche
     */
    public function canAssignUser($userId): bool
    {
        // Utilisation de l'opérateur nullsafe `?->` pour plus de sécurité
        return $this->project?->isUserInTeam($userId) ?? false;
    }

    /**
     * Scope pour les tâches à venir
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('start_date', '>', now())
            ->orWhere(function ($q) {
                $q->whereNull('start_date')
                    ->where('due_date', '>', now());
            })
            ->where('status', '!=', 'done');
    }

    /**
     * Scope pour les tâches planifiées
     */
    public function scopeScheduled(Builder $query): Builder
    {
        return $query->whereNotNull('start_date');
    }

    /**
     * Scope pour les tâches non planifiées
     */
    public function scopeUnscheduled(Builder $query): Builder
    {
        return $query->whereNull('start_date');
    }



    /**
     * Vérifie si la tâche est à venir
     */
    public function isUpcoming(): bool
    {
        if ($this->start_date) {
            return $this->start_date->isFuture() && $this->status !== 'done';
        }

        if ($this->due_date) {
            return $this->due_date->isFuture() && $this->status !== 'done';
        }

        return false;
    }

    /**
     * Vérifie si la tâche est planifiée
     */
    public function isScheduled(): bool
    {
        return !is_null($this->start_date);
    }
}
