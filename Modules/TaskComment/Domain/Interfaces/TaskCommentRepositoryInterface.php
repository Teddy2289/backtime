<?php

namespace Modules\TaskComment\Domain\Interfaces;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\TaskComment\Domain\Entities\TaskComment;

interface TaskCommentRepositoryInterface
{
    /**
     * Récupérer tous les commentaires avec pagination
     */
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Récupérer un commentaire par son ID
     */
    public function findById(int $id): ?TaskComment;

    /**
     * Créer un nouveau commentaire
     */
    public function create(array $data): TaskComment;

    /**
     * Mettre à jour un commentaire
     */
    public function update(int $id, array $data): ?TaskComment;

    /**
     * Supprimer un commentaire
     */
    public function delete(int $id): bool;

    /**
     * Récupérer les commentaires d'une tâche
     */
    public function getByTask(int $taskId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Récupérer les commentaires d'un utilisateur
     */
    public function getByUser(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Rechercher dans les commentaires
     */
    public function search(string $query, array $filters = []): LengthAwarePaginator;

    /**
     * Compter les commentaires
     */
    public function countComments(int $taskId = null, int $userId = null): int;

    /**
     * Obtenir les statistiques des commentaires
     */
    public function getStatistics(int $taskId = null, int $userId = null): array;

    /**
     * Ajouter une réponse à un commentaire
     */
    public function addReply(int $parentId, array $data): ?TaskComment;

    /**
     * Récupérer les réponses d'un commentaire
     */
    public function getReplies(int $commentId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Récupérer les commentaires récents
     */
    public function getRecentComments(int $limit = 10, array $filters = []): Collection;

    /**
     * Marquer un commentaire comme édité
     */
    public function markAsEdited(int $id, string $oldContent): ?TaskComment;
}