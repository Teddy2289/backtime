<?php

namespace Modules\ProjectsTeams\Domain\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\ProjectsTeams\Domain\Entities\ProjectsTeams;

interface ProjectsTeamsRepositoryInterface
{
    /**
     * Get all projects.
     */
    public function all(array $filters = []): Collection;

    /**
     * Get paginated projects.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Find a ProjectsTeams by ID.
     */
    public function find(string $id): ?ProjectsTeams;

    /**
     * Find a ProjectsTeams by ID or throw exception.
     */
    public function findOrFail(string $id): ProjectsTeams;

    /**
     * Create a new ProjectsTeams.
     */
    public function create(array $data): ProjectsTeams;

    /**
     * Update a ProjectsTeams.
     */
    public function update(string $id, array $data): ProjectsTeams;

    /**
     * Delete a ProjectsTeams.
     */
    public function delete(string $id): bool;

    /**
     * Restore a soft-deleted ProjectsTeams.
     */
    public function restore(string $id): bool;

    /**
     * Force delete a ProjectsTeams.
     */
    public function forceDelete(string $id): bool;

    /**
     * Get projects by team ID.
     */
    public function getByTeam(string $teamId, array $filters = []): Collection;

    /**
     * Get paginated projects by team ID.
     */
    public function paginateByTeam(string $teamId, int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Search projects by name or description.
     */
    public function search(string $query, array $filters = []): Collection;

    /**
     * Get projects by status.
     */
    public function getByStatus(string $status): Collection;

    /**
     * Get projects with upcoming end dates.
     */
    public function getUpcomingEndDateProjects(int $days = 7): Collection;

    /**
     * Check if ProjectsTeams belongs to team.
     */
    public function belongsToTeam(string $projectId, string $teamId): bool;

    /**
     * Get ProjectsTeams statistics.
     */
    public function getStatistics(string $teamId = null): array;
}