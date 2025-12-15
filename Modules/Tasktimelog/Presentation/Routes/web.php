<?php

use Illuminate\Support\Facades\Route;

Route::prefix('tasktimelog')
    ->middleware(['web'])
    ->group(function () {
        Route::get('/', function () {
            return view('tasktimelog::app');
        });
    });
