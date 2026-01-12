<?php

namespace Modules\TaskComment\Application\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\TaskComment\Domain\Entities\TaskComment;
use Modules\TaskComment\Domain\Interfaces\TaskCommentRepositoryInterface;

class TaskCommentService
{
    protected TaskCommentRepositoryInterface $commentRepository;

    public function __construct(TaskCommentRepositoryInterface $commentRepository)
    {
        $this->commentRepository = $commentRepository;
    }

    /**
     * Récupérer tous les commentaires avec pagination
     */
    public function getPaginatedComments(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->commentRepository->getAll($filters, $perPage);
    }

    /**
     * Récupérer un commentaire par son ID
     */
    public function getCommentById(int $id): ?TaskComment
    {
        return $this->commentRepository->findById($id);
    }

    /**
     * Créer un nouveau commentaire
     */
    public function createComment(array $data): TaskComment
    {
        return $this->commentRepository->create($data);
    }

    /**
     * Mettre à jour un commentaire
     */
    public function updateComment(int $id, array $data): ?TaskComment
    {
        return $this->commentRepository->update($id, $data);
    }

    /**
     * Supprimer un commentaire
     */
    public function deleteComment(int $id): bool
    {
        return $this->commentRepository->delete($id);
    }

    /**
     * Récupérer les commentaires d'une tâche
     */
    public function getPaginatedCommentsByTask(int $taskId, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->commentRepository->getByTask($taskId, $filters, $perPage);
    }

    /**
     * Récupérer les commentaires d'un utilisateur
     */
    public function getPaginatedCommentsByUser(int $userId, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->commentRepository->getByUser($userId, $filters, $perPage);
    }

    /**
     * Rechercher dans les commentaires
     */
    public function searchComments(string $query, array $filters = []): LengthAwarePaginator
    {
        return $this->commentRepository->search($query, $filters);
    }

    /**
     * Ajouter une réponse à un commentaire
     */
    public function addReply(int $parentId, array $data): ?TaskComment
    {
        return $this->commentRepository->addReply($parentId, $data);
    }

    /**
     * Récupérer les réponses d'un commentaire
     */
    public function getPaginatedReplies(int $commentId, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->commentRepository->getReplies($commentId, $filters, $perPage);
    }

    /**
     * Récupérer les commentaires récents
     */
    public function getRecentComments(int $limit = 10, array $filters = []): Collection
    {
        return $this->commentRepository->getRecentComments($limit, $filters);
    }

    /**
     * Obtenir les statistiques des commentaires
     */
    public function getCommentStatistics(int $taskId = null, int $userId = null): array
    {
        return $this->commentRepository->getStatistics($taskId, $userId);
    }

    /**
     * Compter les commentaires
     */
    public function countComments(int $taskId = null, int $userId = null): int
    {
        return $this->commentRepository->countComments($taskId, $userId);
    }

    /**
     * Vérifier si un utilisateur peut éditer un commentaire
     */
    public function canUserEditComment(int $userId, int $commentId): bool
    {
        $comment = $this->getCommentById($commentId);

        if (!$comment) {
            return false;
        }

        return $comment->can_edit;
    }

    /**
     * Vérifier si un utilisateur peut supprimer un commentaire
     */
    public function canUserDeleteComment(int $userId, int $commentId): bool
    {
        $comment = $this->getCommentById($commentId);

        if (!$comment) {
            return false;
        }

        return $comment->can_delete;
    }

    /**
     * Éditer un commentaire avec historique
     */
    public function editComment(int $id, string $newContent): ?TaskComment
    {
        $comment = $this->getCommentById($id);

        if (!$comment) {
            return null;
        }

        $oldContent = $comment->content;

        $comment->content = $newContent;
        $comment->saveEditHistory($oldContent);

        return $comment->fresh();
    }

    /**
     * Notifier les mentions dans un commentaire
     */
    public function notifyMentions(TaskComment $comment): void
    {
        $mentions = $comment->mentions;

        if (empty($mentions)) {
            return;
        }

        // Ici, vous pouvez implémenter la logique de notification
        // Par exemple : notifications en base de données, emails, etc.
        foreach ($mentions as $username) {
            // Logique pour notifier l'utilisateur mentionné
            // $user = User::where('username', $username)->first();
            // if ($user) {
            //     // Créer une notification
            // }
        }
    }

    /**
     * Vérifier si un utilisateur peut commenter sur une tâche
     */
    public function canUserCommentOnTask(int $userId, int $taskId): bool
    {
        // Cette logique peut être adaptée selon vos besoins
        // Par défaut, l'assigné de la tâche, le propriétaire du projet, 
        // les membres de l'équipe, ou un admin peuvent commenter
        return true;
    }
}