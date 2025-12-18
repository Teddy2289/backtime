<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\ProjectsTeams\Domain\Entities\ProjectsTeams;
use Modules\Task\Domain\Entities\Task;
use Modules\TaskComment\Domain\Entities\TaskComment;
use Modules\TaskFiles\Domain\Entities\TaskFile;
use Modules\Team\Domain\Entities\Team;
use Modules\User\Domain\Entities\User;

class DashboardController extends Controller
{
    /**
     * Récupère toutes les statistiques du tableau de bord
     */
    public function index(): JsonResponse
    {
        try {
            $data = [
                'overview' => $this->getOverviewStats(),
                'task_stats' => $this->getTaskStatistics(),
                'user_stats' => $this->getUserStatistics(),
                'recent_activity' => $this->getRecentActivity(),
                'team_stats' => $this->getTeamStatistics(),
                'weekly_analytics' => $this->getWeeklyAnalytics(),
                'monthly_analytics' => $this->getMonthlyAnalytics(),
                'top_performers' => $this->getTopPerformers(),
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Statistiques du tableau de bord récupérées avec succès'
            ]);
        } catch (\Exception $e) {
            Log::error('Dashboard error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques: ' . $e->getMessage()
            ], 500);
        }
    }

    // Dans DashboardController.php - Ajoutez ces méthodes

    /**
     * Récupérer le temps de travail d'un utilisateur spécifique
     */
    public function userWorkTime($userId): JsonResponse
    {
        try {
            $user = DB::table('users')->where('id', $userId)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé'
                ], 404);
            }

            // Vérifier si les tables existent
            $workTimesExists = Schema::hasTable('work_times');
            $workSessionsExists = Schema::hasTable('work_sessions');

            $data = [
                'user_info' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ]
            ];

            if ($workTimesExists) {
                // Temps de travail aujourd'hui
                $today = Carbon::today();
                $workTimeToday = DB::table('work_times')
                    ->where('user_id', $userId)
                    ->whereDate('work_date', $today)
                    ->first();

                // Temps de travail cette semaine
                $startOfWeek = Carbon::now()->startOfWeek();
                $endOfWeek = Carbon::now()->endOfWeek();

                $weeklyWorkTime = DB::table('work_times')
                    ->where('user_id', $userId)
                    ->whereBetween('work_date', [$startOfWeek, $endOfWeek])
                    ->get();

                // Temps de travail ce mois
                $startOfMonth = Carbon::now()->startOfMonth();
                $endOfMonth = Carbon::now()->endOfMonth();

                $monthlyWorkTime = DB::table('work_times')
                    ->where('user_id', $userId)
                    ->whereBetween('work_date', [$startOfMonth, $endOfMonth])
                    ->get();

                $data['work_time_stats'] = [
                    'today' => [
                        'total_seconds' => $workTimeToday->total_seconds ?? 0,
                        'total_hours' => round(($workTimeToday->total_seconds ?? 0) / 3600, 2),
                        'net_seconds' => $workTimeToday->net_seconds ?? 0,
                        'net_hours' => round(($workTimeToday->net_seconds ?? 0) / 3600, 2),
                        'pause_seconds' => $workTimeToday->pause_seconds ?? 0,
                        'pause_hours' => round(($workTimeToday->pause_seconds ?? 0) / 3600, 2),
                        'status' => $workTimeToday->status ?? 'pending'
                    ],
                    'weekly' => [
                        'total_seconds' => $weeklyWorkTime->sum('total_seconds'),
                        'total_hours' => round($weeklyWorkTime->sum('total_seconds') / 3600, 2),
                        'net_seconds' => $weeklyWorkTime->sum('net_seconds'),
                        'net_hours' => round($weeklyWorkTime->sum('net_seconds') / 3600, 2),
                        'days_worked' => $weeklyWorkTime->count(),
                        'average_daily_hours' => $weeklyWorkTime->count() > 0 ?
                            round($weeklyWorkTime->sum('net_seconds') / $weeklyWorkTime->count() / 3600, 2) : 0
                    ],
                    'monthly' => [
                        'total_seconds' => $monthlyWorkTime->sum('total_seconds'),
                        'total_hours' => round($monthlyWorkTime->sum('total_seconds') / 3600, 2),
                        'net_seconds' => $monthlyWorkTime->sum('net_seconds'),
                        'net_hours' => round($monthlyWorkTime->sum('net_seconds') / 3600, 2),
                        'days_worked' => $monthlyWorkTime->count(),
                        'average_daily_hours' => $monthlyWorkTime->count() > 0 ?
                            round($monthlyWorkTime->sum('net_seconds') / $monthlyWorkTime->count() / 3600, 2) : 0
                    ]
                ];
            }

            if ($workSessionsExists) {
                // Sessions de travail récentes
                $recentSessions = DB::table('work_sessions')
                    ->join('work_times', 'work_sessions.work_time_id', '=', 'work_times.id')
                    ->where('work_times.user_id', $userId)
                    ->select(
                        'work_sessions.*',
                        'work_times.work_date'
                    )
                    ->orderBy('work_sessions.session_start', 'desc')
                    ->limit(10)
                    ->get()
                    ->map(function ($session) {
                        return [
                            'id' => $session->id,
                            'session_start' => $session->session_start,
                            'session_end' => $session->session_end,
                            'duration_seconds' => $session->duration_seconds,
                            'duration_hours' => round($session->duration_seconds / 3600, 2),
                            'type' => $session->type,
                            'work_date' => $session->work_date,
                            'created_at' => Carbon::parse($session->created_at)->diffForHumans()
                        ];
                    });

                $data['recent_sessions'] = $recentSessions;
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('User work time error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du temps de travail'
            ], 500);
        }
    }

    /**
     * Récupérer le temps de travail d'un utilisateur par période
     */
    public function userWorkTimeByPeriod($userId, $period = 'week'): JsonResponse
    {
        try {
            $user = DB::table('users')->where('id', $userId)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé'
                ], 404);
            }

            if (!Schema::hasTable('work_times')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Les données de temps de travail ne sont pas disponibles'
                ], 404);
            }

            $startDate = null;
            $endDate = Carbon::now();
            $periodLabel = '';

            switch ($period) {
                case 'today':
                    $startDate = Carbon::today();
                    $periodLabel = 'Aujourd\'hui';
                    break;
                case 'week':
                    $startDate = Carbon::now()->startOfWeek();
                    $periodLabel = 'Cette semaine';
                    break;
                case 'month':
                    $startDate = Carbon::now()->startOfMonth();
                    $periodLabel = 'Ce mois';
                    break;
                case 'year':
                    $startDate = Carbon::now()->startOfYear();
                    $periodLabel = 'Cette année';
                    break;
                case 'last_week':
                    $startDate = Carbon::now()->subWeek()->startOfWeek();
                    $endDate = Carbon::now()->subWeek()->endOfWeek();
                    $periodLabel = 'Semaine dernière';
                    break;
                case 'last_month':
                    $startDate = Carbon::now()->subMonth()->startOfMonth();
                    $endDate = Carbon::now()->subMonth()->endOfMonth();
                    $periodLabel = 'Mois dernier';
                    break;
                default:
                    $startDate = Carbon::now()->startOfWeek();
                    $periodLabel = 'Cette semaine';
            }

            $workTimes = DB::table('work_times')
                ->where('user_id', $userId)
                ->whereBetween('work_date', [$startDate, $endDate])
                ->orderBy('work_date', 'desc')
                ->get();

            // Détail par jour
            $dailyDetails = $workTimes->map(function ($workTime) {
                return [
                    'date' => $workTime->work_date,
                    'total_seconds' => $workTime->total_seconds,
                    'total_hours' => round($workTime->total_seconds / 3600, 2),
                    'net_seconds' => $workTime->net_seconds,
                    'net_hours' => round($workTime->net_seconds / 3600, 2),
                    'pause_seconds' => $workTime->pause_seconds,
                    'pause_hours' => round($workTime->pause_seconds / 3600, 2),
                    'status' => $workTime->status,
                    'notes' => $workTime->notes
                ];
            });

            // Statistiques globales
            $totalSeconds = $workTimes->sum('total_seconds');
            $netSeconds = $workTimes->sum('net_seconds');
            $pauseSeconds = $workTimes->sum('pause_seconds');
            $daysWorked = $workTimes->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'period' => $periodLabel,
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'summary' => [
                        'total_seconds' => $totalSeconds,
                        'total_hours' => round($totalSeconds / 3600, 2),
                        'net_seconds' => $netSeconds,
                        'net_hours' => round($netSeconds / 3600, 2),
                        'pause_seconds' => $pauseSeconds,
                        'pause_hours' => round($pauseSeconds / 3600, 2),
                        'days_worked' => $daysWorked,
                        'average_daily_hours' => $daysWorked > 0 ?
                            round($netSeconds / $daysWorked / 3600, 2) : 0
                    ],
                    'daily_details' => $dailyDetails,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('User work time by period error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du temps de travail'
            ], 500);
        }
    }

    /**
     * Récupérer le temps de travail via les logs de tâches
     */
    public function userTaskWorkTime($userId): JsonResponse
    {
        try {
            if (!Schema::hasTable('task_time_logs')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Les données de temps de travail ne sont pas disponibles'
                ], 404);
            }

            $user = DB::table('users')->where('id', $userId)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé'
                ], 404);
            }

            // Temps aujourd'hui
            $today = Carbon::today();
            $todayWorkTime = DB::table('task_time_logs')
                ->where('user_id', $userId)
                ->whereDate('start_time', $today)
                ->sum('duration') ?? 0; // duration en minutes

            // Temps cette semaine
            $startOfWeek = Carbon::now()->startOfWeek();
            $endOfWeek = Carbon::now()->endOfWeek();
            $weeklyWorkTime = DB::table('task_time_logs')
                ->where('user_id', $userId)
                ->whereBetween('start_time', [$startOfWeek, $endOfWeek])
                ->sum('duration') ?? 0;

            // Temps ce mois
            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth = Carbon::now()->endOfMonth();
            $monthlyWorkTime = DB::table('task_time_logs')
                ->where('user_id', $userId)
                ->whereBetween('start_time', [$startOfMonth, $endOfMonth])
                ->sum('duration') ?? 0;

            // Tâches sur lesquelles l'utilisateur a travaillé
            $recentTasks = DB::table('task_time_logs')
                ->join('tasks', 'task_time_logs.task_id', '=', 'tasks.id')
                ->where('task_time_logs.user_id', $userId)
                ->select(
                    'tasks.id',
                    'tasks.title',
                    'tasks.status',
                    DB::raw('SUM(task_time_logs.duration) as total_minutes')
                )
                ->groupBy('tasks.id', 'tasks.title', 'tasks.status')
                ->orderBy('total_minutes', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($task) {
                    return [
                        'id' => $task->id,
                        'title' => $task->title,
                        'status' => $task->status,
                        'total_minutes' => $task->total_minutes,
                        'total_hours' => round($task->total_minutes / 60, 2)
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name
                    ],
                    'work_time' => [
                        'today' => [
                            'minutes' => $todayWorkTime,
                            'hours' => round($todayWorkTime / 60, 2)
                        ],
                        'weekly' => [
                            'minutes' => $weeklyWorkTime,
                            'hours' => round($weeklyWorkTime / 60, 2),
                            'days_worked' => DB::table('task_time_logs')
                                ->where('user_id', $userId)
                                ->whereBetween('start_time', [$startOfWeek, $endOfWeek])
                                ->distinct(DB::raw('DATE(start_time)'))
                                ->count()
                        ],
                        'monthly' => [
                            'minutes' => $monthlyWorkTime,
                            'hours' => round($monthlyWorkTime / 60, 2),
                            'days_worked' => DB::table('task_time_logs')
                                ->where('user_id', $userId)
                                ->whereBetween('start_time', [$startOfMonth, $endOfMonth])
                                ->distinct(DB::raw('DATE(start_time)'))
                                ->count()
                        ]
                    ],
                    'tasks_worked_on' => $recentTasks
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('User task work time error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du temps de travail'
            ], 500);
        }
    }

    /**
     * Historique du temps de travail (avec pagination)
     */
    public function userWorkHistory($userId): JsonResponse
    {
        try {
            if (!Schema::hasTable('work_times')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Les données de temps de travail ne sont pas disponibles'
                ], 404);
            }

            // Pagination
            $perPage = request('per_page', 20);
            $page = request('page', 1);

            $workTimes = DB::table('work_times')
                ->where('user_id', $userId)
                ->orderBy('work_date', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            $data = $workTimes->map(function ($workTime) {
                return [
                    'id' => $workTime->id,
                    'work_date' => $workTime->work_date,
                    'day_name' => Carbon::parse($workTime->work_date)->locale('fr')->dayName,
                    'start_time' => $workTime->start_time,
                    'end_time' => $workTime->end_time,
                    'pause_start' => $workTime->pause_start,
                    'pause_end' => $workTime->pause_end,
                    'total_seconds' => $workTime->total_seconds,
                    'total_hours' => round($workTime->total_seconds / 3600, 2),
                    'net_seconds' => $workTime->net_seconds,
                    'net_hours' => round($workTime->net_seconds / 3600, 2),
                    'pause_seconds' => $workTime->pause_seconds,
                    'pause_hours' => round($workTime->pause_seconds / 3600, 2),
                    'status' => $workTime->status,
                    'notes' => $workTime->notes,
                    'created_at' => $workTime->created_at
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'work_times' => $data,
                    'pagination' => [
                        'current_page' => $workTimes->currentPage(),
                        'per_page' => $workTimes->perPage(),
                        'total' => $workTimes->total(),
                        'last_page' => $workTimes->lastPage(),
                        'from' => $workTimes->firstItem(),
                        'to' => $workTimes->lastItem()
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('User work history error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'historique'
            ], 500);
        }
    }

    /**
     * Statistiques générales - UTILISE LES NOMS DE TABLES CORRECTS
     */
    private function getOverviewStats(): array
    {
        return [
            'total_users' => DB::table('users')->count(),
            'total_tasks' => DB::table('tasks')->count(),
            'total_projects' => DB::table('projects')->count(),
            'total_teams' => DB::table('teams')->count(),
            'total_comments' => DB::table('task_comments')->count(),
            'total_files' => DB::table('task_files')->count(),
            'active_users' => $this->getActiveUsersCount(),
            'pending_tasks' => DB::table('tasks')->where('status', '!=', 'done')->count(),
        ];
    }

    /**
     * Compter les utilisateurs actifs
     */
    private function getActiveUsersCount(): int
    {
        // Vérifier si la colonne last_login_at existe
        $columns = DB::getSchemaBuilder()->getColumnListing('users');

        if (in_array('last_login_at', $columns)) {
            return DB::table('users')->where('last_login_at', '>=', Carbon::now()->subDays(7))->count();
        }

        // Fallback: utiliser created_at
        return DB::table('users')->where('created_at', '>=', Carbon::now()->subDays(30))->count();
    }

    /**
     * Statistiques des tâches par statut
     */
    private function getTaskStatistics(): array
    {
        $totalTasks = DB::table('tasks')->count();

        $statusStats = DB::table('tasks')
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) use ($totalTasks) {
                $percentage = $totalTasks > 0 ? round(($item->count / $totalTasks) * 100, 1) : 0;
                return [
                    $item->status => [
                        'count' => $item->count,
                        'percentage' => $percentage,
                        'label' => $this->getStatusLabel($item->status)
                    ]
                ];
            })
            ->toArray();

        // Statistiques de priorité
        $priorityStats = DB::table('tasks')
            ->select('priority', DB::raw('COUNT(*) as count'))
            ->whereNotNull('priority')
            ->groupBy('priority')
            ->get()
            ->mapWithKeys(fn($item) => [$item->priority => $item->count])
            ->toArray();

        // Tâches en retard
        $overdueTasks = DB::table('tasks')
            ->where('due_date', '<', Carbon::now())
            ->where('status', '!=', 'done')
            ->count();

        // Tâches à venir (dans les 7 prochains jours)
        $upcomingTasks = DB::table('tasks')
            ->where('start_date', '>', Carbon::now())
            ->where('start_date', '<=', Carbon::now()->addDays(7))
            ->count();

        // Temps total estimé (en minutes)
        $totalEstimatedTime = DB::table('tasks')->sum('estimated_time');

        // Temps total travaillé (via time logs)
        $totalWorkedTime = DB::table('task_time_logs')
            ->join('tasks', 'task_time_logs.task_id', '=', 'tasks.id')
            ->select(DB::raw('COALESCE(SUM(task_time_logs.duration), 0) as total'))
            ->first()->total ?? 0;

        // Tâches complétées ce mois-ci
        $completedThisMonth = DB::table('tasks')
            ->where('status', 'done')
            ->whereMonth('updated_at', Carbon::now()->month)
            ->whereYear('updated_at', Carbon::now()->year)
            ->count();

        return [
            'status_distribution' => $statusStats,
            'priority_distribution' => $priorityStats,
            'overdue_tasks' => $overdueTasks,
            'upcoming_tasks' => $upcomingTasks,
            'total_estimated_time' => $totalEstimatedTime,
            'total_worked_time' => $totalWorkedTime,
            'completed_this_month' => $completedThisMonth,
            'completion_rate' => $totalTasks > 0 ?
                round(($statusStats['done']['count'] ?? 0) / $totalTasks * 100, 1) : 0,
        ];
    }

    /**
     * Statistiques des utilisateurs - UTILISE DB::TABLE POUR ÉVITER LES ENUMS
     */
    private function getUserStatistics(): array
    {
        $totalUsers = DB::table('users')->count();

        // Distribution par rôle (requête brute pour éviter les enums)
        $roleStats = DB::table('users')
            ->select('role', DB::raw('COUNT(*) as count'))
            ->groupBy('role')
            ->get()
            ->mapWithKeys(function ($item) use ($totalUsers) {
                $percentage = $totalUsers > 0 ? round(($item->count / $totalUsers) * 100, 1) : 0;
                return [
                    $item->role => [
                        'count' => $item->count,
                        'percentage' => $percentage,
                        'label' => $this->getRoleLabel($item->role)
                    ]
                ];
            })
            ->toArray();

        // Utilisateurs actifs ce mois-ci (créés ou mis à jour)
        $activeUsersThisMonth = DB::table('users')
            ->where('created_at', '>=', Carbon::now()->startOfMonth())
            ->orWhere('updated_at', '>=', Carbon::now()->startOfMonth())
            ->count();

        // Nouveaux utilisateurs cette semaine
        $newUsersThisWeek = DB::table('users')
            ->where('created_at', '>=', Carbon::now()->startOfWeek())
            ->count();

        // Tâches par utilisateur
        $tasksPerUser = DB::table('tasks')
            ->select('assigned_to', DB::raw('COUNT(*) as task_count'))
            ->whereNotNull('assigned_to')
            ->groupBy('assigned_to')
            ->get()
            ->mapWithKeys(fn($item) => [$item->assigned_to => $item->task_count])
            ->toArray();

        // Utilisateurs avec email vérifié
        $verifiedUsers = DB::table('users')->whereNotNull('email_verified_at')->count();

        return [
            'total_users' => $totalUsers,
            'role_distribution' => $roleStats,
            'active_users_this_month' => $activeUsersThisMonth,
            'new_users_this_week' => $newUsersThisWeek,
            'tasks_per_user' => $tasksPerUser,
            'verified_users' => $verifiedUsers,
            'unverified_users' => $totalUsers - $verifiedUsers,
        ];
    }

    /**
     * Activité récente - UTILISE DB::TABLE POUR ÉVITER LES MODÈLES
     */
    private function getRecentActivity(): array
    {
        // Tâches récentes
        $recentTasks = DB::table('tasks')
            ->leftJoin('users', 'tasks.assigned_to', '=', 'users.id')
            ->leftJoin('projects', 'tasks.project_id', '=', 'projects.id')
            ->select(
                'tasks.id',
                'tasks.title',
                'tasks.status',
                'tasks.priority',
                'tasks.created_at',
                'users.name as assigned_name',
                'projects.name as project_name'
            )
            ->orderBy('tasks.created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'status' => $task->status,
                    'priority' => $task->priority,
                    'assigned_to' => $task->assigned_name ? ['name' => $task->assigned_name] : null,
                    'project' => $task->project_name ? ['name' => $task->project_name] : null,
                    'created_at' => Carbon::parse($task->created_at)->diffForHumans(),
                ];
            });

        // Commentaires récents
        $recentComments = DB::table('task_comments')
            ->join('users', 'task_comments.user_id', '=', 'users.id')
            ->join('tasks', 'task_comments.task_id', '=', 'tasks.id')
            ->select(
                'task_comments.id',
                'task_comments.content',
                'task_comments.created_at',
                'users.name as user_name',
                'tasks.title as task_title'
            )
            ->orderBy('task_comments.created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($comment) {
                return [
                    'id' => $comment->id,
                    'content' => strlen($comment->content) > 100 ?
                        substr($comment->content, 0, 100) . '...' : $comment->content,
                    'user' => [
                        'name' => $comment->user_name ?? 'Utilisateur inconnu'
                    ],
                    'task' => [
                        'id' => null, // À adapter si besoin
                        'title' => $comment->task_title ?? 'Tâche inconnue'
                    ],
                    'created_at' => Carbon::parse($comment->created_at)->diffForHumans(),
                ];
            });

        // Fichiers récents
        $recentFiles = DB::table('task_files')
            ->join('users', 'task_files.uploaded_by', '=', 'users.id')
            ->join('tasks', 'task_files.task_id', '=', 'tasks.id')
            ->select(
                'task_files.id',
                'task_files.file_name',
                'task_files.file_size',
                'task_files.extension',
                'task_files.created_at',
                'users.name as uploader_name',
                'tasks.title as task_title'
            )
            ->orderBy('task_files.created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($file) {
                // Formatter la taille du fichier
                $size = $file->file_size;
                $formattedSize = '';
                if ($size >= 1073741824) {
                    $formattedSize = number_format($size / 1073741824, 2) . ' GB';
                } elseif ($size >= 1048576) {
                    $formattedSize = number_format($size / 1048576, 2) . ' MB';
                } elseif ($size >= 1024) {
                    $formattedSize = number_format($size / 1024, 2) . ' KB';
                } else {
                    $formattedSize = $size . ' bytes';
                }

                return [
                    'id' => $file->id,
                    'file_name' => $file->file_name,
                    'file_size' => $formattedSize,
                    'uploader' => [
                        'name' => $file->uploader_name ?? 'Utilisateur inconnu'
                    ],
                    'task' => [
                        'title' => $file->task_title ?? 'Tâche inconnue'
                    ],
                    'created_at' => Carbon::parse($file->created_at)->diffForHumans(),
                ];
            });

        return [
            'recent_tasks' => $recentTasks,
            'recent_comments' => $recentComments,
            'recent_files' => $recentFiles,
        ];
    }

    /**
     * Statistiques des équipes - CORRIGÉ (table projects au lieu de projects_teams)
     */
    private function getTeamStatistics(): array
    {
        $totalTeams = DB::table('teams')->count();

        // Tâches par équipe - CORRECTION: utiliser 'projects' au lieu de 'projects_teams'
        $tasksPerTeam = DB::table('projects')
            ->leftJoin('tasks', 'projects.id', '=', 'tasks.project_id')
            ->select(
                'projects.team_id',
                DB::raw('COUNT(tasks.id) as task_count')
            )
            ->groupBy('projects.team_id')
            ->get()
            ->mapWithKeys(fn($item) => [$item->team_id => $item->task_count])
            ->toArray();

        // Projets par équipe
        $projectsPerTeam = DB::table('projects')
            ->select('team_id', DB::raw('COUNT(*) as project_count'))
            ->groupBy('team_id')
            ->get()
            ->mapWithKeys(fn($item) => [$item->team_id => $item->project_count])
            ->toArray();

        // Membres par équipe
        $membersPerTeam = DB::table('teams')
            ->leftJoin('team_members', 'teams.id', '=', 'team_members.team_id')
            ->select(
                'teams.id',
                DB::raw('COUNT(team_members.user_id) as members_count')
            )
            ->groupBy('teams.id')
            ->get()
            ->mapWithKeys(fn($team) => [$team->id => $team->members_count])
            ->toArray();

        return [
            'total_teams' => $totalTeams,
            'tasks_per_team' => $tasksPerTeam,
            'projects_per_team' => $projectsPerTeam,
            'members_per_team' => $membersPerTeam,
            'public_teams' => DB::table('teams')->where('is_public', true)->count(),
            'private_teams' => DB::table('teams')->where('is_public', false)->count(),
        ];
    }

    /**
     * Analytics hebdomadaires
     */
    private function getWeeklyAnalytics(): array
    {
        $startDate = Carbon::now()->subDays(6)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        // Tâches créées par jour
        $tasksCreated = DB::table('tasks')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        // Commentaires par jour
        $commentsCreated = DB::table('task_comments')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        // Fichiers uploadés par jour
        $filesUploaded = DB::table('task_files')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        // Préparer les données pour les 7 derniers jours
        $dates = [];
        $tasksData = [];
        $commentsData = [];
        $filesData = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->toDateString();
            $dates[] = Carbon::parse($date)->format('d/m');

            $tasksData[] = $tasksCreated->firstWhere('date', $date)->count ?? 0;
            $commentsData[] = $commentsCreated->firstWhere('date', $date)->count ?? 0;
            $filesData[] = $filesUploaded->firstWhere('date', $date)->count ?? 0;
        }

        return [
            'labels' => $dates,
            'tasks' => $tasksData,
            'comments' => $commentsData,
            'files' => $filesData,
            'weekly_tasks_completed' => DB::table('tasks')
                ->where('status', 'done')
                ->whereBetween('updated_at', [$startDate, $endDate])
                ->count(),
        ];
    }

    /**
     * Analytics mensuels
     */
    private function getMonthlyAnalytics(): array
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        // Tâches par statut ce mois-ci
        $monthlyTaskStatus = DB::table('tasks')
            ->select('status', DB::raw('COUNT(*) as count'))
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn($item) => [$item->status => $item->count])
            ->toArray();

        // Utilisateurs créés ce mois-ci
        $monthlyUsers = DB::table('users')
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->count();

        return [
            'monthly_task_status' => $monthlyTaskStatus,
            'monthly_users' => $monthlyUsers,
            'monthly_completion_rate' => $this->calculateMonthlyCompletionRate(),
        ];
    }

    /**
     * Top performers
     */
    private function getTopPerformers(): array
    {
        // Utilisateurs avec le plus de tâches complétées
        $topTaskCompleters = DB::table('tasks')
            ->select('assigned_to', DB::raw('COUNT(*) as completed_count'))
            ->where('status', 'done')
            ->whereNotNull('assigned_to')
            ->groupBy('assigned_to')
            ->orderBy('completed_count', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                $user = DB::table('users')->where('id', $item->assigned_to)->first();
                return [
                    'user_id' => $item->assigned_to,
                    'name' => $user ? $user->name : 'Utilisateur inconnu',
                    'completed_tasks' => $item->completed_count,
                ];
            });

        // Utilisateurs les plus actifs (commentaires)
        $topCommenters = DB::table('task_comments')
            ->select('user_id', DB::raw('COUNT(*) as comment_count'))
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->groupBy('user_id')
            ->orderBy('comment_count', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                $user = DB::table('users')->where('id', $item->user_id)->first();
                return [
                    'user_id' => $item->user_id,
                    'name' => $user ? $user->name : 'Utilisateur inconnu',
                    'comment_count' => $item->comment_count,
                ];
            });

        // Utilisateurs avec le plus de fichiers uploadés
        $topFileUploaders = DB::table('task_files')
            ->select('uploaded_by', DB::raw('COUNT(*) as file_count'))
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->groupBy('uploaded_by')
            ->orderBy('file_count', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                $user = DB::table('users')->where('id', $item->uploaded_by)->first();
                return [
                    'user_id' => $item->uploaded_by,
                    'name' => $user ? $user->name : 'Utilisateur inconnu',
                    'file_count' => $item->file_count,
                ];
            });

        return [
            'top_task_completers' => $topTaskCompleters,
            'top_commenters' => $topCommenters,
            'top_file_uploaders' => $topFileUploaders,
        ];
    }

    /**
     * Calculer le taux d'achèvement mensuel
     */
    private function calculateMonthlyCompletionRate(): float
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $totalTasks = DB::table('tasks')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();

        $completedTasks = DB::table('tasks')
            ->where('status', 'done')
            ->whereBetween('updated_at', [$startOfMonth, $endOfMonth])
            ->count();

        return $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 1) : 0;
    }

    /**
     * Obtenir le label d'un statut
     */
    private function getStatusLabel(string $status): string
    {
        $labels = [
            'backlog' => 'Backlog',
            'todo' => 'À faire',
            'doing' => 'En cours',
            'done' => 'Terminé',
        ];

        return $labels[$status] ?? ucfirst($status);
    }

    /**
     * Obtenir le label d'un rôle
     */
    private function getRoleLabel(string $role): string
    {
        $labels = [
            'admin' => 'Administrateur',
            'manager' => 'Manager',
            'user' => 'Utilisateur',
        ];

        return $labels[$role] ?? ucfirst($role);
    }

    /**
     * Statistiques simplifiées pour widgets
     */
    public function widgetStats(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => [
                    'quick_stats' => [
                        'total_users' => DB::table('users')->count(),
                        'total_tasks' => DB::table('tasks')->count(),
                        'completed_tasks' => DB::table('tasks')->where('status', 'done')->count(),
                        'pending_tasks' => DB::table('tasks')->where('status', '!=', 'done')->count(),
                        'total_projects' => DB::table('projects')->count(),
                        'active_projects' => DB::table('projects')->where('status', 'active')->count(),
                        'total_comments' => DB::table('task_comments')->count(),
                        'total_files' => DB::table('task_files')->count(),
                    ],
                    'status_distribution' => DB::table('tasks')
                        ->select('status', DB::raw('COUNT(*) as count'))
                        ->groupBy('status')
                        ->get()
                        ->map(fn($item) => [
                            'status' => $this->getStatusLabel($item->status),
                            'count' => $item->count
                        ])
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Statistiques pour un utilisateur spécifique
     */
    public function userStats($userId): JsonResponse
    {
        try {
            $user = DB::table('users')->where('id', $userId)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'user_info' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'joined_at' => Carbon::parse($user->created_at)->format('d/m/Y'),
                    ],
                    'task_stats' => [
                        'assigned_tasks' => DB::table('tasks')->where('assigned_to', $userId)->count(),
                        'completed_tasks' => DB::table('tasks')
                            ->where('assigned_to', $userId)
                            ->where('status', 'done')->count(),
                        'in_progress_tasks' => DB::table('tasks')
                            ->where('assigned_to', $userId)
                            ->where('status', 'doing')->count(),
                        'overdue_tasks' => DB::table('tasks')
                            ->where('assigned_to', $userId)
                            ->where('due_date', '<', Carbon::now())
                            ->where('status', '!=', 'done')
                            ->count(),
                    ],
                    'contribution_stats' => [
                        'comments_count' => DB::table('task_comments')->where('user_id', $userId)->count(),
                        'files_uploaded' => DB::table('task_files')->where('uploaded_by', $userId)->count(),
                        'teams_count' => DB::table('team_members')->where('user_id', $userId)->count(),
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
}