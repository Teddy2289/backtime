<?php

use Illuminate\Support\Facades\Route;
use Modules\TaskFiles\Presentation\Controllers\TaskfilesController;

// Routes pour les fichiers
Route::prefix('files')->middleware(['auth:api'])->group(function () {
    Route::get('/', [TaskfilesController::class, 'index']);
    Route::post('/', [TaskfilesController::class, 'store']);
    Route::get('/{id}', [TaskfilesController::class, 'show'])->where('id', '[0-9]+');
    Route::put('/{id}', [TaskfilesController::class, 'update'])->where('id', '[0-9]+');
    Route::delete('/{id}', [TaskfilesController::class, 'destroy'])->where('id', '[0-9]+');
    Route::delete('/', [TaskfilesController::class, 'destroyMultiple']);

    // Actions spécifiques
    Route::get('/{id}/download', [TaskfilesController::class, 'download'])->where('id', '[0-9]+');
    Route::get('/{id}/preview', [TaskfilesController::class, 'preview'])->where('id', '[0-9]+');

    // Filtres
    Route::get('/task/{taskId}', [TaskfilesController::class, 'getByTask'])->where('taskId', '[0-9]+');
    Route::get('/uploader/{userId}', [TaskfilesController::class, 'getByUploader'])->where('userId', '[0-9]+');
    Route::post('/search', [TaskfilesController::class, 'search']);

    // Statistiques
    Route::get('/statistics', [TaskfilesController::class, 'statistics']);

    // Routes de santé
    Route::get('/health', [TaskfilesController::class, 'health']);
});

// Routes spécifiques pour les fichiers d'une tâche
Route::group([], function () {
    Route::get('tasks/{taskId}/files', [TaskfilesController::class, 'getByTask'])->where('taskId', '[0-9]+');
    Route::post('tasks/{taskId}/files', [TaskfilesController::class, 'store'])->where('taskId', '[0-9]+');
    Route::get('tasks/{taskId}/files/statistics', [TaskfilesController::class, 'statistics'])->where('taskId', '[0-9]+');
});

// Route publique de santé
Route::get('/files/health/public', [TaskfilesController::class, 'health']);