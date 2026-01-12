<?php

use Illuminate\Support\Facades\Route;

Route::prefix('team')
    ->middleware(['web'])
    ->group(function () {
        Route::get('/', function () {
            return view('team::app');
        });
    });
