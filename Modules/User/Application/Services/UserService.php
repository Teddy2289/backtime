<?php

namespace Modules\User\Application\Services;

use Modules\User\Domain\Entities\User;
use Modules\User\Domain\Enums\UserRole;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserService
{
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $userData = $this->processUserData($data);

            // Gérer l'upload de l'avatar s'il existe
            if (isset($data['avatar']) && $data['avatar'] instanceof UploadedFile) {
                $userData['avatar'] = $this->storeAvatar($data['avatar']);
                $userData['avatar_url'] = Storage::url($userData['avatar']);
            }

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
            $userData = $this->processUserData($data, $user);

            // Gérer l'upload de l'avatar s'il existe
            if (isset($data['avatar']) && $data['avatar'] instanceof UploadedFile) {
                // Supprimer l'ancien avatar s'il existe
                $this->deleteAvatar($user->avatar);
                
                $userData['avatar'] = $this->storeAvatar($data['avatar']);
                $userData['avatar_url'] = Storage::url($userData['avatar']);
            } else {
                // Si pas de nouveau fichier, ne pas modifier l'avatar
                unset($userData['avatar']);
            }

            // Hash du mot de passe si fourni
            if (isset($userData['password'])) {
                $userData['password'] = Hash::make($userData['password']);
            } else {
                unset($userData['password']);
            }

            // Gérer le rôle
            if (isset($userData['role'])) {
                $role = UserRole::tryFrom($userData['role'])
                    ? UserRole::from($userData['role'])
                    : UserRole::USER;

                $userData['role'] = $role->value;
                $user->syncRoles([$role->value]);
            }

            $user->update($userData);

            return $user->fresh();
        });
    }

    public function delete(User $user): bool
    {
        return DB::transaction(function () use ($user) {
            // Supprimer l'avatar s'il existe
            $this->deleteAvatar($user->avatar);

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

    /**
     * Upload d'avatar séparé
     */
    public function uploadAvatar(User $user, UploadedFile $avatarFile): User
    {
        return DB::transaction(function () use ($user, $avatarFile) {
            // Supprimer l'ancien avatar s'il existe
            $this->deleteAvatar($user->avatar);
            
            // Stocker le nouvel avatar
            $avatarPath = $this->storeAvatar($avatarFile);
            
            $user->update([
                'avatar' => $avatarPath,
                'avatar_url' => Storage::url($avatarPath),
            ]);

            return $user->fresh();
        });
    }

    /**
     * Supprimer l'avatar d'un utilisateur
     */
    public function removeAvatar(User $user): User
    {
        return DB::transaction(function () use ($user) {
            $this->deleteAvatar($user->avatar);
            
            $user->update([
                'avatar' => null,
                'avatar_url' => null,
            ]);

            return $user->fresh();
        });
    }

    /**
     * Stocker un fichier avatar
     */
// Modules\User\Application\Services\UserService.php
protected function storeAvatar(UploadedFile $avatarFile): string
{
    // Générer un nom de fichier unique
    $extension = $avatarFile->getClientOriginalExtension();
    $filename = 'avatar_' . time() . '_' . Str::random(10) . '.' . $extension;
    
    // Stocker directement dans public/avatars
    $path = 'avatars/' . $filename;
    $avatarFile->move(public_path('avatars'), $filename);
    
    return $path; // Retourne 'avatars/filename.png'
}

    /**
     * Supprimer un fichier avatar
     */
    protected function deleteAvatar(?string $avatarPath): void
    {
        if ($avatarPath && Storage::disk('public')->exists($avatarPath)) {
            Storage::disk('public')->delete($avatarPath);
        }
    }

    /**
     * Traiter les données utilisateur (filtrer et valider)
     */
    protected function processUserData(array $data, ?User $user = null): array
    {
        $allowedFields = [
            'name', 'email', 'password', 'role', 
            'avatar', 'avatar_url', 'email_verified_at'
        ];

        $processedData = array_filter($data, function ($key) use ($allowedFields) {
            return in_array($key, $allowedFields);
        }, ARRAY_FILTER_USE_KEY);

        // Traiter email_verified_at
        if (isset($processedData['email_verified_at'])) {
            if ($processedData['email_verified_at'] === 'true' || $processedData['email_verified_at'] === true) {
                $processedData['email_verified_at'] = now();
            } elseif ($processedData['email_verified_at'] === 'false' || $processedData['email_verified_at'] === false || $processedData['email_verified_at'] === 'null') {
                $processedData['email_verified_at'] = null;
            }
        }

        return $processedData;
    }

    /**
     * Obtenir l'URL complète de l'avatar
     */
  public function getAvatarUrl(?string $avatarPath): ?string
{
    if (!$avatarPath) {
        return null;
    }

    // Si le chemin commence déjà par 'avatars/', ne pas ajouter de préfixe
    if (strpos($avatarPath, 'avatars/') === 0) {
        return asset('storage/' . $avatarPath);
    }

    return asset('storage/' . $avatarPath);
}
}