<?php

use Illuminate\Support\Facades\Route;
use Modules\Team\Presentation\Controllers\TeamController;

Route::prefix('teams')->middleware(['api'])->group(function () {
    // Public routes
    Route::get('/', [TeamController::class, 'index']);
    Route::get('/public', [TeamController::class, 'search']); // Search with is_public filter
    
    // Protected routes (with auth)
    Route::middleware(['auth:api'])->group(function () {
        // CRUD operations
        Route::post('/', [TeamController::class, 'store']);
        Route::get('/{id}', [TeamController::class, 'show']);
        Route::put('/{id}', [TeamController::class, 'update']);
        Route::delete('/{id}', [TeamController::class, 'destroy']);
        
        // Member management
        Route::post('/{teamId}/members', [TeamController::class, 'addMember']);
        Route::delete('/{teamId}/members', [TeamController::class, 'removeMember']);
        Route::get('/{teamId}/members', [TeamController::class, 'getMembers']);
        
        // Additional operations
        Route::get('/owner/{ownerId}', [TeamController::class, 'getByOwner']);
        Route::get('/user/my-teams', [TeamController::class, 'myTeams']);
        Route::post('/{teamId}/transfer-ownership', [TeamController::class, 'transferOwnership']);
        Route::get('/{teamId}/statistics', [TeamController::class, 'statistics']);
        Route::get('/{teamId}/check-ownership', [TeamController::class, 'checkOwnership']);
        Route::get('/{teamId}/check-membership', [TeamController::class, 'checkMembership']);
        
        // Search with filters
        Route::post('/search', [TeamController::class, 'search']);
    });
});

// Health check route (keep it simple)
Route::get('/team/health', function () {
    return response()->json([
        'status' => 'ok',
        'module' => 'Team',
        'timestamp' => now()->toISOString()
    ]);
});