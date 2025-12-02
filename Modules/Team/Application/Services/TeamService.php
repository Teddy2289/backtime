<?php

namespace Modules\Team\Application\Services;

use Modules\Team\Domain\Entities\Team;
use Modules\Team\Domain\Interfaces\TeamRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TeamService
{
    public function __construct(
        protected TeamRepositoryInterface $repository
    ) {}

    /**
     * Find a team by ID.
     */
    public function find(string $id): ?Team
    {
        return $this->repository->find($id);
    }

    /**
     * Create a new team.
     */
    public function create(array $data): Team
    {
        return DB::transaction(function () use ($data) {
            $team = $this->repository->create($data);
            
            // Automatically add the owner as a member
            if (isset($data['owner_id'])) {
                $this->repository->addMember($team->id, $data['owner_id']);
            }
            
            return $team;
        });
    }

    /**
     * Update a team.
     */
    public function update(string $id, array $data): ?Team
    {
        return DB::transaction(function () use ($id, $data) {
            $team = $this->repository->update($id, $data);
            
            // If owner changed, add new owner as member
            if ($team && isset($data['owner_id'])) {
                $this->repository->addMember($team->id, $data['owner_id']);
            }
            
            return $team;
        });
    }

    /**
     * Delete a team.
     */
    public function delete(string $id): bool
    {
        return DB::transaction(function () use ($id) {
            return $this->repository->delete($id);
        });
    }

    /**
     * Paginate teams.
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage, $filters);
    }

    /**
     * Find teams by owner.
     */
    public function findByOwner(string $ownerId)
    {
        return $this->repository->findByOwner($ownerId);
    }

    /**
     * Add a member to a team.
     */
    public function addMember(string $teamId, string $userId): bool
    {
        return DB::transaction(function () use ($teamId, $userId) {
            return $this->repository->addMember($teamId, $userId);
        });
    }

    /**
     * Remove a member from a team.
     */
    public function removeMember(string $teamId, string $userId): bool
    {
        return DB::transaction(function () use ($teamId, $userId) {
            // Don\'t allow removing the team owner
            $team = $this->repository->find($teamId);
            if ($team && $team->owner_id == $userId) {
                return false;
            }
            
            return $this->repository->removeMember($teamId, $userId);
        });
    }

    /**
     * Get team members.
     */
    public function getMembers(string $teamId)
    {
        return $this->repository->getMembers($teamId);
    }

    /**
     * Check if user is team owner.
     */
    public function isOwner(string $teamId, string $userId): bool
    {
        $team = $this->repository->find($teamId);
        return $team && $team->owner_id == $userId;
    }

    /**
     * Check if user is team member.
     */
public function isMember(string $teamId, string $userId): bool
{
    $members = $this->repository->getMembers($teamId);
    return $members->contains('id', $userId);
}

    /**
     * Get teams where user is a member (including owned teams).
     */
/**
 * Get teams where user is a member (including owned teams).
 */
public function getUserTeams(string $userId)
    {
        // Utilisez la méthode du repository
        return $this->repository->getUserTeams($userId);
    }

    /**
     * Transfer team ownership.
     */
    public function transferOwnership(string $teamId, string $newOwnerId): ?Team
    {
        return DB::transaction(function () use ($teamId, $newOwnerId) {
            // First, ensure new owner is a team member
            $this->repository->addMember($teamId, $newOwnerId);
            
            // Then transfer ownership
            return $this->repository->update($teamId, [
                'owner_id' => $newOwnerId
            ]);
        });
    }

    /**
     * Search teams with advanced filters.
     */
    public function search(array $criteria, int $perPage = 15): LengthAwarePaginator
    {
        $filters = [];
        
        if (isset($criteria['search'])) {
            $filters['search'] = $criteria['search'];
        }
        
        if (isset($criteria['owner_id'])) {
            $filters['owner_id'] = $criteria['owner_id'];
        }
        
        if (isset($criteria['is_public'])) {
            $filters['is_public'] = $criteria['is_public'];
        }
        
        return $this->repository->paginate($perPage, $filters);
    }

    /**
     * Get team statistics.
     */
    public function getStatistics(string $teamId): array
    {
        $team = $this->repository->find($teamId);
        
        if (!$team) {
            return [];
        }
        
        $members = $this->repository->getMembers($teamId);
        
        return [
            'total_members' => $members->count(),
            'created_at' => $team->created_at,
            'is_public' => $team->is_public,
            // Add more statistics as needed
        ];
    }
}
