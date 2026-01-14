<?php

namespace App\Services\Dashboard;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\WorkTime;
use Modules\User\Domain\Entities\User;
use Modules\Team\Domain\Entities\Team;
use Modules\Task\Domain\Entities\Task;
use Modules\ProjectsTeams\Domain\Entities\ProjectsTeams;

class AdminDashboardService
{
    protected $helperService;

    public function __construct(DashboardHelperService $helperService)
    {
        $this->helperService = $helperService;
    }

    public function getData(User $admin): array
    {
        $today = Carbon::today();

        return [
            'role' => 'admin',
            'summary' => $this->getSummaryStats(),
            'today_work' => $this->getTodayWorkStats($today),
            'active_users' => $this->helperService->getActiveUsersMetrics(),
            'recent_projects' => $this->getRecentProjects(),
            'team_performance' => $this->helperService->getTeamPerformanceMetrics(),
            'weekly_summary' => $this->helperService->getWeeklySummary($today, 7),
            'user' => $this->getUserData($admin),
        ];
    }

    private function getSummaryStats(): array
    {
        return [
            'total_users' => User::count(),
            'total_teams' => Team::count(),
            'total_projects' => ProjectsTeams::count(),
            'total_active_tasks' => Task::where('status', '!=', Task::STATUS_DONE)->count(),
            'users_working_today' => WorkTime::whereDate('work_date', Carbon::today())->count(),
        ];
    }

    private function getTodayWorkStats(Carbon $today): array
    {
        $todayWorkTimes = WorkTime::with(['user', 'sessions'])
            ->whereDate('work_date', $today)
            ->get()
            ->map(function ($workTime) {
                // FORCER le recalcul avant de retourner les données
                $workTime->calculateTotalTime();
                $workTime->refresh();

                return [
                    'user_name' => $workTime->user->name ?? 'Utilisateur inconnu',
                    'net_hours' => round($workTime->net_hours, 2),  // Arrondir à 2 décimales
                    'progress_percentage' => $workTime->progress_percentage ?? 0,
                    'is_within_schedule' => $workTime->is_within_schedule ?? false,
                    'has_sessions' => $workTime->sessions->count() > 0,
                    'active_sessions' => $workTime->sessions->whereNull('session_end')->count(),
                ];
            });

        // Calculer les totaux après recalcul
        $totalWorkTimeToday = $todayWorkTimes->sum(function ($wt) {
            return $wt['net_hours'];
        });

        $averageWorkTimeToday = $todayWorkTimes->count() > 0
            ? $totalWorkTimeToday / $todayWorkTimes->count()
            : 0;

        return [
            'total_hours' => round($totalWorkTimeToday, 2),
            'average_hours_per_user' => round($averageWorkTimeToday, 2),
            'work_times' => $todayWorkTimes,
        ];
    }

    private function getRecentProjects(): array
    {
        return ProjectsTeams::with(['team', 'tasks'])
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
            })
            ->toArray();
    }

    private function getUserData(User $admin): array
    {
        return [
            'id' => $admin->id,
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => $admin->role->value,
            'avatar' => $admin->avatar_url ?? null,
            'initials' => $admin->initials ?? strtoupper(substr($admin->name, 0, 2)),
        ];
    }

    public function getTaskStatusStatsResponse($request)
    {
        $user = auth()->user();
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
}
