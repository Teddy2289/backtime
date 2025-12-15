<?php

namespace Modules\TaskFiles\Domain\Interfaces;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\TaskFiles\Domain\Entities\TaskFile;

interface TaskFileRepositoryInterface
{
    /**
     * Récupérer tous les fichiers avec pagination
     */
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Récupérer un fichier par son ID
     */
    public function findById(int $id): ?TaskFile;

    /**
     * Créer un nouveau fichier
     */
    public function create(array $data): TaskFile;

    /**
     * Créer plusieurs fichiers
     */
    public function createMany(array $filesData): Collection;

    /**
     * Mettre à jour un fichier
     */
    public function update(int $id, array $data): ?TaskFile;

    /**
     * Supprimer un fichier
     */
    public function delete(int $id): bool;

    /**
     * Supprimer plusieurs fichiers
     */
    public function deleteMany(array $ids): bool;

    /**
     * Récupérer les fichiers d'une tâche
     */
    public function getByTask(int $taskId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Récupérer les fichiers uploadés par un utilisateur
     */
    public function getByUploader(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Rechercher des fichiers
     */
    public function search(string $query, array $filters = []): LengthAwarePaginator;

    /**
     * Compter les fichiers par type
     */
    public function countByType(int $taskId = null): array;

    /**
     * Obtenir la taille totale des fichiers
     */
    public function getTotalSize(int $taskId = null, int $userId = null): int;

    /**
     * Obtenir les statistiques des fichiers
     */
    public function getStatistics(int $taskId = null, int $userId = null): array;

    /**
     * Vérifier si un fichier existe pour une tâche
     */
    public function existsForTask(int $taskId, string $fileName): bool;

    /**
     * Mettre à jour la description d'un fichier
     */
    public function updateDescription(int $id, string $description): ?TaskFile;
}