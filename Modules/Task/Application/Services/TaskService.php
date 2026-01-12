<?php

namespace Modules\Task\Application\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Task\Domain\Entities\Task;
use Modules\Task\Domain\Interfaces\TaskRepositoryInterface;

class TaskService
{
    protected TaskRepositoryInterface $taskRepository;

    public function __construct(TaskRepositoryInterface $taskRepository)
    {
        $this->taskRepository = $taskRepository;
    }

    /**
     * Récupérer toutes les tâches avec pagination
     */
    public function getPaginatedTasks(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->taskRepository->getAll($filters, $perPage);
    }
    /**
     * Récupérer les tâches planifiées
     */
    public function getScheduledTasks(int $projectId = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->taskRepository->getScheduledTasks($projectId, $perPage);
    }

    /**
     * Récupérer les tâches non planifiées
     */
    public function getUnscheduledTasks(int $projectId = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->taskRepository->getUnscheduledTasks($projectId, $perPage);
    }

    /**
     * Planifier une tâche (définir une date de début)
     */
    public function scheduleTask(int $id, string $startDate, string $dueDate = null): ?Task
    {
        $data = ['start_date' => $startDate];

        if ($dueDate) {
            $data['due_date'] = $dueDate;
        }

        return $this->updateTask($id, $data);
    }
    /**
     * Récupérer une tâche par son ID
     */
    public function getTaskById(int $id): ?Task
    {
        return $this->taskRepository->findById($id);
    }

    /**
     * Créer une nouvelle tâche
     */
    public function createTask(array $data): Task
    {
        // Validation supplémentaire si nécessaire
        return $this->taskRepository->create($data);
    }

    /**
     * Mettre à jour une tâche
     */
    public function updateTask(int $id, array $data): ?Task
    {
        return $this->taskRepository->update($id, $data);
    }

    /**
     * Supprimer une tâche
     */
    public function deleteTask(int $id): bool
    {
        return $this->taskRepository->delete($id);
    }

    /**
     * Restaurer une tâche
     */
    public function restoreTask(int $id): bool
    {
        return $this->taskRepository->restore($id);
    }

    /**
     * Récupérer les tâches d'un projet
     */
    public function getPaginatedTasksByProject(int $projectId, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->taskRepository->getByProject($projectId, $filters, $perPage);
    }

    /**
     * Récupérer les tâches assignées à un utilisateur
     */
    public function getPaginatedTasksByAssignee(int $userId, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->taskRepository->getByAssignee($userId, $filters, $perPage);
    }

    /**
     * Rechercher des tâches
     */
    public function searchTasks(string $query, array $filters = []): LengthAwarePaginator
    {
        return $this->taskRepository->search($query, $filters);
    }

    /**
     * Récupérer les statistiques des tâches
     */
    public function getTaskStatistics(int $projectId = null): array
    {
        return $this->taskRepository->getStatistics($projectId);
    }

    /**
     * Mettre à jour le statut d'une tâche
     */
    public function updateTaskStatus(int $id, string $status): ?Task
    {
        return $this->taskRepository->updateStatus($id, $status);
    }

    /**
     * Assigner une tâche à un utilisateur
     */
    public function assignTaskToUser(int $taskId, int $userId): ?Task
    {
        return $this->taskRepository->assignTo($taskId, $userId);
    }

    /**
     * Récupérer les tâches en retard
     */
    public function getOverdueTasks(int $projectId = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->taskRepository->getOverdueTasks($projectId, $perPage);
    }

    /**
     * Récupérer les tâches à venir
     */
    public function getUpcomingTasks(int $projectId = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->taskRepository->getUpcomingTasks($projectId, $perPage);
    }

    /**
     * Compter les tâches par statut
     */
    public function countTasksByStatus(int $projectId = null): array
    {
        return $this->taskRepository->countByStatus($projectId);
    }

    /**
     * Marquer une tâche comme complétée
     */
    public function completeTask(int $id): ?Task
    {
        return $this->updateTaskStatus($id, 'done');
    }

    /**
     * Marquer une tâche comme en cours
     */
    public function startTask(int $id): ?Task
    {
        return $this->updateTaskStatus($id, 'doing');
    }

    /**
     * Remettre une tâche à faire
     */
    public function resetTaskToTodo(int $id): ?Task
    {
        return $this->updateTaskStatus($id, 'todo');
    }

    /**
     * Vérifier si l'utilisateur peut modifier la tâche
     */
    public function canUserModifyTask(int $userId, int $taskId): bool
    {
        $task = $this->getTaskById($taskId);

        if (!$task) {
            return false;
        }

        // L'utilisateur assigné, le propriétaire du projet, ou un admin peut modifier
        return $task->assigned_to === $userId
            || optional($task->project)->owner_id === $userId
            || auth()->user()->hasRole('admin');
    }
}
