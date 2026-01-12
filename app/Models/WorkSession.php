<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_time_id',
        'session_start',
        'session_end',
        'duration_seconds',
        'type'
    ];

    protected $casts = [
        'session_start' => 'datetime',
        'session_end' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['duration_hours'];

    public function workTime()
    {
        return $this->belongsTo(WorkTime::class);
    }

    public function getDurationHoursAttribute(): float
    {
        return $this->duration_seconds / 3600;
    }

    // Calcul automatique de la durée
    public static function boot()
    {
        parent::boot();

        static::saving(function ($session) {
            if ($session->session_start && $session->session_end) {
                $start = \Carbon\Carbon::parse($session->session_start);
                $end = \Carbon\Carbon::parse($session->session_end);
                $session->duration_seconds = $end->diffInSeconds($start);
            }
        });
    }
}