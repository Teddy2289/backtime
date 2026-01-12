<?php

use Illuminate\Support\Facades\Route;

Route::prefix('taskfiles')
    ->middleware(['web'])
    ->group(function () {
        Route::get('/', function () {
            return view('taskfiles::app');
        });
    });
