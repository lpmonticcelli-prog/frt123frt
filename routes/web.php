<?php
use Illuminate\Support\Facades\Route;

// SINCRONIA: Foco estrito em SPA
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');