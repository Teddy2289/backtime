<?php

namespace Modules\TaskComment\Infrastructure\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\TaskComment\Domain\Entities\TaskComment;
use Modules\TaskComment\Domain\Interfaces\TaskCommentRepositoryInterface;

class TaskCommentRepository implements TaskCommentRepositoryInterface
{
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = TaskComment::with(['user', 'task', 'replies.user']);

        // Appliquer les filtres
        if (!empty($filters['task_id'])) {
            $query->where('task_id', $filters['task_id']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['parent_id'])) {
            if ($filters['parent_id'] === 'null') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $filters['parent_id']);
            }
        }

        if (!empty($filters['search'])) {
            $query->where('content', 'like', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['edited'])) {
            $query->where('edited', $filters['edited'] === 'true');
        }

        // Tri
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDirection = $filters['order_direction'] ?? 'desc';
        $query->orderBy($orderBy, $orderDirection);

        return $query->paginate($perPage);
    }

    public function findById(int $id): ?TaskComment
    {
        return TaskComment::with(['user', 'task', 'parent', 'replies.user'])->find($id);
    }

    public function create(array $data): TaskComment
    {
        return TaskComment::create($data);
    }

    public function update(int $id, array $data): ?TaskComment
    {
        $comment = $this->findById($id);

        if (!$comment) {
            return null;
        }

        $comment->update($data);
        return $comment->fresh(['user', 'task', 'parent', 'replies.user']);
    }

    public function delete(int $id): bool
    {
        $comment = $this->findById($id);

        if (!$comment) {
            return false;
        }

        return $comment->delete();
    }

    public function getByTask(int $taskId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $filters['task_id'] = $taskId;
        $filters['parent_id'] = 'null'; // Seulement les commentaires parents
        return $this->getAll($filters, $perPage);
    }

    public function getByUser(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $filters['user_id'] = $userId;
        return $this->getAll($filters, $perPage);
    }

    public function search(string $query, array $filters = []): LengthAwarePaginator
    {
        $filters['search'] = $query;
        return $this->getAll($filters);
    }

    public function countComments(int $taskId = null, int $userId = null): int
    {
        $query = TaskComment::query();

        if ($taskId) {
            $query->where('task_id', $taskId);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->count();
    }

    public function getStatistics(int $taskId = null, int $userId = null): array
    {
        $query = TaskComment::query();

        if ($taskId) {
            $query->where('task_id', $taskId);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $totalComments = $query->count();
        $recentComments = $query->clone()->recent(7)->count();
        $editedComments = $query->clone()->where('edited', true)->count();
        $commentsWithReplies = $query->clone()->has('replies')->count();

        // Commentaires par jour (derniers 7 jours)
        $dailyComments = TaskComment::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->whereDate('created_at', '>=', now()->subDays(7))
            ->when($taskId, function ($q) use ($taskId) {
                return $q->where('task_id', $taskId);
            })
            ->when($userId, function ($q) use ($userId) {
                return $q->where('user_id', $userId);
            })
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();

        // Top utilisateurs (si pas de filtre par utilisateur)
        if (!$userId) {
            $topUsers = TaskComment::selectRaw('user_id, COUNT(*) as count')
                ->with('user:id,name')
                ->when($taskId, function ($q) use ($taskId) {
                    return $q->where('task_id', $taskId);
                })
                ->groupBy('user_id')
                ->orderBy('count', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($item) {
                    return [
                        'user_id' => $item->user_id,
                        'user_name' => $item->user->name ?? 'Unknown',
                        'count' => $item->count,
                    ];
                })
                ->toArray();
        }

        return [
            'total_comments' => $totalComments,
            'recent_comments' => $recentComments,
            'edited_comments' => $editedComments,
            'comments_with_replies' => $commentsWithReplies,
            'average_replies_per_comment' => $totalComments > 0 ? round($commentsWithReplies / $totalComments, 2) : 0,
            'daily_comments' => $dailyComments,
            'top_users' => $topUsers ?? [],
        ];
    }

    public function addReply(int $parentId, array $data): ?TaskComment
    {
        $parent = $this->findById($parentId);

        if (!$parent) {
            return null;
        }

        $data['parent_id'] = $parentId;
        $data['task_id'] = $parent->task_id;

        return $this->create($data);
    }

    public function getReplies(int $commentId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $filters['parent_id'] = $commentId;
        return $this->getAll($filters, $perPage);
    }

    public function getRecentComments(int $limit = 10, array $filters = []): Collection
    {
        $query = TaskComment::with(['user', 'task'])->recent(30);

        if (!empty($filters['task_id'])) {
            $query->where('task_id', $filters['task_id']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return $query->orderBy('created_at', 'desc')->limit($limit)->get();
    }

    public function markAsEdited(int $id, string $oldContent): ?TaskComment
    {
        $comment = $this->findById($id);

        if (!$comment) {
            return null;
        }

        $comment->saveEditHistory($oldContent);
        return $comment->fresh();
    }
}