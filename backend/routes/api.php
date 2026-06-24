<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\DatabaseController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DocenteController;
use App\Http\Controllers\Api\ReporteDocenteController;

use App\Http\Controllers\Api\ResolucionPdfController;

use App\Http\Controllers\Api\ResolucionDetalleController;
use App\Http\Controllers\Api\SecretariaController;
use App\Http\Controllers\Api\HorarioDocenteController;
use App\Http\Controllers\Api\TallerEstudiantesController;

use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\HorarioAdminController;

use App\Http\Controllers\Api\MigracionController;
use App\Http\Controllers\Api\DashboardAdminController;

use App\Http\Controllers\Api\EstudianteInscritoController;

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

//DASBOARD ADMIN 
Route::prefix('admin/dashboard')->group(function () {
    Route::get('/kpis', [DashboardAdminController::class, 'kpis']);
});

//
Route::post('/database/migraciontot', [DatabaseController::class, 'migraciontot']);

//reporte
Route::post('/reporte-docente', [ReporteDocenteController::class, 'materiasDictadas']);

// DOCENTES (puedes proteger o no)
Route::apiResource('docentes', DocenteController::class);

// routes/api.php
Route::post('/database/incremental-migrate', [DatabaseController::class, 'incrementalMigrate']);


Route::get('/reporte-horario', [ReporteDocenteController::class, 'horario']);


//
Route::get('/resoluciones/listado', [ResolucionDetalleController::class, 'listado']);
// Resoluciones


Route::get('/resoluciones', [ResolucionPdfController::class, 'index']);
Route::get('/resoluciones/por-numero', [ResolucionPdfController::class, 'porNumero']);
Route::get('/resoluciones/{id}', [ResolucionPdfController::class, 'show']);
Route::post('/resoluciones', [ResolucionPdfController::class, 'store']);
Route::get('/resoluciones/{id}/pdf', [ResolucionPdfController::class, 'descargar']);
Route::get('/resoluciones/{id}/detalles', [ResolucionDetalleController::class, 'index']);


// Detalles por resolución
Route::post('/resoluciones/{id}/aplicar-grupos', [ResolucionDetalleController::class, 'aplicarEnGrupos']);
Route::get('/resoluciones/{id}/detalles', [ResolucionDetalleController::class, 'index']);
Route::post('/resoluciones/{id}/detalles', [ResolucionDetalleController::class, 'store']);
Route::post('/resoluciones/{id}/detalles/bulk', [ResolucionDetalleController::class, 'storeBulk']);

// Detalle individual
Route::get('/detalles/{id}', [ResolucionDetalleController::class, 'show']);


// Rutas de Secretaria
Route::prefix('secretaria')->group(function () {
    Route::get('/docentes', [SecretariaController::class, 'getDocentes']);
    Route::get('/docentes/{codigo}', [SecretariaController::class, 'getDocente']);
    Route::get('/docentes/{codigo}/horario', [SecretariaController::class, 'getHorarioDocente']);
    Route::get('/dashboard/kpis', [SecretariaController::class, 'getDashboardKPIs']); // Nueva ruta


});

// Rutas para horarios de docentes
Route::prefix('horarios')->group(function () {
    // Obtener todos los horarios
    Route::get('/docentes', [HorarioDocenteController::class, 'index']);

    // Obtener horario de un docente específico
    Route::get('/docentes/{codigo_docente}', [HorarioDocenteController::class, 'show']);
});

// Lista original (materia y plan obligatorios)
Route::get('/talleres', [TallerEstudiantesController::class, 'index']);

Route::get('/talleres/{materia}', [TallerEstudiantesController::class, 'materia']);
//     // Lista de estudiantes inscritos en materias de taller
//     // ?anio=2026&periodo=1&plan=109401&materia=1301054&grupo=00
//     Route::get('/estudiantes', [TallerEstudiantesController::class, 'index'])
//         ->name('talleres.estudiantes');
//     // Materias de tipo TALLER disponibles
//     // ?anio=2026&periodo=1&plan=109401
//     Route::get('/materias', [TallerEstudiantesController::class, 'materias'])
//         ->name('talleres.materias');
//     // Grupos para un plan/materia
//     // ?anio=2026&periodo=1&plan=109401&materia=1301054
//     Route::get('/grupos', [TallerEstudiantesController::class, 'grupos'])
//         ->name('talleres.grupos');
// });

// Contacto de un estudiante
Route::get('/estudiantes/{codigo}/contacto', [TallerEstudiantesController::class, 'contacto'])
    ->name('estudiantes.contacto');


// Route::prefix('secretaria-talleres')->group(function () {

//     // Dashboard KPIs
//     Route::get('/dashboard/kpis', [DashboardTalleresController::class, 'kpis']);

// });




Route::post('/copiar-tablas', [
    MigracionController::class,
    'copiarTablas'
]);

Route::post('/sync-table', [
    MigracionController::class,
    'syncTable'
]);

// Carga horaria docentes (Admin)


// Route::prefix('admin/horarios')->group(function () {

//     // Horario completo
//     Route::get('/', [HorarioAdminController::class, 'index']);
//     Route::get('/{docente}', [HorarioAdminController::class, 'show']);

//     // Resumen
//     Route::get('/resumen/listado', [HorarioAdminController::class, 'resumen']);
//     Route::get('/resumen/docente/{docente}', [HorarioAdminController::class, 'resumenDocente']);

// });


// Route::prefix('admin/horarios')->group(function () {

//     //  Primero las rutas específicas/estáticas
//     Route::get('/resumen/listado', [HorarioAdminController::class, 'resumen']);
//     Route::get('/resumen/docente/{docente}', [HorarioAdminController::class, 'resumenDocente']);

//     // Después las dinámicas (van al final siempre)
//     Route::get('/', [HorarioAdminController::class, 'index']);
//     Route::get('/{docente}', [HorarioAdminController::class, 'show']);

// });

Route::prefix('admin/horarios')->group(function () {

    //  Primero las rutas específicas/estáticas
    Route::get('/resumen/listado', [HorarioAdminController::class, 'resumen']);
    Route::get('/resumen/docente/{docente}', [HorarioAdminController::class, 'resumenDocente']);

    // ── Inscritos ──────────────────────────────────────────────────
    Route::get('/inscritos/listado', [HorarioAdminController::class, 'listaInscritos']);
    Route::get('/inscritos/docente/{docente}', [HorarioAdminController::class, 'listaInscritosDocente']);

    // Después las dinámicas (van al final siempre)
    Route::get('/', [HorarioAdminController::class, 'index']);
    Route::get('/{docente}', [HorarioAdminController::class, 'show']);



});


Route::post('database/migrate-all', [DatabaseController::class, 'migrateAll']);



Route::get('/estudiantes-inscritos', [EstudianteInscritoController::class, 'index']);