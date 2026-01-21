<?php

use Illuminate\Support\Facades\Route;

// Route pour les assets Vue.js
Route::get('/build/{path}', function ($path) {
    $file = public_path("build/{$path}");

    if (file_exists($file)) {
        return response()->file($file);
    }

    abort(404);
})->where('path', '.*');

// Routes API sont déjà dans api.php
Route::prefix('admin')->group(function () {
    Route::get('/{any?}', function () {
        return view('app');
    })->where('any', '.*')->name('admin.spa');
});

// Route racine (optionnel)
Route::get('/', function () {
    return redirect('/admin');
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
