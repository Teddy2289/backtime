<?php

use Illuminate\Support\Facades\Route;

Route::prefix('taskcomment')
    ->middleware(['web'])
    ->group(function () {
        Route::get('/', function () {
            return view('taskcomment::app');
        });
    });
