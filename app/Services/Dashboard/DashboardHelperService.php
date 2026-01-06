<?php

namespace App\Services\Dashboard;

use Carbon\Carbon;
use App\Models\WorkTime;
use Illuminate\Support\Collection;
use Modules\Team\Domain\Entities\Team;
use Modules\Task\Domain\Entities\Task;
use Modules\User\Domain\Entities\User;
use Modules\ProjectsTeams\Domain\Entities\ProjectsTeams;

class DashboardHelperService
{
    /**
     * Calculate project progress based on tasks
     */
    public function calculateProjectProgress(ProjectsTeams $project): float
    {
        $tasks = $project->tasks;

        if ($tasks->isEmpty()) {
            return 0;
        }

        $totalTasks = $tasks->count();
        $completedTasks = $tasks->where('status', Task::STATUS_DONE)->count();

        return round(($completedTasks / $totalTasks) * 100, 2);
    }

    /**
     * Get task statuses with detailed statistics
     */
    public function getTaskStatuses(Collection $tasks): array
    {
        // Statistiques de base
        $stats = [
            'total' => $tasks->count(),
            'backlog' => $tasks->where('status', Task::STATUS_BACKLOG)->count(),
            'todo' => $tasks->where('status', Task::STATUS_TODO)->count(),
            'doing' => $tasks->where('status', Task::STATUS_DOING)->count(),
            'done' => $tasks->where('status', Task::STATUS_DONE)->count(),
            'overdue' => $tasks->where('is_overdue', true)->count(),
        ];

        // Calcul des pourcentages
        $stats['backlog_percentage'] = $stats['total'] > 0 ?
            round(($stats['backlog'] / $stats['total']) * 100, 2) : 0;

        $stats['todo_percentage'] = $stats['total'] > 0 ?
            round(($stats['todo'] / $stats['total']) * 100, 2) : 0;

        $stats['doing_percentage'] = $stats['total'] > 0 ?
            round(($stats['doing'] / $stats['total']) * 100, 2) : 0;

        $stats['done_percentage'] = $stats['total'] > 0 ?
            round(($stats['done'] / $stats['total']) * 100, 2) : 0;

        $stats['overdue_percentage'] = $stats['total'] > 0 ?
            round(($stats['overdue'] / $stats['total']) * 100, 2) : 0;

        // Tâches par priorité
        $stats['high_priority'] = $tasks->where('priority', 'high')->count();
        $stats['medium_priority'] = $tasks->where('priority', 'medium')->count();
        $stats['low_priority'] = $tasks->where('priority', 'low')->count();

        // Tâches avec dates
        $stats['with_due_date'] = $tasks->whereNotNull('due_date')->count();
        $stats['without_due_date'] = $tasks->whereNull('due_date')->count();

        // Tâches avec estimation de temps
        $stats['with_time_estimate'] = $tasks->whereNotNull('estimated_time')->count();
        $stats['without_time_estimate'] = $tasks->whereNull('estimated_time')->count();

        // Temps total travaillé sur toutes les tâches
        $stats['total_worked_time_minutes'] = $tasks->sum('total_worked_time');
        $stats['total_worked_time_hours'] = round($stats['total_worked_time_minutes'] / 60, 2);

        // Temps moyen par tâche
        $stats['avg_worked_time_per_task'] = $stats['total'] > 0 ?
            round($stats['total_worked_time_minutes'] / $stats['total'], 2) : 0;

        // Tâches récentes (créées dans les 7 derniers jours)
        $weekAgo = Carbon::now()->subDays(7);
        $stats['recent_tasks'] = $tasks->where('created_at', '>=', $weekAgo)->count();
        $stats['recent_percentage'] = $stats['total'] > 0 ?
            round(($stats['recent_tasks'] / $stats['total']) * 100, 2) : 0;

        // Tâches terminées récemment
        $stats['recently_done'] = $tasks->where('status', Task::STATUS_DONE)
            ->where('updated_at', '>=', $weekAgo)
            ->count();

        // Tâches en retard par priorité
        $overdueTasks = $tasks->where('is_overdue', true);
        $stats['overdue_high_priority'] = $overdueTasks->where('priority', 'high')->count();
        $stats['overdue_medium_priority'] = $overdueTasks->where('priority', 'medium')->count();
        $stats['overdue_low_priority'] = $overdueTasks->where('priority', 'low')->count();

        // Distribution des tâches par projet
        $projectDistribution = [];
        foreach ($tasks->groupBy('project_id') as $projectId => $projectTasks) {
            $project = $projectTasks->first()->project ?? null;
            if ($project) {
                $projectDistribution[] = [
                    'project_id' => $projectId,
                    'project_name' => $project->name,
                    'task_count' => $projectTasks->count(),
                    'percentage' => $stats['total'] > 0 ?
                        round(($projectTasks->count() / $stats['total']) * 100, 2) : 0,
                ];
            }
        }

        // Trier par nombre de tâches décroissant
        usort($projectDistribution, function ($a, $b) {
            return $b['task_count'] - $a['task_count'];
        });

        $stats['project_distribution'] = array_slice($projectDistribution, 0, 5); // Top 5

        // Évolution des statuts sur les 30 derniers jours
        $statusEvolution = $this->getTaskStatusEvolution($tasks);
        $stats['status_evolution'] = $statusEvolution;

        // Prévisions de fin (basées sur la progression moyenne)
        $activeTasks = $tasks->whereIn('status', [Task::STATUS_TODO, Task::STATUS_DOING]);
        $stats['active_tasks_count'] = $activeTasks->count();
        $stats['avg_progress'] = $activeTasks->count() > 0 ?
            round($activeTasks->avg('progress'), 2) : 0;

        // Taux de complétion moyen par jour (basé sur les tâches terminées récemment)
        $recentlyCompletedTasks = $tasks->where('status', Task::STATUS_DONE)
            ->where('updated_at', '>=', Carbon::now()->subDays(30));

        $stats['avg_completion_per_day'] = $recentlyCompletedTasks->count() > 0 ?
            round($recentlyCompletedTasks->count() / 30, 2) : 0;

        // Temps estimé total vs temps travaillé total
        $stats['total_estimated_time'] = $tasks->sum('estimated_time');
        $stats['time_efficiency'] = $stats['total_estimated_time'] > 0 ?
            round(($stats['total_worked_time_minutes'] / $stats['total_estimated_time']) * 100, 2) : 0;

        return $stats;
    }

    /**
     * Get task status evolution over time
     */
    public function getTaskStatusEvolution(Collection $tasks): array
    {
        $evolution = [];
        $today = Carbon::today();

        for ($i = 29; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            $dateStr = $date->format('Y-m-d');

            // Tâches créées avant ou à cette date
            $tasksAtDate = $tasks->filter(function ($task) use ($date) {
                return $task->created_at->lte($date->endOfDay());
            });

            $evolution[$dateStr] = [
                'date' => $dateStr,
                'day_name' => $date->locale('fr')->dayName,
                'total' => $tasksAtDate->count(),
                'backlog' => $tasksAtDate->where('status', Task::STATUS_BACKLOG)->count(),
                'todo' => $tasksAtDate->where('status', Task::STATUS_TODO)->count(),
                'doing' => $tasksAtDate->where('status', Task::STATUS_DOING)->count(),
                'done' => $tasksAtDate->where('status', Task::STATUS_DONE)->count(),
                'completion_rate' => $tasksAtDate->count() > 0 ?
                    round(($tasksAtDate->where('status', Task::STATUS_DONE)->count() / $tasksAtDate->count()) * 100, 2) : 0,
            ];
        }

        return $evolution;
    }

    /**
     * Get all tasks for admin (no restrictions)
     */
    public function getAllTasksForAdmin($startDate = null, $endDate = null)
    {
        $query = Task::with(['project', 'assignedUser', 'timeLogs']);

        // Ajouter la condition de période si spécifiée
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        return $query->get();
    }

    /**
     * Get user's tasks based on teams/projects
     */
    public function getUserTasks(User $user, $startDate = null, $endDate = null)
    {
        // 1. Get user's teams
        $userTeams = $user->teams()->get();

        // Si l'utilisateur n'est pas dans une équipe, on retourne seulement les tâches assignées
        if ($userTeams->isEmpty()) {
            $query = Task::where('assigned_to', $user->id)
                ->orWhereHas('timeLogs', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
        } else {
            // 2. Get all projects from user's teams
            $projectIds = $userTeams->flatMap(function ($team) {
                return $team->projects()->pluck('id');
            })->unique();

            // 3. Get tasks assigned to user OR with time logs from user
            $query = Task::whereIn('project_id', $projectIds)
                ->where(function ($query) use ($user) {
                    $query->where('assigned_to', $user->id)
                        ->orWhereHas('timeLogs', function ($q) use ($user) {
                            $q->where('user_id', $user->id);
                        });
                });
        }

        // Ajouter les relations
        $query->with(['project', 'assignedUser', 'timeLogs']);

        // Ajouter la condition de période si spécifiée
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        return $query->get();
    }

    /**
     * Calculate previous period stats for comparison
     */
    public function calculatePreviousPeriodStats(User $user, $timeframe, $currentStartDate, $currentEndDate)
    {
        $previousStartDate = null;
        $previousEndDate = null;

        // Calculer les dates de la période précédente
        switch ($timeframe) {
            case 'day':
                $previousStartDate = $currentStartDate->copy()->subDay();
                $previousEndDate = $currentEndDate->copy()->subDay();
                break;
            case 'week':
                $previousStartDate = $currentStartDate->copy()->subDays(7);
                $previousEndDate = $currentEndDate->copy()->subDays(7);
                break;
            case 'month':
                $previousStartDate = $currentStartDate->copy()->subDays(30);
                $previousEndDate = $currentEndDate->copy()->subDays(30);
                break;
            case 'year':
                $previousStartDate = $currentStartDate->copy()->subYear();
                $previousEndDate = $currentEndDate->copy()->subYear();
                break;
        }

        // Récupérer les tâches de la période précédente
        if ($user->isAdmin()) {
            $previousTasks = $this->getAllTasksForAdmin($previousStartDate, $previousEndDate);
        } else {
            $previousTasks = $this->getUserTasks($user, $previousStartDate, $previousEndDate);
        }

        // Calculer les statistiques de la période précédente
        return $this->getTaskStatuses($previousTasks);
    }

    /**
     * Calculate changes between current and previous stats
     */
    public function calculateChanges($currentStats, $previousStats): array
    {
        return [
            'total_change' => $this->calculateChange($currentStats['total'], $previousStats['total']),
            'backlog_change' => $this->calculateChange($currentStats['backlog'], $previousStats['backlog']),
            'todo_change' => $this->calculateChange($currentStats['todo'], $previousStats['todo']),
            'doing_change' => $this->calculateChange($currentStats['doing'], $previousStats['doing']),
            'done_change' => $this->calculateChange($currentStats['done'], $previousStats['done']),
            'overdue_change' => $this->calculateChange($currentStats['overdue'], $previousStats['overdue']),
            'completion_rate_change' => $this->calculateChange(
                $currentStats['done_percentage'],
                $previousStats['done_percentage']
            ),
            'productivity_change' => $this->calculateChange(
                $currentStats['total_worked_time_minutes'],
                $previousStats['total_worked_time_minutes']
            ),
        ];
    }

    /**
     * Calculate percentage change between two values
     */
    public function calculateChange($current, $previous): array
    {
        if ($previous == 0) {
            return [
                'value' => $current,
                'percentage' => 0,
                'trend' => $current > 0 ? 'up' : ($current < 0 ? 'down' : 'neutral'),
                'is_positive' => $current > 0,
            ];
        }

        $change = $current - $previous;
        $percentage = round(($change / abs($previous)) * 100, 2);

        return [
            'value' => $change,
            'percentage' => $percentage,
            'trend' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'neutral'),
            'is_positive' => $change > 0,
        ];
    }

    /**
     * Build query for admin (all tasks)
     */
    public function buildAdminTasksQuery(array $filters)
    {
        $query = Task::with([
            'project',
            'project.team',
            'assignedUser',
            'timeLogs',
            'comments',
            'files'
        ]);

        // Appliquer les filtres
        $this->applyFiltersToQuery($query, $filters);

        return $query;
    }

    /**
     * Build query for regular user (only team/project tasks)
     */
    public function buildUserTasksQuery(User $user, array $filters)
    {
        // 1. Get user's teams
        $userTeams = $user->teams()->get();

        // 2. Get project IDs from user's teams
        if ($userTeams->isEmpty()) {
            // Si pas d'équipe, seulement les tâches assignées
            $query = Task::where('assigned_to', $user->id)
                ->orWhereHas('timeLogs', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
        } else {
            $projectIds = $userTeams->flatMap(function ($team) {
                return $team->projects()->pluck('id');
            })->unique();

            $query = Task::whereIn('project_id', $projectIds)
                ->where(function ($query) use ($user) {
                    $query->where('assigned_to', $user->id)
                        ->orWhereHas('timeLogs', function ($q) use ($user) {
                            $q->where('user_id', $user->id);
                        });
                });
        }

        // Ajouter les relations
        $query->with([
            'project',
            'project.team',
            'assignedUser',
            'timeLogs',
            'comments',
            'files'
        ]);

        // Appliquer les filtres
        $this->applyFiltersToQuery($query, $filters);

        return $query;
    }

    /**
     * Apply filters to query
     */
    public function applyFiltersToQuery($query, array $filters)
    {
        // Filtre par statut
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'overdue') {
                $query->where('due_date', '<', Carbon::now())
                    ->where('status', '!=', Task::STATUS_DONE);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        // Filtre par priorité
        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        // Filtre par projet
        if (!empty($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }

        // Filtre par assigné à
        if (!empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        // Recherche
        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                    ->orWhere('description', 'like', "%{$searchTerm}%")
                    ->orWhere('tags', 'like', "%{$searchTerm}%")
                    ->orWhereHas('project', function ($q) use ($searchTerm) {
                        $q->where('name', 'like', "%{$searchTerm}%");
                    });
            });
        }

        // Tri
        $sortBy = $filters['sort_by'];
        $sortOrder = $filters['sort_order'];

        if ($sortBy === 'due_date') {
            // Pour trier null en dernier
            $query->orderByRaw("due_date IS NULL, due_date {$sortOrder}");
        } else if ($sortBy === 'priority') {
            // Ordre personnalisé pour la priorité
            $priorityOrder = [
                'high' => 1,
                'medium' => 2,
                'low' => 3,
            ];
            $query->orderByRaw("FIELD(priority, 'high', 'medium', 'low') {$sortOrder}");
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        return $query;
    }

    /**
     * Transform task for API response
     */
    public function transformTask(Task $task, User $user): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->status,
            'priority' => $task->priority,
            'progress' => $task->progress,
            'is_overdue' => $task->is_overdue,
            'total_worked_time' => $task->total_worked_time,
            'formatted_worked_time' => $this->formatMinutesToTime($task->total_worked_time),
            'start_date' => $task->start_date ? $task->start_date->format('Y-m-d') : null,
            'due_date' => $task->due_date ? $task->due_date->format('Y-m-d') : null,
            'estimated_time' => $task->estimated_time,
            'tags' => $task->tags ?? [],
            'created_at' => $task->created_at->format('Y-m-d H:i'),
            'updated_at' => $task->updated_at->format('Y-m-d H:i'),

            // Relations
            'project' => $task->project ? [
                'id' => $task->project->id,
                'name' => $task->project->name,
                'team_name' => $task->project->team->name ?? null,
            ] : null,

            'assigned_user' => $task->assignedUser ? [
                'id' => $task->assignedUser->id,
                'name' => $task->assignedUser->name,
                'avatar' => $task->assignedUser->avatar_url,
                'initials' => $task->assignedUser->initials,
            ] : null,

            'statistics' => [
                'time_logs_count' => $task->timeLogs->count(),
                'comments_count' => $task->comments->count(),
                'files_count' => $task->files->count(),
                'active_time_logs' => $task->timeLogs->whereNull('end_time')->count(),
            ],

            'actions' => [
                'can_edit' => $this->canUserEditTask($user, $task),
                'can_delete' => $this->canUserDeleteTask($user, $task),
                'can_assign' => $this->canUserAssignTask($user, $task),
            ]
        ];
    }

    /**
     * Format minutes to readable time
     */
    public function formatMinutesToTime(int $minutes): string
    {
        if ($minutes < 60) {
            return "{$minutes}m";
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($remainingMinutes === 0) {
            return "{$hours}h";
        }

        return "{$hours}h {$remainingMinutes}m";
    }

    /**
     * Check if user can edit task
     */
    public function canUserEditTask(User $user, Task $task): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // L'utilisateur assigné peut éditer
        if ($task->assigned_to === $user->id) {
            return true;
        }

        // Le propriétaire du projet peut éditer
        if ($task->project && $task->project->owner_id === $user->id) {
            return true;
        }

        // Le propriétaire de l'équipe peut éditer
        if ($task->project && $task->project->team && $task->project->team->owner_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Check if user can delete task
     */
    public function canUserDeleteTask(User $user, Task $task): bool
    {
        // Seuls les admins peuvent supprimer
        return $user->isAdmin();
    }

    /**
     * Check if user can assign task
     */
    public function canUserAssignTask(User $user, Task $task): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // L'utilisateur assigné ne peut pas se réassigner
        if ($task->assigned_to === $user->id) {
            return false;
        }

        // Le propriétaire du projet ou de l'équipe peut assigner
        if ($task->project) {
            if ($task->project->owner_id === $user->id) {
                return true;
            }

            if ($task->project->team && $task->project->team->owner_id === $user->id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get time ago string
     */
    public function getTimeAgo(Carbon $date): string
    {
        $diff = $date->diff(now());

        if ($diff->days > 7) {
            return $date->format('d/m/Y');
        } elseif ($diff->days > 0) {
            return $diff->days . ' jour' . ($diff->days > 1 ? 's' : '');
        } elseif ($diff->h > 0) {
            return $diff->h . ' heure' . ($diff->h > 1 ? 's' : '');
        } else {
            return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '');
        }
    }

    /**
     * Get recent changes for a task
     */
    public function getRecentChanges(Task $task): array
    {
        $changes = [];
        $oneWeekAgo = Carbon::now()->subWeek();

        // Vérifier les changements de statut
        if ($task->updated_at->gte($oneWeekAgo) && $task->created_at->lt($task->updated_at)) {
            $changes[] = 'Mis à jour';
        }

        // Vérifier les nouveaux commentaires
        $recentComments = $task->comments()
            ->where('created_at', '>=', $oneWeekAgo)
            ->count();

        if ($recentComments > 0) {
            $changes[] = $recentComments . ' nouveau' . ($recentComments > 1 ? 'x' : '') . ' commentaire' . ($recentComments > 1 ? 's' : '');
        }

        // Vérifier les nouveaux fichiers
        $recentFiles = $task->files()
            ->where('created_at', '>=', $oneWeekAgo)
            ->count();

        if ($recentFiles > 0) {
            $changes[] = $recentFiles . ' nouveau' . ($recentFiles > 1 ? 'x' : '') . ' fichier' . ($recentFiles > 1 ? 's' : '');
        }

        return $changes;
    }

    /**
     * Group tasks by day
     */
    public function groupTasksByDay($tasks): array
    {
        return $tasks->groupBy(function ($task) {
            return $task->updated_at->format('Y-m-d');
        })->map(function ($dayTasks, $date) {
            $dateObj = Carbon::parse($date);

            return [
                'date' => $date,
                'day_name' => $dateObj->locale('fr')->dayName,
                'is_today' => $dateObj->isToday(),
                'is_yesterday' => $dateObj->isYesterday(),
                'tasks_count' => $dayTasks->count(),
                'tasks' => $dayTasks->map(function ($task) {
                    return [
                        'id' => $task->id,
                        'title' => $task->title,
                        'status' => $task->status,
                        'updated_at' => $task->updated_at->format('H:i'),
                    ];
                })->take(5)->values(),
            ];
        })->sortByDesc('date')->values()->toArray();
    }

    /**
     * Get overdue severity level
     */
    public function getOverdueSeverity(int $daysOverdue, string $priority): string
    {
        if ($daysOverdue > 14 || ($daysOverdue > 7 && $priority === 'high')) {
            return 'critical';
        } elseif ($daysOverdue > 7 || ($daysOverdue > 3 && $priority === 'high')) {
            return 'high';
        } elseif ($daysOverdue > 3 || ($daysOverdue > 1 && $priority === 'medium')) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Get task urgency level
     */
    public function getTaskUrgency(int $daysUntil, string $priority): string
    {
        if ($daysUntil < 0) {
            return 'overdue';
        }

        if ($daysUntil == 0) {
            return 'today';
        }

        if ($daysUntil <= 1) {
            return 'tomorrow';
        }

        if ($daysUntil <= 3 && $priority === 'high') {
            return 'urgent';
        }

        if ($daysUntil <= 7) {
            return 'upcoming';
        }

        return 'future';
    }

    /**
     * Get weekly summary for admin dashboard
     */
    public function getWeeklySummary(Carbon $today, ?int $days = 7): array
    {
        $summary = [];
        $totalHours = 0;

        for ($i = ($days - 1); $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            $workTimes = WorkTime::whereDate('work_date', $date)->get();

            $totalWorkSeconds = $workTimes->sum('net_seconds');
            $userCount = $workTimes->count();

            $summary[] = [
                'date' => $date->format('Y-m-d'),
                'day_name' => $date->locale('fr')->dayName,
                'total_hours' => round($totalWorkSeconds / 3600, 2),
                'user_count' => $userCount,
                'avg_hours_per_user' => $userCount > 0
                    ? round(($totalWorkSeconds / $userCount) / 3600, 2)
                    : 0,
            ];

            $totalHours += round($totalWorkSeconds / 3600, 2);
        }

        return [
            'days' => $summary,
            'total_hours' => $totalHours,
            'avg_hours_per_day' => round($totalHours / $days, 2),
        ];
    }

    /**
     * Get user weekly summary
     */
    public function getUserWeeklySummary(User $user, Carbon $today, ?int $days = 7): array
    {
        $summary = [];
        $totalHours = 0;

        for ($i = ($days - 1); $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            $workTime = WorkTime::where('user_id', $user->id)
                ->whereDate('work_date', $date)
                ->first();

            $netHours = $workTime ? $workTime->net_hours : 0;
            $totalHours += $netHours;

            $summary[] = [
                'date' => $date->format('Y-m-d'),
                'day_name' => $date->locale('fr')->dayName,
                'hours' => $netHours,
                'daily_target' => $workTime ? $workTime->daily_target_hours : 0,
                'progress_percentage' => $workTime ? $workTime->progress_percentage : 0,
                'is_within_schedule' => $workTime ? $workTime->is_within_schedule : false,
            ];
        }

        return [
            'days' => $summary,
            'total_hours' => round($totalHours, 2),
            'average_daily_hours' => round($totalHours / $days, 2),
        ];
    }

    /**
     * Get team performance metrics
     */
    public function getTeamPerformanceMetrics(?int $limit = 5): array
    {
        $teams = Team::withCount(['members', 'projects'])
            ->with(['projects.tasks' => function ($query) {
                $query->select('project_id')
                    ->selectRaw('COUNT(*) as total_tasks')
                    ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed_tasks', [Task::STATUS_DONE])
                    ->groupBy('project_id');
            }])
            ->limit($limit)
            ->get()
            ->map(function ($team) {
                $totalTasks = $team->projects->sum('tasks.total_tasks') ?? 0;
                $completedTasks = $team->projects->sum('tasks.completed_tasks') ?? 0;

                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'member_count' => $team->members_count,
                    'project_count' => $team->projects_count,
                    'completion_rate' => $totalTasks > 0
                        ? round(($completedTasks / $totalTasks) * 100, 2)
                        : 0,
                    'total_tasks' => $totalTasks,
                    'completed_tasks' => $completedTasks,
                ];
            });

        return $teams->toArray();
    }

    /**
     * Get active users metrics
     */
    public function getActiveUsersMetrics(?int $limit = 10): array
    {
        $activeUsers = User::whereHas('teams')
            ->with(['teams.projects.tasks' => function ($query) {
                $query->where('status', Task::STATUS_DOING);
            }])
            ->limit($limit)
            ->get()
            ->map(function ($user) {
                $currentTasksCount = $user->teams->flatMap(function ($team) {
                    return $team->projects->flatMap->tasks;
                })->count();

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar_url,
                    'current_tasks_count' => $currentTasksCount,
                    'teams_count' => $user->teams->count(),
                ];
            });

        return $activeUsers->toArray();
    }
}
