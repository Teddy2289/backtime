<?php

namespace Modules\User\Infrastructure\Repositories;

use Modules\User\Domain\Entities\User;
use Modules\User\Domain\Interfaces\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserRepository implements UserRepositoryInterface
{
    public function find(string $id): ?User
    {
        return User::find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(string $id, array $data): ?User
    {
        $user = $this->find($id);

        if (!$user) {
            return null;
        }

        $user->update($data);
        return $user->fresh();
    }

    public function delete(string $id): bool
    {
        $user = $this->find($id);

        if (!$user) {
            return false;
        }

        return $user->delete();
    }

    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = User::query();

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('email', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (isset($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        return $query->paginate($perPage);
    }

    public function count(array $conditions = []): int
    {
        return User::where($conditions)->count();
    }
}
