<?php

use Illuminate\Support\Facades\Route;
use Modules\Team\Presentation\Controllers\TeamController;
// Routes publiques pour Team
Route::prefix('teams')->group(function () {
    Route::get('/', [TeamController::class, 'index']);
    Route::get('/public', [TeamController::class, 'search']);
    Route::get('/{id}', [TeamController::class, 'show']);

    // Recherche publique
    Route::post('/search', [TeamController::class, 'search']);
});

// Routes protégées pour Team (avec authentification JWT)
Route::prefix('teamsCrud')->group(function () {
    // CRUD operations
    Route::post('/', [TeamController::class, 'store']);
    Route::put('/{id}', [TeamController::class, 'update']);
    Route::delete('/{id}', [TeamController::class, 'destroy']);

    // Member management
    Route::post('/{teamId}/members', [TeamController::class, 'addMember']);
    Route::delete('/{teamId}/members/{userId}', [TeamController::class, 'removeMember']);
    Route::get('/{teamId}/members', [TeamController::class, 'getMembers']);

    // Additional operations
    Route::get('/owner/{ownerId}', [TeamController::class, 'getByOwner']);
    Route::get('/user/my-teams', [TeamController::class, 'myTeams']);
    Route::post('/{teamId}/transfer-ownership', [TeamController::class, 'transferOwnership']);
    Route::get('/{teamId}/statistics', [TeamController::class, 'statistics']);
    Route::get('/{teamId}/check-ownership', [TeamController::class, 'checkOwnership']);
    Route::get('/{teamId}/check-membership', [TeamController::class, 'checkMembership']);

    // Search protégée (si différente de la recherche publique)
    Route::post('/search/advanced', [TeamController::class, 'advancedSearch']);
});
