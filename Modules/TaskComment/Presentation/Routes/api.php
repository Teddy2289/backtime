<?php

use Illuminate\Support\Facades\Route;
use Modules\TaskComment\Presentation\Controllers\TaskcommentController;

// Routes pour les commentaires
Route::prefix('comments')->middleware(['auth:api'])->group(function () {
    Route::get('/', [TaskCommentController::class, 'index']);
    Route::post('/', [TaskCommentController::class, 'store']);
    Route::get('/{id}', [TaskCommentController::class, 'show'])->where('id', '[0-9]+');
    Route::put('/{id}', [TaskCommentController::class, 'update'])->where('id', '[0-9]+');
    Route::delete('/{id}', [TaskCommentController::class, 'destroy'])->where('id', '[0-9]+');

    // Actions spécifiques pour les commentaires
    Route::post('/{id}/reply', [TaskCommentController::class, 'addReply'])->where('id', '[0-9]+');
    Route::get('/{id}/replies', [TaskCommentController::class, 'getReplies'])->where('id', '[0-9]+');
    Route::get('/{id}/edit-history', [TaskCommentController::class, 'getEditHistory'])->where('id', '[0-9]+');

    // Filtres
    Route::get('/task/{taskId}', [TaskCommentController::class, 'getByTask'])->where('taskId', '[0-9]+');
    Route::get('/user/{userId}', [TaskCommentController::class, 'getByUser'])->where('userId', '[0-9]+');
    Route::get('/recent', [TaskCommentController::class, 'getRecent']);
    Route::get('/my-comments', [TaskCommentController::class, 'myComments']);
    Route::post('/search', [TaskCommentController::class, 'search']);

    // Statistiques et comptage
    Route::get('/statistics', [TaskCommentController::class, 'statistics']);
    Route::get('/count', [TaskCommentController::class, 'count']);

    // Routes de santé
    Route::get('/health', [TaskCommentController::class, 'health']);
});

// Routes spécifiques pour les commentaires d'une tâche
Route::prefix('tasks/{taskId}/comments')->middleware(['auth:api'])->group(function () {
    Route::get('/', [TaskCommentController::class, 'getByTask']);
    Route::post('/', [TaskCommentController::class, 'store']);
    Route::get('/statistics', [TaskCommentController::class, 'statistics']);
    Route::get('/count', [TaskCommentController::class, 'count']);
    Route::get('/recent', [TaskCommentController::class, 'getRecent']);
});

// Route publique de santé
Route::get('/comments/health/public', [TaskCommentController::class, 'health']);