<?php

use Illuminate\Support\Facades\Route;

Route::prefix('task')
    ->middleware(['web'])
    ->group(function () {
        Route::get('/', function () {
            return view('task::app');
        });
    });
