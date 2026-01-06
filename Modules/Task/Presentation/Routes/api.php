<?php

use Illuminate\Support\Facades\Route;
use Modules\Task\Presentation\Controllers\TaskController;

Route::prefix('tasks')->group(function () {

    // Routes principales CRUD
    Route::get('/', [TaskController::class, 'index']);
    Route::post('/', [TaskController::class, 'store']);
    Route::get('/{id}', [TaskController::class, 'show'])->where('id', '[0-9]+');
    Route::put('/{id}', [TaskController::class, 'update'])->where('id', '[0-9]+');
    Route::delete('/{id}', [TaskController::class, 'destroy'])->where('id', '[0-9]+');
    Route::post('/{id}/restore', [TaskController::class, 'restore'])->where('id', '[0-9]+');
    // routes/api.php ou similaire
    Route::get('/projects/{projectId}/tasks', [TaskController::class, 'getTasksByProject']);

    // Routes spécifiques pour les statuts
    Route::post('/{id}/complete', [TaskController::class, 'complete'])->where('id', '[0-9]+');
    Route::post('/{id}/start', [TaskController::class, 'start'])->where('id', '[0-9]+');
    Route::post('/{id}/reset-todo', [TaskController::class, 'resetToTodo'])->where('id', '[0-9]+');
    Route::post('/{id}/status', [TaskController::class, 'updateStatus'])->where('id', '[0-9]+');
    Route::post('/{id}/assign', [TaskController::class, 'assignTo'])->where('id', '[0-9]+');

    // Routes de recherche et filtrage
    Route::post('/search', [TaskController::class, 'search']);
    Route::get('/project/{projectId}', [TaskController::class, 'getByProject'])->where('projectId', '[0-9]+');
    Route::get('/user/{userId}', [TaskController::class, 'getByAssignee'])->where('userId', '[0-9]+');
    Route::get('/overdue', [TaskController::class, 'getOverdue']);
    Route::get('/upcoming', [TaskController::class, 'getUpcoming']);

    // Routes de statistiques
    Route::get('/statistics', [TaskController::class, 'statistics']);
    Route::get('/count-by-status', [TaskController::class, 'countByStatus']);

    // Routes de santé et test
    Route::get('/health', [TaskController::class, 'health']);
    Route::get('/test', [TaskController::class, 'test']);

    Route::get('/project/{projectId}/assignable-users', [TaskController::class, 'getAssignableUsersForProject'])
        ->where('projectId', '[0-9]+');

    // Route pour les membres de l'équipe
    Route::get('/project/{projectId}/team-members', [TaskController::class, 'getTeamMembers'])
        ->where('projectId', '[0-9]+');

    Route::get('/scheduled', [TaskController::class, 'getScheduled']);
    Route::get('/unscheduled', [TaskController::class, 'getUnscheduled']);
    Route::post('/{id}/schedule', [TaskController::class, 'schedule'])->where('id', '[0-9]+');
});

// Route de santé publique (sans auth)
Route::get('/tasks/health/public', [TaskController::class, 'health']);

// Route test publique (sans auth)
Route::get('/tasks/test/public', [TaskController::class, 'test']);
