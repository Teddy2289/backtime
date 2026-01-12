<?php

namespace App\Services\Dashboard;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use App\Models\WorkTime;
use Modules\User\Domain\Entities\User;
use Modules\Task\Domain\Entities\Task;
use Modules\Team\Domain\Entities\Team;
use Modules\ProjectsTeams\Domain\Entities\ProjectsTeams;
use Modules\TaskTimeLog\Domain\Entities\TaskTimeLog;

class UserDashboardService
{
    protected $helperService;

    public function __construct(DashboardHelperService $helperService)
    {
        $this->helperService = $helperService;
    }

    public function getData(User $user): array
    {
        $userTeams = $user->teams()->with(['projects.tasks'])->get();

        if ($userTeams->isEmpty()) {
            return $this->getDataWithoutTeams($user);
        }

        return [
            'role' => $user->role->value,
            'summary' => $this->getSummaryStats($user, $userTeams),
            'today_work' => $this->getTodayWorkData($user),
            'weekly_summary' => $this->helperService->getUserWeeklySummary($user, Carbon::today(), 7),
            'task_statistics' => $this->getTaskStatistics($user),
            'active_time_logs' => $this->getActiveTimeLogs($user),
            'recent_activity' => $this->getRecentActivity($user),
            'teams' => $this->getTeamInfo($user, $userTeams),
            'upcoming_deadlines' => $this->getUpcomingDeadlines($user, $userTeams),
            'current_tasks' => $this->getCurrentTasks($user, $userTeams),
            'user' => $this->getUserData($user),
        ];
    }

    public function getDataWithoutTeams(User $user): array
    {
        $today = Carbon::today();

        // Today's work time
        $todayWorkTime = WorkTime::with('sessions')
            ->where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->first();

        // Weekly work summary
        $weeklySummary = $this->helperService->getUserWeeklySummary($user, $today, 7);

        // Tasks assigned directly to user (sans équipe)
        $userTasks = Task::where('assigned_to', $user->id)
            ->with(['project', 'assignedUser', 'timeLogs' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }])
            ->get();

        // Task statistics
        $taskStats = $this->helperService->getTaskStatuses($userTasks);

        return [
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
                })
                ->toArray(),
            'user' => $this->getUserData($user),
        ];
    }

    private function getSummaryStats(User $user, Collection $userTeams): array
    {
        // Get all projects from user's teams
        $projectIds = $userTeams->flatMap->projects->pluck('id')->unique();

        // Get tasks assigned to user in their team projects
        $userTasks = Task::whereIn('project_id', $projectIds)
            ->where(function ($query) use ($user) {
                $query->where('assigned_to', $user->id)
                    ->orWhereHas('timeLogs', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    });
            })
            ->get();

        $taskStats = $this->helperService->getTaskStatuses($userTasks);

        return [
            'total_tasks' => $taskStats['total'],
            'active_tasks' => $taskStats['doing'],
            'overdue_tasks' => $taskStats['overdue'],
            'completed_tasks' => $taskStats['done'],
            'teams_count' => $userTeams->count(),
            'projects_count' => $projectIds->count(),
        ];
    }

    private function getTodayWorkData(User $user): ?array
    {
        $today = Carbon::today();
        $todayWorkTime = WorkTime::with('sessions')
            ->where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->first();

        if (!$todayWorkTime) {
            return null;
        }

        return [
            'net_hours' => $todayWorkTime->net_hours,
            'pause_hours' => $todayWorkTime->pause_hours,
            'total_hours' => $todayWorkTime->total_hours,
            'progress_percentage' => $todayWorkTime->progress_percentage,
            'is_within_schedule' => $todayWorkTime->is_within_schedule,
            'sessions_count' => $todayWorkTime->sessions->count(),
            'daily_target' => $todayWorkTime->daily_target_hours,
        ];
    }

    private function getTaskStatistics(User $user): array
    {
        $userTeams = $user->teams()->get();

        if ($userTeams->isEmpty()) {
            $userTasks = Task::where('assigned_to', $user->id)->get();
        } else {
            $projectIds = $userTeams->flatMap(function ($team) {
                return $team->projects()->pluck('id');
            })->unique();

            $userTasks = Task::whereIn('project_id', $projectIds)
                ->where(function ($query) use ($user) {
                    $query->where('assigned_to', $user->id)
                        ->orWhereHas('timeLogs', function ($q) use ($user) {
                            $q->where('user_id', $user->id);
                        });
                })
                ->get();
        }

        return $this->helperService->getTaskStatuses($userTasks);
    }

    private function getActiveTimeLogs(User $user): array
    {
        return TaskTimeLog::where('user_id', $user->id)
            ->whereNull('end_time')
            ->with('task')
            ->get()
            ->map(function ($timeLog) {
                return [
                    'id' => $timeLog->id,
                    'task_id' => $timeLog->task_id,
                    'task_title' => $timeLog->task->title ?? 'Tâche inconnue',
                    'start_time' => $timeLog->start_time->format('H:i'),
                    'duration' => $timeLog->start_time->diffInMinutes(now()) . 'm',
                ];
            })
            ->toArray();
    }

    private function getRecentActivity(User $user): array
    {
        return TaskTimeLog::where('user_id', $user->id)
            ->with(['task', 'task.project'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($timeLog) {
                return [
                    'id' => $timeLog->id,
                    'task_title' => $timeLog->task->title ?? 'Tâche inconnue',
                    'project_name' => $timeLog->task->project->name ?? 'Projet inconnu',
                    'duration' => $timeLog->formatted_duration ?? '0m',
                    'note' => $timeLog->note,
                    'date' => $timeLog->created_at->format('Y-m-d H:i'),
                    'is_running' => $timeLog->is_running ?? false,
                ];
            })
            ->toArray();
    }

    private function getTeamInfo(User $user, Collection $userTeams): array
    {
        return $userTeams->map(function ($team) use ($user) {
            $teamProjects = $team->projects;
            $teamTasks = $teamProjects->flatMap->tasks;

            return [
                'id' => $team->id,
                'name' => $team->name,
                'description' => $team->description,
                'is_owner' => $team->isOwner($user->id) ?? false,
                'member_count' => $team->members()->count(),
                'project_count' => $teamProjects->count(),
                'active_tasks_count' => $teamTasks->where('status', Task::STATUS_DOING)->count(),
            ];
        })
            ->toArray();
    }

    private function getUpcomingDeadlines(User $user, Collection $userTeams): array
    {
        $today = Carbon::today();
        $projectIds = $userTeams->flatMap->projects->pluck('id')->unique();

        return Task::whereIn('project_id', $projectIds)
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
            })
            ->toArray();
    }

    private function getCurrentTasks(User $user, Collection $userTeams): array
    {
        $projectIds = $userTeams->flatMap->projects->pluck('id')->unique();

        $userTasks = Task::whereIn('project_id', $projectIds)
            ->where(function ($query) use ($user) {
                $query->where('assigned_to', $user->id)
                    ->orWhereHas('timeLogs', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    });
            })
            ->with(['project'])
            ->get();

        return $userTasks->where('status', Task::STATUS_DOING)
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
            })
            ->toArray();
    }

    private function getUserData(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar_url ?? null,
            'initials' => $user->initials ?? strtoupper(substr($user->name, 0, 2)),
        ];
    }

    public function getMonthlySummaryResponse($request)
    {
        $user = auth()->user();
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
                'day_name' => $workTime->day_name ?? Carbon::parse($workTime->work_date)->locale('fr')->dayName,
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
}
