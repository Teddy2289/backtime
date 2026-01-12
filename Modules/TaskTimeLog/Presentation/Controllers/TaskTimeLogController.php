<?php

namespace Modules\TaskTimeLog\Presentation\Controllers;

use App\Traits\ApiResponser;
use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Task\Application\Services\TaskService;
use Modules\TaskTimeLog\Application\Services\TaskTimeLogService;

class TaskTimeLogController extends Controller
{
    use ApiResponser;

    protected TaskTimeLogService $timeLogService;
    protected TaskService $taskService;

    public function __construct(TaskTimeLogService $timeLogService, TaskService $taskService)
    {
        $this->timeLogService = $timeLogService;
        $this->taskService = $taskService;
    }

    /**
     * Display a listing of time logs.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $filters = $request->only(['task_id', 'user_id', 'date', 'date_from', 'date_to', 'is_running', 'order_by', 'order_direction']);

            $timeLogs = $this->timeLogService->getPaginatedTimeLogs($perPage, $filters);

            return $this->paginatedResponse($timeLogs, 'Time logs retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve time logs',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Store a newly created time log.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'task_id' => 'required|integer|exists:tasks,id',
                'user_id' => 'required|integer|exists:users,id',
                'start_time' => 'nullable|date',
                'end_time' => 'nullable|date|after_or_equal:start_time',
                'duration' => 'nullable|integer|min:1',
                'note' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $data = $validator->validated();

            // Vérifier que l'utilisateur a accès à la tâche
            if (!$this->canUserTrackTime($data['user_id'], $data['task_id'])) {
                return $this->unauthorizedResponse('You are not authorized to track time for this task');
            }

            $timeLog = $this->timeLogService->createTimeLog($data);

            return $this->successResponse(
                $timeLog,
                'Time log created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to create time log',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Display the specified time log.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $timeLog = $this->timeLogService->getTimeLogById($id);

            if (!$timeLog) {
                return $this->notFoundResponse('Time log not found');
            }

            return $this->successResponse(
                $timeLog,
                'Time log retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve time log',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Update the specified time log.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'start_time' => 'sometimes|date',
                'end_time' => 'nullable|date|after_or_equal:start_time',
                'duration' => 'nullable|integer|min:1',
                'note' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            // Vérifier les permissions
            if (!$this->timeLogService->canUserModifyTimeLog(auth()->id(), $id)) {
                return $this->unauthorizedResponse('You are not authorized to modify this time log');
            }

            $timeLog = $this->timeLogService->updateTimeLog($id, $validator->validated());

            if (!$timeLog) {
                return $this->notFoundResponse('Time log not found');
            }

            return $this->successResponse(
                $timeLog,
                'Time log updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to update time log',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Remove the specified time log.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            // Vérifier les permissions
            if (!$this->timeLogService->canUserModifyTimeLog(auth()->id(), $id)) {
                return $this->unauthorizedResponse('You are not authorized to delete this time log');
            }

            $deleted = $this->timeLogService->deleteTimeLog($id);

            if (!$deleted) {
                return $this->errorResponse('Failed to delete time log', null, 500);
            }

            return $this->successResponse(
                null,
                'Time log deleted successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to delete time log',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Start a new time log.
     */
    public function start(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'task_id' => 'required|integer|exists:tasks,id',
                'note' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $taskId = $request->input('task_id');
            $userId = auth()->id();
            $note = $request->input('note');

            // Vérifier que l'utilisateur peut démarrer un time log sur cette tâche
            if (!$this->timeLogService->canUserStartTimeLog($userId, $taskId)) {
                return $this->unauthorizedResponse('You are not authorized to start time tracking for this task');
            }

            // Vérifier si un time log est déjà en cours pour cet utilisateur
            $runningLog = $this->timeLogService->getRunningTimeLogForUser($userId);
            if ($runningLog) {
                return $this->errorResponse(
                    'You already have a running time log. Please stop it first.',
                    ['running_log_id' => $runningLog->id],
                    400
                );
            }

            // Vérifier si un time log est déjà en cours pour cette tâche
            $runningTaskLog = $this->timeLogService->getRunningTimeLogForTask($taskId);
            if ($runningTaskLog) {
                return $this->errorResponse(
                    'This task already has a running time log.',
                    ['running_log_id' => $runningTaskLog->id],
                    400
                );
            }

            $timeLog = $this->timeLogService->startTimeLog($taskId, $userId, $note);

            return $this->successResponse(
                $timeLog,
                'Time log started successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to start time log',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Stop a running time log.
     */
    public function stop(Request $request, int $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'note' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            // Vérifier que le time log existe et est en cours
            $timeLog = $this->timeLogService->getTimeLogById($id);

            if (!$timeLog) {
                return $this->notFoundResponse('Time log not found');
            }

            if (!$timeLog->is_running) {
                return $this->errorResponse('Time log is not running', null, 400);
            }

            // Vérifier les permissions
            if (!$this->timeLogService->canUserModifyTimeLog(auth()->id(), $id)) {
                return $this->unauthorizedResponse('You are not authorized to stop this time log');
            }

            $note = $request->input('note');
            $stoppedLog = $this->timeLogService->stopTimeLog($id, $note);

            if (!$stoppedLog) {
                return $this->errorResponse('Failed to stop time log', null, 500);
            }

            return $this->successResponse(
                $stoppedLog,
                'Time log stopped successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to stop time log',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Stop the current running time log for authenticated user.
     */
    public function stopCurrent(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'note' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $userId = auth()->id();
            $runningLog = $this->timeLogService->getRunningTimeLogForUser($userId);

            if (!$runningLog) {
                return $this->errorResponse('No running time log found', null, 404);
            }

            $note = $request->input('note');
            $stoppedLog = $this->timeLogService->stopTimeLog($runningLog->id, $note);

            if (!$stoppedLog) {
                return $this->errorResponse('Failed to stop time log', null, 500);
            }

            return $this->successResponse(
                $stoppedLog,
                'Time log stopped successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to stop current time log',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Get time logs by task.
     */
    public function getByTask(int $taskId, Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $filters = $request->only(['user_id', 'date_from', 'date_to', 'order_by', 'order_direction']);

            $timeLogs = $this->timeLogService->getPaginatedTimeLogsByTask($taskId, $perPage, $filters);

            return $this->paginatedResponse($timeLogs, 'Task time logs retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve task time logs',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Get time logs by user.
     */
    public function getByUser(int $userId, Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $filters = $request->only(['task_id', 'date_from', 'date_to', 'order_by', 'order_direction']);

            $timeLogs = $this->timeLogService->getPaginatedTimeLogsByUser($userId, $perPage, $filters);

            return $this->paginatedResponse($timeLogs, 'User time logs retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve user time logs',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Get running time logs.
     */
    public function getRunning(Request $request): JsonResponse
    {
        try {
            $userId = $request->get('user_id');
            $runningLogs = $this->timeLogService->getRunningTimeLogs($userId);

            return $this->successResponse(
                $runningLogs,
                'Running time logs retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve running time logs',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Get time statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['user_id', 'task_id', 'date_from', 'date_to']);
            $stats = $this->timeLogService->getTimeStatistics($filters);

            return $this->successResponse(
                $stats,
                'Time statistics retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve time statistics',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Get total time for a task.
     */
    public function getTotalTimeForTask(int $taskId): JsonResponse
    {
        try {
            $totalTime = $this->timeLogService->getTotalTimeForTask($taskId);

            return $this->successResponse(
                [
                    'task_id' => $taskId,
                    'total_time_minutes' => $totalTime,
                    'total_time_formatted' => $this->formatMinutes($totalTime),
                ],
                'Total time for task retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve total time for task',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Get total time for a user.
     */
    public function getTotalTimeForUser(int $userId, Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['date_from', 'date_to', 'task_id']);
            $totalTime = $this->timeLogService->getTotalTimeForUser($userId, $filters);

            return $this->successResponse(
                [
                    'user_id' => $userId,
                    'total_time_minutes' => $totalTime,
                    'total_time_formatted' => $this->formatMinutes($totalTime),
                    'filters' => $filters,
                ],
                'Total time for user retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve total time for user',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Get current user's time logs summary.
     */
    public function mySummary(Request $request): JsonResponse
    {
        try {
            $userId = auth()->id();

            // Récupérer le time log en cours
            $currentLog = $this->timeLogService->getRunningTimeLogForUser($userId);

            // Récupérer les statistiques du jour
            $todayStats = $this->timeLogService->getTimeStatistics([
                'user_id' => $userId,
                'date_from' => now()->format('Y-m-d'),
                'date_to' => now()->format('Y-m-d'),
            ]);

            // Récupérer les statistiques de la semaine
            $weekStats = $this->timeLogService->getTimeStatistics([
                'user_id' => $userId,
                'date_from' => now()->startOfWeek()->format('Y-m-d'),
                'date_to' => now()->endOfWeek()->format('Y-m-d'),
            ]);

            return $this->successResponse(
                [
                    'user_id' => $userId,
                    'current_time_log' => $currentLog,
                    'today' => $todayStats,
                    'this_week' => $weekStats,
                ],
                'Time summary retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve time summary',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Update a time log's note.
     */
    public function updateNote(Request $request, int $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'note' => 'required|string|max:1000',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            // Vérifier les permissions
            if (!$this->timeLogService->canUserModifyTimeLog(auth()->id(), $id)) {
                return $this->unauthorizedResponse('You are not authorized to update this time log');
            }

            $timeLog = $this->timeLogService->updateTimeLog($id, [
                'note' => $request->input('note')
            ]);

            if (!$timeLog) {
                return $this->notFoundResponse('Time log not found');
            }

            return $this->successResponse(
                $timeLog,
                'Time log note updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to update time log note',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Health check endpoint.
     */
    public function health(): JsonResponse
    {
        return $this->successResponse([
            'status' => 'ok',
            'module' => 'TaskTimeLog',
            'version' => '1.0.0',
            'timestamp' => now()->toISOString()
        ], 'Task time log module is healthy');
    }

    /**
     * Vérifier si un utilisateur peut tracker le temps sur une tâche.
     */
    private function canUserTrackTime(int $userId, int $taskId): bool
    {
        // Vérifier si l'utilisateur est assigné à la tâche
        $task = $this->taskService->getTaskById($taskId);

        if (!$task) {
            return false;
        }

        // L'utilisateur assigné, le propriétaire du projet, ou un admin peut tracker le temps
        return $task->assigned_to === $userId
            || optional($task->project)->owner_id === $userId
            || auth()->user()->hasRole('admin');
    }

    /**
     * Formatter les minutes en heures et minutes.
     */
    private function formatMinutes(int $minutes): string
    {
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($hours > 0) {
            return "{$hours}h {$remainingMinutes}m";
        }

        return "{$remainingMinutes}m";
    }
}