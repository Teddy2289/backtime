<?php

use Illuminate\Support\Facades\Route;
use Modules\TaskTimeLog\Presentation\Controllers\TasktimelogController;

// Ajoutez ceci dans votre fichier de routes
Route::prefix('time-logs')->middleware(['api', 'auth:api'])->group(function () {
    Route::get('/', [TaskTimeLogController::class, 'index']);
    Route::post('/', [TaskTimeLogController::class, 'store']);
    Route::get('/{id}', [TaskTimeLogController::class, 'show'])->where('id', '[0-9]+');
    Route::put('/{id}', [TaskTimeLogController::class, 'update'])->where('id', '[0-9]+');
    Route::delete('/{id}', [TaskTimeLogController::class, 'destroy'])->where('id', '[0-9]+');

    // Actions spécifiques pour les time logs
    Route::post('/start', [TaskTimeLogController::class, 'start']);
    Route::post('/{id}/stop', [TaskTimeLogController::class, 'stop'])->where('id', '[0-9]+');
    Route::post('/stop-current', [TaskTimeLogController::class, 'stopCurrent']);
    Route::put('/{id}/note', [TaskTimeLogController::class, 'updateNote'])->where('id', '[0-9]+');

    // Filtres pour les time logs
    Route::get('/task/{taskId}', [TaskTimeLogController::class, 'getByTask'])->where('taskId', '[0-9]+');
    Route::get('/user/{userId}', [TaskTimeLogController::class, 'getByUser'])->where('userId', '[0-9]+');
    Route::get('/running', [TaskTimeLogController::class, 'getRunning']);
    Route::get('/my-summary', [TaskTimeLogController::class, 'mySummary']);

    // Statistiques pour les time logs
    Route::get('/statistics', [TaskTimeLogController::class, 'statistics']);
    Route::get('/total-time/task/{taskId}', [TaskTimeLogController::class, 'getTotalTimeForTask'])->where('taskId', '[0-9]+');
    Route::get('/total-time/user/{userId}', [TaskTimeLogController::class, 'getTotalTimeForUser'])->where('userId', '[0-9]+');

    // Routes de santé pour les time logs
    Route::get('/health', [TaskTimeLogController::class, 'health']);
});

// Routes publiques (sans auth)
Route::get('/time-logs/health/public', [TaskTimeLogController::class, 'health']);