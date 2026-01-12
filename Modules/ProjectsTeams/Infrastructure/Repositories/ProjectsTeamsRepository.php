<?php

namespace Modules\ProjectsTeams\Infrastructure\Repositories;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\ProjectsTeams\Domain\Entities\ProjectsTeams;
use Modules\ProjectsTeams\Domain\Interfaces\ProjectsTeamsRepositoryInterface;

class ProjectsTeamsRepository implements ProjectsTeamsRepositoryInterface
{
    /**
     * The Project model instance.
     */
    protected ProjectsTeams $model;

    /**
     * Create a new repository instance.
     */
    public function __construct()
    {
        // Crée une nouvelle instance du modèle
        $this->model = new ProjectsTeams();
    }

    /**
     * Get all projects.
     */
    public function all(array $filters = []): Collection
    {
        $query = $this->model->newQuery()->with('team');

        $query = $this->applyFilters($query, $filters);

        return $query->get();
    }

    /**
     * Get paginated projects.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->with('team');

        $query = $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    /**
     * Find a project by ID.
     */
    public function find(string $id): ?ProjectsTeams
    {
        return $this->model->newQuery()->with('team')->find($id);
    }

    /**
     * Find a project by ID or throw exception.
     */
    public function findOrFail(string $id): ProjectsTeams
    {
        return $this->model->newQuery()->with('team')->findOrFail($id);
    }

    /**
     * Create a new project.
     */
    public function create(array $data): ProjectsTeams
    {
        return $this->model->newQuery()->create($data);
    }

    /**
     * Update a project.
     */
    public function update(string $id, array $data): ProjectsTeams
    {
        $project = $this->findOrFail($id);
        $project->update($data);

        return $project->fresh('team');
    }

    /**
     * Delete a project.
     */
    public function delete(string $id): bool
    {
        $project = $this->findOrFail($id);
        return $project->delete();
    }

    /**
     * Restore a soft-deleted project.
     */
    public function restore(string $id): bool
    {
        return $this->model->newQuery()->withTrashed()->where('id', $id)->restore();
    }

    /**
     * Force delete a project.
     */
    public function forceDelete(string $id): bool
    {
        $project = $this->model->newQuery()->withTrashed()->findOrFail($id);
        return $project->forceDelete();
    }

    /**
     * Get projects by team ID.
     */
    public function getByTeam(string $teamId, array $filters = []): Collection
    {
        $query = $this->model->newQuery()->where('team_id', $teamId)->with('team');

        $query = $this->applyFilters($query, $filters);

        return $query->get();
    }

    /**
     * Get paginated projects by team ID.
     */
    public function paginateByTeam(string $teamId, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->where('team_id', $teamId)->with('team');

        $query = $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    /**
     * Search projects by name or description.
     */
    public function search(string $query, array $filters = []): Collection
    {
        $searchQuery = $this->model->newQuery()->with('team')
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                    ->orWhere('description', 'LIKE', "%{$query}%");
            });

        $searchQuery = $this->applyFilters($searchQuery, $filters);

        return $searchQuery->get();
    }

    /**
     * Get projects by status.
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->newQuery()->where('status', $status)->with('team')->get();
    }

    /**
     * Get projects with upcoming end dates.
     */
    public function getUpcomingEndDateProjects(int $days = 7): Collection
    {
        $date = now()->addDays($days)->toDateString();

        return $this->model->newQuery()->with('team')
            ->where('end_date', '<=', $date)
            ->where('end_date', '>=', now()->toDateString())
            ->where('status', 'active')
            ->get();
    }

    /**
     * Check if project belongs to team.
     */
    public function belongsToTeam(string $projectId, string $teamId): bool
    {
        return $this->model->newQuery()->where('id', $projectId)
            ->where('team_id', $teamId)
            ->exists();
    }

    /**
     * Get project statistics.
     */
    public function getStatistics(string $teamId = null): array
    {
        $query = $this->model->newQuery();

        if ($teamId) {
            $query->where('team_id', $teamId);
        }

        $total = $query->count();
        $active = (clone $query)->where('status', 'active')->count();
        $completed = (clone $query)->where('status', 'completed')->count();
        $onHold = (clone $query)->where('status', 'on_hold')->count();
        $cancelled = (clone $query)->where('status', 'cancelled')->count();

        // Projects ending in next 7 days
        $upcomingEnd = (clone $query)
            ->where('end_date', '<=', now()->addDays(7)->toDateString())
            ->where('end_date', '>=', now()->toDateString())
            ->where('status', 'active')
            ->count();

        // Average project duration (in days)
        $averageDuration = (clone $query)
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->where('end_date', '>=', 'start_date')
            ->selectRaw('AVG(DATEDIFF(end_date, start_date)) as avg_duration')
            ->first()
            ->avg_duration ?? 0;

        return [
            'total' => $total,
            'active' => $active,
            'completed' => $completed,
            'on_hold' => $onHold,
            'cancelled' => $cancelled,
            'upcoming_end' => $upcomingEnd,
            'average_duration_days' => round($averageDuration, 2),
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Apply filters to query.
     */
    protected function applyFilters($query, array $filters)
    {
        if (isset($filters['team_id'])) {
            $query->where('team_id', $filters['team_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['start_date_from'])) {
            $query->where('start_date', '>=', $filters['start_date_from']);
        }

        if (isset($filters['start_date_to'])) {
            $query->where('start_date', '<=', $filters['start_date_to']);
        }

        if (isset($filters['end_date_from'])) {
            $query->where('end_date', '>=', $filters['end_date_from']);
        }

        if (isset($filters['end_date_to'])) {
            $query->where('end_date', '<=', $filters['end_date_to']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'LIKE', "%{$filters['search']}%")
                    ->orWhere('description', 'LIKE', "%{$filters['search']}%");
            });
        }

        // Order by
        if (isset($filters['order_by'])) {
            $direction = $filters['order_direction'] ?? 'asc';
            $query->orderBy($filters['order_by'], $direction);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query;
    }
}