<?php

namespace Modules\Task\Domain\Interfaces;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Task\Domain\Entities\Task;

interface TaskRepositoryInterface
{
    /**
     * Récupérer toutes les tâches avec pagination
     */
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Récupérer une tâche par son ID
     */
    public function findById(int $id): ?Task;

    /**
     * Créer une nouvelle tâche
     */
    public function create(array $data): Task;

    /**
     * Mettre à jour une tâche
     */
    public function update(int $id, array $data): ?Task;

    /**
     * Supprimer une tâche (soft delete)
     */
    public function delete(int $id): bool;

    /**
     * Restaurer une tâche supprimée
     */
    public function restore(int $id): bool;

    /**
     * Récupérer les tâches d'un projet
     */
    public function getByProject(int $projectId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Récupérer les tâches assignées à un utilisateur
     */
    public function getByAssignee(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Récupérer les tâches par statut
     */
    public function getByStatus(string $status, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Rechercher des tâches
     */
    public function search(string $query, array $filters = []): LengthAwarePaginator;

    /**
     * Compter les tâches par statut
     */
    public function countByStatus(int $projectId = null): array;

    /**
     * Mettre à jour le statut d'une tâche
     */
    public function updateStatus(int $id, string $status): ?Task;

    /**
     * Assigner une tâche à un utilisateur
     */
    public function assignTo(int $id, int $userId): ?Task;

    /**
     * Récupérer les tâches en retard
     */
    public function getOverdueTasks(int $projectId = null, int $perPage = 15): LengthAwarePaginator;

    /**
     * Récupérer les tâches à venir
     */
    public function getUpcomingTasks(int $projectId = null, int $perPage = 15): LengthAwarePaginator;

    /**
     * Calculer les statistiques des tâches
     */
    public function getStatistics(int $projectId = null): array;
    public function getScheduledTasks(int $projectId = null, int $perPage = 15): LengthAwarePaginator;
    public function getUnscheduledTasks(int $projectId = null, int $perPage = 15): LengthAwarePaginator;
}