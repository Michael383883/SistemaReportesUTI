<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ClasificacionDocenteController extends Controller
{
    // GET /clasificaciones
    // Nivel de fila: CLASIFICACION_DOCENTE (documento + un docente + sus materias)
    public function index(Request $request)
    {
        $query = DB::table('CLASIFICACION_DOCENTE as ccd')
            ->join('CLASIFICACION_DOCUMENTO as cdoc', 'cdoc.ID_DOCUMENTO', '=', 'ccd.ID_DOCUMENTO')
            ->join('DOCENTES as d', 'd.CODIGO', '=', 'ccd.COD_DOCENTE')
            ->select(
                'ccd.ID_CLASIFICACION_DOCENTE',
                'ccd.ID_DOCUMENTO',
                'ccd.COD_DOCENTE',
                DB::raw("LTRIM(RTRIM(d.APELLIDOS + ' ' + d.NOMBRES)) AS NOMBRE_DOCENTE"),
                'cdoc.CATEGORIA',
                'cdoc.NIVEL',
                'cdoc.TIPO_DOCUMENTO',
                'cdoc.GESTION',
                'cdoc.PERIODO',
                'cdoc.FOTOCOPIA_TITULAR',
                'cdoc.NOMBRE_ARCHIVO',
                'cdoc.FECHA_REGISTRO'
            );

        if ($request->filled('categoria')) {
            $query->where('cdoc.CATEGORIA', $request->query('categoria'));
        }
        if ($request->filled('nivel')) {
            $query->where('cdoc.NIVEL', $request->query('nivel'));
        }
        if ($request->filled('gestion')) {
            $query->where('cdoc.GESTION', $request->query('gestion'));
        }
        if ($request->filled('cod_docente')) {
            $query->where('ccd.COD_DOCENTE', $request->query('cod_docente'));
        }
        if ($request->filled('tipo_documento')) {
            $query->where('cdoc.TIPO_DOCUMENTO', $request->query('tipo_documento'));
        }

        $listado = $query->orderBy('NOMBRE_DOCENTE')->get();

        return response()->json($listado);
    }

    // GET /clasificaciones/{id}
    // $id = ID_CLASIFICACION_DOCENTE (fila docente dentro del documento)
    public function show($id)
    {
        $cabecera = DB::table('CLASIFICACION_DOCENTE as ccd')
            ->join('CLASIFICACION_DOCUMENTO as cdoc', 'cdoc.ID_DOCUMENTO', '=', 'ccd.ID_DOCUMENTO')
            ->join('DOCENTES as d', 'd.CODIGO', '=', 'ccd.COD_DOCENTE')
            ->where('ccd.ID_CLASIFICACION_DOCENTE', $id)
            ->select(
                'ccd.ID_CLASIFICACION_DOCENTE',
                'ccd.ID_DOCUMENTO',
                'ccd.COD_DOCENTE',
                'cdoc.*',
                DB::raw("LTRIM(RTRIM(d.APELLIDOS + ' ' + d.NOMBRES)) AS NOMBRE_DOCENTE")
            )
            ->first();

        if (!$cabecera) {
            return response()->json(['ok' => false, 'error' => 'Clasificación no encontrada'], 404);
        }

        // Materias asignadas a ESTE docente dentro del documento
        $cabecera->materias = DB::table('CLASIFICACION_MATERIA')
            ->where('ID_CLASIFICACION_DOCENTE', $id)
            ->orderBy('ORDEN')
            ->get();

        // Las referencias son del documento completo, no de un docente en particular
        $cabecera->referencias = DB::table('CLASIFICACION_REFERENCIA')
            ->where('ID_DOCUMENTO', $cabecera->ID_DOCUMENTO)
            ->get();

        // Bonus: otros docentes que comparten el mismo documento (útil para la UI)
        $cabecera->otros_docentes = DB::table('CLASIFICACION_DOCENTE as ccd2')
            ->join('DOCENTES as d2', 'd2.CODIGO', '=', 'ccd2.COD_DOCENTE')
            ->where('ccd2.ID_DOCUMENTO', $cabecera->ID_DOCUMENTO)
            ->where('ccd2.ID_CLASIFICACION_DOCENTE', '!=', $id)
            ->select(
                'ccd2.ID_CLASIFICACION_DOCENTE',
                'ccd2.COD_DOCENTE',
                DB::raw("LTRIM(RTRIM(d2.APELLIDOS + ' ' + d2.NOMBRES)) AS NOMBRE_DOCENTE")
            )
            ->get();

        return response()->json($cabecera);
    }

    // POST /clasificaciones
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $request->validate([
                'cod_docente' => 'nullable|integer', // docente "general" (usado en caso "no regenta")
                'categoria' => 'required|string|max:60',
                'nivel' => 'nullable|string|in:Primer nivel,Segundo nivel,Tercer nivel',
                'tipo_documento' => 'nullable|string|max:40',
                'gestion' => 'nullable|string|max:10',
                'periodo' => 'nullable|string|max:30',
                'detalle_general' => 'nullable|string',
                'observacion' => 'nullable|string|max:300',
                'observacion2' => 'nullable|string|max:300',
                'archivo_pdf' => 'nullable|file|mimes:pdf|max:20480',
                'materias' => 'nullable|string',    // JSON string
                'referencias' => 'nullable|string', // JSON string
            ]);

            // ------- decodificar materias primero, para saber qué docentes hay -------
            $materias = [];
            if ($request->filled('materias')) {
                $materias = json_decode($request->materias, true);
                if (!is_array($materias)) {
                    throw new \Exception('materias inválidas: el JSON no es un array');
                }
            }

            // Recolecta los docentes distintos que aparecen en las materias
            // (cada materia trae su propio objeto "docente": { cod_docente, nombres, apellidos })
            $codigosDocentes = collect($materias)
                ->pluck('docente.cod_docente')
                ->filter()
                ->unique()
                ->values();

            // Caso "no regenta materia en la FCE": no hay materias reales, pero
            // sí hay un docente general (form.cod_docente) que debe registrarse igual.
            if ($codigosDocentes->isEmpty() && $request->filled('cod_docente')) {
                $codigosDocentes = collect([(int) $request->cod_docente]);
            }

            if ($codigosDocentes->isEmpty()) {
                throw new \Exception('No se especificó ningún docente (ni en materias ni como docente general).');
            }

            // ------- archivo -------
            $rutaArchivo = null;
            $nombreArchivo = null;
            $fotocopia = false;

            if ($request->hasFile('archivo_pdf')) {
                $archivo = $request->file('archivo_pdf');

                if (!$archivo->isValid()) {
                    return response()->json(['ok' => false, 'error' => 'Archivo inválido'], 400);
                }

                $carpeta = 'clasificacion_docente/' . ($request->gestion ?: 'sin_gestion');
                $nombreLimpio = preg_replace('/[^A-Za-z0-9\-_\.]/', '-', $archivo->getClientOriginalName());
                $rutaArchivo = $archivo->storeAs($carpeta, $nombreLimpio, 'public');
                $nombreArchivo = $archivo->getClientOriginalName();
                $fotocopia = true;
            }

            // ------- 1) CLASIFICACION_DOCUMENTO (una sola vez) -------
            $idDocumento = DB::table('CLASIFICACION_DOCUMENTO')->insertGetId([
                'CATEGORIA' => $request->categoria,
                'NIVEL' => $request->nivel ?: null,
                'GESTION' => $request->gestion,
                'PERIODO' => $request->periodo,
                'TIPO_DOCUMENTO' => $request->tipo_documento,
                'DETALLE_GENERAL' => $request->detalle_general,
                'FOTOCOPIA_TITULAR' => $fotocopia,
                'RUTA_ARCHIVO' => $rutaArchivo,
                'NOMBRE_ARCHIVO' => $nombreArchivo,
                'OBSERVACION' => $request->observacion,
                'OBSERVACION2' => $request->observacion2,
                //'FECHA_REGISTRO' => now(),
                'FECHA_REGISTRO' => DB::raw('GETDATE()'),
            ], 'ID_DOCUMENTO');

            // ------- 2) CLASIFICACION_DOCENTE (una fila por cada docente distinto) -------
            $mapaDocenteId = []; // cod_docente => ID_CLASIFICACION_DOCENTE

            foreach ($codigosDocentes as $cod) {
                $idClasifDocente = DB::table('CLASIFICACION_DOCENTE')->insertGetId([
                    'ID_DOCUMENTO' => $idDocumento,
                    'COD_DOCENTE' => $cod,
                ], 'ID_CLASIFICACION_DOCENTE');

                $mapaDocenteId[$cod] = $idClasifDocente;
            }

            // ------- 3) CLASIFICACION_MATERIA -------
            $materiasInsertadas = 0;

            foreach ($materias as $i => $m) {
                $codDocenteMateria = $m['docente']['cod_docente'] ?? null;
                $idClasifDocenteMateria = $codDocenteMateria
                    ? ($mapaDocenteId[$codDocenteMateria] ?? null)
                    : null;

                DB::table('CLASIFICACION_MATERIA')->insert([
                    'ID_DOCUMENTO' => $idDocumento,
                    'ID_CLASIFICACION_DOCENTE' => $idClasifDocenteMateria,
                    'COD_MATERIA' => $m['cod_materia'] ?? null,
                    'NOMBRE_MATERIA' => $m['nombre_materia'],
                    'COD_PLAN' => $m['cod_plan'] ?? null,
                    'GRUPO' => isset($m['grupo']) && $m['grupo'] !== null ? (string) $m['grupo'] : null, // ← cast explícito a string
                    'NOTA' => $m['nota'] ?? null,
                    'DETALLE' => $m['detalle'] ?? null,
                    'ORDEN' => $i,
                ]);
                $materiasInsertadas++;
            }

            // ------- 4) CLASIFICACION_REFERENCIA (a nivel documento) -------
            $referenciasInsertadas = 0;

            if ($request->filled('referencias')) {
                $referencias = json_decode($request->referencias, true);

                if (!is_array($referencias)) {
                    throw new \Exception('referencias inválidas: el JSON no es un array');
                }

                foreach ($referencias as $r) {
                    DB::table('CLASIFICACION_REFERENCIA')->insert([
                        'ID_DOCUMENTO' => $idDocumento,
                        'NRO_REFERENCIA' => $r['nro_referencia'],
                        'ID_RESOLUCION' => $r['id_resolucion'] ?? null,
                    ]);
                    $referenciasInsertadas++;
                }
            }



            DB::commit();

            return response()->json([
                'ok' => true,
                'mensaje' => 'Clasificación registrada correctamente',
                'id_documento' => $idDocumento,
                'ids_clasificacion_docente' => array_values($mapaDocenteId),
                'materias_insertadas' => $materiasInsertadas,
                'referencias_insertadas' => $referenciasInsertadas,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();

            return response()->json([
                'ok' => false,
                'tipo' => 'validacion',
                'errores' => $e->errors(),
            ], 422);

        } catch (\Throwable $e) {
            DB::rollBack();

            $mensajeSeguro = preg_replace(
                '/[\x00-\x08\x0B\x0C\x0E-\x1F\x80-\xFF]/',
                '?',
                $e->getMessage()
            );

            \Log::error('Error ClasificacionDocente', [
                'mensaje' => $mensajeSeguro,
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json(['ok' => false, 'error' => $mensajeSeguro], 500);
        }
    }

    // GET /clasificaciones/{id}/pdf
    // $id = ID_DOCUMENTO (el PDF es del documento, no de un docente en particular)
    public function descargar(Request $request, $id)
    {
        $doc = DB::table('CLASIFICACION_DOCUMENTO')
            ->select('ID_DOCUMENTO', 'NOMBRE_ARCHIVO', 'RUTA_ARCHIVO')
            ->where('ID_DOCUMENTO', $id)
            ->first();

        if (!$doc) {
            return response()->json(['ok' => false, 'error' => 'Documento no encontrado'], 404);
        }

        if (!$doc->RUTA_ARCHIVO || !Storage::disk('public')->exists($doc->RUTA_ARCHIVO)) {
            return response()->json(['ok' => false, 'error' => 'Archivo no encontrado en storage'], 404);
        }

        $contenido = Storage::disk('public')->get($doc->RUTA_ARCHIVO);
        $disposicion = $request->query('modo') === 'descargar' ? 'attachment' : 'inline';

        return response($contenido)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', $disposicion . '; filename="' . $doc->NOMBRE_ARCHIVO . '"');
    }

    // DELETE /clasificaciones/{id}
    // $id = ID_DOCUMENTO -> borra el documento completo (docentes, materias, referencias en cascada)
    public function destroy($id)
    {
        $doc = DB::table('CLASIFICACION_DOCUMENTO')->where('ID_DOCUMENTO', $id)->first();

        if (!$doc) {
            return response()->json(['ok' => false, 'error' => 'Documento no encontrado'], 404);
        }

        DB::table('CLASIFICACION_DOCUMENTO')->where('ID_DOCUMENTO', $id)->delete();

        try {
            if ($doc->RUTA_ARCHIVO && Storage::disk('public')->exists($doc->RUTA_ARCHIVO)) {
                Storage::disk('public')->delete($doc->RUTA_ARCHIVO);
            }
        } catch (\Throwable $eArchivo) {
            \Log::warning('No se pudo borrar el archivo físico del documento', [
                'id' => $id,
                'ruta' => $doc->RUTA_ARCHIVO,
                'error' => $eArchivo->getMessage(),
            ]);
        }

        return response()->json(['ok' => true, 'mensaje' => 'Documento eliminado correctamente']);
    }

    // DELETE /clasificaciones/docente/{idClasificacionDocente}
    // Elimina SOLO un docente del documento (no borra el documento ni sus otros docentes).
    // Las materias que apuntaban a este docente quedan con ID_CLASIFICACION_DOCENTE = NULL.
    public function destroyDocente($idClasificacionDocente)
    {
        $ccd = DB::table('CLASIFICACION_DOCENTE')
            ->where('ID_CLASIFICACION_DOCENTE', $idClasificacionDocente)
            ->first();

        if (!$ccd) {
            return response()->json(['ok' => false, 'error' => 'Registro de docente no encontrado'], 404);
        }

        DB::transaction(function () use ($idClasificacionDocente) {
            DB::table('CLASIFICACION_MATERIA')
                ->where('ID_CLASIFICACION_DOCENTE', $idClasificacionDocente)
                ->update(['ID_CLASIFICACION_DOCENTE' => null]);

            DB::table('CLASIFICACION_DOCENTE')
                ->where('ID_CLASIFICACION_DOCENTE', $idClasificacionDocente)
                ->delete();
        });

        return response()->json(['ok' => true, 'mensaje' => 'Docente eliminado de la clasificación']);
    }


    // PUT /clasificaciones/{id}/aplicar
//
// $id = ID_DOCUMENTO (CLASIFICACION_DOCUMENTO)
//
// Traslada los datos del documento hacia GRUPOS, SOLO para los grupos que
// coincidan con las materias que el/los docente(s) tienen en
// CLASIFICACION_MATERIA para este documento.
//
// Mapeo de campos:
//   CLASIFICACION_DOCUMENTO.TIPO_DOCUMENTO  -> GRUPOS.RESOLUCION
//   CLASIFICACION_DOCUMENTO.DETALLE_GENERAL -> GRUPOS.DESIGNACION
//   CLASIFICACION_DOCUMENTO.CATEGORIA       -> GRUPOS.TIPO_INGRESO
//
// Cruce (match) contra GRUPOS:
//   CLASIFICACION_DOCUMENTO.GESTION   = GRUPOS.ANIO
//   CLASIFICACION_DOCUMENTO.PERIODO   = GRUPOS.PERIODO
//   CLASIFICACION_MATERIA.COD_PLAN    = GRUPOS.PLAN
//   CLASIFICACION_MATERIA.COD_MATERIA = GRUPOS.MATERIA
//   CLASIFICACION_MATERIA.GRUPO       = GRUPOS.GRUPO
//   CLASIFICACION_DOCENTE.COD_DOCENTE = GRUPOS.DOCENTE
//
// IMPORTANTE: si el documento se guardó en el caso "no regenta materia"
// (sin filas en CLASIFICACION_MATERIA), no hay PLAN/MATERIA/GRUPO con qué
// cruzar. En ese caso esta función NO toca GRUPOS y devuelve
// filas_afectadas = 0 con un aviso, tal como pediste ("solo guarda como antes").
//
// Acepta opcionalmente "ids_materia" en el body (IDs de CLASIFICACION_MATERIA)
// para limitar a materias puntuales; si no viene, aplica todas las del documento.

    public function aplicarEnGrupos(Request $request, $id)
    {
        $idsMateria = $request->input('ids_materia', []);
        $filtrarPorIds = is_array($idsMateria) && count($idsMateria) > 0;

        $sqlUpdate = null;
        $paramsUpdate = null;

        try {
            $tieneMaterias = DB::table('CLASIFICACION_MATERIA')
                ->where('ID_DOCUMENTO', $id)
                ->whereNotNull('COD_MATERIA')
                ->exists();

            if (!$tieneMaterias) {
                return response()->json([
                    'ok' => true,
                    'filas_afectadas' => 0,
                    'grupos' => [],
                    'mensaje' => 'Este documento no tiene materias asignadas (caso "no regenta"); no se modifica GRUPOS.',
                ]);
            }

            $placeholders = $filtrarPorIds
                ? implode(',', array_fill(0, count($idsMateria), '?'))
                : null;

            $filtroIdMateriaSql = $filtrarPorIds
                ? "AND cm.ID_DETALLE IN ($placeholders)"
                : '';

            $paramsUpdate = $filtrarPorIds
                ? array_merge([$id], $idsMateria)
                : [$id];

            $sqlUpdate = "
        UPDATE g
        SET
            g.RESOLUCION   = cdoc.TIPO_DOCUMENTO,
            g.DESIGNACION  = cdoc.DETALLE_GENERAL,
            g.TIPO_INGRESO = cdoc.CATEGORIA
        FROM GRUPOS g
        JOIN CLASIFICACION_MATERIA cm
            ON  g.[PLAN]  COLLATE Modern_Spanish_CI_AS = cm.COD_PLAN    COLLATE Modern_Spanish_CI_AS
            AND g.MATERIA COLLATE Modern_Spanish_CI_AS = cm.COD_MATERIA COLLATE Modern_Spanish_CI_AS
            AND g.[GRUPO] COLLATE Modern_Spanish_CI_AS = CAST(cm.[GRUPO] AS VARCHAR(10)) COLLATE Modern_Spanish_CI_AS
        JOIN CLASIFICACION_DOCENTE ccd
            ON ccd.ID_CLASIFICACION_DOCENTE = cm.ID_CLASIFICACION_DOCENTE
        JOIN CLASIFICACION_DOCUMENTO cdoc
            ON cdoc.ID_DOCUMENTO = ccd.ID_DOCUMENTO
        WHERE
            cdoc.ID_DOCUMENTO = ?
            AND g.DOCENTE = ccd.COD_DOCENTE
            AND g.ANIO    = CAST(cdoc.GESTION AS NUMERIC(5,0))
            AND g.PERIODO COLLATE Modern_Spanish_CI_AS = cdoc.PERIODO COLLATE Modern_Spanish_CI_AS
            AND g.[TIPO]  COLLATE Modern_Spanish_CI_AS = 'N'
            {$filtroIdMateriaSql}
        ";

            $actualizados = DB::update($sqlUpdate, $paramsUpdate);

            $sqlSelect = "
        SELECT
            g.ANIO, g.PERIODO, g.[PLAN], g.MATERIA, g.[GRUPO],
            g.DOCENTE, g.[TIPO], g.TIPO_INGRESO, g.RESOLUCION, g.DESIGNACION
        FROM GRUPOS g
        JOIN CLASIFICACION_MATERIA cm
            ON  g.[PLAN]  COLLATE Modern_Spanish_CI_AS = cm.COD_PLAN    COLLATE Modern_Spanish_CI_AS
            AND g.MATERIA COLLATE Modern_Spanish_CI_AS = cm.COD_MATERIA COLLATE Modern_Spanish_CI_AS
            AND g.[GRUPO] COLLATE Modern_Spanish_CI_AS = CAST(cm.[GRUPO] AS VARCHAR(10)) COLLATE Modern_Spanish_CI_AS
        JOIN CLASIFICACION_DOCENTE ccd
            ON ccd.ID_CLASIFICACION_DOCENTE = cm.ID_CLASIFICACION_DOCENTE
        JOIN CLASIFICACION_DOCUMENTO cdoc
            ON cdoc.ID_DOCUMENTO = ccd.ID_DOCUMENTO
        WHERE
            cdoc.ID_DOCUMENTO = ?
            AND g.DOCENTE = ccd.COD_DOCENTE
            AND g.ANIO    = CAST(cdoc.GESTION AS NUMERIC(5,0))
            AND g.PERIODO COLLATE Modern_Spanish_CI_AS = cdoc.PERIODO COLLATE Modern_Spanish_CI_AS
            AND g.[TIPO]  COLLATE Modern_Spanish_CI_AS = 'N'
            {$filtroIdMateriaSql}
        ";

            $gruposActualizados = DB::select($sqlSelect, $paramsUpdate);

            return response()->json([
                'ok' => true,
                'filas_afectadas' => $actualizados,
                'grupos' => $gruposActualizados,
            ]);

        } catch (\Illuminate\Database\QueryException $e) {
            // Error específico de SQL Server: código de error, SQLSTATE, mensaje real del motor
            $errorInfo = $e->errorInfo ?? null; // [SQLSTATE, driver_error_code, driver_error_message]

            Log::error('Error SQL en aplicarEnGrupos', [
                'id_documento' => $id,
                'sql' => $sqlUpdate,
                'bindings' => $paramsUpdate,
                'sqlstate' => $errorInfo[0] ?? null,
                'driver_code' => $errorInfo[1] ?? null,
                'driver_message' => $errorInfo[2] ?? null,
                'mensaje_completo' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'tipo_error' => 'sql',
                'sqlstate' => $errorInfo[0] ?? null,
                'codigo_driver' => $errorInfo[1] ?? null,
                'mensaje_driver' => $errorInfo[2] ?? null,
                'error' => $e->getMessage(),
                'sql' => $sqlUpdate,
                'bindings' => $paramsUpdate,
            ], 500);

        } catch (\Exception $e) {
            Log::error('Error inesperado en aplicarEnGrupos', [
                'id_documento' => $id,
                'mensaje' => $e->getMessage(),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine(),
            ]);

            return response()->json([
                'ok' => false,
                'tipo_error' => 'general',
                'error' => $e->getMessage(),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine(),
            ], 500);
        }
    }


    // PUT /clasificaciones/{id}/quitar
    public function quitarDeGrupos(Request $request, $id)
    {
        $idsMateria = $request->input('ids_materia', []);
        $filtrarPorIds = is_array($idsMateria) && count($idsMateria) > 0;

        $sqlUpdate = null;
        $paramsUpdate = null;

        try {
            $tieneMaterias = DB::table('CLASIFICACION_MATERIA')
                ->where('ID_DOCUMENTO', $id)
                ->whereNotNull('COD_MATERIA')
                ->exists();

            if (!$tieneMaterias) {
                return response()->json([
                    'ok' => true,
                    'filas_afectadas' => 0,
                    'grupos' => [],
                    'mensaje' => 'Este documento no tiene materias asignadas (caso "no regenta"); no hay nada que quitar de GRUPOS.',
                ]);
            }

            $placeholders = $filtrarPorIds
                ? implode(',', array_fill(0, count($idsMateria), '?'))
                : null;

            $filtroIdMateriaSql = $filtrarPorIds
                ? "AND cm.ID_DETALLE IN ($placeholders)"
                : '';

            $paramsUpdate = $filtrarPorIds
                ? array_merge([$id], $idsMateria)
                : [$id];

            $sqlSelect = "
        SELECT
            g.ANIO, g.PERIODO, g.[PLAN], g.MATERIA, g.[GRUPO],
            g.DOCENTE, g.[TIPO], g.TIPO_INGRESO, g.RESOLUCION, g.DESIGNACION
        FROM GRUPOS g
        JOIN CLASIFICACION_MATERIA cm
            ON  g.[PLAN]  COLLATE Modern_Spanish_CI_AS = cm.COD_PLAN    COLLATE Modern_Spanish_CI_AS
            AND g.MATERIA COLLATE Modern_Spanish_CI_AS = cm.COD_MATERIA COLLATE Modern_Spanish_CI_AS
            AND g.[GRUPO] COLLATE Modern_Spanish_CI_AS = CAST(cm.[GRUPO] AS VARCHAR(10)) COLLATE Modern_Spanish_CI_AS
        JOIN CLASIFICACION_DOCENTE ccd
            ON ccd.ID_CLASIFICACION_DOCENTE = cm.ID_CLASIFICACION_DOCENTE
        JOIN CLASIFICACION_DOCUMENTO cdoc
            ON cdoc.ID_DOCUMENTO = ccd.ID_DOCUMENTO
        WHERE
            cdoc.ID_DOCUMENTO = ?
            AND g.DOCENTE = ccd.COD_DOCENTE
            AND g.ANIO    = CAST(cdoc.GESTION AS NUMERIC(5,0))
            AND g.PERIODO COLLATE Modern_Spanish_CI_AS = cdoc.PERIODO COLLATE Modern_Spanish_CI_AS
            AND g.[TIPO]  COLLATE Modern_Spanish_CI_AS = 'N'
            {$filtroIdMateriaSql}
        ";

            $gruposAfectados = DB::select($sqlSelect, $paramsUpdate);

            $sqlUpdate = "
        UPDATE g
        SET
            g.RESOLUCION   = NULL,
            g.DESIGNACION  = NULL,
            g.TIPO_INGRESO = NULL
        FROM GRUPOS g
        JOIN CLASIFICACION_MATERIA cm
            ON  g.[PLAN]  COLLATE Modern_Spanish_CI_AS = cm.COD_PLAN    COLLATE Modern_Spanish_CI_AS
            AND g.MATERIA COLLATE Modern_Spanish_CI_AS = cm.COD_MATERIA COLLATE Modern_Spanish_CI_AS
            AND g.[GRUPO] COLLATE Modern_Spanish_CI_AS = CAST(cm.[GRUPO] AS VARCHAR(10)) COLLATE Modern_Spanish_CI_AS
        JOIN CLASIFICACION_DOCENTE ccd
            ON ccd.ID_CLASIFICACION_DOCENTE = cm.ID_CLASIFICACION_DOCENTE
        JOIN CLASIFICACION_DOCUMENTO cdoc
            ON cdoc.ID_DOCUMENTO = ccd.ID_DOCUMENTO
        WHERE
            cdoc.ID_DOCUMENTO = ?
            AND g.DOCENTE = ccd.COD_DOCENTE
            AND g.ANIO    = CAST(cdoc.GESTION AS NUMERIC(5,0))
            AND g.PERIODO COLLATE Modern_Spanish_CI_AS = cdoc.PERIODO COLLATE Modern_Spanish_CI_AS
            AND g.[TIPO]  COLLATE Modern_Spanish_CI_AS = 'N'
            {$filtroIdMateriaSql}
        ";

            $actualizados = DB::update($sqlUpdate, $paramsUpdate);

            return response()->json([
                'ok' => true,
                'filas_afectadas' => $actualizados,
                'grupos' => $gruposAfectados,
            ]);

        } catch (\Illuminate\Database\QueryException $e) {
            $errorInfo = $e->errorInfo ?? null;

            Log::error('Error SQL en quitarDeGrupos', [
                'id_documento' => $id,
                'sql' => $sqlUpdate,
                'bindings' => $paramsUpdate,
                'sqlstate' => $errorInfo[0] ?? null,
                'driver_code' => $errorInfo[1] ?? null,
                'driver_message' => $errorInfo[2] ?? null,
                'mensaje_completo' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'tipo_error' => 'sql',
                'sqlstate' => $errorInfo[0] ?? null,
                'codigo_driver' => $errorInfo[1] ?? null,
                'mensaje_driver' => $errorInfo[2] ?? null,
                'error' => $e->getMessage(),
                'sql' => $sqlUpdate,
                'bindings' => $paramsUpdate,
            ], 500);

        } catch (\Exception $e) {
            Log::error('Error inesperado en quitarDeGrupos', [
                'id_documento' => $id,
                'mensaje' => $e->getMessage(),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine(),
            ]);

            return response()->json([
                'ok' => false,
                'tipo_error' => 'general',
                'error' => $e->getMessage(),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine(),
            ], 500);
        }
    }

}