<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class WorkTime extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_PAUSED = 'paused';
    const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'user_id',
        'work_date',
        'start_time',
        'end_time',
        'pause_start',
        'pause_end',
        'total_seconds',
        'pause_seconds',
        'net_seconds',
        'status',
        'notes',
        'daily_target',
    ];

    protected $casts = [
        'work_date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'pause_start' => 'datetime',
        'pause_end' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'total_hours',
        'pause_hours',
        'net_hours',
        'daily_target_hours',
        'progress_percentage',
        'is_within_schedule',
        'day_name'
    ];

    // Retirez ces colonnes qui n'existent pas dans la table
    // protected $appends = ['net_hours', 'pause_hours']; // Ces sont des accesseurs

    // Relation avec l'utilisateur
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relation avec les sessions
    public function sessions()
    {
        return $this->hasMany(WorkSession::class);
    }

    // Accesseurs (calculés à la volée)
    public function getTotalHoursAttribute(): float
    {
        return $this->total_seconds / 3600;
    }

    public function getPauseHoursAttribute(): float
    {
        return $this->pause_seconds / 3600;
    }

    public function getNetHoursAttribute(): float
    {
        return $this->net_seconds / 3600;
    }

    public function getDailyTargetHoursAttribute(): int
    {
        $dayOfWeek = Carbon::parse($this->work_date)->dayOfWeek;

        if ($dayOfWeek === 6) { // Samedi
            return 4; // heures
        }

        if ($dayOfWeek === 0) { // Dimanche
            return 0;
        }

        // Lundi-Vendredi (1-5) : 8 heures
        return 8; // heures
    }

    public function getDailyTargetAttribute(): int
    {
        return $this->getDailyTargetHoursAttribute() * 3600; // en secondes
    }

    public function getProgressPercentageAttribute(): float
    {
        $dailyTarget = $this->getDailyTargetAttribute();

        if ($dailyTarget === 0) {
            return 0;
        }

        $progress = ($this->net_seconds / $dailyTarget) * 100;
        return min(100, round($progress, 2));
    }

    public function getIsWithinScheduleAttribute(): bool
    {
        $workDate = Carbon::parse($this->work_date);
        $dayOfWeek = $workDate->dayOfWeek;

        // Heures de travail selon le jour
        if ($dayOfWeek === 6) { // Samedi
            $startHour = 9;
            $endHour = 13;
        } elseif ($dayOfWeek === 0) { // Dimanche
            return false; // Pas de travail le dimanche
        } else { // Lundi-Vendredi
            $startHour = 9;
            $endHour = 17;
        }

        // Vérifier si les heures de travail sont dans la plage horaire
        $startTime = $this->start_time ? Carbon::parse($this->start_time) : null;
        $endTime = $this->end_time ? Carbon::parse($this->end_time) : null;

        if ($startTime && $endTime) {
            $workDateStart = $workDate->copy()->setTime($startHour, 0);
            $workDateEnd = $workDate->copy()->setTime($endHour, 0);

            return $startTime->between($workDateStart, $workDateEnd) &&
                $endTime->between($workDateStart, $workDateEnd);
        }

        return true;
    }

    public function getDayNameAttribute(): string
    {
        return Carbon::parse($this->work_date)->locale('fr')->dayName;
    }

    // Méthodes utilitaires
    public function isWorkDay(): bool
    {
        $dayOfWeek = Carbon::parse($this->work_date)->dayOfWeek;
        return $dayOfWeek >= 1 && $dayOfWeek <= 6; // Lundi à Samedi
    }

    // Méthode corrigée pour calculer le temps total
    public function calculateTotalTime()
    {
        if (!$this->sessions || $this->sessions->isEmpty()) {
            $this->total_seconds = 0;
            $this->pause_seconds = 0;
            $this->net_seconds = 0;
            // NE PAS sauvegarder les heures calculées (net_hours, pause_hours)
            // car ce sont des accesseurs, pas des colonnes de la base
            return $this->save();
        }

        $totalWorkSeconds = 0;
        $totalPauseSeconds = 0;

        foreach ($this->sessions as $session) {
            $start = Carbon::parse($session->session_start);
            $end = $session->session_end
                ? Carbon::parse($session->session_end)
                : Carbon::now();

            $duration = $start->diffInSeconds($end);

            if ($session->type === 'work') {
                $totalWorkSeconds += $duration;
            } else {
                $totalPauseSeconds += $duration;
            }
        }

        // Mettre à jour uniquement les colonnes qui existent dans la table
        $this->total_seconds = $totalWorkSeconds + $totalPauseSeconds;
        $this->pause_seconds = $totalPauseSeconds;
        $this->net_seconds = $totalWorkSeconds;

        // NE PAS essayer de sauvegarder net_hours et pause_hours
        // car ce sont des accesseurs calculés, pas des colonnes

        $this->save();
    }
}
