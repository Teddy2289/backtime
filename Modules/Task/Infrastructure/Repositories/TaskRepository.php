<?php

namespace Modules\Task\Infrastructure\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Task\Domain\Entities\Task;
use Modules\Task\Domain\Interfaces\TaskRepositoryInterface;

class TaskRepository implements TaskRepositoryInterface
{
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Task::query();

        // Appliquer les filtres
        if (!empty($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (!empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }

        // Gestion des dates
        if (!empty($filters['start_date_from'])) {
            $query->whereDate('start_date', '>=', $filters['start_date_from']);
        }

        if (!empty($filters['start_date_to'])) {
            $query->whereDate('start_date', '<=', $filters['start_date_to']);
        }

        if (!empty($filters['due_date_from'])) {
            $query->whereDate('due_date', '>=', $filters['due_date_from']);
        }

        if (!empty($filters['due_date_to'])) {
            $query->whereDate('due_date', '<=', $filters['due_date_to']);
        }

        // Tri
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDirection = $filters['order_direction'] ?? 'desc';
        $query->orderBy($orderBy, $orderDirection);

        return $query->paginate($perPage);
    }

    public function findById(int $id): ?Task
    {
        return Task::with(['project', 'assignedUser', 'timeLogs', 'files', 'comments'])
            ->find($id);
    }

    public function create(array $data): Task
    {
        return Task::create($data);
    }

    public function update(int $id, array $data): ?Task
    {
        $task = $this->findById($id);

        if (!$task) {
            return null;
        }

        $task->update($data);
        return $task->fresh();
    }

    public function delete(int $id): bool
    {
        $task = $this->findById($id);

        if (!$task) {
            return false;
        }

        return $task->delete();
    }

    public function restore(int $id): bool
    {
        $task = Task::withTrashed()->find($id);

        if (!$task) {
            return false;
        }

        return $task->restore();
    }

    public function getByProject(int $projectId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $filters['project_id'] = $projectId;
        return $this->getAll($filters, $perPage);
    }

    public function getByAssignee(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $filters['assigned_to'] = $userId;
        return $this->getAll($filters, $perPage);
    }

    public function getByStatus(string $status, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $filters['status'] = $status;
        return $this->getAll($filters, $perPage);
    }

    public function search(string $query, array $filters = []): LengthAwarePaginator
    {
        $filters['search'] = $query;
        return $this->getAll($filters);
    }

    public function countByStatus(int $projectId = null): array
    {
        $query = Task::query();

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        return $query->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();
    }

    public function updateStatus(int $id, string $status): ?Task
    {
        return $this->update($id, ['status' => $status]);
    }

    public function assignTo(int $id, int $userId): ?Task
    {
        return $this->update($id, ['assigned_to' => $userId]);
    }

    public function getOverdueTasks(int $projectId = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = Task::overdue();

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        return $query->paginate($perPage);
    }

    public function getUpcomingTasks(int $projectId = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = Task::upcoming();

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        return $query->paginate($perPage);
    }

    public function getStatistics(int $projectId = null): array
    {
        $query = Task::query();

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        $totalTasks = $query->count();
        $completedTasks = $query->clone()->where('status', 'done')->count();
        $overdueTasks = $this->getOverdueTasks($projectId, 1000)->total(); // 1000 pour récupérer tous
        $upcomingTasks = $this->getUpcomingTasks($projectId, 1000)->total();

        $avgCompletionTime = $query->clone()
            ->where('status', 'done')
            ->whereNotNull('start_date')
            ->selectRaw('AVG(DATEDIFF(updated_at, start_date)) as avg_days')
            ->value('avg_days');

        return [
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'in_progress_tasks' => $query->clone()->where('status', 'doing')->count(),
            'pending_tasks' => $query->clone()->where('status', 'todo')->count(),
            'overdue_tasks' => $overdueTasks,
            'upcoming_tasks' => $upcomingTasks,
            'completion_rate' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 2) : 0,
            'avg_completion_days' => round($avgCompletionTime, 2),
        ];
    }
}