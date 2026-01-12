<?php

namespace App\Services;

use App\Models\User;
use App\Models\WorkTime;
use Carbon\Carbon;

class WorkTimeService
{
    public function getExpectedHoursForDate(Carbon $date): int
    {
        $dayOfWeek = $date->dayOfWeek;

        if ($dayOfWeek === 0) { // Dimanche
            return 0;
        }

        if ($dayOfWeek === 6) { // Samedi
            return 4; // heures
        }

        return 8; // heures (Lundi-Vendredi)
    }

    // app/Services/WorkTimeService.php
    public function isWithinWorkingHours(Carbon $date, Carbon $time): bool
    {
        $dayOfWeek = $date->dayOfWeek;

        if ($dayOfWeek === 0) { // Dimanche
            return false; // Pas de travail le dimanche
        }

        $hour = $time->hour;

        if ($dayOfWeek === 6) { // Samedi
            return $hour >= 9 && $hour < 13;
        }

        // Lundi-Vendredi
        return $hour >= 9 && $hour < 17;
    }

    // Ajouter une méthode pour vérifier seulement si c'est un jour ouvrable
    public function isWorkDay(Carbon $date): bool
    {
        $dayOfWeek = $date->dayOfWeek;
        return $dayOfWeek >= 1 && $dayOfWeek <= 6; // Lundi à Samedi
    }

    // Nouvelle méthode pour savoir si on est hors des heures normales
    public function isOutsideNormalHours(Carbon $date, Carbon $time): bool
    {
        return !$this->isWithinWorkingHours($date, $time);
    }

    public function getWorkDaySummary(User $user, Carbon $date)
    {
        $workTime = WorkTime::where('user_id', $user->id)
            ->where('work_date', $date)
            ->first();

        if (!$workTime) {
            return null;
        }

        return [
            'date' => $date->format('Y-m-d'),
            'day_name' => $date->locale('fr')->dayName,
            'status' => $workTime->status,
            'net_hours' => $workTime->net_hours,
            'target_hours' => $this->getExpectedHoursForDate($date),
            'progress' => $workTime->progress_percentage,
            'sessions' => $workTime->sessions->map(function ($session) {
                return [
                    'type' => $session->type,
                    'start' => $session->session_start->format('H:i:s'),
                    'end' => $session->session_end ? $session->session_end->format('H:i:s') : null,
                    'duration_hours' => $session->duration_hours
                ];
            })
        ];
    }
}