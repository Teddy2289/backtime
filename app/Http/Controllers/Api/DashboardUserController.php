<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\WorkTime;
use Modules\Task\Domain\Entities\Task;
use Modules\Team\Domain\Entities\Team;
use Modules\ProjectsTeams\Domain\Entities\ProjectsTeams;
use Modules\TaskTimeLog\Domain\Entities\TaskTimeLog;
use Modules\User\Domain\Entities\User;
use App\Services\Dashboard\DashboardHelperService;

class DashboardUserController extends Controller
{
    protected $helperService;

    public function __construct(DashboardHelperService $helperService)
    {
        $this->helperService = $helperService;
    }

    /**
     * Get dashboard statistics for authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            return $this->adminDashboard($user);
        }

        return $this->userDashboard($user);
    }

    /**
     * Admin dashboard - Shows data for all users
     */
    private function adminDashboard(User $admin): JsonResponse
    {
        $today = Carbon::today();

        // 1. General statistics
        $totalUsers = User::count();
        $totalTeams = Team::count();
        $totalProjects = ProjectsTeams::count();
        $totalActiveTasks = Task::where('status', '!=', Task::STATUS_DONE)->count();

        // 2. Today's work statistics
        $todayWorkTimes = WorkTime::with(['user', 'sessions'])
            ->whereDate('work_date', $today)
            ->get();

        $usersWorkingToday = $todayWorkTimes->count();
        $totalWorkTimeToday = $todayWorkTimes->sum('net_seconds');
        $averageWorkTimeToday = $usersWorkingToday > 0
            ? $todayWorkTimes->avg('net_seconds')
            : 0;

        // 3. Active users
        $activeUsers = $this->helperService->getActiveUsersMetrics();

        // 4. Recent projects
        $recentProjects = ProjectsTeams::with(['team', 'tasks'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($project) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'team_name' => $project->team->name ?? 'N/A',
                    'task_count' => $project->tasks->count(),
                    'status' => $project->status,
                    'progress' => $this->helperService->calculateProjectProgress($project),
                ];
            });

        // 5. Team performance metrics
        $teams = $this->helperService->getTeamPerformanceMetrics();

        // 6. Daily work summary
        $weeklySummary = $this->helperService->getWeeklySummary($today, 7);

        return response()->json([
            'success' => true,
            'data' => [
                'role' => 'admin',
                'summary' => [
                    'total_users' => $totalUsers,
                    'total_teams' => $totalTeams,
                    'total_projects' => $totalProjects,
                    'total_active_tasks' => $totalActiveTasks,
                    'users_working_today' => $usersWorkingToday,
                ],
                'today_work' => [
                    'total_hours' => round($totalWorkTimeToday / 3600, 2),
                    'average_hours_per_user' => round($averageWorkTimeToday / 3600, 2),
                    'work_times' => $todayWorkTimes->map(function ($workTime) {
                        return [
                            'user_name' => $workTime->user->name,
                            'net_hours' => $workTime->net_hours,
                            'progress_percentage' => $workTime->progress_percentage,
                            'is_within_schedule' => $workTime->is_within_schedule,
                        ];
                    }),
                ],
                'active_users' => $activeUsers,
                'recent_projects' => $recentProjects,
                'team_performance' => $teams,
                'weekly_summary' => $weeklySummary,
                'user' => [
                    'id' => $admin->id,
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'role' => $admin->role->value,
                ],
            ],
        ]);
    }

    /**
     * User dashboard - Shows data for user's team and projects
     */
    private function userDashboard(User $user): JsonResponse
    {
        // 1. Get user's teams
        $userTeams = $user->teams()->with(['projects.tasks'])->get();

        // Si l'utilisateur n'est pas dans une équipe
        if ($userTeams->isEmpty()) {
            return $this->userWithoutTeams($user);
        }

        // 2. Get all projects from user's teams
        $projectIds = $userTeams->flatMap->projects->pluck('id')->unique();

        // 3. Get tasks assigned to user in their team projects
        $userTasks = Task::whereIn('project_id', $projectIds)
            ->where(function ($query) use ($user) {
                $query->where('assigned_to', $user->id)
                    ->orWhereHas('timeLogs', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    });
            })
            ->with(['project', 'assignedUser', 'timeLogs' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }])
            ->get();

        // 4. Today's work time
        $today = Carbon::today();
        $todayWorkTime = WorkTime::with('sessions')
            ->where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->first();

        // 5. Weekly work summary for user
        $weeklySummary = $this->helperService->getUserWeeklySummary($user, $today, 7);

        // 6. Current active time logs
        $activeTimeLogs = TaskTimeLog::where('user_id', $user->id)
            ->whereNull('end_time')
            ->with('task')
            ->get();

        // 7. Task statistics by status
        $taskStats = $this->helperService->getTaskStatuses($userTasks);

        // 8. Recent activity (time logs and task updates)
        $recentActivity = TaskTimeLog::where('user_id', $user->id)
            ->with(['task', 'task.project'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($timeLog) {
                return [
                    'id' => $timeLog->id,
                    'task_title' => $timeLog->task->title ?? 'Tâche inconnue',
                    'project_name' => $timeLog->task->project->name ?? 'Projet inconnu',
                    'duration' => $timeLog->formatted_duration,
                    'note' => $timeLog->note,
                    'date' => $timeLog->created_at->format('Y-m-d H:i'),
                    'is_running' => $timeLog->is_running,
                ];
            });

        // 9. Team information
        $teamInfo = $userTeams->map(function ($team) use ($user) {
            $teamProjects = $team->projects;
            $teamTasks = $teamProjects->flatMap->tasks;

            return [
                'id' => $team->id,
                'name' => $team->name,
                'description' => $team->description,
                'is_owner' => $team->isOwner($user->id),
                'member_count' => $team->members()->count(),
                'project_count' => $teamProjects->count(),
                'active_tasks_count' => $teamTasks->where('status', Task::STATUS_DOING)->count(),
            ];
        });

        // 10. Project deadlines (upcoming)
        $upcomingDeadlines = Task::whereIn('project_id', $projectIds)
            ->where('assigned_to', $user->id)
            ->where('status', '!=', Task::STATUS_DONE)
            ->whereNotNull('due_date')
            ->where('due_date', '>=', $today)
            ->where('due_date', '<=', $today->copy()->addDays(7))
            ->with('project')
            ->orderBy('due_date', 'asc')
            ->limit(5)
            ->get()
            ->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'project_name' => $task->project->name ?? 'N/A',
                    'due_date' => $task->due_date->format('Y-m-d'),
                    'days_left' => $task->due_date->diffInDays(Carbon::today()),
                    'priority' => $task->priority,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'role' => $user->role->value,
                'summary' => [
                    'total_tasks' => $taskStats['total'],
                    'active_tasks' => $taskStats['doing'],
                    'overdue_tasks' => $taskStats['overdue'],
                    'completed_tasks' => $taskStats['done'],
                    'teams_count' => $userTeams->count(),
                    'projects_count' => $projectIds->count(),
                ],
                'today_work' => $todayWorkTime ? [
                    'net_hours' => $todayWorkTime->net_hours,
                    'pause_hours' => $todayWorkTime->pause_hours,
                    'total_hours' => $todayWorkTime->total_hours,
                    'progress_percentage' => $todayWorkTime->progress_percentage,
                    'is_within_schedule' => $todayWorkTime->is_within_schedule,
                    'sessions_count' => $todayWorkTime->sessions->count(),
                    'daily_target' => $todayWorkTime->daily_target_hours,
                ] : null,
                'weekly_summary' => $weeklySummary,
                'task_statistics' => $taskStats,
                'active_time_logs' => $activeTimeLogs->map(function ($timeLog) {
                    return [
                        'id' => $timeLog->id,
                        'task_id' => $timeLog->task_id,
                        'task_title' => $timeLog->task->title ?? 'Tâche inconnue',
                        'start_time' => $timeLog->start_time->format('H:i'),
                        'duration' => $timeLog->start_time->diffInMinutes(now()) . 'm',
                    ];
                }),
                'recent_activity' => $recentActivity,
                'teams' => $teamInfo,
                'upcoming_deadlines' => $upcomingDeadlines,
                'current_tasks' => $userTasks->where('status', Task::STATUS_DOING)
                    ->take(5)
                    ->map(function ($task) {
                        return [
                            'id' => $task->id,
                            'title' => $task->title,
                            'project_name' => $task->project->name ?? 'N/A',
                            'priority' => $task->priority,
                            'progress' => $task->progress,
                            'due_date' => $task->due_date ? $task->due_date->format('Y-m-d') : null,
                            'total_worked_time' => $task->total_worked_time,
                        ];
                    }),
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar_url,
                    'initials' => $user->initials,
                ],
            ],
        ]);
    }

    /**
     * Dashboard pour utilisateur sans équipe
     */
    private function userWithoutTeams(User $user): JsonResponse
    {
        $today = Carbon::today();

        // 1. Today's work time
        $todayWorkTime = WorkTime::with('sessions')
            ->where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->first();

        // 2. Weekly work summary
        $weeklySummary = $this->helperService->getUserWeeklySummary($user, $today, 7);

        // 3. Tasks assigned directly to user (sans équipe)
        $userTasks = Task::where('assigned_to', $user->id)
            ->with(['project', 'assignedUser', 'timeLogs' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }])
            ->get();

        // 4. Task statistics
        $taskStats = $this->helperService->getTaskStatuses($userTasks);

        return response()->json([
            'success' => true,
            'data' => [
                'role' => $user->role->value,
                'summary' => [
                    'total_tasks' => $taskStats['total'],
                    'active_tasks' => $taskStats['doing'],
                    'overdue_tasks' => $taskStats['overdue'],
                    'completed_tasks' => $taskStats['done'],
                    'teams_count' => 0,
                    'projects_count' => 0,
                    'message' => 'Vous n\'êtes actuellement dans aucune équipe.',
                ],
                'today_work' => $todayWorkTime ? [
                    'net_hours' => $todayWorkTime->net_hours,
                    'pause_hours' => $todayWorkTime->pause_hours,
                    'total_hours' => $todayWorkTime->total_hours,
                    'progress_percentage' => $todayWorkTime->progress_percentage,
                    'is_within_schedule' => $todayWorkTime->is_within_schedule,
                    'sessions_count' => $todayWorkTime->sessions->count(),
                    'daily_target' => $todayWorkTime->daily_target_hours,
                ] : null,
                'weekly_summary' => $weeklySummary,
                'task_statistics' => $taskStats,
                'current_tasks' => $userTasks->where('status', Task::STATUS_DOING)
                    ->take(5)
                    ->map(function ($task) {
                        return [
                            'id' => $task->id,
                            'title' => $task->title,
                            'project_name' => $task->project->name ?? 'Sans projet',
                            'priority' => $task->priority,
                            'progress' => $task->progress,
                            'due_date' => $task->due_date ? $task->due_date->format('Y-m-d') : null,
                            'total_worked_time' => $task->total_worked_time,
                        ];
                    }),
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar_url,
                    'initials' => $user->initials,
                ],
            ],
        ]);
    }

    /**
     * Get detailed task status statistics with or without timeframe
     */
    public function taskStatusStats(Request $request): JsonResponse
    {
        $user = Auth::user();
        $timeframe = $request->input('timeframe', 'month');

        // Définir la période si spécifiée
        $startDate = null;
        $endDate = null;

        if ($timeframe !== 'all') {
            $endDate = Carbon::today();
            switch ($timeframe) {
                case 'day':
                    $startDate = $endDate->copy()->startOfDay();
                    break;
                case 'week':
                    $startDate = $endDate->copy()->subDays(6)->startOfDay();
                    break;
                case 'year':
                    $startDate = $endDate->copy()->subYear()->startOfDay();
                    break;
                case 'month':
                default:
                    $startDate = $endDate->copy()->subDays(29)->startOfDay();
                    break;
            }
        }

        // Si l'utilisateur est admin, on affiche toutes les tâches
        if ($user->isAdmin()) {
            $tasks = $this->helperService->getAllTasksForAdmin($startDate, $endDate);
        } else {
            // Sinon, on récupère seulement les tâches de l'utilisateur dans ses équipes/projets
            $tasks = $this->helperService->getUserTasks($user, $startDate, $endDate);
        }

        // Calculer les statistiques détaillées
        $stats = $this->helperService->getTaskStatuses($tasks);

        // Ajouter des métriques spécifiques
        $stats['timeframe'] = $timeframe;
        $stats['user_role'] = $user->role->value;

        if ($timeframe !== 'all') {
            $stats['start_date'] = $startDate->format('Y-m-d');
            $stats['end_date'] = $endDate->format('Y-m-d');

            // Calculer les changements par rapport à la période précédente
            $previousStats = $this->helperService->calculatePreviousPeriodStats($user, $timeframe, $startDate, $endDate);
            $stats['changes'] = $this->helperService->calculateChanges($stats, $previousStats);
        } else {
            $stats['start_date'] = null;
            $stats['end_date'] = null;
            $stats['changes'] = null;
        }

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get user's monthly work summary
     */
    public function monthlySummary(Request $request): JsonResponse
    {
        $user = Auth::user();
        $year = $request->input('year', Carbon::now()->year);
        $month = $request->input('month', Carbon::now()->month);

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $workTimes = WorkTime::where('user_id', $user->id)
            ->whereBetween('work_date', [$startDate, $endDate])
            ->orderBy('work_date', 'asc')
            ->get();

        $monthlySummary = [];
        $totalMonthHours = 0;
        $totalDaysWorked = 0;

        foreach ($workTimes as $workTime) {
            $monthlySummary[] = [
                'date' => $workTime->work_date->format('Y-m-d'),
                'day_name' => $workTime->day_name,
                'net_hours' => $workTime->net_hours,
                'pause_hours' => $workTime->pause_hours,
                'total_hours' => $workTime->total_hours,
                'progress_percentage' => $workTime->progress_percentage,
                'is_within_schedule' => $workTime->is_within_schedule,
            ];

            $totalMonthHours += $workTime->net_hours;
            $totalDaysWorked++;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'month' => $startDate->format('F Y'),
                'summary' => $monthlySummary,
                'statistics' => [
                    'total_hours' => round($totalMonthHours, 2),
                    'total_days_worked' => $totalDaysWorked,
                    'average_daily_hours' => $totalDaysWorked > 0
                        ? round($totalMonthHours / $totalDaysWorked, 2)
                        : 0,
                ],
            ],
        ]);
    }

    /**
     * Get tasks list with filters
     */
    public function tasksList(Request $request): JsonResponse
    {
        $user = Auth::user();
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
            $query = $this->helperService->buildAdminTasksQuery($filters);
        } else {
            $query = $this->helperService->buildUserTasksQuery($user, $filters);
        }

        // Pagination
        $tasks = $query->paginate($perPage, ['*'], 'page', $page);

        // Transformer les données
        $transformedTasks = $tasks->map(function ($task) use ($user) {
            return $this->helperService->transformTask($task, $user);
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
     * Get recent tasks for authenticated user
     */
    public function recentTasks(Request $request): JsonResponse
    {
        $user = Auth::user();
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
                'time_since_update' => $this->helperService->getTimeAgo($task->updated_at),
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
                'changes' => $this->helperService->getRecentChanges($task),
            ];
        });

        // Grouper par jour pour l'affichage
        $groupedTasks = $this->helperService->groupTasksByDay($tasks);

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
     * Get overdue tasks for authenticated user
     */
    public function overdueTasks(Request $request): JsonResponse
    {
        $user = Auth::user();
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
                'severity' => $this->helperService->getOverdueSeverity($daysOverdue, $task->priority),
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
     * Get upcoming tasks for authenticated user
     */
    public function upcomingTasks(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

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
                            'urgency' => $this->helperService->getTaskUrgency($daysUntil, $task->priority),
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
