<?php

namespace Modules\TaskTimeLog\Domain\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Task\Domain\Entities\Task;
use Modules\User\Domain\Entities\User;

class TaskTimeLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'user_id',
        'start_time',
        'end_time',
        'duration',
        'note',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'duration' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['is_running', 'formatted_duration'];

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
     * Vérifier si le time log est en cours
     */
    public function getIsRunningAttribute(): bool
    {
        return !empty($this->start_time) && empty($this->end_time);
    }

    /**
     * Obtenir la durée formatée
     */
    public function getFormattedDurationAttribute(): string
    {
        if (!$this->duration) {
            return '0m';
        }

        $hours = floor($this->duration / 60);
        $minutes = $this->duration % 60;

        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }

        return "{$minutes}m";
    }

    /**
     * Calculer la durée en minutes
     */
    public function calculateDuration(): ?int
    {
        if (!$this->start_time || !$this->end_time) {
            return null;
        }

        $diff = $this->start_time->diffInMinutes($this->end_time);
        $this->duration = max(1, $diff); // Au moins 1 minute

        return $this->duration;
    }

    /**
     * Démarrer un time log
     */
    public function start(): void
    {
        $this->start_time = now();
        $this->end_time = null;
        $this->duration = null;
        $this->save();
    }

    /**
     * Arrêter un time log
     */
    public function stop(string $note = null): void
    {
        if ($this->is_running) {
            $this->end_time = now();
            $this->calculateDuration();

            if ($note) {
                $this->note = $note;
            }

            $this->save();
        }
    }

    /**
     * Scope pour les time logs en cours
     */
    public function scopeRunning($query)
    {
        return $query->whereNotNull('start_time')->whereNull('end_time');
    }

    /**
     * Scope pour les time logs d'une tâche
     */
    public function scopeByTask($query, int $taskId)
    {
        return $query->where('task_id', $taskId);
    }

    /**
     * Scope pour les time logs d'un utilisateur
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope pour les time logs par date
     */
    public function scopeByDate($query, string $date)
    {
        return $query->whereDate('start_time', $date);
    }

    /**
     * Scope pour les time logs par période
     */
    public function scopeBetweenDates($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('start_time', [$startDate, $endDate]);
    }

    /**
     * Scope pour les time logs complets (avec durée)
     */
    public function scopeCompleted($query)
    {
        return $query->whereNotNull('duration')->whereNotNull('end_time');
    }
}