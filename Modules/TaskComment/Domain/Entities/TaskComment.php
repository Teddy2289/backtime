<?php

namespace Modules\TaskComment\Domain\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Task\Domain\Entities\Task;
use Modules\User\Domain\Entities\User;

class TaskComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'user_id',
        'content',
        'parent_id',
        'edited',
        'edit_history',
    ];

    protected $casts = [
        'edited' => 'boolean',
        'edit_history' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'is_edited',
        'can_edit',
        'can_delete',
        'mentions',
    ];

    /**
     * Relation avec la tâche
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    /**
     * Relation avec l'utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relation avec le commentaire parent (pour les réponses)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(TaskComment::class, 'parent_id');
    }

    /**
     * Relation avec les réponses
     */
    public function replies(): HasMany
    {
        return $this->hasMany(TaskComment::class, 'parent_id')->orderBy('created_at', 'asc');
    }

    /**
     * Vérifier si le commentaire a été édité
     */
    public function getIsEditedAttribute(): bool
    {
        // Vérifier que les dates ne sont pas nulles avant d'appeler gt()
        if (!$this->updated_at || !$this->created_at) {
            return (bool) $this->edited; // Retourne l'état booléen du champ 'edited' si les dates sont absentes
        }

        // Si edited est vrai OU si updated_at est strictement après created_at
        // J'ai aussi ajouté un contrôle de nullité pour être sûr
        return (bool) $this->edited || $this->updated_at->gt($this->created_at);
    }

    /**
     * Vérifier si l'utilisateur courant peut éditer le commentaire
     */
    public function getCanEditAttribute(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        // L'auteur du commentaire peut l'éditer pendant 15 minutes
        if ($this->user_id === $user->id) {
            return $this->created_at->diffInMinutes(now()) <= 15;
        }

        // Le propriétaire de la tâche ou un admin peut éditer
        if ($this->task && $this->task->assigned_to === $user->id) {
            return true;
        }

        // Le propriétaire du projet ou un admin
        if ($this->task && $this->task->project && $this->task->project->owner_id === $user->id) {
            return true;
        }

        return $user->hasRole('admin');
    }

    /**
     * Vérifier si l'utilisateur courant peut supprimer le commentaire
     */
    public function getCanDeleteAttribute(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        // L'auteur du commentaire peut le supprimer
        if ($this->user_id === $user->id) {
            return true;
        }

        // Le propriétaire de la tâche peut supprimer
        if ($this->task && $this->task->assigned_to === $user->id) {
            return true;
        }

        // Le propriétaire du projet ou un admin
        if ($this->task && $this->task->project && $this->task->project->owner_id === $user->id) {
            return true;
        }

        return $user->hasRole('admin');
    }

    /**
     * Extraire les mentions (@username) du contenu
     */
    public function getMentionsAttribute(): array
    {
        preg_match_all('/@([a-zA-Z0-9_]+)/', $this->content, $matches);
        return $matches[1] ?? [];
    }

    /**
     * Formater le contenu avec les mentions
     */
    public function getFormattedContentAttribute(): string
    {
        $content = htmlspecialchars($this->content);

        // Remplacer les mentions
        foreach ($this->mentions as $mention) {
            $content = str_replace(
                '@' . $mention,
                '<span class="mention" data-user="' . $mention . '">@' . $mention . '</span>',
                $content
            );
        }

        // Remplacer les URLs
        $content = preg_replace(
            '/(https?:\/\/[^\s]+)/',
            '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>',
            $content
        );

        // Remplacer les sauts de ligne
        $content = nl2br($content);

        return $content;
    }

    /**
     * Obtenir le nombre de réponses
     */
    public function getRepliesCountAttribute(): int
    {
        return $this->replies()->count();
    }

    /**
     * Enregistrer l'historique d'édition
     */
    public function saveEditHistory(string $oldContent): void
    {
        $history = $this->edit_history ?? [];

        $history[] = [
            'old_content' => $oldContent,
            'new_content' => $this->content,
            'edited_at' => now()->toISOString(),
            'edited_by' => auth()->id(),
        ];

        $this->edit_history = $history;
        $this->edited = true;
        $this->save();
    }

    /**
     * Ajouter une réponse
     */
    public function addReply(string $content, int $userId): TaskComment
    {
        return $this->replies()->create([
            'task_id' => $this->task_id,
            'user_id' => $userId,
            'content' => $content,
            'parent_id' => $this->id,
        ]);
    }

    /**
     * Scope pour les commentaires d'une tâche (sans les réponses)
     */
    public function scopeByTask($query, int $taskId)
    {
        return $query->where('task_id', $taskId)->whereNull('parent_id');
    }

    /**
     * Scope pour les commentaires d'un utilisateur
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope pour les commentaires récents
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope pour les commentaires avec réponses
     */
    public function scopeWithReplies($query)
    {
        return $query->with(['replies.user', 'replies.replies']);
    }

    /**
     * Scope pour les commentaires parent uniquement
     */
    public function scopeParentOnly($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope pour rechercher dans les commentaires
     */
    public function scopeSearch($query, string $searchTerm)
    {
        return $query->where('content', 'like', '%' . $searchTerm . '%');
    }
}