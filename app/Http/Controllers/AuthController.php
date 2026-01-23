<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\User\Domain\Entities\User;
use Modules\User\Domain\Enums\UserRole;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Throwable;
use App\Http\Requests\UpdateProfileRequest;
use Carbon\Carbon;
use Modules\Task\Domain\Entities\Task;

class AuthController extends Controller
{
    use ApiResponser;

    /**
     * Login user and get JWT token.
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string|min:6',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $credentials = $request->only('email', 'password');

            if (!$token = auth()->attempt($credentials)) {
                return $this->unauthorizedResponse('Invalid credentials');
            }

            $user = auth()->user();
            return $this->authResponse($token, $user, 'Login successful');
        } catch (Throwable $e) {
            return $this->errorResponse(
                'Login failed',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Register a new user.
     */
    public function register(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|between:2,100',
                'email' => 'required|string|email|max:100|unique:users',
                'password' => 'required|string|confirmed|min:6',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $user = User::create(array_merge(
                $validator->validated(),
                [
                    'password' => Hash::make($request->password),
                    'role' => UserRole::USER->value,
                ]
            ));

            // Ensure role exists
            $this->ensureRoleExists(UserRole::USER->value);

            // Assign role
            $user->assignRole(UserRole::USER->value);

            $token = auth()->login($user);

            DB::commit();

            return $this->authResponse($token, $user, 'Registration successful')
                ->setStatusCode(201);
        } catch (Throwable $e) {
            DB::rollBack();

            return $this->errorResponse(
                'Registration failed',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Logout user.
     */
    public function logout()
    {
        try {
            auth()->logout();

            return $this->successResponse(
                null,
                'Successfully logged out'
            );
        } catch (Throwable $e) {
            return $this->errorResponse(
                'Logout failed',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Refresh JWT token.
     */
    public function refresh()
    {
        try {
            $token = auth()->refresh();
            $user = auth()->user();

            return $this->authResponse($token, $user, 'Token refreshed');
        } catch (Throwable $e) {
            return $this->errorResponse(
                'Token refresh failed',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Get authenticated user.
     */
    public function me()
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return $this->unauthorizedResponse();
            }

            return $this->successResponse(
                $user,
                'User retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->errorResponse(
                'Failed to get user data',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Ensure role exists in database.
     */
    protected function ensureRoleExists(string $roleName): void
    {
        try {
            $role = \Spatie\Permission\Models\Role::where('name', $roleName)
                ->where('guard_name', 'api')
                ->first();

            if (!$role) {
                \Spatie\Permission\Models\Role::create([
                    'name' => $roleName,
                    'guard_name' => 'api',
                ]);
            }
        } catch (Throwable $e) {
            // Log error but don't crash registration
            Log::error('Failed to ensure role exists: ' . $e->getMessage());
        }
    }

    public function updateProfile(UpdateProfileRequest  $request)
    {
        DB::beginTransaction();

        try {
            $user = auth()->user();

            if (!$user) {
                return $this->unauthorizedResponse();
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|between:2,100',
                'email' => 'sometimes|string|email|max:100|unique:users,email,' . $user->id,
                'current_password' => 'required_with:password',
                'password' => 'sometimes|string|min:6|confirmed',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            // Vérifier le mot de passe actuel si changement de mot de passe
            if ($request->has('password')) {
                if (!Hash::check($request->current_password, $user->password)) {
                    return $this->errorResponse('Current password is incorrect', null, 422);
                }

                $user->password = Hash::make($request->password);
            }

            // Mettre à jour les autres champs
            if ($request->has('name')) {
                $user->name = $request->name;
            }

            if ($request->has('email')) {
                $user->email = $request->email;
            }

            $user->save();

            DB::commit();

            return $this->successResponse(
                $user,
                'Profile updated successfully'
            );
        } catch (Throwable $e) {
            DB::rollBack();

            return $this->errorResponse(
                'Failed to update profile',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Upload user avatar.
     */
    public function uploadAvatar(Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return $this->unauthorizedResponse();
            }

            $validator = Validator::make($request->all(), [
                'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:10240', // Corrigé: 10240 au lieu de 10102400
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            // Supprimer l'ancien avatar si existe
            if ($user->avatar) {
                $oldAvatarPath = 'avatars/' . $user->avatar;
                if (Storage::disk('public')->exists($oldAvatarPath)) {
                    Storage::disk('public')->delete($oldAvatarPath);
                }
            }

            // Upload le nouvel avatar
            $avatar = $request->file('avatar');
            $filename = 'avatar_' . $user->id . '_' . time() . '.' . $avatar->getClientOriginalExtension();

            // Stocker dans storage/app/public/avatars
            $path = $avatar->storeAs('avatars', $filename, 'public');

            // Mettre à jour l'utilisateur
            $user->avatar = $filename;
            $user->save();

            return $this->successResponse(
                [
                    'avatar_url' => $user->avatar_url,
                    'filename' => $filename,
                ],
                'Avatar uploaded successfully'
            );
        } catch (Throwable $e) {
            return $this->errorResponse(
                'Failed to upload avatar',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Remove user avatar.
     */
    public function removeAvatar()
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return $this->unauthorizedResponse();
            }

            if (!$user->avatar) {
                return $this->errorResponse('No avatar to remove', null, 404);
            }

            // Supprimer le fichier
            $avatarPath = 'avatars/' . $user->avatar;
            if (Storage::disk('public')->exists($avatarPath)) {
                Storage::disk('public')->delete($avatarPath);
            }

            // Mettre à jour l'utilisateur
            $user->avatar = null;
            $user->save();

            return $this->successResponse(
                null,
                'Avatar removed successfully'
            );
        } catch (Throwable $e) {
            return $this->errorResponse(
                'Failed to remove avatar',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Get user profile with more details.
     */
    public function profile()
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return $this->unauthorizedResponse();
            }

            // Charger les relations si nécessaire
            $user->load(['roles', 'permissions']);

            // Convertir le rôle en string pour éviter les problèmes de sérialisation
            $role = $user->role;

            $profileData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $role instanceof UserRole ? $role->value : $role,
                'role_label' => $role instanceof UserRole ? $role->label() : null,
                'avatar' => $user->avatar,
                'avatar_url' => $user->avatar_url,
                'initials' => $user->initials,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ];

            return $this->successResponse(
                $profileData,
                'Profile retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->errorResponse(
                'Failed to get profile',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Change user password (fonction dédiée)
     */
    public function changePassword(Request $request)
    {
        DB::beginTransaction();

        try {
            $user = auth()->user();

            if (!$user) {
                return $this->unauthorizedResponse();
            }

            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:6|confirmed',
                'new_password_confirmation' => 'required|string|min:6',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            // Vérifier le mot de passe actuel
            if (!Hash::check($request->current_password, $user->password)) {
                return $this->errorResponse('Current password is incorrect', null, 422);
            }

            // Vérifier que le nouveau mot de passe est différent de l'actuel
            if (Hash::check($request->new_password, $user->password)) {
                return $this->errorResponse('New password must be different from current password', null, 422);
            }

            // Mettre à jour le mot de passe
            $user->password = Hash::make($request->new_password);
            $user->save();

            DB::commit();

            // Invalider les tokens JWT existants (optionnel - selon votre stratégie)
            // auth()->logout();

            return $this->successResponse(
                null,
                'Password changed successfully'
            );
        } catch (Throwable $e) {
            DB::rollBack();

            return $this->errorResponse(
                'Failed to change password',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }
    public function getWorkStatistics(Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return $this->unauthorizedResponse();
            }

            // Récupérer les paramètres de période
            $period = $request->input('period', 'month'); // month, week, year, all
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            // Définir les dates selon la période
            $dateRange = $this->getDateRange($period, $startDate, $endDate);

            // 1. Statistiques des heures de travail
            $workStats = $this->getWorkTimeStats($user, $dateRange);

            // 2. Statistiques des tâches (utilise assigned_to au lieu de created_by)
            $taskStats = $this->getTaskStats($user, $dateRange);

            // 3. Total général
            $totalStats = [
                'total_work_hours' => $workStats['total_hours'],
                'total_tasks_created' => $taskStats['total_created'],
                'total_tasks_assigned' => $taskStats['total_assigned'],
                'completed_tasks' => $taskStats['completed'],
                'productivity_score' => $this->calculateProductivityScore($workStats, $taskStats),
            ];

            $response = [
                'period' => [
                    'type' => $period,
                    'start_date' => $dateRange['start']->toDateString(),
                    'end_date' => $dateRange['end']->toDateString(),
                ],
                'work_statistics' => $workStats,
                'task_statistics' => $taskStats,
                'totals' => $totalStats,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role' => $user->role,
                ]
            ];

            return $this->successResponse(
                $response,
                'Work statistics retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->errorResponse(
                'Failed to get work statistics',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Get work time statistics for a user
     */
    private function getWorkTimeStats(User $user, array $dateRange): array
    {
        $workTimes = $user->workTimes()
            ->whereBetween('work_date', [$dateRange['start'], $dateRange['end']])
            ->get();

        // Si aucun workTime trouvé, retourner des valeurs par défaut
        if ($workTimes->isEmpty()) {
            return [
                'total_hours' => 0,
                'total_days' => 0,
                'average_daily_hours' => 0,
                'daily_target_hours' => 0,
                'extra_hours' => 0,
                'completion_rate' => 0,
                'days_with_exceeded_target' => 0,
                'daily_statistics' => [],
                'work_days_details' => [],
            ];
        }

        // Calcul des totaux
        $totalSeconds = $workTimes->sum('net_seconds');
        $totalHours = round($totalSeconds / 3600, 2);

        // Par jour de la semaine
        $dailyStats = $workTimes->groupBy(function ($item) {
            return Carbon::parse($item->work_date)->locale('fr')->dayName;
        })->map(function ($group) {
            return [
                'days_count' => $group->count(),
                'total_hours' => round($group->sum('net_seconds') / 3600, 2),
                'average_hours' => round($group->avg('net_seconds') / 3600, 2),
            ];
        });

        // Statistiques détaillées
        $stats = [
            'total_hours' => $totalHours,
            'total_days' => $workTimes->count(),
            'average_daily_hours' => $workTimes->count() > 0
                ? round($totalHours / $workTimes->count(), 2)
                : 0,
            'daily_target_hours' => round($workTimes->sum('daily_target_hours'), 2),
            'extra_hours' => round($workTimes->sum('extra_hours'), 2),
            'completion_rate' => $workTimes->count() > 0
                ? round($workTimes->avg('progress_percentage'), 2)
                : 0,
            'days_with_exceeded_target' => $workTimes->where('has_exceeded_target', true)->count(),
            'daily_statistics' => $dailyStats,
            'work_days_details' => $workTimes->map(function ($workTime) {
                return [
                    'date' => $workTime->work_date->toDateString(),
                    'day_name' => $workTime->day_name,
                    'total_hours' => $workTime->net_hours,
                    'target_hours' => $workTime->daily_target_hours,
                    'extra_hours' => $workTime->extra_hours,
                    'progress_percentage' => $workTime->progress_percentage,
                    'status' => $workTime->status,
                    'is_within_schedule' => $workTime->is_within_schedule,
                ];
            })->values(),
        ];

        return $stats;
    }

    /**
     * Get task statistics for a user
     */
    private function getTaskStats(User $user, array $dateRange): array
    {
        // Version 1: Utiliser assigned_to (comme dans votre schéma actuel)
        // Note: Si created_by n'existe pas, on utilise assigned_to pour toutes les statistiques

        // Tâches assignées à l'utilisateur (comme "créées" et "assignées")
        $assignedTasks = Task::where('assigned_to', $user->id)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->get();

        // Tâches complétées
        $completedTasks = Task::where('assigned_to', $user->id)
            ->where('status', Task::STATUS_DONE)
            ->whereBetween('updated_at', [$dateRange['start'], $dateRange['end']])
            ->get();

        // Tâches en retard
        $overdueTasks = Task::where('assigned_to', $user->id)
            ->where('status', '!=', Task::STATUS_DONE)
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->whereBetween('due_date', [$dateRange['start'], $dateRange['end']])
            ->get();

        // Par statut
        $tasksByStatus = $assignedTasks->groupBy('status')->map->count();

        // Par priorité
        $tasksByPriority = $assignedTasks->groupBy('priority')->map->count();

        return [
            'total_created' => $assignedTasks->count(), // Utiliser assigned_to comme proxy
            'total_assigned' => $assignedTasks->count(),
            'completed' => $completedTasks->count(),
            'overdue' => $overdueTasks->count(),
            'completion_rate' => $assignedTasks->count() > 0
                ? round(($completedTasks->count() / $assignedTasks->count()) * 100, 2)
                : 0,
            'overdue_rate' => $assignedTasks->count() > 0
                ? round(($overdueTasks->count() / $assignedTasks->count()) * 100, 2)
                : 0,
            'by_status' => $tasksByStatus,
            'by_priority' => $tasksByPriority,
            'assigned_tasks_details' => $assignedTasks->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'status' => $task->status,
                    'priority' => $task->priority,
                    'due_date' => $task->due_date?->toDateString(),
                    'is_overdue' => $task->is_overdue,
                    'progress' => $task->progress,
                    'created_at' => $task->created_at->toDateTimeString(),
                ];
            })->values(),
        ];
    }

    /**
     * Calculate productivity score
     */
    private function calculateProductivityScore(array $workStats, array $taskStats): float
    {
        $score = 0;

        // Score basé sur les heures travaillées (40%)
        if ($workStats['total_hours'] > 0) {
            $workScore = min(($workStats['total_hours'] / 160) * 40, 40); // 160h = 1 mois plein
            $score += $workScore;
        }

        // Score basé sur le taux de complétion des tâches (40%)
        $score += $taskStats['completion_rate'] * 0.4;

        // Score basé sur le respect des délais (20%)
        $timelinessScore = max(0, 100 - $taskStats['overdue_rate']) * 0.2;
        $score += $timelinessScore;

        return round(min($score, 100), 2);
    }

    /**
     * Get date range based on period
     */
    private function getDateRange(string $period, $startDate = null, $endDate = null): array
    {
        // Si les deux dates sont fournies, les utiliser
        if ($startDate && $endDate) {
            return [
                'start' => Carbon::parse($startDate)->startOfDay(),
                'end' => Carbon::parse($endDate)->endOfDay(),
            ];
        }

        // Si seule startDate est fournie, utiliser jusqu'à aujourd'hui
        if ($startDate && !$endDate) {
            return [
                'start' => Carbon::parse($startDate)->startOfDay(),
                'end' => Carbon::now()->endOfDay(),
            ];
        }

        // Si seule endDate est fournie, utiliser depuis le début du mois
        if (!$startDate && $endDate) {
            return [
                'start' => Carbon::parse($endDate)->startOfMonth(),
                'end' => Carbon::parse($endDate)->endOfDay(),
            ];
        }

        $now = Carbon::now();

        switch ($period) {
            case 'today':
                return [
                    'start' => $now->copy()->startOfDay(),
                    'end' => $now->copy()->endOfDay(),
                ];
            case 'week':
                return [
                    'start' => $now->copy()->startOfWeek(),
                    'end' => $now->copy()->endOfWeek(),
                ];
            case 'month':
                return [
                    'start' => $now->copy()->startOfMonth(),
                    'end' => $now->copy()->endOfMonth(),
                ];
            case 'year':
                return [
                    'start' => $now->copy()->startOfYear(),
                    'end' => $now->copy()->endOfYear(),
                ];
            case 'all':
                return [
                    'start' => Carbon::create(2000, 1, 1)->startOfDay(),
                    'end' => $now->copy()->endOfDay(),
                ];
            default:
                return [
                    'start' => $now->copy()->startOfMonth(),
                    'end' => $now->copy()->endOfMonth(),
                ];
        }
    }

    /**
     * Get detailed work sessions for a specific period
     */
    public function getWorkSessions(Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return $this->unauthorizedResponse();
            }

            // Si aucune date n'est fournie, utiliser le mois en cours
            if (!$request->has('start_date') && !$request->has('end_date')) {
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
            } else {
                // Valider seulement si les dates sont fournies
                $validator = Validator::make($request->all(), [
                    'start_date' => 'sometimes|date',
                    'end_date' => 'sometimes|date|after_or_equal:start_date',
                ]);

                if ($validator->fails()) {
                    return $this->validationErrorResponse($validator->errors());
                }

                $startDate = $request->has('start_date')
                    ? Carbon::parse($request->start_date)->startOfDay()
                    : Carbon::now()->startOfMonth();

                $endDate = $request->has('end_date')
                    ? Carbon::parse($request->end_date)->endOfDay()
                    : Carbon::now()->endOfMonth();
            }

            $workTimes = $user->workTimes()
                ->whereBetween('work_date', [$startDate, $endDate])
                ->with(['sessions' => function ($query) {
                    $query->orderBy('session_start', 'asc');
                }])
                ->orderBy('work_date', 'desc')
                ->get();

            $sessionsByDay = $workTimes->map(function ($workTime) {
                return [
                    'date' => $workTime->work_date->toDateString(),
                    'day_name' => $workTime->day_name,
                    'total_hours' => $workTime->net_hours,
                    'status' => $workTime->status,
                    'sessions' => $workTime->sessions->map(function ($session) {
                        return [
                            'type' => $session->type,
                            'start' => $session->session_start?->toDateTimeString(),
                            'end' => $session->session_end?->toDateTimeString(),
                            'duration_hours' => $session->duration_hours,
                            'duration_formatted' => $this->formatDuration($session->duration_seconds),
                        ];
                    }),
                ];
            });

            return $this->successResponse(
                [
                    'period' => [
                        'start_date' => $startDate->toDateString(),
                        'end_date' => $endDate->toDateString(),
                    ],
                    'total_days' => $workTimes->count(),
                    'total_hours' => round($workTimes->sum('net_seconds') / 3600, 2),
                    'sessions_by_day' => $sessionsByDay,
                ],
                'Work sessions retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->errorResponse(
                'Failed to get work sessions',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Format duration in seconds to human readable format
     */
    private function formatDuration(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%dh %02dm', $hours, $minutes); // Simplifié
        } elseif ($minutes > 0) {
            return sprintf('%dm', $minutes);
        } else {
            return sprintf('%ds', $seconds);
        }
    }
}
