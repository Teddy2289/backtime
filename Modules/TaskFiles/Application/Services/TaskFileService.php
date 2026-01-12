<?php

namespace Modules\TaskFiles\Application\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Modules\TaskFiles\Domain\Entities\TaskFile;
use Modules\TaskFiles\Domain\Interfaces\TaskFileRepositoryInterface;

class TaskFileService
{
    protected TaskFileRepositoryInterface $fileRepository;

    public function __construct(TaskFileRepositoryInterface $fileRepository)
    {
        $this->fileRepository = $fileRepository;
    }

    /**
     * Récupérer tous les fichiers avec pagination
     */
    public function getPaginatedFiles(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->fileRepository->getAll($filters, $perPage);
    }

    /**
     * Récupérer un fichier par son ID
     */
    public function getFileById(int $id): ?TaskFile
    {
        return $this->fileRepository->findById($id);
    }

    /**
     * Uploader un fichier
     */
    public function uploadFile(int $taskId, UploadedFile $file, int $uploadedBy, string $description = null): TaskFile
    {
        $fileName = $this->generateUniqueFileName($taskId, $file->getClientOriginalName());
        $filePath = $file->storeAs("tasks/{$taskId}", $fileName, 'public');

        // Créez seulement avec les colonnes qui existent
        $data = [
            'task_id' => $taskId,
            'file_url' => $filePath,  // Utilisez file_url comme dans votre DB
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'uploaded_by' => $uploadedBy,
        ];

        // Ajoutez seulement si la colonne existe
        if (Schema::hasColumn('task_files', 'mime_type')) {
            $data['mime_type'] = $file->getMimeType();
        }

        if (Schema::hasColumn('task_files', 'extension')) {
            $data['extension'] = $file->getClientOriginalExtension();
        }

        if ($description && Schema::hasColumn('task_files', 'description')) {
            $data['description'] = $description;
        }

        return $this->fileRepository->create($data);
    }
    /**
     * Uploader plusieurs fichiers
     */
    public function uploadFiles(int $taskId, array $files, int $uploadedBy): Collection
    {
        $uploadedFiles = new Collection();

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $uploadedFiles->push($this->uploadFile($taskId, $file, $uploadedBy));
            }
        }

        return $uploadedFiles;
    }

    /**
     * Supprimer un fichier
     */
    public function deleteFile(int $id): bool
    {
        $file = $this->getFileById($id);

        if (!$file) {
            return false;
        }

        // Supprimer le fichier du stockage
        if (Storage::disk('public')->exists($file->file_url)) {
            Storage::disk('public')->delete($file->file_url);
        }

        return $this->fileRepository->delete($id);
    }

    /**
     * Supprimer plusieurs fichiers
     */
    public function deleteFiles(array $ids): bool
    {
        $files = TaskFile::whereIn('id', $ids)->get();

        foreach ($files as $file) {
            // Supprimer les fichiers du stockage
            if (Storage::disk('public')->exists($file->file_url)) {
                Storage::disk('public')->delete($file->file_url);
            }
        }

        return $this->fileRepository->deleteMany($ids);
    }

    /**
     * Récupérer les fichiers d'une tâche
     */
    public function getPaginatedFilesByTask(int $taskId, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->fileRepository->getByTask($taskId, $filters, $perPage);
    }

    /**
     * Récupérer les fichiers uploadés par un utilisateur
     */
    public function getPaginatedFilesByUploader(int $userId, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->fileRepository->getByUploader($userId, $filters, $perPage);
    }

    /**
     * Rechercher des fichiers
     */
    public function searchFiles(string $query, array $filters = []): LengthAwarePaginator
    {
        return $this->fileRepository->search($query, $filters);
    }

    /**
     * Obtenir les statistiques des fichiers
     */
    public function getFileStatistics(int $taskId = null, int $userId = null): array
    {
        return $this->fileRepository->getStatistics($taskId, $userId);
    }

    /**
     * Mettre à jour la description d'un fichier
     */
    public function updateFileDescription(int $id, string $description): ?TaskFile
    {
        return $this->fileRepository->updateDescription($id, $description);
    }

    /**
     * Télécharger un fichier
     */
    public function downloadFile(int $id)
    {
        $file = $this->getFileById($id);

        if (!$file) {
            return null;
        }

        $filePath = storage_path('app/public/' . $file->file_url);

        if (!file_exists($filePath)) {
            return null;
        }

        return response()->download($filePath, $file->file_name);
    }

    /**
     * Obtenir l'URL de prévisualisation d'un fichier
     */
    public function getPreviewUrl(int $id): ?string
    {
        $file = $this->getFileById($id);

        if (!$file || !$file->isImage()) {
            return null;
        }

        return $file->file_url_full;
    }

    /**
     * Vérifier si un utilisateur peut modifier un fichier
     */
    public function canUserModifyFile(int $userId, int $fileId): bool
    {
        $file = $this->getFileById($fileId);

        if (!$file) {
            return false;
        }

        // L'utilisateur qui a uploadé le fichier, l'assigné de la tâche, 
        // le propriétaire du projet, ou un admin peut modifier
        return $file->uploaded_by === $userId
            || optional($file->task)->assigned_to === $userId
            || optional($file->task->project)->owner_id === $userId
            || auth()->user()->hasRole('admin');
    }

    /**
     * Vérifier si un utilisateur peut uploader des fichiers sur une tâche
     */
    public function canUserUploadToTask(int $userId, int $taskId): bool
    {
        // Cette logique peut être adaptée selon vos besoins
        // Par défaut, l'assigné de la tâche, le propriétaire du projet, 
        // ou un admin peuvent uploader des fichiers
        return true;
    }

    /**
     * Générer un nom de fichier unique
     */
    private function generateUniqueFileName(int $taskId, string $originalName): string
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $name = pathinfo($originalName, PATHINFO_FILENAME);

        // Nettoyer le nom
        $name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);

        $counter = 1;
        $fileName = "{$name}.{$extension}";

        while ($this->fileRepository->existsForTask($taskId, $fileName)) {
            $fileName = "{$name}_{$counter}.{$extension}";
            $counter++;
        }

        return $fileName;
    }

    /**
     * Valider un fichier avant l'upload
     */
    public function validateFile(UploadedFile $file): array
    {
        $errors = [];

        // Vérifier la taille (max 10MB)
        $maxSize = 10 * 1024 * 1024; // 10MB en bytes
        if ($file->getSize() > $maxSize) {
            $errors[] = 'Le fichier est trop volumineux (max 10MB)';
        }

        // Vérifier l'extension
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip', 'rar'];
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, $allowedExtensions)) {
            $errors[] = 'Type de fichier non autorisé';
        }

        // Vérifier le type MIME
        $allowedMimes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
            'application/zip',
            'application/x-rar-compressed',
        ];

        if (!in_array($file->getMimeType(), $allowedMimes)) {
            $errors[] = 'Type de fichier non autorisé';
        }

        return $errors;
    }
}