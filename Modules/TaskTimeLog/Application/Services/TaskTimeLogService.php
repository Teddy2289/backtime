<?php

namespace Modules\TaskTimeLog\Application\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\TaskTimeLog\Domain\Entities\TaskTimeLog;
use Modules\TaskTimeLog\Domain\Interfaces\TaskTimeLogRepositoryInterface;

class TaskTimeLogService
{
    protected TaskTimeLogRepositoryInterface $timeLogRepository;

    public function __construct(TaskTimeLogRepositoryInterface $timeLogRepository)
    {
        $this->timeLogRepository = $timeLogRepository;
    }

    /**
     * Récupérer tous les time logs avec pagination
     */
    public function getPaginatedTimeLogs(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->timeLogRepository->getAll($filters, $perPage);
    }

    /**
     * Récupérer un time log par son ID
     */
    public function getTimeLogById(int $id): ?TaskTimeLog
    {
        return $this->timeLogRepository->findById($id);
    }

    /**
     * Créer un nouveau time log
     */
    public function createTimeLog(array $data): TaskTimeLog
    {
        return $this->timeLogRepository->create($data);
    }

    /**
     * Mettre à jour un time log
     */
    public function updateTimeLog(int $id, array $data): ?TaskTimeLog
    {
        return $this->timeLogRepository->update($id, $data);
    }

    /**
     * Supprimer un time log
     */
    public function deleteTimeLog(int $id): bool
    {
        return $this->timeLogRepository->delete($id);
    }

    /**
     * Récupérer les time logs d'une tâche
     */
    public function getPaginatedTimeLogsByTask(int $taskId, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->timeLogRepository->getByTask($taskId, $filters, $perPage);
    }

    /**
     * Récupérer les time logs d'un utilisateur
     */
    public function getPaginatedTimeLogsByUser(int $userId, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->timeLogRepository->getByUser($userId, $filters, $perPage);
    }

    /**
     * Récupérer les time logs en cours
     */
    public function getRunningTimeLogs(int $userId = null): Collection
    {
        return $this->timeLogRepository->getRunningTimeLogs($userId);
    }

    /**
     * Démarrer un time log
     */
    public function startTimeLog(int $taskId, int $userId, string $note = null): TaskTimeLog
    {
        return $this->timeLogRepository->startTimeLog($taskId, $userId, $note);
    }

    /**
     * Arrêter un time log
     */
    public function stopTimeLog(int $id, string $note = null): ?TaskTimeLog
    {
        return $this->timeLogRepository->stopTimeLog($id, $note);
    }

    /**
     * Arrêter le time log en cours d'un utilisateur
     */
    public function stopCurrentTimeLog(int $userId): bool
    {
        $timeLog = $this->timeLogRepository->getRunningTimeLogForUser($userId);

        if ($timeLog) {
            $this->timeLogRepository->stopTimeLog($timeLog->id, 'Arrêté manuellement');
            return true;
        }

        return false;
    }

    /**
     * Obtenir le time log en cours d'une tâche
     */
    public function getRunningTimeLogForTask(int $taskId): ?TaskTimeLog
    {
        return $this->timeLogRepository->getRunningTimeLogForTask($taskId);
    }

    /**
     * Obtenir le time log en cours d'un utilisateur
     */
    public function getRunningTimeLogForUser(int $userId): ?TaskTimeLog
    {
        return $this->timeLogRepository->getRunningTimeLogForUser($userId);
    }

    /**
     * Calculer le temps total travaillé sur une tâche
     */
    public function getTotalTimeForTask(int $taskId): int
    {
        return $this->timeLogRepository->getTotalTimeForTask($taskId);
    }

    /**
     * Calculer le temps total travaillé par un utilisateur
     */
    public function getTotalTimeForUser(int $userId, array $filters = []): int
    {
        return $this->timeLogRepository->getTotalTimeForUser($userId, $filters);
    }

    /**
     * Obtenir les statistiques de temps
     */
    public function getTimeStatistics(array $filters = []): array
    {
        return $this->timeLogRepository->getTimeStatistics($filters);
    }

    /**
     * Vérifier si un utilisateur peut démarrer un time log sur une tâche
     */
    public function canUserStartTimeLog(int $userId, int $taskId): bool
    {
        // Vérifier si l'utilisateur est assigné à la tâche
        // ou s'il a la permission de tracker le temps
        // (cette logique peut être adaptée selon vos besoins)
        return true;
    }

    /**
     * Vérifier si un utilisateur peut modifier un time log
     */
    public function canUserModifyTimeLog(int $userId, int $timeLogId): bool
    {
        $timeLog = $this->getTimeLogById($timeLogId);

        if (!$timeLog) {
            return false;
        }

        // L'utilisateur qui a créé le time log peut le modifier
        // ou un admin
        return $timeLog->user_id === $userId || auth()->user()->hasRole('admin');
    }
}