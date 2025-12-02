<?php

namespace Modules\Team\Domain\Interfaces;

use Modules\Team\Domain\Entities\Team;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface TeamRepositoryInterface
{
    public function find(string $id): ?Team;
    public function create(array $data): Team;
    public function update(string $id, array $data): ?Team;
    public function delete(string $id): bool;
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;
    public function findByOwner(string $ownerId): Collection;
    public function addMember(string $teamId, string $userId): bool;
    public function removeMember(string $teamId, string $userId): bool;
    public function getMembers(string $teamId): Collection;
    
    // Ajoutez ces méthodes qui sont utilisées dans TeamService
    public function countByOwner(string $ownerId): int;
    public function getPublicTeams(int $perPage = 15): LengthAwarePaginator;
    public function getUserTeams(string $userId): Collection;
}