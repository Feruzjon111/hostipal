<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DirectorController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\HospitalController;
use App\Http\Controllers\LookupController;
use App\Http\Controllers\PatientController;
use Illuminate\Support\Facades\Route;

Route::get('/csrf', [AuthController::class, 'csrf']);
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:20,1');

Route::middleware('auth')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/lookups', LookupController::class);
    Route::get('/dashboard', DashboardController::class);
    Route::apiResource('patients', PatientController::class);

    Route::middleware('role:admin,director')->group(function () {
        Route::apiResource('doctors', DoctorController::class)->parameters(['doctors' => 'staff']);
        Route::apiResource('hospitals', HospitalController::class);
    });

    Route::apiResource('directors', DirectorController::class)
        ->parameters(['directors' => 'staff'])
        ->middleware('role:admin');
});

Route::any('/{path}', function () {
    return response()->json(['message' => 'Manzil topilmadi.'], 404);
})->where('path', '.*');
