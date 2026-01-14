<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkTime;
use App\Models\WorkSession;
use App\Services\WorkTimeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class WorkTimeController extends Controller
{
    protected $workTimeService;

    public function __construct(WorkTimeService $workTimeService)
    {
        $this->workTimeService = $workTimeService;
    }

    // Démarrer la journée de travail
    public function startDay(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today();
        $currentTime = Carbon::now();

        // Vérifier si c'est un jour ouvrable (on garde seulement dimanche comme jour non travaillé)
        if (!$this->workTimeService->isWorkDay($today)) {
            return response()->json([
                'success' => false,
                'message' => 'Pas de travail aujourd\'hui (dimanche)'
            ], 400);
        }

        // Vérifier si une journée existe déjà
        $workTime = WorkTime::with('sessions')
            ->where('user_id', $user->id)
            ->where('work_date', $today)
            ->first();

        // CAS 1: Aucune journée existante - Créer une nouvelle
        if (!$workTime) {
            $workTime = WorkTime::create([
                'user_id' => $user->id,
                'work_date' => $today,
                'status' => 'in_progress',
                'start_time' => $currentTime,
                'daily_target' => $this->workTimeService->getExpectedHoursForDate($today) * 3600,
            ]);

            // Créer la première session de travail
            WorkSession::create([
                'work_time_id' => $workTime->id,
                'session_start' => $currentTime,
                'type' => 'work'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Journée démarrée',
                'data' => $workTime->load('sessions')
            ]);
        }

        // CAS 2: Journée déjà terminée - On permet de continuer en heures supplémentaires
        if ($workTime->status === 'completed') {
            // On change le statut pour permettre de continuer le travail
            $workTime->update(['status' => 'in_progress']);

            // Démarrer une nouvelle session de travail
            WorkSession::create([
                'work_time_id' => $workTime->id,
                'session_start' => $currentTime,
                'type' => 'work'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Travail repris (en heures supplémentaires)',
                'data' => $workTime->fresh()->load('sessions')
            ]);
        }

        // CAS 3: Journée en pause - Reprendre automatiquement
        if ($workTime->status === 'paused') {
            // Terminer la pause en cours
            $lastPauseSession = $workTime->sessions()
                ->where('type', 'pause')
                ->whereNull('session_end')
                ->latest()
                ->first();

            if ($lastPauseSession) {
                $lastPauseSession->update(['session_end' => $currentTime]);
            }

            // Démarrer une nouvelle session de travail
            WorkSession::create([
                'work_time_id' => $workTime->id,
                'session_start' => $currentTime,
                'type' => 'work'
            ]);

            $workTime->update(['status' => 'in_progress']);

            return response()->json([
                'success' => true,
                'message' => 'Travail repris (était en pause)',
                'data' => $workTime->fresh()->load('sessions')
            ]);
        }

        // CAS 4: Journée déjà en cours - Retourner l'état actuel
        if ($workTime->status === 'in_progress') {
            // Recalculer le temps pour être à jour
            $workTime->calculateTotalTime();

            return response()->json([
                'success' => true,
                'message' => 'Journée déjà en cours',
                'data' => $workTime->fresh()->load('sessions'),
                'already_started' => true
            ]);
        }

        // CAS 5: Statut 'pending' - Démarrer
        if ($workTime->status === 'pending') {
            $workTime->update([
                'status' => 'in_progress',
                'start_time' => $currentTime
            ]);

            WorkSession::create([
                'work_time_id' => $workTime->id,
                'session_start' => $currentTime,
                'type' => 'work'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Journée démarrée',
                'data' => $workTime->load('sessions')
            ]);
        }

        // CAS par défaut
        return response()->json([
            'success' => false,
            'message' => 'Statut inconnu: ' . $workTime->status,
            'data' => $workTime
        ], 400);
    }

    // Mettre en pause
    public function pause(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today();

        $workTime = WorkTime::where('user_id', $user->id)
            ->where('work_date', $today)
            // Seulement si en cours
            ->where('status', WorkTime::STATUS_IN_PROGRESS)
            ->first();

        if (!$workTime) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune journée en cours'
            ], 404);
        }

        // Terminer la session de travail en cours
        $lastWorkSession = $workTime->sessions()
            ->where('type', 'work')
            ->whereNull('session_end')
            ->latest()
            ->first();

        if ($lastWorkSession) {
            $lastWorkSession->update([
                'session_end' => Carbon::now(),
                'duration_seconds' => Carbon::now()->diffInSeconds($lastWorkSession->session_start)
            ]);
        }

        // Démarrer une session de pause
        $workTime->sessions()->create([
            'session_start' => Carbon::now(),
            'type' => 'pause',
        ]);

        // Mettre à jour le statut
        $workTime->update([
            'status' => WorkTime::STATUS_PAUSED,
            'pause_start' => Carbon::now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pause démarrée',
            'data' => $workTime->fresh()->load('sessions')
        ]);
    }

    // Reprendre le travail
    public function resume(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today();

        $workTime = WorkTime::where('user_id', $user->id)
            ->where('work_date', $today)
            ->where('status', WorkTime::STATUS_PAUSED) // Seulement si en pause
            ->first();

        if (!$workTime) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune journée en pause'
            ], 404);
        }

        // Terminer la session de pause en cours
        $lastPauseSession = $workTime->sessions()
            ->where('type', 'pause')
            ->whereNull('session_end')
            ->latest()
            ->first();

        if ($lastPauseSession) {
            $lastPauseSession->update([
                'session_end' => Carbon::now(),
                'duration_seconds' => Carbon::now()->diffInSeconds($lastPauseSession->session_start)
            ]);
        }

        // Démarrer une nouvelle session de travail
        $workTime->sessions()->create([
            'session_start' => Carbon::now(),
            'type' => 'work',
        ]);

        // Mettre à jour le statut
        $workTime->update([
            'status' => WorkTime::STATUS_IN_PROGRESS,
            'pause_end' => Carbon::now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Travail repris',
            'data' => $workTime->fresh()->load('sessions')
        ]);
    }

    // Terminer la journée
    public function endDay(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today();

        // Chercher la journée d'aujourd'hui (peut être déjà terminée)
        $workTime = WorkTime::where('user_id', $user->id)
            ->where('work_date', $today)
            ->whereIn('status', [WorkTime::STATUS_IN_PROGRESS, WorkTime::STATUS_PAUSED])
            ->first();

        if (!$workTime) {
            // Si pas de journée en cours, chercher n'importe quelle journée pour aujourd'hui
            $workTime = WorkTime::where('user_id', $user->id)
                ->where('work_date', $today)
                ->first();

            if (!$workTime) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune journée trouvée pour aujourd\'hui'
                ], 404);
            }
        }

        // Si la journée est déjà terminée, informer l'utilisateur qu'il peut la redémarrer
        if ($workTime->status === WorkTime::STATUS_COMPLETED) {
            return response()->json([
                'success' => false,
                'message' => 'La journée est déjà terminée. Utilisez "Démarrer" pour continuer en heures supplémentaires.',
                'already_completed' => true,
                'data' => $workTime
            ], 400);
        }

        // Terminer la dernière session de travail si elle existe
        $lastSession = $workTime->sessions()
            ->where('type', 'work')
            ->whereNull('session_end')
            ->latest()
            ->first();

        if ($lastSession) {
            $lastSession->update([
                'session_end' => Carbon::now(),
                'duration_seconds' => Carbon::now()->diffInSeconds($lastSession->session_start)
            ]);
        }

        // Terminer la session de pause si elle existe
        $lastPauseSession = $workTime->sessions()
            ->where('type', 'pause')
            ->whereNull('session_end')
            ->latest()
            ->first();

        if ($lastPauseSession) {
            $lastPauseSession->update([
                'session_end' => Carbon::now(),
                'duration_seconds' => Carbon::now()->diffInSeconds($lastPauseSession->session_start)
            ]);
        }

        // Calculer les totaux AVANT de mettre à jour le statut
        $workTime->calculateTotalTime();

        // Calculer les heures supplémentaires
        $dailyTarget = $workTime->getDailyTargetAttribute();
        $extraHours = 0;
        $extraSeconds = 0;

        if ($workTime->net_seconds > $dailyTarget) {
            $extraSeconds = $workTime->net_seconds - $dailyTarget;
            $extraHours = $extraSeconds / 3600;
        }

        // Mettre à jour le statut
        $workTime->update([
            'status' => WorkTime::STATUS_COMPLETED,
            'end_time' => Carbon::now()
        ]);

        // Recharger les sessions pour avoir les données fraîches
        $workTime->load('sessions');

        return response()->json([
            'success' => true,
            'message' => 'Journée terminée' . ($extraHours > 0 ? ' (+' . round($extraHours, 2) . 'h supplémentaires)' : ''),
            'data' => $workTime,
            'extra_hours' => round($extraHours, 2),
            'extra_seconds' => $extraSeconds,
            'daily_target_hours' => $dailyTarget / 3600,
            'worked_hours' => $workTime->net_seconds / 3600
        ]);
    }

    // Récupérer le statut actuel
    public function status()
    {
        $user = Auth::user();
        $today = Carbon::today();
        $currentTime = Carbon::now();

        $workTime = WorkTime::with('sessions')
            ->where('user_id', $user->id)
            ->where('work_date', $today)
            ->first();

        $expectedHours = $this->workTimeService->getExpectedHoursForDate($today);
        $isWithinHours = $this->workTimeService->isWithinWorkingHours($today, $currentTime);

        $response = [
            'has_active_day' => false,
            'current_status' => null,
            'today' => $today->format('Y-m-d'),
            'day_name' => $today->locale('fr')->dayName,
            'daily_target_hours' => $expectedHours,
            'is_work_day' => $expectedHours > 0,
            'is_within_working_hours' => $isWithinHours,
            'current_time' => $currentTime->format('H:i:s'),
            'work_time' => null
        ];

        if ($workTime) {
            // Toujours recalculer le temps pour être à jour
            $workTime->calculateTotalTime();
            $workTime = $workTime->fresh();

            $response['has_active_day'] = true;
            $response['current_status'] = $workTime->status;
            $response['work_time'] = $workTime;

            // Ajouter le temps écoulé en temps réel si la journée est en cours
            if ($workTime->status === 'in_progress') {
                $response['elapsed_seconds'] = $workTime->net_seconds;
            }
        }

        return response()->json($response);
    }

    // Dans WorkTimeController.php
    public function resumeDay(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today();
        $currentTime = Carbon::now();

        $workTime = WorkTime::with('sessions')
            ->where('user_id', $user->id)
            ->where('work_date', $today)
            ->whereIn('status', ['in_progress', 'paused'])
            ->first();

        if (!$workTime) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune journée en cours à reprendre'
            ], 404);
        }

        // Recalculer le temps avant de reprendre
        $workTime->calculateTotalTime();

        // Si en pause, reprendre normalement
        if ($workTime->status === 'paused') {
            // Terminer la pause
            $lastPauseSession = $workTime->sessions()
                ->where('type', 'pause')
                ->whereNull('session_end')
                ->latest()
                ->first();

            if ($lastPauseSession) {
                $lastPauseSession->update(['session_end' => $currentTime]);
            }

            // Démarrer une nouvelle session de travail
            WorkSession::create([
                'work_time_id' => $workTime->id,
                'session_start' => $currentTime,
                'type' => 'work'
            ]);

            $workTime->update(['status' => 'in_progress']);
        }
        // Si déjà en cours, juste recalculer et retourner l'état
        else if ($workTime->status === 'in_progress') {
            // Vérifier s'il y a une session active
            $activeSession = $workTime->sessions()
                ->where('type', 'work')
                ->whereNull('session_end')
                ->first();

            if (!$activeSession) {
                WorkSession::create([
                    'work_time_id' => $workTime->id,
                    'session_start' => $currentTime,
                    'type' => 'work'
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Journée reprise',
            'data' => $workTime->fresh()->load('sessions')
        ]);
    }

    // Récupérer les statistiques hebdomadaires
    public function weeklyStats(Request $request)
    {
        $user = Auth::user();

        // Début et fin de la semaine (lundi à samedi)
        $startOfWeek = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $endOfWeek = Carbon::now()->endOfWeek(Carbon::SATURDAY);

        $workTimes = WorkTime::where('user_id', $user->id)
            ->whereBetween('work_date', [$startOfWeek, $endOfWeek])
            ->get();

        $totalNetHours = $workTimes->sum('net_hours');
        $totalTargetHours = $workTimes->sum(function ($workTime) {
            return $workTime->daily_target / 3600;
        });

        return response()->json([
            'success' => true,
            'period' => [
                'start' => $startOfWeek->format('Y-m-d'),
                'end' => $endOfWeek->format('Y-m-d')
            ],
            'stats' => [
                'total_days' => $workTimes->count(),
                'total_net_hours' => round($totalNetHours, 2),
                'total_target_hours' => round($totalTargetHours, 2),
                'completion_percentage' => $totalTargetHours > 0 ?
                    round(($totalNetHours / $totalTargetHours) * 100, 2) : 0,
                'daily_average' => $workTimes->count() > 0 ?
                    round($totalNetHours / $workTimes->count(), 2) : 0
            ],
            'daily_details' => $workTimes->map(function ($workTime) {
                return [
                    'date' => $workTime->work_date->format('Y-m-d'),
                    'day_name' => $workTime->day_name,
                    'net_hours' => round($workTime->net_hours, 2),
                    'target_hours' => $workTime->daily_target / 3600,
                    'progress_percentage' => $workTime->progress_percentage,
                    'status' => $workTime->status
                ];
            })
        ]);
    }

    // Récupérer les statistiques mensuelles
    public function monthlyStats(Request $request)
    {
        $user = Auth::user();

        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);

        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfMonth = Carbon::create($year, $month, 1)->endOfMonth();

        $workTimes = WorkTime::where('user_id', $user->id)
            ->whereBetween('work_date', [$startOfMonth, $endOfMonth])
            ->get();

        $totalNetHours = $workTimes->sum('net_hours');
        $totalTargetHours = $workTimes->sum(function ($workTime) {
            return $workTime->daily_target / 3600;
        });

        // Jours travaillés par semaine dans le mois
        $weeklyBreakdown = [];
        $currentWeek = $startOfMonth->copy();

        while ($currentWeek <= $endOfMonth) {
            $weekStart = $currentWeek->copy()->startOfWeek(Carbon::MONDAY);
            $weekEnd = $currentWeek->copy()->endOfWeek(Carbon::SATURDAY);

            if ($weekStart->month != $month) {
                $weekStart = $startOfMonth->copy();
            }

            if ($weekEnd->month != $month) {
                $weekEnd = $endOfMonth->copy();
            }

            $weekWorkTimes = $workTimes->filter(function ($workTime) use ($weekStart, $weekEnd) {
                return $workTime->work_date->between($weekStart, $weekEnd);
            });

            $weeklyNetHours = $weekWorkTimes->sum('net_hours');
            $weeklyTargetHours = $weekWorkTimes->sum(function ($workTime) {
                return $workTime->daily_target / 3600;
            });

            $weeklyBreakdown[] = [
                'week' => $weekStart->weekOfYear,
                'period' => $weekStart->format('M d') . ' - ' . $weekEnd->format('M d'),
                'net_hours' => round($weeklyNetHours, 2),
                'target_hours' => round($weeklyTargetHours, 2),
                'completion_percentage' => $weeklyTargetHours > 0 ?
                    round(($weeklyNetHours / $weeklyTargetHours) * 100, 2) : 0,
                'days_worked' => $weekWorkTimes->count()
            ];

            $currentWeek->addWeek();
        }

        return response()->json([
            'success' => true,
            'month' => $startOfMonth->locale('fr')->monthName,
            'year' => $year,
            'stats' => [
                'total_days_worked' => $workTimes->count(),
                'total_net_hours' => round($totalNetHours, 2),
                'total_target_hours' => round($totalTargetHours, 2),
                'completion_percentage' => $totalTargetHours > 0 ?
                    round(($totalNetHours / $totalTargetHours) * 100, 2) : 0,
                'daily_average' => $workTimes->count() > 0 ?
                    round($totalNetHours / $workTimes->count(), 2) : 0
            ],
            'weekly_breakdown' => $weeklyBreakdown,
            'daily_details' => $workTimes->map(function ($workTime) {
                return [
                    'date' => $workTime->work_date->format('Y-m-d'),
                    'day_name' => $workTime->day_name,
                    'net_hours' => round($workTime->net_hours, 2),
                    'target_hours' => $workTime->daily_target / 3600,
                    'progress_percentage' => $workTime->progress_percentage,
                    'status' => $workTime->status
                ];
            })
        ]);
    }

    // Récupérer le résumé du jour
    public function todaySummary()
    {
        $user = Auth::user();
        $today = Carbon::today();

        // UTILISATION DIRECTE DU SERVICE
        $summary = $this->workTimeService->getWorkDaySummary($user, $today);

        if (!$summary) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun suivi pour aujourd\'hui'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }

    // Dans WorkTimeController.php
    public function canStartDay()
    {
        $user = Auth::user();
        $today = Carbon::today();
        $currentTime = Carbon::now();

        // Vérifier si déjà une journée en cours
        $existingWorkTime = WorkTime::where('user_id', $user->id)
            ->where('work_date', $today)
            ->first();

        if ($existingWorkTime) {
            return response()->json([
                'can_start' => false,
                'reason' => $existingWorkTime->status === 'in_progress'
                    ? 'Journée déjà en cours'
                    : 'Journée déjà terminée',
                'current_status' => $existingWorkTime->status
            ]);
        }

        // Vérifier si jour ouvrable
        if (!$this->workTimeService->isWorkDay($today)) {
            return response()->json([
                'can_start' => false,
                'reason' => 'Pas de travail aujourd\'hui (dimanche)'
            ]);
        }

        // Vérifier les heures (pour information seulement)
        $isWithinHours = $this->workTimeService->isWithinWorkingHours($today, $currentTime);

        return response()->json([
            'can_start' => true,
            'is_within_working_hours' => $isWithinHours,
            'message' => $isWithinHours
                ? 'Vous pouvez démarrer votre journée'
                : 'Attention: Hors des heures normales de travail (9h-17h)'
        ]);
    }

    // app/Http/Controllers/WorkTimeController.php
    public function syncTime(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'work_time_id' => 'required|integer',
            'elapsed_seconds' => 'required|integer|min:0',
            'is_running' => 'required|boolean',
            'last_sync_at' => 'required|date',
        ]);

        // Vérifier que le work_time appartient à l'utilisateur
        $workTime = WorkTime::where('id', $validated['work_time_id'])
            ->where('user_id', $user->id)
            ->first();

        if (!$workTime) {
            return response()->json([
                'success' => false,
                'message' => 'WorkTime non trouvé'
            ], 404);
        }

        // Calculer le temps de travail basé sur les sessions
        $workTime->calculateTotalTime();

        // Comparer avec le temps synchro
        $backendSeconds = $workTime->net_seconds;
        $frontendSeconds = $validated['elapsed_seconds'];

        // Si la différence est significative (> 5 minutes), utiliser le temps backend
        $difference = abs($backendSeconds - $frontendSeconds);

        if ($difference > 300) { // 5 minutes
            // Le backend a l'autorité
            return response()->json([
                'success' => true,
                'message' => 'Synchronisé (backend prioritaire)',
                'data' => $workTime->fresh(),
                'server_time' => $backendSeconds,
                'client_time' => $frontendSeconds,
                'time_difference' => $difference
            ]);
        }

        // Mettre à jour le statut si nécessaire
        if ($validated['is_running'] && $workTime->status !== 'in_progress') {
            $workTime->update(['status' => 'in_progress']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Synchronisé',
            'data' => $workTime->fresh(),
            'server_time' => $backendSeconds,
            'client_time' => $frontendSeconds,
            'time_difference' => $difference
        ]);
    }
}
