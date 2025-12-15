<?php

namespace Modules\ProjectsTeams\Application\Services;

use Modules\Project\Domain\Entities\Project;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\ProjectsTeams\Domain\Entities\ProjectsTeams;
use Modules\ProjectsTeams\Domain\Interfaces\ProjectsTeamsRepositoryInterface;

class ProjectsTeamsService
{
    /**
     * The project repository instance.
     */
    protected ProjectsTeamsRepositoryInterface $projectRepository;

    /**
     * Create a new service instance.
     */
    public function __construct(ProjectsTeamsRepositoryInterface $projectRepository)
    {
        $this->projectRepository = $projectRepository;
    }

    /**
     * Get all projects.
     */
    public function getAllProjects(array $filters = []): Collection
    {
        return $this->projectRepository->all($filters);
    }

    /**
     * Get paginated projects.
     */
    public function getPaginatedProjects(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->projectRepository->paginate($perPage, $filters);
    }

    /**
     * Get project by ID.
     */
    public function getProjectById(string $id): ?ProjectsTeams
    {
        return $this->projectRepository->find($id);
    }

    /**
     * Create a new project.
     */
    public function createProject(array $data): ProjectsTeams
    {
        // Validate status
        if (isset($data['status'])) {
            $this->validateStatus($data['status']);
        }

        return $this->projectRepository->create($data);
    }

    /**
     * Update an existing project.
     */
    public function updateProject(string $id, array $data): ProjectsTeams
    {
        // Validate status if provided
        if (isset($data['status'])) {
            $this->validateStatus($data['status']);
        }

        return $this->projectRepository->update($id, $data);
    }

    /**
     * Delete a project.
     */
    public function deleteProject(string $id): bool
    {
        return $this->projectRepository->delete($id);
    }

    /**
     * Restore a soft-deleted project.
     */
    public function restoreProject(string $id): bool
    {
        return $this->projectRepository->restore($id);
    }

    /**
     * Get projects by team ID.
     */
    public function getProjectsByTeam(string $teamId, array $filters = []): Collection
    {
        return $this->projectRepository->getByTeam($teamId, $filters);
    }

    /**
     * Get paginated projects by team ID.
     */
    public function getPaginatedProjectsByTeam(string $teamId, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->projectRepository->paginateByTeam($teamId, $perPage, $filters);
    }

    /**
     * Search projects.
     */
    public function searchProjects(string $query, array $filters = []): Collection
    {
        return $this->projectRepository->search($query, $filters);
    }

    /**
     * Get projects by status.
     */
    public function getProjectsByStatus(string $status): Collection
    {
        $this->validateStatus($status);

        return $this->projectRepository->getByStatus($status);
    }

    /**
     * Get upcoming projects ending soon.
     */
    public function getUpcomingEndDateProjects(int $days = 7): Collection
    {
        return $this->projectRepository->getUpcomingEndDateProjects($days);
    }

    /**
     * Check if project belongs to team.
     */
    public function projectBelongsToTeam(string $projectId, string $teamId): bool
    {
        return $this->projectRepository->belongsToTeam($projectId, $teamId);
    }

    /**
     * Get project statistics.
     */
    public function getProjectStatistics(string $teamId = null): array
    {
        return $this->projectRepository->getStatistics($teamId);
    }

    /**
     * Validate project status.
     */
    protected function validateStatus(string $status): void
    {
        $validStatuses = ['active', 'completed', 'on_hold', 'cancelled'];

        if (!in_array($status, $validStatuses)) {
            throw new \InvalidArgumentException("Invalid project status: {$status}");
        }
    }

    /**
     * Update project status.
     */
    public function updateProjectStatus(string $id, string $status): ProjectsTeams
    {
        $this->validateStatus($status);

        return $this->projectRepository->update($id, ['status' => $status]);
    }

    /**
     * Complete a project.
     */
    public function completeProject(string $id): ProjectsTeams
    {
        return $this->updateProjectStatus($id, 'completed');
    }

    /**
     * Put project on hold.
     */
    public function putProjectOnHold(string $id): ProjectsTeams
    {
        return $this->updateProjectStatus($id, 'on_hold');
    }

    /**
     * Cancel a project.
     */
    public function cancelProject(string $id): ProjectsTeams
    {
        return $this->updateProjectStatus($id, 'cancelled');
    }

    /**
     * Reactivate a project.
     */
    public function reactivateProject(string $id): ProjectsTeams
    {
        return $this->updateProjectStatus($id, 'active');
    }

    /**
     * Get project with team relationship.
     */
    public function getProjectWithTeam(string $id): ?ProjectsTeams
    {
        return $this->projectRepository->find($id);
    }
}