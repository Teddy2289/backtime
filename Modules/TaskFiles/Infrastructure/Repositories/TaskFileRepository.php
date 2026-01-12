<?php

namespace Modules\TaskFiles\Infrastructure\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\TaskFiles\Domain\Entities\TaskFile;
use Modules\TaskFiles\Domain\Interfaces\TaskFileRepositoryInterface;

class TaskFileRepository implements TaskFileRepositoryInterface
{
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = TaskFile::with(['task', 'uploader']);

        // Appliquer les filtres
        if (!empty($filters['task_id'])) {
            $query->where('task_id', $filters['task_id']);
        }

        if (!empty($filters['uploaded_by'])) {
            $query->where('uploaded_by', $filters['uploaded_by']);
        }

        if (!empty($filters['type'])) {
            if ($filters['type'] === 'image') {
                $query->images();
            } elseif ($filters['type'] === 'document') {
                $query->documents();
            }
        }

        if (!empty($filters['extension'])) {
            $query->where('extension', $filters['extension']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('file_name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }

        // Tri
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDirection = $filters['order_direction'] ?? 'desc';
        $query->orderBy($orderBy, $orderDirection);

        return $query->paginate($perPage);
    }

    public function findById(int $id): ?TaskFile
    {
        return TaskFile::with(['task', 'uploader'])->find($id);
    }

    public function create(array $data): TaskFile
    {
        return TaskFile::create($data);
    }

    public function createMany(array $filesData): Collection
    {
        $files = new Collection();

        foreach ($filesData as $data) {
            $files->push($this->create($data));
        }

        return $files;
    }

    public function update(int $id, array $data): ?TaskFile
    {
        $file = $this->findById($id);

        if (!$file) {
            return null;
        }

        $file->update($data);
        return $file->fresh();
    }

    public function delete(int $id): bool
    {
        $file = $this->findById($id);

        if (!$file) {
            return false;
        }

        return $file->delete();
    }

    public function deleteMany(array $ids): bool
    {
        return TaskFile::whereIn('id', $ids)->delete();
    }

    public function getByTask(int $taskId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $filters['task_id'] = $taskId;
        return $this->getAll($filters, $perPage);
    }

    public function getByUploader(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $filters['uploaded_by'] = $userId;
        return $this->getAll($filters, $perPage);
    }

    public function search(string $query, array $filters = []): LengthAwarePaginator
    {
        $filters['search'] = $query;
        return $this->getAll($filters);
    }

    public function countByType(int $taskId = null): array
    {
        $query = TaskFile::query();

        if ($taskId) {
            $query->where('task_id', $taskId);
        }

        return [
            'total' => $query->count(),
            'images' => $query->clone()->images()->count(),
            'documents' => $query->clone()->documents()->count(),
            'others' => $query->clone()->whereNotIn('extension', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt'])->count(),
        ];
    }

    public function getTotalSize(int $taskId = null, int $userId = null): int
    {
        $query = TaskFile::query();

        if ($taskId) {
            $query->where('task_id', $taskId);
        }

        if ($userId) {
            $query->where('uploaded_by', $userId);
        }

        return $query->sum('file_size') ?? 0;
    }

    public function getStatistics(int $taskId = null, int $userId = null): array
    {
        $query = TaskFile::query();

        if ($taskId) {
            $query->where('task_id', $taskId);
        }

        if ($userId) {
            $query->where('uploaded_by', $userId);
        }

        $totalFiles = $query->count();
        $totalSize = $this->getTotalSize($taskId, $userId);
        $recentFiles = $query->clone()->recent(7)->count();

        // Fichiers par extension
        $filesByExtension = $query->clone()
            ->selectRaw('extension, COUNT(*) as count')
            ->groupBy('extension')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()
            ->pluck('count', 'extension')
            ->toArray();

        return [
            'total_files' => $totalFiles,
            'total_size_bytes' => $totalSize,
            'total_size_formatted' => $this->formatBytes($totalSize),
            'recent_files' => $recentFiles,
            'files_by_extension' => $filesByExtension,
            'count_by_type' => $this->countByType($taskId),
        ];
    }

    public function existsForTask(int $taskId, string $fileName): bool
    {
        return TaskFile::where('task_id', $taskId)
            ->where('file_name', $fileName)
            ->exists();
    }

    public function updateDescription(int $id, string $description): ?TaskFile
    {
        return $this->update($id, ['description' => $description]);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
}