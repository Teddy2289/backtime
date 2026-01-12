<?php

namespace Modules\Task\Infrastructure\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Task\Domain\Entities\Task;
use Modules\Task\Domain\Interfaces\TaskRepositoryInterface;

class TaskRepository implements TaskRepositoryInterface
{

    private function applyFilter($query, array $filters, string $key, callable $callback)
    {
        if (!array_key_exists($key, $filters)) {
            return;
        }

        $value = $filters[$key];

        // Valeurs ignorées
        if ($value === null || $value === '' || $value === 'all') {
            return;
        }

        $callback($query, $value);
    }

    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Task::with([
            'project:id,name,description',
            'assignedUser:id,name,email,avatar',
            'timeLogs:id,task_id,duration,description',
            'files:id,task_id,file_url,file_name,file_size,mime_type,extension,uploaded_by',
            'comments:id,task_id,content,user_id,created_at',
        ]);

        $this->applyFilter(
            $query,
            $filters,
            'project_id',
            fn($q, $v) =>
            $q->where('project_id', (int) $v)
        );

        $this->applyFilter(
            $query,
            $filters,
            'status',
            fn($q, $v) =>
            $q->where('status', $v)
        );

        $this->applyFilter(
            $query,
            $filters,
            'priority',
            fn($q, $v) =>
            $q->where('priority', $v)
        );

        $this->applyFilter(
            $query,
            $filters,
            'assigned_to',
            fn($q, $v) =>
            $q->where('assigned_to', (int) $v)
        );

        $this->applyFilter(
            $query,
            $filters,
            'search',
            fn($q, $v) =>
            $q->where(
                fn($sub) =>
                $sub->where('title', 'like', "%$v%")
                    ->orWhere('description', 'like', "%$v%")
            )
        );

        $orderBy = $filters['order_by'] ?? 'created_at';
        $direction = $filters['order_direction'] ?? 'desc';

        $query->orderBy($orderBy, $direction);

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

    public function getScheduledTasks(int $projectId = null, int $perPage = 15): LengthAwarePaginator
{
    $query = Task::scheduled()->with([
        'project:id,name,description',
        'assignedUser:id,name,email,avatar',
    ]);

    if ($projectId) {
        $query->where('project_id', $projectId);
    }

    return $query->orderBy('start_date', 'asc')->paginate($perPage);
}

public function getUnscheduledTasks(int $projectId = null, int $perPage = 15): LengthAwarePaginator
{
    $query = Task::unscheduled()->with([
        'project:id,name,description',
        'assignedUser:id,name,email,avatar',
    ]);

    if ($projectId) {
        $query->where('project_id', $projectId);
    }

    return $query->orderBy('created_at', 'desc')->paginate($perPage);
}
}