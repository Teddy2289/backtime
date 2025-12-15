<?php

namespace Modules\TaskTimeLog\Domain\Interfaces;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\TaskTimeLog\Domain\Entities\TaskTimeLog;

interface TaskTimeLogRepositoryInterface
{
    /**
     * Récupérer tous les time logs avec pagination
     */
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Récupérer un time log par son ID
     */
    public function findById(int $id): ?TaskTimeLog;

    /**
     * Créer un nouveau time log
     */
    public function create(array $data): TaskTimeLog;

    /**
     * Mettre à jour un time log
     */
    public function update(int $id, array $data): ?TaskTimeLog;

    /**
     * Supprimer un time log
     */
    public function delete(int $id): bool;

    /**
     * Récupérer les time logs d'une tâche
     */
    public function getByTask(int $taskId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Récupérer les time logs d'un utilisateur
     */
    public function getByUser(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Récupérer les time logs en cours d'un utilisateur
     */
    public function getRunningTimeLogs(int $userId = null): Collection;

    /**
     * Démarrer un time log
     */
    public function startTimeLog(int $taskId, int $userId, string $note = null): TaskTimeLog;

    /**
     * Arrêter un time log
     */
    public function stopTimeLog(int $id, string $note = null): ?TaskTimeLog;

    /**
     * Arrêter tous les time logs en cours d'un utilisateur
     */
    public function stopAllRunningLogs(int $userId): bool;

    /**
     * Obtenir le time log en cours d'une tâche
     */
    public function getRunningTimeLogForTask(int $taskId): ?TaskTimeLog;

    /**
     * Obtenir le time log en cours d'un utilisateur
     */
    public function getRunningTimeLogForUser(int $userId): ?TaskTimeLog;

    /**
     * Calculer le temps total travaillé sur une tâche
     */
    public function getTotalTimeForTask(int $taskId): int;

    /**
     * Calculer le temps total travaillé par un utilisateur
     */
    public function getTotalTimeForUser(int $userId, array $filters = []): int;

    /**
     * Obtenir les statistiques de temps
     */
    public function getTimeStatistics(array $filters = []): array;
}