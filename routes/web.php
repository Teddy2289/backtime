<?php

use Illuminate\Support\Facades\Route;

// ============================================
// IMPORTANT: TOUTES les routes /admin/* doivent être capturées
// ============================================

// Route pour servir les assets Vue.js (CSS/JS compilés)
Route::get('/build/{path}', function ($path) {
    $file = public_path("build/{$path}");

    if (file_exists($file)) {
        return response()->file($file);
    }

    abort(404);
})->where('path', '.*');

// ============================================
// ROUTE PRINCIPALE POUR VUE.JS SPA
// ============================================
Route::prefix('admin')->group(function () {
    // Cette route capture TOUTES les URLs sous /admin/
    Route::get('/{any}', function () {
        return view('app');
    })->where('any', '.*');
});

// ============================================
// ROUTE RACINE (redirection vers /admin)
// ============================================
Route::get('/', function () {
    // Redirigez vers votre SPA Vue.js
    return redirect('/admin');
});
