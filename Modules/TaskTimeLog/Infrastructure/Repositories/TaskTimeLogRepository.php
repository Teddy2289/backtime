<?php

namespace Modules\TaskTimeLog\Infrastructure\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\TaskTimeLog\Domain\Entities\TaskTimeLog;
use Modules\TaskTimeLog\Domain\Interfaces\TaskTimeLogRepositoryInterface;

class TaskTimeLogRepository implements TaskTimeLogRepositoryInterface
{
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = TaskTimeLog::with(['task', 'user']);

        // Appliquer les filtres
        if (!empty($filters['task_id'])) {
            $query->where('task_id', $filters['task_id']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['date'])) {
            $query->whereDate('start_time', $filters['date']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('start_time', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('start_time', '<=', $filters['date_to']);
        }

        if (!empty($filters['is_running'])) {
            if ($filters['is_running'] === 'true') {
                $query->running();
            } else {
                $query->completed();
            }
        }

        // Tri
        $orderBy = $filters['order_by'] ?? 'start_time';
        $orderDirection = $filters['order_direction'] ?? 'desc';
        $query->orderBy($orderBy, $orderDirection);

        return $query->paginate($perPage);
    }

    public function findById(int $id): ?TaskTimeLog
    {
        return TaskTimeLog::with(['task', 'user'])->find($id);
    }

    public function create(array $data): TaskTimeLog
    {
        return TaskTimeLog::create($data);
    }

    public function update(int $id, array $data): ?TaskTimeLog
    {
        $timeLog = $this->findById($id);

        if (!$timeLog) {
            return null;
        }

        $timeLog->update($data);
        return $timeLog->fresh();
    }

    public function delete(int $id): bool
    {
        $timeLog = $this->findById($id);

        if (!$timeLog) {
            return false;
        }

        return $timeLog->delete();
    }

    public function getByTask(int $taskId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $filters['task_id'] = $taskId;
        return $this->getAll($filters, $perPage);
    }

    public function getByUser(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $filters['user_id'] = $userId;
        return $this->getAll($filters, $perPage);
    }

    public function getRunningTimeLogs(int $userId = null): Collection
    {
        $query = TaskTimeLog::running()->with(['task', 'user']);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->get();
    }

    public function startTimeLog(int $taskId, int $userId, string $note = null): TaskTimeLog
    {
        // Arrêter tous les time logs en cours de l'utilisateur
        $this->stopAllRunningLogs($userId);

        $data = [
            'task_id' => $taskId,
            'user_id' => $userId,
            'start_time' => now(),
            'note' => $note,
        ];

        return $this->create($data);
    }

    public function stopTimeLog(int $id, string $note = null): ?TaskTimeLog
    {
        $timeLog = $this->findById($id);

        if (!$timeLog || !$timeLog->is_running) {
            return null;
        }

        $timeLog->stop($note);
        return $timeLog->fresh();
    }

    public function stopAllRunningLogs(int $userId): bool
    {
        $runningLogs = TaskTimeLog::running()->where('user_id', $userId)->get();

        foreach ($runningLogs as $log) {
            $log->stop('Arrêté automatiquement pour démarrer un nouveau time log');
        }

        return true;
    }

    public function getRunningTimeLogForTask(int $taskId): ?TaskTimeLog
    {
        return TaskTimeLog::running()
            ->where('task_id', $taskId)
            ->first();
    }

    public function getRunningTimeLogForUser(int $userId): ?TaskTimeLog
    {
        return TaskTimeLog::running()
            ->where('user_id', $userId)
            ->first();
    }

    public function getTotalTimeForTask(int $taskId): int
    {
        return TaskTimeLog::where('task_id', $taskId)
            ->completed()
            ->sum('duration') ?? 0;
    }

    public function getTotalTimeForUser(int $userId, array $filters = []): int
    {
        $query = TaskTimeLog::where('user_id', $userId)->completed();

        if (!empty($filters['date_from'])) {
            $query->whereDate('start_time', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('start_time', '<=', $filters['date_to']);
        }

        if (!empty($filters['task_id'])) {
            $query->where('task_id', $filters['task_id']);
        }

        return $query->sum('duration') ?? 0;
    }

    public function getTimeStatistics(array $filters = []): array
    {
        $query = TaskTimeLog::query();

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['task_id'])) {
            $query->where('task_id', $filters['task_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('start_time', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('start_time', '<=', $filters['date_to']);
        }

        $totalTime = $query->clone()->sum('duration') ?? 0;
        $totalLogs = $query->clone()->count();
        $avgTimePerLog = $totalLogs > 0 ? round($totalTime / $totalLogs) : 0;
        $runningLogs = TaskTimeLog::running()->count();

        // Temps par jour (derniers 7 jours)
        $dailyTime = TaskTimeLog::selectRaw('DATE(start_time) as date, SUM(duration) as total_time')
            ->whereDate('start_time', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('total_time', 'date')
            ->toArray();

        return [
            'total_time_minutes' => $totalTime,
            'total_time_formatted' => $this->formatMinutes($totalTime),
            'total_logs' => $totalLogs,
            'avg_time_per_log' => $avgTimePerLog,
            'running_logs' => $runningLogs,
            'daily_time' => $dailyTime,
        ];
    }

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