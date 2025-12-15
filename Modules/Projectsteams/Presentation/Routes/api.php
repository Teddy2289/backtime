<?php

use Illuminate\Support\Facades\Route;
use Modules\ProjectsTeams\Presentation\Controllers\ProjectsteamsController;

Route::prefix('projectsTeams')->group(function () {
    // Public routes
    Route::get('/', [ProjectsteamsController::class, 'index']);
    Route::get('/search', [ProjectsteamsController::class, 'search']);
    Route::get('/upcoming', [ProjectsteamsController::class, 'getUpcoming']);
    Route::get('/statistics', [ProjectsteamsController::class, 'statistics']);
    Route::get('/health', [ProjectsteamsController::class, 'health']);
    Route::get('/status/{status}', [ProjectsteamsController::class, 'getByStatus']);
    Route::get('/team/{teamId}', [ProjectsteamsController::class, 'getByTeam']);

    // Protected routes (with authentication)
    Route::middleware(['auth:api'])->group(function () {
        // CRUD operations
        Route::post('/', [ProjectsteamsController::class, 'store']);
        Route::get('/{id}', [ProjectsteamsController::class, 'show']);
        Route::put('/{id}', [ProjectsteamsController::class, 'update']);
        Route::delete('/{id}', [ProjectsteamsController::class, 'destroy']);

        // Status management
        Route::put('/{id}/status', [ProjectsteamsController::class, 'updateStatus']);
        Route::put('/{id}/complete', [ProjectsteamsController::class, 'complete']);
        Route::put('/{id}/on-hold', [ProjectsteamsController::class, 'putOnHold']);
        Route::put('/{id}/cancel', [ProjectsteamsController::class, 'cancel']);
        Route::put('/{id}/reactivate', [ProjectsteamsController::class, 'reactivate']);

        // Restore soft-deleted project
        Route::put('/{id}/restore', [ProjectsteamsController::class, 'restore']);
        Route::get('/{id}/team-users', [ProjectsteamsController::class, 'getProjectTeamUsers'])
            ->where('id', '[0-9]+');

        Route::get('/{id}/assignable-users', [ProjectsteamsController::class, 'getAssignableUsers'])
            ->where('id', '[0-9]+');

        Route::get('/{id}/with-team', [ProjectsteamsController::class, 'showWithTeam'])
            ->where('id', '[0-9]+');
        // Membership check
        Route::get('/{projectId}/check-team/{teamId}', [ProjectsteamsController::class, 'checkTeamMembership']);
    });
});