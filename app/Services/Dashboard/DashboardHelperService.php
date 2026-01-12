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
                'avatar' => $task->assignedUser->avatar,
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

    /**
     * Get task status stats API response
     */
    public function getTaskStatusStatsResponse($request)
    {
        // Cette méthode est maintenant dans AdminDashboardService
        // Elle est maintenue ici pour compatibilité
        $adminService = app(AdminDashboardService::class);
        return $adminService->getTaskStatusStatsResponse($request);
    }

    /**
     * Get monthly summary API response
     */
    public function getMonthlySummaryResponse($request)
    {
        // Cette méthode est maintenant dans UserDashboardService
        $userService = app(UserDashboardService::class);
        return $userService->getMonthlySummaryResponse($request);
    }

    /**
     * Get tasks list API response
     */
    public function getTasksListResponse($request): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);

        // Récupérer les filtres
        $filters = [
            'status' => $request->input('status'),
            'priority' => $request->input('priority'),
            'project_id' => $request->input('project_id'),
            'assigned_to' => $request->input('assigned_to'),
            'search' => $request->input('search'),
            'sort_by' => $request->input('sort_by', 'created_at'),
            'sort_order' => $request->input('sort_order', 'desc'),
        ];

        // Construire la requête
        if ($user->isAdmin()) {
            $query = $this->buildAdminTasksQuery($filters);
        } else {
            $query = $this->buildUserTasksQuery($user, $filters);
        }

        // Pagination
        $tasks = $query->paginate($perPage, ['*'], 'page', $page);

        // Transformer les données
        $transformedTasks = $tasks->map(function ($task) use ($user) {
            return $this->transformTask($task, $user);
        });

        return response()->json([
            'success' => true,
            'data' => [
                'tasks' => $transformedTasks,
                'pagination' => [
                    'total' => $tasks->total(),
                    'per_page' => $tasks->perPage(),
                    'current_page' => $tasks->currentPage(),
                    'last_page' => $tasks->lastPage(),
                    'from' => $tasks->firstItem(),
                    'to' => $tasks->lastItem(),
                ],
                'filters' => $filters,
                'user_role' => $user->role->value,
            ],
        ]);
    }

    /**
     * Get recent tasks API response
     */
    public function getRecentTasksResponse($request): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();
        $limit = $request->input('limit', 10);

        // Définir la période récente (7 derniers jours par défaut)
        $days = $request->input('days', 7);
        $startDate = Carbon::now()->subDays($days);

        // Construire la requête selon le rôle
        if ($user->isAdmin()) {
            $query = Task::with(['project', 'project.team', 'assignedUser'])
                ->where('created_at', '>=', $startDate)
                ->orWhere('updated_at', '>=', $startDate);
        } else {
            // Récupérer les projets des équipes de l'utilisateur
            $userTeams = $user->teams()->get();

            if ($userTeams->isEmpty()) {
                // Si pas d'équipe, seulement les tâches assignées
                $query = Task::with(['project', 'assignedUser'])
                    ->where('assigned_to', $user->id)
                    ->orWhereHas('timeLogs', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    });
            } else {
                $projectIds = $userTeams->flatMap(function ($team) {
                    return $team->projects()->pluck('id');
                })->unique();

                $query = Task::with(['project', 'project.team', 'assignedUser'])
                    ->whereIn('project_id', $projectIds)
                    ->where(function ($query) use ($user) {
                        $query->where('assigned_to', $user->id)
                            ->orWhereHas('timeLogs', function ($q) use ($user) {
                                $q->where('user_id', $user->id);
                            });
                    });
            }

            // Filtrer par date récente
            $query->where(function ($q) use ($startDate) {
                $q->where('created_at', '>=', $startDate)
                    ->orWhere('updated_at', '>=', $startDate);
            });
        }

        // Ordonner par date de mise à jour (les plus récentes d'abord)
        $tasks = $query->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get();

        // Transformer les données
        $transformedTasks = $tasks->map(function ($task) {
            return [
                'id' => $task->id,
                'title' => $task->title,
                'status' => $task->status,
                'priority' => $task->priority,
                'progress' => $task->progress,
                'is_overdue' => $task->is_overdue,
                'due_date' => $task->due_date ? $task->due_date->format('Y-m-d') : null,
                'created_at' => $task->created_at->format('Y-m-d H:i'),
                'updated_at' => $task->updated_at->format('Y-m-d H:i'),
                'time_since_update' => $this->getTimeAgo($task->updated_at),
                'project' => $task->project ? [
                    'id' => $task->project->id,
                    'name' => $task->project->name,
                    'team_name' => $task->project->team->name ?? null,
                ] : null,
                'assigned_user' => $task->assignedUser ? [
                    'id' => $task->assignedUser->id,
                    'name' => $task->assignedUser->name,
                    'avatar' => $task->assignedUser->avatar,
                    'initials' => $task->assignedUser->initials,
                ] : null,
                'changes' => $this->getRecentChanges($task),
            ];
        });

        // Grouper par jour pour l'affichage
        $groupedTasks = $this->groupTasksByDay($tasks);

        return response()->json([
            'success' => true,
            'data' => [
                'tasks' => $transformedTasks,
                'grouped_tasks' => $groupedTasks,
                'total' => $tasks->count(),
                'period' => [
                    'days' => $days,
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => Carbon::now()->format('Y-m-d'),
                ],
                'user_role' => $user->role->value,
            ],
        ]);
    }

    /**
     * Get overdue tasks API response
     */
    public function getOverdueTasksResponse($request): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();
        $limit = $request->input('limit', 20);

        // Construire la requête pour les tâches en retard
        if ($user->isAdmin()) {
            $query = Task::with(['project', 'project.team', 'assignedUser'])
                ->whereNotNull('due_date')
                ->where('due_date', '<', Carbon::now())
                ->where('status', '!=', Task::STATUS_DONE);
        } else {
            // Récupérer les projets des équipes de l'utilisateur
            $userTeams = $user->teams()->get();

            if ($userTeams->isEmpty()) {
                // Si pas d'équipe, seulement les tâches assignées
                $query = Task::with(['project', 'assignedUser'])
                    ->where('assigned_to', $user->id)
                    ->whereNotNull('due_date')
                    ->where('due_date', '<', Carbon::now())
                    ->where('status', '!=', Task::STATUS_DONE);
            } else {
                $projectIds = $userTeams->flatMap(function ($team) {
                    return $team->projects()->pluck('id');
                })->unique();

                $query = Task::with(['project', 'project.team', 'assignedUser'])
                    ->whereIn('project_id', $projectIds)
                    ->where(function ($query) use ($user) {
                        $query->where('assigned_to', $user->id)
                            ->orWhereHas('timeLogs', function ($q) use ($user) {
                                $q->where('user_id', $user->id);
                            });
                    })
                    ->whereNotNull('due_date')
                    ->where('due_date', '<', Carbon::now())
                    ->where('status', '!=', Task::STATUS_DONE);
            }
        }

        // Trier par retard (les plus en retard d'abord)
        $tasks = $query->orderBy('due_date', 'asc')
            ->limit($limit)
            ->get();

        // Calculer les statistiques de retard
        $overdueStats = [
            'total' => $tasks->count(),
            'by_priority' => [
                'high' => $tasks->where('priority', 'high')->count(),
                'medium' => $tasks->where('priority', 'medium')->count(),
                'low' => $tasks->where('priority', 'low')->count(),
            ],
            'by_project' => $tasks->groupBy('project_id')->map(function ($projectTasks, $projectId) {
                $project = $projectTasks->first()->project;
                return [
                    'project_id' => $projectId,
                    'project_name' => $project ? $project->name : 'N/A',
                    'count' => $projectTasks->count(),
                ];
            })->values()->take(5),
            'days_overdue_stats' => [
                '1-3_days' => $tasks->filter(fn($task) => $task->due_date->diffInDays(Carbon::now()) <= 3)->count(),
                '4-7_days' => $tasks->filter(fn($task) => $task->due_date->diffInDays(Carbon::now()) > 3 &&
                    $task->due_date->diffInDays(Carbon::now()) <= 7)->count(),
                '8-14_days' => $tasks->filter(fn($task) => $task->due_date->diffInDays(Carbon::now()) > 7 &&
                    $task->due_date->diffInDays(Carbon::now()) <= 14)->count(),
                '15+_days' => $tasks->filter(fn($task) => $task->due_date->diffInDays(Carbon::now()) > 14)->count(),
            ],
        ];

        // Transformer les tâches
        $transformedTasks = $tasks->map(function ($task) {
            $daysOverdue = $task->due_date->diffInDays(Carbon::now());

            return [
                'id' => $task->id,
                'title' => $task->title,
                'status' => $task->status,
                'priority' => $task->priority,
                'progress' => $task->progress,
                'due_date' => $task->due_date->format('Y-m-d'),
                'days_overdue' => $daysOverdue,
                'severity' => $this->getOverdueSeverity($daysOverdue, $task->priority),
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
                'total_worked_time' => $task->total_worked_time,
                'estimated_time' => $task->estimated_time,
                'time_completion_rate' => $task->estimated_time > 0 ?
                    round(($task->total_worked_time / $task->estimated_time) * 100, 2) : 0,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'tasks' => $transformedTasks,
                'statistics' => $overdueStats,
                'total' => $tasks->count(),
                'user_role' => $user->role->value,
                'current_date' => Carbon::now()->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Get upcoming tasks API response
     */
    public function getUpcomingTasksResponse($request): \Illuminate\Http\JsonResponse
    {
        try {
            $user = auth()->user();

            // S'assurer que les paramètres sont des entiers
            $limit = (int) $request->input('limit', 15);
            $daysAhead = (int) $request->input('days_ahead', 7);

            // Utiliser now() au lieu de Carbon::now() si possible
            $startDate = now();
            $endDate = now()->addDays($daysAhead);

            // Construire la requête pour les tâches à venir
            if ($user->isAdmin()) {
                $query = Task::with(['project', 'project.team', 'assignedUser'])
                    ->where(function ($query) use ($startDate, $endDate) {
                        $query->where(function ($q) use ($startDate, $endDate) {
                            $q->whereNotNull('start_date')
                                ->where('start_date', '>', $startDate)
                                ->where('start_date', '<=', $endDate)
                                ->where('status', '!=', Task::STATUS_DONE);
                        })->orWhere(function ($q) use ($startDate, $endDate) {
                            $q->whereNull('start_date')
                                ->whereNotNull('due_date')
                                ->where('due_date', '>', $startDate)
                                ->where('due_date', '<=', $endDate)
                                ->where('status', '!=', Task::STATUS_DONE);
                        });
                    });
            } else {
                // Récupérer les projets des équipes de l'utilisateur
                $userTeams = $user->teams()->get();

                if ($userTeams->isEmpty()) {
                    // Si pas d'équipe, seulement les tâches assignées
                    $query = Task::with(['project', 'assignedUser'])
                        ->where('assigned_to', $user->id)
                        ->where(function ($query) use ($startDate, $endDate) {
                            $query->where(function ($q) use ($startDate, $endDate) {
                                $q->whereNotNull('start_date')
                                    ->where('start_date', '>', $startDate)
                                    ->where('start_date', '<=', $endDate)
                                    ->where('status', '!=', Task::STATUS_DONE);
                            })->orWhere(function ($q) use ($startDate, $endDate) {
                                $q->whereNull('start_date')
                                    ->whereNotNull('due_date')
                                    ->where('due_date', '>', $startDate)
                                    ->where('due_date', '<=', $endDate)
                                    ->where('status', '!=', Task::STATUS_DONE);
                            });
                        });
                } else {
                    $projectIds = $userTeams->flatMap(function ($team) {
                        return $team->projects()->pluck('id');
                    })->unique();

                    $query = Task::with(['project', 'project.team', 'assignedUser'])
                        ->whereIn('project_id', $projectIds)
                        ->where(function ($query) use ($user) {
                            $query->where('assigned_to', $user->id)
                                ->orWhereHas('timeLogs', function ($q) use ($user) {
                                    $q->where('user_id', $user->id);
                                });
                        })
                        ->where(function ($query) use ($startDate, $endDate) {
                            $query->where(function ($q) use ($startDate, $endDate) {
                                $q->whereNotNull('start_date')
                                    ->where('start_date', '>', $startDate)
                                    ->where('start_date', '<=', $endDate)
                                    ->where('status', '!=', Task::STATUS_DONE);
                            })->orWhere(function ($q) use ($startDate, $endDate) {
                                $q->whereNull('start_date')
                                    ->whereNotNull('due_date')
                                    ->where('due_date', '>', $startDate)
                                    ->where('due_date', '<=', $endDate)
                                    ->where('status', '!=', Task::STATUS_DONE);
                            });
                        });
                }
            }

            // Trier par date la plus proche (priorité à start_date, sinon due_date)
            $tasks = $query->orderByRaw('COALESCE(start_date, due_date) ASC')
                ->orderBy('priority', 'desc')
                ->limit($limit)
                ->get();

            // Grouper par jour avec la date appropriée
            $tasksByDay = $tasks->groupBy(function ($task) {
                // Utiliser start_date si disponible, sinon due_date
                $referenceDate = $task->start_date ?? $task->due_date;
                return $referenceDate ? $referenceDate->format('Y-m-d') : 'no-date';
            })->filter(function ($dayTasks, $date) {
                return $date !== 'no-date';
            })->map(function ($dayTasks, $date) {
                $dateObj = Carbon::parse($date);
                $today = now();

                // Calculer les jours restants
                $daysUntil = $today->diffInDays($dateObj, false);

                return [
                    'date' => $date,
                    'day_name' => $dateObj->locale('fr')->dayName,
                    'is_today' => $dateObj->isToday(),
                    'is_tomorrow' => $dateObj->isTomorrow(),
                    'date_type' => $dayTasks->first()->start_date ? 'start_date' : 'due_date',
                    'days_until' => $daysUntil,
                    'tasks' => $dayTasks->map(function ($task) {
                        $referenceDate = $task->start_date ?? $task->due_date;
                        $today = now();
                        $daysUntil = $referenceDate ? $today->diffInDays($referenceDate, false) : 0;

                        return [
                            'id' => $task->id,
                            'title' => $task->title,
                            'description' => $task->description,
                            'status' => $task->status,
                            'priority' => $task->priority,
                            'progress' => $task->progress ?? 0,
                            'start_date' => $task->start_date?->format('Y-m-d'),
                            'due_date' => $task->due_date?->format('Y-m-d'),
                            'reference_date' => $referenceDate?->format('Y-m-d'),
                            'date_type' => $task->start_date ? 'start_date' : 'due_date',
                            'days_until' => $daysUntil,
                            'urgency' => $this->getTaskUrgency($daysUntil, $task->priority),
                            'is_scheduled' => !is_null($task->start_date),
                            'project' => $task->project ? [
                                'id' => $task->project->id,
                                'name' => $task->project->name,
                                'team_name' => $task->project->team->name ?? null,
                            ] : null,
                            'assigned_user' => $task->assignedUser ? [
                                'id' => $task->assignedUser->id,
                                'name' => $task->assignedUser->name,
                                'avatar' => $task->assignedUser->avatar_url,
                                'initials' => $task->assignedUser->initials ?? strtoupper(substr($task->assignedUser->name, 0, 2)),
                            ] : null,
                            'estimated_time' => $task->estimated_time,
                            'total_worked_time' => $task->total_worked_time ?? 0,
                            'time_remaining' => $task->estimated_time > 0 ?
                                max(0, $task->estimated_time - ($task->total_worked_time ?? 0)) : null,
                            'tags' => $task->tags ?? [],
                        ];
                    }),
                    'count' => $dayTasks->count(),
                    'high_priority_count' => $dayTasks->where('priority', 'high')->count(),
                    'scheduled_count' => $dayTasks->whereNotNull('start_date')->count(),
                    'unscheduled_count' => $dayTasks->whereNull('start_date')->count(),
                ];
            })->sortBy('date')->values();

            // Tâches critiques (haute priorité et moins de 3 jours)
            $criticalTasks = $tasks->filter(function ($task) {
                $referenceDate = $task->start_date ?? $task->due_date;
                if (!$referenceDate) return false;

                $daysUntil = now()->diffInDays($referenceDate, false);

                return $task->priority === 'high' && $daysUntil <= 3 && $daysUntil >= 0;
            })->map(function ($task) {
                $referenceDate = $task->start_date ?? $task->due_date;

                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'project_name' => $task->project->name ?? 'N/A',
                    'date_type' => $task->start_date ? 'start_date' : 'due_date',
                    'reference_date' => $referenceDate?->format('Y-m-d'),
                    'due_date' => $task->due_date?->format('Y-m-d'),
                    'start_date' => $task->start_date?->format('Y-m-d'),
                    'days_until' => $referenceDate ? now()->diffInDays($referenceDate, false) : 0,
                    'progress' => $task->progress ?? 0,
                    'priority' => $task->priority,
                ];
            })->values();

            // Statistiques des tâches à venir
            $upcomingStats = [
                'total' => $tasks->count(),
                'by_day' => $tasksByDay->map(function ($day) {
                    return [
                        'date' => $day['date'],
                        'count' => $day['count'],
                        'high_priority_count' => $day['high_priority_count'],
                        'scheduled_count' => $day['scheduled_count'],
                        'unscheduled_count' => $day['unscheduled_count'],
                    ];
                })->values(),
                'by_priority' => [
                    'high' => $tasks->where('priority', 'high')->count(),
                    'medium' => $tasks->where('priority', 'medium')->count(),
                    'low' => $tasks->where('priority', 'low')->count(),
                ],
                'by_schedule_type' => [
                    'scheduled' => $tasks->whereNotNull('start_date')->count(),
                    'unscheduled' => $tasks->whereNull('start_date')->count(),
                ],
                'by_project' => $tasks->groupBy('project_id')->map(function ($projectTasks, $projectId) {
                    $project = $projectTasks->first()->project;
                    return [
                        'project_id' => $projectId,
                        'project_name' => $project ? $project->name : 'N/A',
                        'count' => $projectTasks->count(),
                        'high_priority_count' => $projectTasks->where('priority', 'high')->count(),
                        'scheduled_count' => $projectTasks->whereNotNull('start_date')->count(),
                    ];
                })->sortByDesc('count')->values()->take(5),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'tasks_by_day' => $tasksByDay,
                    'critical_tasks' => $criticalTasks,
                    'statistics' => $upcomingStats,
                    'period' => [
                        'days_ahead' => $daysAhead,
                        'start_date' => $startDate->format('Y-m-d'),
                        'end_date' => $endDate->format('Y-m-d'),
                    ],
                    'user_role' => $user->role->value ?? $user->role,
                    'current_date' => now()->format('Y-m-d'),
                    'date_logic' => [
                        'priority_order' => 'start_date avant due_date',
                        'definition' => 'Tâche à venir = start_date OU due_date dans le futur (max ' . $daysAhead . ' jours)',
                        'excluded_status' => 'done',
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in upcomingTasks: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve upcoming tasks: ' . $e->getMessage(),
                'data' => [
                    'tasks_by_day' => [],
                    'critical_tasks' => [],
                    'statistics' => [
                        'total' => 0,
                        'by_day' => [],
                        'by_priority' => ['high' => 0, 'medium' => 0, 'low' => 0],
                        'by_schedule_type' => ['scheduled' => 0, 'unscheduled' => 0],
                        'by_project' => [],
                    ],
                    'period' => [
                        'days_ahead' => $request->input('days_ahead', 7),
                        'start_date' => now()->format('Y-m-d'),
                        'end_date' => now()->addDays($request->input('days_ahead', 7))->format('Y-m-d'),
                    ],
                    'user_role' => 'error',
                    'current_date' => now()->format('Y-m-d'),
                ],
                'error' => config('app.debug') ? [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ] : null,
            ], 500);
        }
    }
}
