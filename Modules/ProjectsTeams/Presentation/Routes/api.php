<?php

use Illuminate\Support\Facades\Route;
use Modules\ProjectsTeams\Presentation\Controllers\ProjectsTeamsController;

Route::prefix('projectsTeams')->group(function () {
    // Public routes
    Route::get('/', [ProjectsTeamsController::class, 'index']);
    Route::get('/search', [ProjectsTeamsController::class, 'search']);
    Route::get('/upcoming', [ProjectsTeamsController::class, 'getUpcoming']);
    Route::get('/statistics', [ProjectsTeamsController::class, 'statistics']);
    Route::get('/health', [ProjectsTeamsController::class, 'health']);
    Route::get('/status/{status}', [ProjectsTeamsController::class, 'getByStatus']);
    Route::get('/team/{teamId}', [ProjectsTeamsController::class, 'getByTeam']);

    // Protected routes (with authentication)
    Route::middleware(['auth:api'])->group(function () {
        // CRUD operations
        Route::post('/', [ProjectsTeamsController::class, 'store']);
        Route::get('/{id}', [ProjectsTeamsController::class, 'show']);
        Route::put('/{id}', [ProjectsTeamsController::class, 'update']);
        Route::delete('/{id}', [ProjectsTeamsController::class, 'destroy']);

        // Status management
        Route::put('/{id}/status', [ProjectsTeamsController::class, 'updateStatus']);
        Route::put('/{id}/complete', [ProjectsTeamsController::class, 'complete']);
        Route::put('/{id}/on-hold', [ProjectsTeamsController::class, 'putOnHold']);
        Route::put('/{id}/cancel', [ProjectsTeamsController::class, 'cancel']);
        Route::put('/{id}/reactivate', [ProjectsTeamsController::class, 'reactivate']);

        // Restore soft-deleted project
        Route::put('/{id}/restore', [ProjectsTeamsController::class, 'restore']);
        Route::get('/{id}/team-users', [ProjectsTeamsController::class, 'getProjectTeamUsers'])
            ->where('id', '[0-9]+');

        Route::get('/{id}/assignable-users', [ProjectsTeamsController::class, 'getAssignableUsers'])
            ->where('id', '[0-9]+');

        Route::get('/{id}/with-team', [ProjectsTeamsController::class, 'showWithTeam'])
            ->where('id', '[0-9]+');
        // Membership check
        Route::get('/{projectId}/check-team/{teamId}', [ProjectsTeamsController::class, 'checkTeamMembership']);
    });
});
