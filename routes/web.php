<?php

use Illuminate\Support\Facades\Route;

// Route pour les assets (CSS/JS) - ils doivent être accessibles
Route::get('/build/{path}', function ($path) {
    $filePath = public_path("build/{$path}");

    if (file_exists($filePath)) {
        return response()->file($filePath);
    }

    abort(404);
})->where('path', '.*');

// Route catch-all pour Vue.js SPA sous /admin
Route::prefix('admin')->group(function () {
    Route::get('/{any}', function () {
        return view('app');
    })->where('any', '^(?!api|storage).*$');
});

// Si vous voulez aussi une page d'accueil Laravel (optionnel)
Route::get('/', function () {
    return response()->json([
        'message' => 'Laravel API Backend',
        'frontend' => 'https://manager.wizi-learn.com',
        'backoffice' => 'https://manager.wizi-learn.com/admin',
        'api' => 'https://manager.wizi-learn.com/api',
    ]);
});
