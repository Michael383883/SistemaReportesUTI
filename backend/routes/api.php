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

//MATERIAS
use App\Http\Controllers\Api\MateriaController;

//REFERENCIAS
use App\Http\Controllers\Api\ReferenciaController;

//reportes excel
use App\Http\Controllers\Api\ReporteExcelController;

//periodos
use App\Http\Controllers\Api\PeriodoAcademicoController;

/*
|--------------------------------------------------------------------------
| AUTH (público)
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
});


Route::prefix('periodos-academicos')->group(function () {
    Route::get('/', [PeriodoAcademicoController::class, 'index']);
    Route::put('/', [PeriodoAcademicoController::class, 'actualizarMasivo']);
    Route::post('/restaurar', [PeriodoAcademicoController::class, 'restaurarValoresPredeterminados']);
});

Route::post('/periodos-academicos/{id}/bloquear', [PeriodoAcademicoController::class, 'bloquear']);
Route::post('/periodos-academicos/{id}/desbloquear', [PeriodoAcademicoController::class, 'desbloquear']);


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
        Route::post('verify-password', [AuthController::class, 'verifyPassword']);
        Route::put('change-password', [AuthController::class, 'changePassword']);
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
    Route::post('carga-inicial', [DatabaseController::class, 'cargaInicial']);
    Route::post('migrar-catalogos', [DatabaseController::class, 'migrarCatalogos']);
    Route::post('migrar-catalogo/{tabla}', [DatabaseController::class, 'migrarCatalogo']);
    Route::post('migrar-grupos', [DatabaseController::class, 'migrarGrupos']);
    Route::post('migrar-semestre', [DatabaseController::class, 'migrarSemestre']);
    Route::post('/migrar-docentes', [DatabaseController::class, 'migrarDocentes']);
});
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

Route::post('/reporte-docente', [ReporteDocenteController::class, 'materiasDictadasCompartidas']);
Route::get('/reporte-horario', [ReporteDocenteController::class, 'horario']);
Route::post('/reporte-docente2', [ReporteDocenteController::class, 'materiasDictadasCompartidas']);

Route::get('reporte-docentes/tipos-titulo', [ReporteDocenteController::class, 'tiposTitulo']);
Route::put('/reporte-docentes/tipos-titulo', [ClasificacionDocenteController::class, 'actualizarTipoTitulo']);
Route::get('reporte-docentes/con-titulo', [ReporteDocenteController::class, 'docentesConTitulo']);
Route::get('reporte-docentes/con-titulo/excel', [ReporteDocenteController::class, 'excel']);
/*
|--------------------------------------------------------------------------
| HORARIOS DE DOCENTES
|--------------------------------------------------------------------------
*/
Route::prefix('horarios')->group(function () {
    Route::get('/docentes', [HorarioDocenteController::class, 'index']);
    Route::post('/docentes', [HorarioDocenteController::class, 'index']); // acepta JSON body
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

Route::post('/resoluciones/{id}', [ResolucionPdfController::class, 'update']);

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


//INSCRITOS ACOMODAR 
//Route::get('/estudiantes-inscritos/resumen-grupo', [EstudianteInscritoController::class, 'resumenPorGrupo']);
Route::get('/admin/horarios/inscritos/agrupados/aprobados-reprobados', [EstudianteInscritoController::class, 'resumenPorGrupo']);

Route::get('/admin/horarios/inscritos/aprobados-reprobados', [EstudianteInscritoController::class, 'resumenAprobadosReprobados']);

/*
|--------------------------------------------------------------------------
| DIGITALSIACION 2 ARCHIVOS 
|--------------------------------------------------------------------------
*/
Route::get('/clasificaciones', [ClasificacionDocenteController::class, 'index']);
Route::get('/clasificaciones/{id}', [ClasificacionDocenteController::class, 'show'])
    ->where('id', '[0-9]+');
Route::post('/clasificaciones', [ClasificacionDocenteController::class, 'store']);
Route::get('/clasificaciones/{id}/pdf', [ClasificacionDocenteController::class, 'descargar'])
    ->where('id', '[0-9]+');
Route::delete('/clasificaciones/{id}', [ClasificacionDocenteController::class, 'destroy'])
    ->where('id', '[0-9]+');
Route::get('/categorias', [ClasificacionDocenteController::class, 'categorias']);
Route::put('/categorias', [ClasificacionDocenteController::class, 'actualizarCategoria']);
// Reportes
Route::get('/reportes/clasificacion', [ReporteClasificacionController::class, 'listado']);
Route::get('/reportes/clasificacion/docente/{cod_docente}', [ReporteClasificacionController::class, 'porDocente'])
    ->where('cod_docente', '[0-9]+');
Route::get('/reportes/clasificacion/por-referencia', [ReporteClasificacionController::class, 'porReferencia']);

Route::delete('/clasificaciones/docente/{idClasificacionDocente}', [ClasificacionDocenteController::class, 'destroyDocente'])
    ->where('idClasificacionDocente', '[0-9]+');

Route::put('/clasificaciones/{id}', [ClasificacionDocenteController::class, 'update']);

Route::get(
    '/reportes/clasificacion/id-por-referencia',
    [ReporteClasificacionController::class, 'idPorReferencia']
);

Route::get('clasificaciones/docente/{codDocente}/categorias', [ClasificacionDocenteController::class, 'categoriasDocente']);
Route::get('clasificaciones/docente/{codDocente}/documentos', [ClasificacionDocenteController::class, 'documentosDocente']);

// Aplicar / quitar clasificación en GRUPOS
// (id = ID_DOCUMENTO; solo tiene efecto si el documento tiene CLASIFICACION_MATERIA)
Route::put('/clasificaciones/{id}/aplicar', [ClasificacionDocenteController::class, 'aplicarEnGrupos'])
    ->where('id', '[0-9]+');
Route::put('/clasificaciones/{id}/quitar', [ClasificacionDocenteController::class, 'quitarDeGrupos'])
    ->where('id', '[0-9]+');
Route::get('/clasificaciones/materias-registradas', [ClasificacionDocenteController::class, 'materiasRegistradas']);


/*
|--------------------------------------------------------------------------
| MATERIASS 
|--------------------------------------------------------------------------
*/

Route::get('/materias', [MateriaController::class, 'index']);
Route::get('/materias/periodos', [MateriaController::class, 'periodos']);
Route::get('/materias/docente', [MateriaController::class, 'porDocente']);


/*
|--------------------------------------------------------------------------
| referencias 
|--------------------------------------------------------------------------
*/
Route::get('/referencias', [ReferenciaController::class, 'index']);
Route::get('/referencias/anios', [ReferenciaController::class, 'anios']);


// Reporte Excel
Route::get('/reportes/docentes-clasificados/excel', [ReporteExcelController::class, 'generarListadoDocentes']);
Route::get('/reportes/docentes-clasificados/preview', [ReporteExcelController::class, 'previsualizar']);

Route::get('/reportes/docentes-activos', [ReporteExcelController::class, 'obtenerDocentesActivos']);
Route::get('/reportes/carga-horaria-docentes', [ReporteExcelController::class, 'obtenerCargaHorariaDocentes']);
Route::post('/reportes/docentes-clasificados/excel-personalizado', [ReporteExcelController::class, 'generarListadoDocentesDesdeDatos']);