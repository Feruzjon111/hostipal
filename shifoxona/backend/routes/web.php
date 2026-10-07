<?php

use Illuminate\Support\Facades\Route;

// API uchun ham sessiya va CSRF himoyasi ishlatiladi.
Route::prefix('api')->group(__DIR__.'/api.php');

Route::get('/', function () {
    return response()->json([
        'name' => 'Shifo API',
        'url' => config('app.url'),
    ]);
})->name('login');
