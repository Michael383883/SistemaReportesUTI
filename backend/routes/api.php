<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\DatabaseController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DocenteController;
use App\Http\Controllers\Api\ReporteDocenteController;


// Auth (público)
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
});

// Rutas protegidas (requieren token Sanctum)
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });

    // Usuarios (solo admin)
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('users', UserController::class);
    });


});

//dbconection
Route::prefix('database')->group(function () {
    Route::get('status', [DatabaseController::class, 'status']);
    Route::post('migrate', [DatabaseController::class, 'migrate']);
});

//reporte
Route::post('/reporte-docente', [ReporteDocenteController::class, 'materiasDictadas']);

// DOCENTES (puedes proteger o no)
Route::apiResource('docentes', DocenteController::class);
