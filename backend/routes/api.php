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

// PARA DOC EXTRAS
use App\Http\Controllers\Api\ClasificacionDocenteController;
use App\Http\Controllers\Api\ReporteClasificacionController;

// MATERIAS
use App\Http\Controllers\Api\MateriaController;

// REFERENCIAS
use App\Http\Controllers\Api\ReferenciaController;

// Reportes Excel
use App\Http\Controllers\Api\ReporteExcelController;

// Periodos
use App\Http\Controllers\Api\PeriodoAcademicoController;

// Categorías clasificación
use App\Http\Controllers\Api\CategoriaClasificacionController;

// Índices de base de datos
use App\Http\Controllers\Api\DatabaseIndexController;

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

    /* ----------------------------------------------------------------
     | AUTH
     * ---------------------------------------------------------------- */
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::post('verify-password', [AuthController::class, 'verifyPassword']);
        Route::put('change-password', [AuthController::class, 'changePassword']);
    });

    /* ----------------------------------------------------------------
     | USUARIOS (solo admin)
     * ---------------------------------------------------------------- */
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('users', UserController::class);
    });

    /* ----------------------------------------------------------------
     | BASE DE DATOS / MIGRACIONES
     * ---------------------------------------------------------------- */
    Route::prefix('database')->group(function () {
        Route::get('/status', [DatabaseController::class, 'status']);
        Route::get('/tables', [DatabaseController::class, 'tables']);
    });

    /* ----------------------------------------------------------------
     | DASHBOARD ADMIN
     * ---------------------------------------------------------------- */
    Route::prefix('admin/dashboard')->group(function () {
        Route::get('/kpis', [DashboardAdminController::class, 'kpis']);
        Route::post('/refresh', [DashboardAdminController::class, 'refreshKpis']);
    });

    /* ----------------------------------------------------------------
     | PERIODOS ACADÉMICOS
     * ---------------------------------------------------------------- */
    Route::prefix('periodos-academicos')->group(function () {
        Route::get('/', [PeriodoAcademicoController::class, 'index']);
        Route::put('/', [PeriodoAcademicoController::class, 'actualizarMasivo']);
        Route::post('/restaurar', [PeriodoAcademicoController::class, 'restaurarValoresPredeterminados']);
        Route::post('/{id}/bloquear', [PeriodoAcademicoController::class, 'bloquear']);
        Route::post('/{id}/desbloquear', [PeriodoAcademicoController::class, 'desbloquear']);
    });

    /* ----------------------------------------------------------------
     | DOCENTES
     * ---------------------------------------------------------------- */
    Route::apiResource('docentes', DocenteController::class);

    Route::post('/reporte-docente', [ReporteDocenteController::class, 'materiasDictadasCompartidas']);
    Route::get('/reporte-horario', [ReporteDocenteController::class, 'horario']);
    Route::post('/reporte-docente2', [ReporteDocenteController::class, 'materiasDictadasCompartidas']);

    // ── Reportes docentes/títulos ───────────────────────────────────
    // ⚠️ IMPORTANTE: las rutas específicas van ANTES de cualquier {id}
    Route::get('reporte-docentes/tipos-titulo', [ReporteDocenteController::class, 'tiposTitulo']);
    Route::put('/reporte-docentes/tipos-titulo', [ClasificacionDocenteController::class, 'actualizarTipoTitulo']);
    Route::get('reporte-docentes/con-titulo', [ReporteDocenteController::class, 'docentesConTitulo']);
    Route::get('reporte-docentes/con-titulo/excel', [ReporteDocenteController::class, 'excel']);

    /* ----------------------------------------------------------------
     | HORARIOS DE DOCENTES
     * ---------------------------------------------------------------- */
    Route::prefix('horarios')->group(function () {
        Route::get('/docentes', [HorarioDocenteController::class, 'index']);
        Route::post('/docentes', [HorarioDocenteController::class, 'index']);
        Route::get('/docentes/{codigo_docente}', [HorarioDocenteController::class, 'show']);
    });

    // Carga horaria docentes (Admin)
    Route::prefix('admin/horarios')->group(function () {
        // Específicas primero
        Route::get('/resumen/listado', [HorarioAdminController::class, 'resumen']);
        Route::get('/resumen/docente/{docente}', [HorarioAdminController::class, 'resumenDocente']);

        // Inscritos
        Route::get('/inscritos/listado', [HorarioAdminController::class, 'listaInscritos']);
        Route::get('/inscritos/docente/{docente}', [HorarioAdminController::class, 'listaInscritosDocente']);
        Route::get('/inscritos/agrupados/aprobados-reprobados', [EstudianteInscritoController::class, 'resumenPorGrupo']);
        Route::get('/inscritos/aprobados-reprobados', [EstudianteInscritoController::class, 'resumenAprobadosReprobados']);

        // Dinámicas al final
        Route::get('/', [HorarioAdminController::class, 'index']);
        Route::get('/{docente}', [HorarioAdminController::class, 'show']);
    });

    /* ----------------------------------------------------------------
     | RESOLUCIONES
     | ⚠️ ORDEN CRÍTICO: primero rutas estáticas, luego dinámicas {id}
     * ---------------------------------------------------------------- */
    // 1) Rutas estáticas (sin {id})
    Route::get('/resoluciones', [ResolucionPdfController::class, 'index']);
    Route::get('/resoluciones/listado', [ResolucionDetalleController::class, 'listado']);
    Route::get('/resoluciones/por-numero', [ResolucionPdfController::class, 'porNumero']);
    Route::post('/resoluciones', [ResolucionPdfController::class, 'store']);

    // 2) Detalles de resolución (estáticas sobre {id} específico)
    Route::get('/resoluciones/{id}/pdf', [ResolucionPdfController::class, 'descargar']);
    Route::get('/resoluciones/{id}/detalles', [ResolucionDetalleController::class, 'index']);
    Route::post('/resoluciones/{id}/detalles', [ResolucionDetalleController::class, 'store']);
    Route::post('/resoluciones/{id}/detalles/bulk', [ResolucionDetalleController::class, 'storeBulk']);
    Route::post('/resoluciones/{id}/aplicar-grupos', [ResolucionDetalleController::class, 'aplicarEnGrupos']);

    // 3) Detalle individual (otro recurso)
    Route::get('/detalles/{id}', [ResolucionDetalleController::class, 'show']);

    // 4) Operaciones sobre {id} de resolución (van al final)
    Route::get('/resoluciones/{id}', [ResolucionPdfController::class, 'show']);
    Route::post('/resoluciones/{id}', [ResolucionPdfController::class, 'update']);
    Route::delete('resoluciones/{id}', [ResolucionPdfController::class, 'destroy']);
    Route::put('resoluciones/{id}/quitar', [ResolucionDetalleController::class, 'quitarDeGrupos']);

    Route::post('/clasificaciones/{idDocumento}/generar-resolucion', [ResolucionPdfController::class, 'storeDesdeClasificacion']);

    /* ----------------------------------------------------------------
     | SECRETARÍA
     * ---------------------------------------------------------------- */
    Route::prefix('secretaria')->group(function () {
        Route::get('/docentes', [SecretariaController::class, 'getDocentes']);
        Route::get('/docentes/{codigo}', [SecretariaController::class, 'getDocente']);
        Route::get('/docentes/{codigo}/horario', [SecretariaController::class, 'getHorarioDocente']);
        Route::get('/dashboard/kpis', [SecretariaController::class, 'getDashboardKPIs']);
    });

    /* ----------------------------------------------------------------
     | TALLERES Y ESTUDIANTES
     * ---------------------------------------------------------------- */
    Route::get('/talleres', [TallerEstudiantesController::class, 'index']);
    Route::get('/talleres/{materia}', [TallerEstudiantesController::class, 'materia']);

    Route::get('/estudiantes/{codigo}/contacto', [TallerEstudiantesController::class, 'contacto'])
        ->name('estudiantes.contacto');

    Route::get('/estudiantes-inscritos', [EstudianteInscritoController::class, 'index']);

    Route::post('/grupos/tipo-ingreso/bulk', [GrupoTipoIngresoController::class, 'bulkUpdate']);

    /* ----------------------------------------------------------------
     | DIGITALIZACIÓN / CLASIFICACIONES
     | ⚠️ ORDEN CRÍTICO: rutas estáticas antes de {id}
     * ---------------------------------------------------------------- */
    // 1) Estáticas específicas
    Route::get('/categorias', [ClasificacionDocenteController::class, 'categorias']);
    Route::put('/categorias', [ClasificacionDocenteController::class, 'actualizarCategoria']);

    Route::get('/reportes/clasificacion', [ReporteClasificacionController::class, 'listado']);
    Route::get('/reportes/clasificacion/por-referencia', [ReporteClasificacionController::class, 'porReferencia']);
    Route::get('/reportes/clasificacion/id-por-referencia', [ReporteClasificacionController::class, 'idPorReferencia']);
    Route::get('/reportes/clasificacion/docente/{cod_docente}', [ReporteClasificacionController::class, 'porDocente'])
        ->where('cod_docente', '[0-9]+');

    Route::get('/clasificaciones/materias-registradas', [ClasificacionDocenteController::class, 'materiasRegistradas']);

    Route::get('clasificaciones/docente/{codDocente}/categorias', [ClasificacionDocenteController::class, 'categoriasDocente']);
    Route::get('clasificaciones/docente/{codDocente}/documentos', [ClasificacionDocenteController::class, 'documentosDocente']);

    Route::delete('/clasificaciones/docente/{idClasificacionDocente}', [ClasificacionDocenteController::class, 'destroyDocente'])
        ->where('idClasificacionDocente', '[0-9]+');

    // 2) Listado y creación
    Route::get('/clasificaciones', [ClasificacionDocenteController::class, 'index']);
    Route::post('/clasificaciones', [ClasificacionDocenteController::class, 'store']);

    // 3) Operaciones sobre un {idDocumento} específico
    Route::post('clasificaciones/{idDocumento}/materias/bulk', [ClasificacionDocenteController::class, 'agregarMateriasBulk']);
    Route::put('/clasificaciones/{id}/aplicar', [ClasificacionDocenteController::class, 'aplicarEnGrupos'])
        ->where('id', '[0-9]+');
    Route::put('/clasificaciones/{id}/quitar', [ClasificacionDocenteController::class, 'quitarDeGrupos'])
        ->where('id', '[0-9]+');

    // 4) Genéricas {id} al final
    Route::get('/clasificaciones/{id}', [ClasificacionDocenteController::class, 'show'])
        ->where('id', '[0-9]+');
    Route::get('/clasificaciones/{id}/pdf', [ClasificacionDocenteController::class, 'descargar'])
        ->where('id', '[0-9]+');
    Route::put('/clasificaciones/{id}', [ClasificacionDocenteController::class, 'update']);
    Route::delete('/clasificaciones/{id}', [ClasificacionDocenteController::class, 'destroy'])
        ->where('id', '[0-9]+');

    /* ----------------------------------------------------------------
     | MATERIAS
     * ---------------------------------------------------------------- */
    Route::get('/materias', [MateriaController::class, 'index']);
    Route::get('/materias/periodos', [MateriaController::class, 'periodos']);
    Route::get('/materias/docente', [MateriaController::class, 'porDocente']);

    /* ----------------------------------------------------------------
     | REFERENCIAS
     * ---------------------------------------------------------------- */
    Route::get('/referencias', [ReferenciaController::class, 'index']);
    Route::get('/referencias/anios', [ReferenciaController::class, 'anios']);

    /* ----------------------------------------------------------------
     | REPORTES EXCEL
     * ---------------------------------------------------------------- */
    Route::get('/reportes/docentes-clasificados/excel', [ReporteExcelController::class, 'generarListadoDocentes']);
    Route::get('/reportes/docentes-clasificados/preview', [ReporteExcelController::class, 'previsualizar']);
    Route::get('/reportes/docentes-activos', [ReporteExcelController::class, 'obtenerDocentesActivos']);
    Route::get('/reportes/carga-horaria-docentes', [ReporteExcelController::class, 'obtenerCargaHorariaDocentes']);
    Route::post('/reportes/docentes-clasificados/excel-personalizado', [ReporteExcelController::class, 'generarListadoDocentesDesdeDatos']);

    /* ----------------------------------------------------------------
     | CATEGORÍAS CLASIFICACIÓN
     * ---------------------------------------------------------------- */
    Route::pattern('tipoCategoria', 'documento|titulo|kardex');

    Route::get('/categorias-clasificacion/{tipoCategoria}', [CategoriaClasificacionController::class, 'index']);
    Route::post('/categorias-clasificacion/{tipoCategoria}', [CategoriaClasificacionController::class, 'store']);
    Route::put('/categorias-clasificacion/{tipoCategoria}', [CategoriaClasificacionController::class, 'update']);
    Route::delete('/categorias-clasificacion/{tipo}', [CategoriaClasificacionController::class, 'destroy']);

    /* ----------------------------------------------------------------
     | ÍNDICES DE BASE DE DATOS
     * ---------------------------------------------------------------- */
    Route::prefix('indices')->group(function () {
        Route::post('/crear', [DatabaseIndexController::class, 'crearIndices']);
        Route::get('/verificar', [DatabaseIndexController::class, 'verificarIndices']);
    });
});