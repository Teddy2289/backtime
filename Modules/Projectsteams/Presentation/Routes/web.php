<?php

use Illuminate\Support\Facades\Route;

Route::prefix('projectsteams')
    ->middleware(['web'])
    ->group(function () {
        Route::get('/', function () {
            return view('projectsteams::welcome');
        });
    });
