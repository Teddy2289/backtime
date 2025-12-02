<?php

namespace Modules\User\Application\Services;

use Modules\User\Domain\Entities\User;
use Modules\User\Domain\Enums\UserRole;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserService
{
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $userData = $data;

            if (isset($userData['password'])) {
                $userData['password'] = Hash::make($userData['password']);
            }

            $user = User::create($userData);

            // Assigner le rôle si spécifié
            if (isset($data['role']) && in_array($data['role'], UserRole::values())) {
                $user->assignRole($data['role']);
            } else {
                $user->assignRole(UserRole::USER->value);
            }

            return $user;
        });
    }

    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            $user->update($data);

            if (isset($data['role']) && in_array($data['role'], UserRole::values())) {
                $user->syncRoles([$data['role']]);
            }

            return $user->fresh();
        });
    }

    public function delete(User $user): bool
    {
        return $user->delete();
    }

    public function paginate(int $perPage = 15, array $filters = [])
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
}
