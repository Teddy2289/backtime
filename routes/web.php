<?php

use Illuminate\Support\Facades\Route;

// ============================================
// ROUTE POUR LES ASSETS VUE.JS
// ============================================
Route::get('/build/{path}', function ($path) {
    $file = public_path("build/{$path}");

    if (file_exists($file)) {
        return response()->file($file);
    }

    abort(404);
})->where('path', '.*');

// ============================================
// ROUTE DE TEST
// ============================================
Route::get('/test', function () {
    return response()->json(['status' => 'ok', 'message' => 'Laravel works']);
});

// ============================================
// ROUTES SPA VUE.JS - CORRIGÉES
// ============================================
Route::prefix('admin')->group(function () {
    // ROUTE POUR /admin (SANS paramètre)
    Route::get('/', function () {
        return view('app');
    });

    // ROUTE POUR /admin/{anything}
    Route::get('/{any}', function () {
        return view('app');
    })->where('any', '.*');
});

// ============================================
// ROUTE RACINE
// ============================================
Route::get('/', function () {
    return redirect('/admin');
});
