<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Request;
use Modules\Taskfiles\Presentation\Controllers\TaskfilesController;

// Route POUR TOUS les fichiers storage (avec CORS)
Route::middleware(['cors'])->prefix('files')->group(function () {

    // Route pour servir n'importe quel fichier du storage
    Route::get('/serve/{path}', function (Request $request, $path) {
        try {
            // Décoder le chemin
            $decodedPath = urldecode($path);

            // Vérifier si le fichier existe
            $storagePath = 'public/' . $decodedPath;

            if (!Storage::exists($storagePath)) {
                return response()->json([
                    'error' => 'Fichier non trouvé',
                    'path' => $decodedPath
                ], 404);
            }

            // Chemin complet
            $fullPath = Storage::path($storagePath);

            // Type MIME
            $mimeType = mime_content_type($fullPath);

            // Headers CORS SPÉCIFIQUES
            $headers = [
                'Content-Type' => $mimeType,
                'Access-Control-Allow-Origin' => 'http://localhost:5174', // Spécifique, pas '*'
                'Access-Control-Allow-Methods' => 'GET, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization, Accept',
                'Access-Control-Allow-Credentials' => 'true',
                'Access-Control-Expose-Headers' => 'Content-Disposition',
                'Cache-Control' => 'public, max-age=3600',
            ];

            // Téléchargement ou affichage ?
            if ($request->has('download')) {
                $headers['Content-Disposition'] = 'attachment; filename="' . basename($decodedPath) . '"';
            } else {
                $headers['Content-Disposition'] = 'inline; filename="' . basename($decodedPath) . '"';
            }

            return response()->file($fullPath, $headers);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors du chargement du fichier',
                'message' => $e->getMessage()
            ], 500);
        }
    })->where('path', '.*')->name('files.serve');

    // Routes normales pour les fichiers (avec authentification)
    Route::middleware(['auth:api'])->group(function () {
        Route::get('/', [TaskfilesController::class, 'index']);
        Route::post('/', [TaskfilesController::class, 'store']);
        Route::get('/{id}', [TaskfilesController::class, 'show'])->where('id', '[0-9]+');
        Route::get('/{id}/download', [TaskfilesController::class, 'download'])->where('id', '[0-9]+');
        Route::get('/{id}/preview', [TaskfilesController::class, 'preview'])->where('id', '[0-9]+');
        Route::delete('/{id}', [TaskfilesController::class, 'destroy'])->where('id', '[0-9]+');
    });
});

// Autres routes existantes...
Route::middleware(['auth:api'])->group(function () {
    Route::get('tasks/{taskId}/files', [TaskfilesController::class, 'getByTask'])->where('taskId', '[0-9]+');
    Route::post('tasks/{taskId}/files', [TaskfilesController::class, 'store'])->where('taskId', '[0-9]+');
});
