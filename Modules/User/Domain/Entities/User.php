<?php

namespace Modules\User\Domain\Entities;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Modules\User\Domain\Enums\UserRole;
use Modules\Team\Domain\Entities\Team;
use Modules\ProjectsTeams\Domain\Entities\ProjectsTeams;
use Modules\Task\Domain\Entities\Task;
use Modules\TaskComment\Domain\Entities\TaskComment;
use Modules\TaskTimeLog\Domain\Entities\TaskTimeLog;
use App\Models\WorkTime;

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

    /* --------------------------------------------------------------------------
     * RELATIONS MANQUANTES
     * -------------------------------------------------------------------------- */

    /**
     * Relation avec les équipes (many-to-many)
     */
    public function teams()
    {
        return $this->belongsToMany(Team::class, 'team_members', 'user_id', 'team_id')
            ->withTimestamps()
            ->withPivot('created_at');
    }

    /**
     * Relation avec les tâches assignées
     */
    public function tasks()
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    /**
     * Relation avec les tâches créées
     */
    public function createdTasks()
    {
        return $this->hasMany(Task::class, 'created_by');
    }

    /**
     * Relation avec les journaux de temps
     */
    public function timeLogs()
    {
        return $this->hasMany(TaskTimeLog::class, 'user_id');
    }

    /**
     * Relation avec les commentaires de tâches
     */
    public function taskComments()
    {
        return $this->hasMany(TaskComment::class, 'user_id');
    }

    /**
     * Relation avec les temps de travail
     */
    public function workTimes()
    {
        return $this->hasMany(WorkTime::class, 'user_id');
    }

    /**
     * Relation avec les projets créés (si vous avez un champ owner_id dans ProjectsTeams)
     */
    public function ownedProjects()
    {
        return $this->hasMany(ProjectsTeams::class, 'owner_id');
    }

    /**
     * Relation avec les équipes possédées
     */
    public function ownedTeams()
    {
        return $this->hasMany(Team::class, 'owner_id');
    }

    /**
     * Tâches actuellement en cours (avec des time logs actifs)
     */
    public function activeTasks()
    {
        return $this->tasks()
            ->whereHas('timeLogs', function ($query) {
                $query->whereNull('end_time');
            })
            ->with(['timeLogs' => function ($query) {
                $query->whereNull('end_time');
            }]);
    }

    /**
     * Tâches en retard assignées à l'utilisateur
     */
    public function overdueTasks()
    {
        return $this->tasks()
            ->where('status', '!=', Task::STATUS_DONE)
            ->whereNotNull('due_date')
            ->where('due_date', '<', now());
    }

    /**
     * Tâches en cours (status 'doing') assignées à l'utilisateur
     */
    public function doingTasks()
    {
        return $this->tasks()->where('status', Task::STATUS_DOING);
    }

    /**
     * Récupérer les projets auxquels l'utilisateur participe via ses équipes
     */
    public function teamProjects()
    {
        return ProjectsTeams::whereHas('team', function ($query) {
            $query->whereHas('members', function ($query) {
                $query->where('user_id', $this->id);
            });
        });
    }

    /**
     * Vérifier si l'utilisateur est membre d'une équipe spécifique
     */
    public function isMemberOfTeam($teamId): bool
    {
        return $this->teams()->where('teams.id', $teamId)->exists();
    }

    /**
     * Vérifier si l'utilisateur est propriétaire d'une équipe spécifique
     */
    public function isOwnerOfTeam($teamId): bool
    {
        return $this->ownedTeams()->where('id', $teamId)->exists();
    }

    /**
     * Vérifier si l'utilisateur peut accéder à un projet
     */
    public function canAccessProject($projectId): bool
    {
        // Si l'utilisateur est admin, il peut accéder à tous les projets
        if ($this->isAdmin()) {
            return true;
        }

        // Vérifier si le projet appartient à une équipe dont l'utilisateur est membre
        return ProjectsTeams::where('id', $projectId)
            ->whereHas('team', function ($query) {
                $query->whereHas('members', function ($query) {
                    $query->where('user_id', $this->id);
                });
            })
            ->exists();
    }

    /**
     * Récupérer le temps total travaillé aujourd'hui
     */
    public function getTodayWorkTimeAttribute()
    {
        return $this->workTimes()
            ->whereDate('work_date', now()->toDateString())
            ->first();
    }

    /**
     * Récupérer le temps total travaillé cette semaine
     */
    public function getWeeklyWorkTimeAttribute()
    {
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();

        return $this->workTimes()
            ->whereBetween('work_date', [$startOfWeek, $endOfWeek])
            ->get();
    }

    /**
     * Calculer le temps total travaillé ce mois-ci
     */
    public function getMonthlyWorkHoursAttribute(): float
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $totalSeconds = $this->workTimes()
            ->whereBetween('work_date', [$startOfMonth, $endOfMonth])
            ->sum('net_seconds');

        return round($totalSeconds / 3600, 2);
    }

    /**
     * Récupérer les statistiques de productivité
     */
    public function getProductivityStatsAttribute(): array
    {
        $todayWork = $this->today_work_time;
        $weeklyWork = $this->weekly_work_time;

        $totalTasks = $this->tasks()->count();
        $completedTasks = $this->tasks()->where('status', Task::STATUS_DONE)->count();
        $overdueTasks = $this->overdueTasks()->count();

        return [
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'completion_rate' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 2) : 0,
            'overdue_tasks' => $overdueTasks,
            'overdue_rate' => $totalTasks > 0 ? round(($overdueTasks / $totalTasks) * 100, 2) : 0,
            'today_hours' => $todayWork ? $todayWork->net_hours : 0,
            'weekly_hours' => $weeklyWork->sum('net_hours'),
            'monthly_hours' => $this->monthly_work_hours,
        ];
    }

    /**
     * Scope pour les utilisateurs actifs (avec du travail récent)
     */
    public function scopeActive($query, $days = 7)
    {
        $date = now()->subDays($days);
        
        return $query->whereHas('workTimes', function ($query) use ($date) {
            $query->where('work_date', '>=', $date);
        })
        ->orWhereHas('timeLogs', function ($query) use ($date) {
            $query->where('created_at', '>=', $date);
        });
    }

    /**
     * Scope pour les utilisateurs d'une équipe spécifique
     */
    public function scopeOfTeam($query, $teamId)
    {
        return $query->whereHas('teams', function ($query) use ($teamId) {
            $query->where('teams.id', $teamId);
        });
    }

    /**
     * Scope pour les utilisateurs avec un rôle spécifique
     */
    public function scopeWithRole($query, $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope pour rechercher des utilisateurs
     */
    public function scopeSearch($query, $searchTerm)
    {
        return $query->where('name', 'like', "%{$searchTerm}%")
            ->orWhere('email', 'like', "%{$searchTerm}%");
    }
}