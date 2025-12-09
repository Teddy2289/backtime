<?php

namespace Modules\Team\Domain\Interfaces;

use Modules\Team\Domain\Entities\Team;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface TeamRepositoryInterface
{
    public function find(int $id): ?Team;
    public function create(array $data): Team;
    public function update(int $id, array $data): ?Team;
    public function delete(int $id): bool;
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;
    public function findByOwner(int $ownerId): Collection;
    public function addMember(int $teamId, int $userId): bool;
    public function removeMember(int $teamId, int $userId): bool;
    public function getMembers(int $teamId): Collection;

    // Ajoutez ces méthodes qui sont utilisées dans TeamService
    public function countByOwner(int $ownerId): int;
    public function getPublicTeams(int $perPage = 15): LengthAwarePaginator;
    public function getUserTeams(int $userId): Collection;
}