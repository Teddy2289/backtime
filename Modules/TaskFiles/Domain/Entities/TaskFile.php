<?php

namespace Modules\TaskFiles\Domain\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Modules\Task\Domain\Entities\Task;
use Modules\User\Domain\Entities\User;

class TaskFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'file_url',
        'file_name',
        'file_size',
        'mime_type',
        'extension',
        'uploaded_by',
        'description',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'file_url_full',
        'formatted_size',
        'icon',
        'file_type',       // Add computed attribute for compatibility
        'file_path',       // Add computed attribute for compatibility
    ];

    /**
     * Relation avec la tâche
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    /**
     * Relation avec l'utilisateur qui a uploadé le fichier
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Obtenir l'URL complète du fichier
     */
    public function getFileUrlFullAttribute(): string
    {
        if (filter_var($this->file_url, FILTER_VALIDATE_URL)) {
            return $this->file_url;
        }

        return asset('storage/' . $this->file_url);
    }

    /**
     * Computed attribute for compatibility with controller
     */
    public function getFilePathAttribute(): string
    {
        return $this->file_url; // Map file_url to file_path for compatibility
    }

    /**
     * Computed attribute for compatibility with controller
     */
    public function getFileTypeAttribute(): string
    {
        return $this->mime_type ?? $this->getMimeTypeAttribute();
    }

    /**
     * Obtenir la taille formatée
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    /**
     * Obtenir l'icône en fonction du type de fichier
     */
    public function getIconAttribute(): string
    {
        $extension = strtolower($this->extension ?? pathinfo($this->file_name, PATHINFO_EXTENSION));

        $icons = [
            'pdf' => 'fa-file-pdf',
            'doc' => 'fa-file-word',
            'docx' => 'fa-file-word',
            'xls' => 'fa-file-excel',
            'xlsx' => 'fa-file-excel',
            'ppt' => 'fa-file-powerpoint',
            'pptx' => 'fa-file-powerpoint',
            'txt' => 'fa-file-alt',
            'zip' => 'fa-file-archive',
            'rar' => 'fa-file-archive',
            'jpg' => 'fa-file-image',
            'jpeg' => 'fa-file-image',
            'png' => 'fa-file-image',
            'gif' => 'fa-file-image',
            'svg' => 'fa-file-image',
            'mp4' => 'fa-file-video',
            'avi' => 'fa-file-video',
            'mov' => 'fa-file-video',
            'mp3' => 'fa-file-audio',
            'wav' => 'fa-file-audio',
        ];

        return $icons[$extension] ?? 'fa-file';
    }

    /**
     * Extraire l'extension du fichier
     */
    public function getExtensionAttribute(): string
    {
        if (isset($this->attributes['extension'])) {
            return strtolower($this->attributes['extension']);
        }

        return strtolower(pathinfo($this->file_name, PATHINFO_EXTENSION));
    }

    /**
     * Obtenir le type MIME du fichier
     */
    public function getMimeTypeAttribute(): string
    {
        if (isset($this->attributes['mime_type'])) {
            return $this->attributes['mime_type'];
        }

        $mimeTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'zip' => 'application/zip',
        ];

        return $mimeTypes[$this->extension] ?? 'application/octet-stream';
    }

    /**
     * Vérifier si le fichier est une image
     */
    public function isImage(): bool
    {
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'bmp'];
        return in_array($this->extension, $imageExtensions);
    }

    /**
     * Vérifier si le fichier est un document
     */
    public function isDocument(): bool
    {
        $documentExtensions = ['pdf', 'doc', 'docx', 'txt', 'rtf'];
        return in_array($this->extension, $documentExtensions);
    }

    /**
     * Vérifier si le fichier est une archive
     */
    public function isArchive(): bool
    {
        $archiveExtensions = ['zip', 'rar', '7z', 'tar', 'gz'];
        return in_array($this->extension, $archiveExtensions);
    }

    /**
     * Scope pour les fichiers d'une tâche
     */
    public function scopeByTask($query, int $taskId)
    {
        return $query->where('task_id', $taskId);
    }

    /**
     * Scope pour les fichiers uploadés par un utilisateur
     */
    public function scopeByUploader($query, int $userId)
    {
        return $query->where('uploaded_by', $userId);
    }

    /**
     * Scope pour les images
     */
    public function scopeImages($query)
    {
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'bmp'];
        return $query->whereIn('extension', $imageExtensions);
    }

    /**
     * Scope pour les documents
     */
    public function scopeDocuments($query)
    {
        $documentExtensions = ['pdf', 'doc', 'docx', 'txt', 'rtf'];
        return $query->whereIn('extension', $documentExtensions);
    }

    /**
     * Scope pour les fichiers récents
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}