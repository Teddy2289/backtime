<?php

namespace Modules\Task\Presentation\Controllers;

use App\Traits\ApiResponser;
use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Task\Application\Services\TaskService;
use Modules\Task\Domain\Entities\Task;

class TaskController extends Controller
{
    use ApiResponser;

    protected TaskService $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    /**
     * Display a listing of tasks.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $filters = $request->only(['project_id', 'status', 'priority', 'assigned_to', 'search', 'order_by', 'order_direction']);

            $tasks = $this->taskService->getPaginatedTasks($perPage, $filters);

            return $this->paginatedResponse($tasks, 'Tasks retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve tasks',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Store a newly created task.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'project_id' => 'required|integer|exists:projects,id',
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'assigned_to' => 'nullable|integer|exists:users,id',
                'status' => 'nullable|string|in:backlog,todo,doing,done',
                'priority' => 'nullable|string|in:low,medium,high',
                'start_date' => 'nullable|date',
                'due_date' => 'nullable|date|after_or_equal:start_date',
                'estimated_time' => 'nullable|integer|min:1',
                'tags' => 'nullable|array',
                'tags.*' => 'string|max:50',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $task = $this->taskService->createTask($validator->validated());

            return $this->successResponse(
                $task,
                'Task created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to create task',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Display the specified task.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $task = $this->taskService->getTaskById($id);

            if (!$task) {
                return $this->notFoundResponse('Task not found');
            }

            return $this->successResponse(
                $task,
                'Task retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve task',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Update the specified task.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'assigned_to' => 'nullable|integer|exists:users,id',
                'status' => 'sometimes|string|in:backlog,todo,doing,done',
                'priority' => 'sometimes|string|in:low,medium,high',
                'start_date' => 'nullable|date',
                'due_date' => 'nullable|date|after_or_equal:start_date',
                'estimated_time' => 'nullable|integer|min:0',
                'tags' => 'nullable|array',
                'tags.*' => 'string|max:50',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $task = $this->taskService->updateTask($id, $validator->validated());

            if (!$task) {
                return $this->notFoundResponse('Task not found');
            }

            return $this->successResponse(
                $task,
                'Task updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to update task',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Remove the specified task.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->taskService->deleteTask($id);

            if (!$deleted) {
                return $this->errorResponse('Failed to delete task', null, 500);
            }

            return $this->successResponse(
                null,
                'Task deleted successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to delete task',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Restore a soft-deleted task.
     */
    public function restore(int $id): JsonResponse
    {
        try {
            $restored = $this->taskService->restoreTask($id);

            if (!$restored) {
                return $this->errorResponse('Failed to restore task', null, 500);
            }

            return $this->successResponse(
                null,
                'Task restored successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to restore task',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Get tasks by project ID.
     */
    public function getByProject(int $projectId, Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $filters = $request->only(['status', 'priority', 'assigned_to', 'search', 'order_by', 'order_direction']);

            $tasks = $this->taskService->getPaginatedTasksByProject($projectId, $perPage, $filters);

            return $this->paginatedResponse($tasks, 'Project tasks retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve project tasks',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Get tasks assigned to user.
     */
    public function getByAssignee(int $userId, Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $filters = $request->only(['project_id', 'status', 'priority', 'search', 'order_by', 'order_direction']);

            $tasks = $this->taskService->getPaginatedTasksByAssignee($userId, $perPage, $filters);

            return $this->paginatedResponse($tasks, 'User tasks retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve user tasks',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Search tasks.
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'query' => 'required|string|min:2',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $filters = $request->only(['project_id', 'status', 'priority', 'assigned_to']);
            $tasks = $this->taskService->searchTasks($request->input('query'), $filters);

            return $this->paginatedResponse($tasks, 'Tasks search completed successfully');
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to search tasks',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Get task statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $projectId = $request->get('project_id');
            $stats = $this->taskService->getTaskStatistics($projectId);

            return $this->successResponse(
                $stats,
                'Task statistics retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve task statistics',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Update task status.
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|string|in:backlog,todo,doing,done',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $task = $this->taskService->updateTaskStatus($id, $request->input('status'));

            if (!$task) {
                return $this->notFoundResponse('Task not found');
            }

            return $this->successResponse(
                $task,
                'Task status updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to update task status',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Assign task to user.
     */
    public function assignTo(Request $request, int $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer|exists:users,id',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $task = $this->taskService->assignTaskToUser($id, $request->input('user_id'));

            if (!$task) {
                return $this->notFoundResponse('Task not found');
            }

            return $this->successResponse(
                $task,
                'Task assigned successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to assign task',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Get overdue tasks.
     */
    public function getOverdue(Request $request): JsonResponse
    {
        try {
            $projectId = $request->get('project_id');
            $perPage = $request->get('per_page', 15);

            $tasks = $this->taskService->getOverdueTasks($projectId, $perPage);

            return $this->paginatedResponse($tasks, 'Overdue tasks retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve overdue tasks',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Get upcoming tasks.
     */
    public function getUpcoming(Request $request): JsonResponse
    {
        try {
            $projectId = $request->get('project_id');
            $perPage = $request->get('per_page', 15);

            $tasks = $this->taskService->getUpcomingTasks($projectId, $perPage);

            return $this->paginatedResponse($tasks, 'Upcoming tasks retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve upcoming tasks',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Complete a task.
     */
    public function complete(int $id): JsonResponse
    {
        try {
            $task = $this->taskService->completeTask($id);

            if (!$task) {
                return $this->notFoundResponse('Task not found');
            }

            return $this->successResponse(
                $task,
                'Task marked as completed'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to complete task',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Start working on a task.
     */
    public function start(int $id): JsonResponse
    {
        try {
            $task = $this->taskService->startTask($id);

            if (!$task) {
                return $this->notFoundResponse('Task not found');
            }

            return $this->successResponse(
                $task,
                'Task started successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to start task',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Reset task to todo.
     */
    public function resetToTodo(int $id): JsonResponse
    {
        try {
            $task = $this->taskService->resetTaskToTodo($id);

            if (!$task) {
                return $this->notFoundResponse('Task not found');
            }

            return $this->successResponse(
                $task,
                'Task reset to todo'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to reset task',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Count tasks by status.
     */
    public function countByStatus(Request $request): JsonResponse
    {
        try {
            $projectId = $request->get('project_id');
            $counts = $this->taskService->countTasksByStatus($projectId);

            return $this->successResponse(
                $counts,
                'Task counts by status retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to count tasks by status',
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
            'module' => 'Task',
            'version' => '1.0.0',
            'timestamp' => now()->toISOString()
        ], 'Task module is healthy');
    }

    /**
     * Test endpoint.
     */
    public function test(): JsonResponse
    {
        return $this->successResponse([
            'message' => 'Task module is working!',
            'module' => 'Task',
            'version' => '1.0.0',
            'status' => 'active'
        ], 'Test endpoint');
    }

    // Dans TaskController
    public function getTasksByProject($projectId)
    {
        try {
            $tasks = Task::where('project_id', $projectId)
                ->with(['assignedUser', 'timeLogs', 'comments', 'files'])
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->successResponse(
                $tasks,
                'Tasks retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve tasks',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }
}