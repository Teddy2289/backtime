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

            // Hash du mot de passe
            if (isset($userData['password'])) {
                $userData['password'] = Hash::make($userData['password']);
            }

            // Définir le rôle par défaut si non spécifié
            if (!isset($userData['role'])) {
                $userData['role'] = UserRole::USER->value;
            } else {
                // Valider que le rôle est valide
                $userData['role'] = UserRole::tryFrom($userData['role'])
                    ? UserRole::from($userData['role'])->value
                    : UserRole::USER->value;
            }

            $user = User::create($userData);

            // Assigner le rôle avec Spatie Permission
            $user->assignRole($userData['role']);

            // Synchroniser les rôles avec l'attribut 'role'
            $user->syncRoles([$userData['role']]);

            return $user;
        });
    }

    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            // Hash du mot de passe si fourni
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            // Gérer le rôle
            if (isset($data['role'])) {
                $role = UserRole::tryFrom($data['role'])
                    ? UserRole::from($data['role'])
                    : UserRole::USER;

                $data['role'] = $role->value;
                $user->syncRoles([$role->value]);
            }

            $user->update($data);

            return $user->fresh();
        });
    }

    public function delete(User $user): bool
    {
        return DB::transaction(function () use ($user) {
            // Supprimer les rôles associés
            $user->roles()->detach();

            return $user->delete();
        });
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
            $role = UserRole::tryFrom($filters['role']);
            if ($role) {
                $query->where('role', $role->value);
            }
        }

        return $query->paginate($perPage);
    }

    /**
     * Assigner un rôle spécifique à un utilisateur
     */
    public function assignRole(User $user, UserRole $role): User
    {
        return DB::transaction(function () use ($user, $role) {
            $user->role = $role;
            $user->save();

            $user->syncRoles([$role->value]);

            return $user->fresh();
        });
    }

    /**
     * Vérifier si un utilisateur a un rôle spécifique
     */
    public function hasRole(User $user, UserRole $role): bool
    {
        return $user->role === $role || $user->hasRole($role->value);
    }
}