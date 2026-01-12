<?php

namespace Modules\TaskComment\Presentation\Controllers;

use App\Traits\ApiResponser;
use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\TaskComment\Application\Services\TaskCommentService;
use Modules\Task\Application\Services\TaskService;

class TaskCommentController extends Controller
{
    use ApiResponser;

    protected TaskCommentService $commentService;
    protected TaskService $taskService;

    public function __construct(TaskCommentService $commentService, TaskService $taskService)
    {
        $this->commentService = $commentService;
        $this->taskService = $taskService;
    }

    /**
     * Display a listing of comments.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $filters = $request->only(['task_id', 'user_id', 'parent_id', 'search', 'date_from', 'date_to', 'order_by', 'order_direction']);

            $comments = $this->commentService->getPaginatedComments($perPage, $filters);

            return $this->paginatedResponse($comments, 'Comments retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve comments',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Store a newly created comment.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'task_id' => 'required|integer|exists:tasks,id',
                'content' => 'required|string|min:1|max:2000',
                'parent_id' => 'nullable|integer|exists:task_comments,id',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $taskId = $request->input('task_id');
            $userId = auth()->id();

            // Vérifier les permissions
            if (!$this->commentService->canUserCommentOnTask($userId, $taskId)) {
                return $this->unauthorizedResponse('You are not authorized to comment on this task');
            }

            $data = [
                'task_id' => $taskId,
                'user_id' => $userId,
                'content' => $request->input('content'),
                'parent_id' => $request->input('parent_id'),
            ];

            $comment = $this->commentService->createComment($data);

            // Notifier les mentions
            $this->commentService->notifyMentions($comment);

            return $this->successResponse(
                $comment,
                'Comment created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to create comment',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Display the specified comment.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $comment = $this->commentService->getCommentById($id);

            if (!$comment) {
                return $this->notFoundResponse('Comment not found');
            }

            return $this->successResponse(
                $comment,
                'Comment retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve comment',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Update the specified comment.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'content' => 'required|string|min:1|max:2000',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            // Vérifier les permissions
            if (!$this->commentService->canUserEditComment(auth()->id(), $id)) {
                return $this->unauthorizedResponse('You are not authorized to edit this comment');
            }

            $newContent = $request->input('content');
            $comment = $this->commentService->editComment($id, $newContent);

            if (!$comment) {
                return $this->notFoundResponse('Comment not found');
            }

            // Notifier les nouvelles mentions
            $this->commentService->notifyMentions($comment);

            return $this->successResponse(
                $comment,
                'Comment updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to update comment',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Remove the specified comment.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            // Vérifier les permissions
            if (!$this->commentService->canUserDeleteComment(auth()->id(), $id)) {
                return $this->unauthorizedResponse('You are not authorized to delete this comment');
            }

            $deleted = $this->commentService->deleteComment($id);

            if (!$deleted) {
                return $this->errorResponse('Failed to delete comment', null, 500);
            }

            return $this->successResponse(
                null,
                'Comment deleted successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to delete comment',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Get comments by task.
     */
    public function getByTask(int $taskId, Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $filters = $request->only(['user_id', 'search', 'order_by', 'order_direction']);

            $comments = $this->commentService->getPaginatedCommentsByTask($taskId, $perPage, $filters);

            return $this->paginatedResponse($comments, 'Task comments retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve task comments',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Get comments by user.
     */
    public function getByUser(int $userId, Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $filters = $request->only(['task_id', 'search', 'order_by', 'order_direction']);

            $comments = $this->commentService->getPaginatedCommentsByUser($userId, $perPage, $filters);

            return $this->paginatedResponse($comments, 'User comments retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve user comments',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Add a reply to a comment.
     */
    public function addReply(Request $request, int $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'content' => 'required|string|min:1|max:2000',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $parentComment = $this->commentService->getCommentById($id);

            if (!$parentComment) {
                return $this->notFoundResponse('Parent comment not found');
            }

            $taskId = $parentComment->task_id;
            $userId = auth()->id();

            // Vérifier les permissions
            if (!$this->commentService->canUserCommentOnTask($userId, $taskId)) {
                return $this->unauthorizedResponse('You are not authorized to reply to this comment');
            }

            $data = [
                'user_id' => $userId,
                'content' => $request->input('content'),
            ];

            $reply = $this->commentService->addReply($id, $data);

            if (!$reply) {
                return $this->errorResponse('Failed to add reply', null, 500);
            }

            // Notifier les mentions
            $this->commentService->notifyMentions($reply);

            return $this->successResponse(
                $reply,
                'Reply added successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to add reply',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Get replies for a comment.
     */
    public function getReplies(int $id, Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $filters = $request->only(['user_id', 'search', 'order_by', 'order_direction']);

            $replies = $this->commentService->getPaginatedReplies($id, $perPage, $filters);

            return $this->paginatedResponse($replies, 'Comment replies retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve comment replies',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Get recent comments.
     */
    public function getRecent(Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 10);
            $filters = $request->only(['task_id', 'user_id']);

            $comments = $this->commentService->getRecentComments($limit, $filters);

            return $this->successResponse(
                $comments,
                'Recent comments retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve recent comments',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Search comments.
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

            $filters = $request->only(['task_id', 'user_id', 'parent_id']);
            $comments = $this->commentService->searchComments($request->input('query'), $filters);

            return $this->paginatedResponse($comments, 'Comments search completed successfully');
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to search comments',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Get comment statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $taskId = $request->get('task_id');
            $userId = $request->get('user_id');

            $stats = $this->commentService->getCommentStatistics($taskId, $userId);

            return $this->successResponse(
                $stats,
                'Comment statistics retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve comment statistics',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Get comment edit history.
     */
    public function getEditHistory(int $id): JsonResponse
    {
        try {
            $comment = $this->commentService->getCommentById($id);

            if (!$comment) {
                return $this->notFoundResponse('Comment not found');
            }

            $history = $comment->edit_history ?? [];

            return $this->successResponse(
                $history,
                'Comment edit history retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve comment edit history',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }

    /**
     * Count comments.
     */
    public function count(Request $request): JsonResponse
    {
        try {
            $taskId = $request->get('task_id');
            $userId = $request->get('user_id');

            $count = $this->commentService->countComments($taskId, $userId);

            return $this->successResponse(
                ['count' => $count],
                'Comments count retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to count comments',
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
            'module' => 'TaskComment',
            'version' => '1.0.0',
            'timestamp' => now()->toISOString()
        ], 'Task comment module is healthy');
    }

    /**
     * Get my recent comments.
     */
    public function myComments(Request $request): JsonResponse
    {
        try {
            $userId = auth()->id();
            $perPage = $request->get('per_page', 15);
            $filters = $request->only(['task_id', 'search', 'order_by', 'order_direction']);

            $comments = $this->commentService->getPaginatedCommentsByUser($userId, $perPage, $filters);

            return $this->paginatedResponse($comments, 'Your comments retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve your comments',
                config('app.debug') ? $e->getMessage() : null,
                500
            );
        }
    }
}