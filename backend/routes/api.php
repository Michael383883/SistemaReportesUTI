<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\DatabaseController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DocenteController;
use App\Http\Controllers\Api\ReporteDocenteController;

use App\Http\Controllers\Api\ResolucionPdfController;

use App\Http\Controllers\Api\ResolucionDetalleController;

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

// routes/api.php
Route::post('/database/incremental-migrate', [DatabaseController::class, 'incrementalMigrate']);




// Resoluciones
Route::get('/resoluciones', [ResolucionPdfController::class, 'index']);
Route::get('/resoluciones/por-numero', [ResolucionPdfController::class, 'porNumero']);
Route::get('/resoluciones/{id}', [ResolucionPdfController::class, 'show']);
Route::post('/resoluciones', [ResolucionPdfController::class, 'store']);
Route::get('/resoluciones/{id}/pdf', [ResolucionPdfController::class,'descargar']);
Route::get('/resoluciones/{id}/detalles', [ResolucionDetalleController::class, 'index']);


// Detalles por resolución
Route::post('/resoluciones/{id}/aplicar-grupos', [ResolucionDetalleController::class, 'aplicarEnGrupos']);
Route::get('/resoluciones/{id}/detalles', [ResolucionDetalleController::class, 'index']);
Route::post('/resoluciones/{id}/detalles', [ResolucionDetalleController::class, 'store']);
Route::post('/resoluciones/{id}/detalles/bulk', [ResolucionDetalleController::class, 'storeBulk']);

// Detalle individual
Route::get('/detalles/{id}', [ResolucionDetalleController::class, 'show']);
