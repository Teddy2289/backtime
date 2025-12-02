<?php

namespace Modules\User\Domain\Interfaces;

use Modules\User\Domain\Entities\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface
{
    public function find(string $id): ?User;
    public function findByEmail(string $email): ?User;
    public function create(array $data): User;
    public function update(string $id, array $data): ?User;
    public function delete(string $id): bool;
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;
    public function count(array $conditions = []): int;
}
