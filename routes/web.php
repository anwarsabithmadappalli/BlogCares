<?php

use Illuminate\Support\Facades\Route;

Route::get('login', function () {
    return response()->json([
        'success' => false,
        'message' => 'Unauthenticated, Token missing!'
    ], 401);
})->name('login');
