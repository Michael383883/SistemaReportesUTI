<?php

use Illuminate\Support\Facades\Route;

// Auth
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;

// Base de datos / migraciones
use App\Http\Controllers\Api\DatabaseController;
use App\Http\Controllers\Api\MigracionController;

// Docentes y reportes
use App\Http\Controllers\Api\DocenteController;
use App\Http\Controllers\Api\ReporteDocenteController;
use App\Http\Controllers\Api\HorarioDocenteController;
use App\Http\Controllers\Api\HorarioAdminController;

// Resoluciones
use App\Http\Controllers\Api\ResolucionPdfController;
use App\Http\Controllers\Api\ResolucionDetalleController;

// Secretaría
use App\Http\Controllers\Api\SecretariaController;

// Talleres / estudiantes
use App\Http\Controllers\Api\TallerEstudiantesController;
use App\Http\Controllers\Api\EstudianteInscritoController;
use App\Http\Controllers\Api\GrupoTipoIngresoController;

// Dashboards
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DashboardAdminController;


/*
|--------------------------------------------------------------------------
| AUTH (público)
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
});


/*
|--------------------------------------------------------------------------
| RUTAS PROTEGIDAS (requieren token Sanctum)
|--------------------------------------------------------------------------
*/
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


/*
|--------------------------------------------------------------------------
| BASE DE DATOS / MIGRACIONES
|--------------------------------------------------------------------------
*/
Route::prefix('database')->group(function () {
    Route::get('status', [DatabaseController::class, 'status']);
    Route::get('tablas', [DatabaseController::class, 'tablas']);
    Route::post('migrate', [DatabaseController::class, 'migrate']);
    Route::post('migrate-all', [DatabaseController::class, 'migrateAll']);
    Route::post('sync-grupos', [DatabaseController::class, 'syncGrupos']);
    Route::post('sync-gestion', [DatabaseController::class, 'syncGestion']);
    Route::post('sync-table', [DatabaseController::class, 'syncTable']);
});

Route::post('/database/migraciontot', [DatabaseController::class, 'migraciontot']);
Route::post('/database/incremental-migrate', [DatabaseController::class, 'incrementalMigrate']);

Route::post('/copiar-tablas', [MigracionController::class, 'copiarTablas']);
Route::post('/sync-table', [MigracionController::class, 'syncTable']);




/*
|--------------------------------------------------------------------------
| DASHBOARD ADMIN
|--------------------------------------------------------------------------
*/
Route::prefix('admin/dashboard')->group(function () {
    Route::get('/kpis', [DashboardAdminController::class, 'kpis']);
});


/*
|--------------------------------------------------------------------------
| DOCENTES
|--------------------------------------------------------------------------
*/
Route::apiResource('docentes', DocenteController::class);

Route::post('/reporte-docente', [ReporteDocenteController::class, 'materiasDictadas']);
Route::get('/reporte-horario', [ReporteDocenteController::class, 'horario']);


/*
|--------------------------------------------------------------------------
| HORARIOS DE DOCENTES
|--------------------------------------------------------------------------
*/
Route::prefix('horarios')->group(function () {
    Route::get('/docentes', [HorarioDocenteController::class, 'index']);
    Route::get('/docentes/{codigo_docente}', [HorarioDocenteController::class, 'show']);
});

// Carga horaria docentes (Admin)
Route::prefix('admin/horarios')->group(function () {

    // Rutas específicas primero (deben ir antes de las dinámicas)
    Route::get('/resumen/listado', [HorarioAdminController::class, 'resumen']);
    Route::get('/resumen/docente/{docente}', [HorarioAdminController::class, 'resumenDocente']);

    // Inscritos
    Route::get('/inscritos/listado', [HorarioAdminController::class, 'listaInscritos']);
    Route::get('/inscritos/docente/{docente}', [HorarioAdminController::class, 'listaInscritosDocente']);

    // Dinámicas al final
    Route::get('/', [HorarioAdminController::class, 'index']);
    Route::get('/{docente}', [HorarioAdminController::class, 'show']);
});


/*
|--------------------------------------------------------------------------
| RESOLUCIONES
|--------------------------------------------------------------------------
*/
Route::get('/resoluciones', [ResolucionPdfController::class, 'index']);
Route::get('/resoluciones/listado', [ResolucionDetalleController::class, 'listado']);
Route::get('/resoluciones/por-numero', [ResolucionPdfController::class, 'porNumero']);
Route::post('/resoluciones', [ResolucionPdfController::class, 'store']);
Route::get('/resoluciones/{id}', [ResolucionPdfController::class, 'show']);
Route::get('/resoluciones/{id}/pdf', [ResolucionPdfController::class, 'descargar']);

// Detalles por resolución
Route::get('/resoluciones/{id}/detalles', [ResolucionDetalleController::class, 'index']);
Route::post('/resoluciones/{id}/detalles', [ResolucionDetalleController::class, 'store']);
Route::post('/resoluciones/{id}/detalles/bulk', [ResolucionDetalleController::class, 'storeBulk']);
Route::post('/resoluciones/{id}/aplicar-grupos', [ResolucionDetalleController::class, 'aplicarEnGrupos']);

// Detalle individual
Route::get('/detalles/{id}', [ResolucionDetalleController::class, 'show']);

Route::delete('resoluciones/{id}', [ResolucionPdfController::class, 'destroy']);
Route::put('resoluciones/{id}/quitar', [ResolucionDetalleController::class, 'quitarDeGrupos']);
/*
|--------------------------------------------------------------------------
| SECRETARÍA
|--------------------------------------------------------------------------
*/
Route::prefix('secretaria')->group(function () {
    Route::get('/docentes', [SecretariaController::class, 'getDocentes']);
    Route::get('/docentes/{codigo}', [SecretariaController::class, 'getDocente']);
    Route::get('/docentes/{codigo}/horario', [SecretariaController::class, 'getHorarioDocente']);
    Route::get('/dashboard/kpis', [SecretariaController::class, 'getDashboardKPIs']);
});


/*
|--------------------------------------------------------------------------
| TALLERES Y ESTUDIANTES
|--------------------------------------------------------------------------
*/
Route::get('/talleres', [TallerEstudiantesController::class, 'index']);
Route::get('/talleres/{materia}', [TallerEstudiantesController::class, 'materia']);

Route::get('/estudiantes/{codigo}/contacto', [TallerEstudiantesController::class, 'contacto'])
    ->name('estudiantes.contacto');

Route::get('/estudiantes-inscritos', [EstudianteInscritoController::class, 'index']);

Route::post('/grupos/tipo-ingreso/bulk', [GrupoTipoIngresoController::class, 'bulkUpdate']);