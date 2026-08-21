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
        if ($request->filled('periodo')) {
            $query->where('cdoc.PERIODO', $request->query('periodo'));
        }
        if ($request->filled('cod_docente')) {
            $query->where('ccd.COD_DOCENTE', $request->query('cod_docente'));
        }
        if ($request->filled('tipo_documento')) {
            $query->where('cdoc.TIPO_DOCUMENTO', $request->query('tipo_documento'));
        }

        if ($request->filled('tipo_titulo')) {
            $tipoTitulo = $request->query('tipo_titulo');
            $query->whereIn('ccd.ID_CLASIFICACION_DOCENTE', function ($sub) use ($tipoTitulo) {
                $sub->select('ID_CLASIFICACION_DOCENTE')
                    ->from('CLASIFICACION_TITULO')
                    ->where('TIPO_TITULO', $tipoTitulo);
            });
        }

        $listado = $query->orderBy('NOMBRE_DOCENTE')->get();

        return response()->json($listado);
    }

    // GET /clasificaciones/{id}
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
                'd.APELLIDOS',
                'd.NOMBRES',
                DB::raw("LTRIM(RTRIM(d.APELLIDOS + ' ' + d.NOMBRES)) AS NOMBRE_DOCENTE")
            )
            ->first();

        if (!$cabecera) {
            return response()->json(['ok' => false, 'error' => 'Clasificación no encontrada'], 404);
        }

        $cabecera->materias = DB::table('CLASIFICACION_MATERIA')
            ->where('ID_CLASIFICACION_DOCENTE', $id)
            ->orderBy('ORDEN')
            ->get();

        $cabecera->titulos = DB::table('CLASIFICACION_TITULO')
            ->where('ID_CLASIFICACION_DOCENTE', $id)
            ->orderBy('FECHA_TITULO')
            ->get();

        $cabecera->referencias = DB::table('CLASIFICACION_REFERENCIA')
            ->where('ID_DOCUMENTO', $cabecera->ID_DOCUMENTO)
            ->get();

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
                'cod_docente' => 'nullable|integer',
                'categoria' => 'required|string|max:60',
                'nivel' => 'nullable|string|in:Primer nivel,Segundo nivel,Tercer nivel',
                'tipo_documento' => 'nullable|string|max:40',
                'gestion' => 'nullable|string|max:10',
                'periodo' => 'nullable|string|max:30',
                'detalle_general' => 'nullable|string',
                'observacion' => 'nullable|string|max:300',
                'observacion2' => 'nullable|string|max:300',
                'archivo_pdf' => 'nullable|file|mimes:pdf|max:20480',
                'materias' => 'nullable|string',
                'referencias' => 'nullable|string',
                'titulo' => 'nullable|string',
            ]);

            $materias = [];
            if ($request->filled('materias')) {
                $materias = json_decode($request->materias, true);
                if (!is_array($materias)) {
                    throw new \Exception('materias inválidas: el JSON no es un array');
                }
            }

            $titulo = null;
            if ($request->filled('titulo')) {
                $titulo = json_decode($request->titulo, true);
                if (!is_array($titulo)) {
                    throw new \Exception('titulo inválido: el JSON no es un objeto');
                }
            }

            $codigosDocentes = collect($materias)
                ->pluck('docente.cod_docente')
                ->when($titulo && !empty($titulo['cod_docente']), fn($c) => $c->push($titulo['cod_docente']))
                ->filter()
                ->unique()
                ->values();

            if ($codigosDocentes->isEmpty() && $request->filled('cod_docente')) {
                $codigosDocentes = collect([(int) $request->cod_docente]);
            }

            if ($codigosDocentes->isEmpty()) {
                throw new \Exception('No se especificó ningún docente (ni en materias ni como docente general).');
            }

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
                'FECHA_REGISTRO' => DB::raw('GETDATE()'),
            ], 'ID_DOCUMENTO');

            $mapaDocenteId = [];

            foreach ($codigosDocentes as $cod) {
                $idClasifDocente = DB::table('CLASIFICACION_DOCENTE')->insertGetId([
                    'ID_DOCUMENTO' => $idDocumento,
                    'COD_DOCENTE' => $cod,
                ], 'ID_CLASIFICACION_DOCENTE');

                $mapaDocenteId[$cod] = $idClasifDocente;
            }

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
                    'GRUPO' => isset($m['grupo']) && $m['grupo'] !== null ? (string) $m['grupo'] : null,
                    'NOTA' => $m['nota'] ?? null,
                    'DETALLE' => $m['detalle'] ?? null,
                    'ORDEN' => $i,
                ]);
                $materiasInsertadas++;
            }

            $tituloInsertado = false;

            if ($titulo) {
                $codDocenteTitulo = $titulo['cod_docente'] ?? null;
                $idClasifDocenteTitulo = $codDocenteTitulo
                    ? ($mapaDocenteId[$codDocenteTitulo] ?? null)
                    : null;

                DB::table('CLASIFICACION_TITULO')->insert([
                    'ID_DOCUMENTO' => $idDocumento,
                    'ID_CLASIFICACION_DOCENTE' => $idClasifDocenteTitulo,
                    'TIPO_TITULO' => $titulo['tipo_titulo'],
                    'UNIVERSIDAD' => $titulo['universidad'] ?? null,
                    'PAIS' => $titulo['pais'] ?? null,
                    'FECHA_TITULO' => $titulo['fecha_titulo'] ?? null,
                    'NOMBRE_TITULO' => $titulo['nombre_titulo'],
                    'NUMERO' => $titulo['numero'] ?? null,
                ]);
                $tituloInsertado = true;
            }

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
                'titulos_insertado' => $tituloInsertado,
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

    // PUT /clasificaciones/{id}
    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $ccd = DB::table('CLASIFICACION_DOCENTE')
                ->where('ID_CLASIFICACION_DOCENTE', $id)
                ->first();

            if (!$ccd) {
                DB::rollBack();
                return response()->json(['ok' => false, 'error' => 'Clasificación no encontrada'], 404);
            }

            $idDocumento = $ccd->ID_DOCUMENTO;

            $request->validate([
                'categoria' => 'required|string|max:60',
                'nivel' => 'nullable|string|in:Primer nivel,Segundo nivel,Tercer nivel',
                'tipo_documento' => 'nullable|string|max:40',
                'gestion' => 'nullable|string|max:10',
                'periodo' => 'nullable|string|max:30',
                'detalle_general' => 'nullable|string',
                'observacion' => 'nullable|string|max:300',
                'observacion2' => 'nullable|string|max:300',
                'archivo_pdf' => 'nullable|file|mimes:pdf|max:20480',
                'materias' => 'nullable|string',
                'referencias' => 'nullable|string',
                'titulo' => 'nullable|string',
            ]);

            $materias = [];
            if ($request->filled('materias')) {
                $materias = json_decode($request->materias, true);
                if (!is_array($materias)) {
                    throw new \Exception('materias inválidas: el JSON no es un array');
                }
            }

            $titulo = null;
            if ($request->filled('titulo')) {
                $titulo = json_decode($request->titulo, true);
                if (!is_array($titulo)) {
                    throw new \Exception('titulo inválido: el JSON no es un objeto');
                }
            }

            $datosDocumento = [
                'CATEGORIA' => $request->categoria,
                'NIVEL' => $request->nivel ?: null,
                'GESTION' => $request->gestion,
                'PERIODO' => $request->periodo,
                'TIPO_DOCUMENTO' => $request->tipo_documento,
                'DETALLE_GENERAL' => $request->detalle_general,
                'OBSERVACION' => $request->observacion,
                'OBSERVACION2' => $request->observacion2,
            ];

            if ($request->hasFile('archivo_pdf')) {
                $archivo = $request->file('archivo_pdf');
                if (!$archivo->isValid()) {
                    DB::rollBack();
                    return response()->json(['ok' => false, 'error' => 'Archivo inválido'], 400);
                }

                $docActual = DB::table('CLASIFICACION_DOCUMENTO')->where('ID_DOCUMENTO', $idDocumento)->first();
                if ($docActual && $docActual->RUTA_ARCHIVO && Storage::disk('public')->exists($docActual->RUTA_ARCHIVO)) {
                    Storage::disk('public')->delete($docActual->RUTA_ARCHIVO);
                }

                $carpeta = 'clasificacion_docente/' . ($request->gestion ?: 'sin_gestion');
                $nombreLimpio = preg_replace('/[^A-Za-z0-9\-_\.]/', '-', $archivo->getClientOriginalName());
                $rutaArchivo = $archivo->storeAs($carpeta, $nombreLimpio, 'public');

                $datosDocumento['RUTA_ARCHIVO'] = $rutaArchivo;
                $datosDocumento['NOMBRE_ARCHIVO'] = $archivo->getClientOriginalName();
                $datosDocumento['FOTOCOPIA_TITULAR'] = true;
            }

            DB::table('CLASIFICACION_DOCUMENTO')
                ->where('ID_DOCUMENTO', $idDocumento)
                ->update($datosDocumento);

            DB::table('CLASIFICACION_MATERIA')
                ->where('ID_DOCUMENTO', $idDocumento)
                ->where('ID_CLASIFICACION_DOCENTE', $id)
                ->delete();

            $materiasInsertadas = 0;
            foreach ($materias as $i => $m) {
                DB::table('CLASIFICACION_MATERIA')->insert([
                    'ID_DOCUMENTO' => $idDocumento,
                    'ID_CLASIFICACION_DOCENTE' => $id,
                    'COD_MATERIA' => $m['cod_materia'] ?? null,
                    'NOMBRE_MATERIA' => $m['nombre_materia'],
                    'COD_PLAN' => $m['cod_plan'] ?? null,
                    'GRUPO' => isset($m['grupo']) && $m['grupo'] !== null ? (string) $m['grupo'] : null,
                    'NOTA' => $m['nota'] ?? null,
                    'DETALLE' => $m['detalle'] ?? null,
                    'ORDEN' => $i,
                ]);
                $materiasInsertadas++;
            }

            DB::table('CLASIFICACION_TITULO')
                ->where('ID_DOCUMENTO', $idDocumento)
                ->where('ID_CLASIFICACION_DOCENTE', $id)
                ->delete();

            $tituloActualizado = false;
            if ($titulo) {
                DB::table('CLASIFICACION_TITULO')->insert([
                    'ID_DOCUMENTO' => $idDocumento,
                    'ID_CLASIFICACION_DOCENTE' => $id,
                    'TIPO_TITULO' => $titulo['tipo_titulo'],
                    'UNIVERSIDAD' => $titulo['universidad'] ?? null,
                    'PAIS' => $titulo['pais'] ?? null,
                    'FECHA_TITULO' => $titulo['fecha_titulo'] ?? null,
                    'NOMBRE_TITULO' => $titulo['nombre_titulo'],
                    'NUMERO' => $titulo['numero'] ?? null,
                ]);
                $tituloActualizado = true;
            }

            $referenciasActualizadas = 0;
            if ($request->has('referencias')) {
                DB::table('CLASIFICACION_REFERENCIA')->where('ID_DOCUMENTO', $idDocumento)->delete();

                $referencias = json_decode($request->referencias, true) ?: [];
                foreach ($referencias as $r) {
                    DB::table('CLASIFICACION_REFERENCIA')->insert([
                        'ID_DOCUMENTO' => $idDocumento,
                        'NRO_REFERENCIA' => $r['nro_referencia'],
                        'ID_RESOLUCION' => $r['id_resolucion'] ?? null,
                    ]);
                    $referenciasActualizadas++;
                }
            }

            DB::commit();

            return response()->json([
                'ok' => true,
                'mensaje' => 'Clasificación actualizada correctamente',
                'id_documento' => $idDocumento,
                'id_clasificacion_docente' => (int) $id,
                'materias_actualizadas' => $materiasInsertadas,
                'titulo_actualizado' => $tituloActualizado,
                'referencias_actualizadas' => $referenciasActualizadas,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['ok' => false, 'tipo' => 'validacion', 'errores' => $e->errors()], 422);
        } catch (\Throwable $e) {
            DB::rollBack();
            $mensajeSeguro = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x80-\xFF]/', '?', $e->getMessage());
            \Log::error('Error al actualizar ClasificacionDocente', [
                'mensaje' => $mensajeSeguro,
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);
            return response()->json(['ok' => false, 'error' => $mensajeSeguro], 500);
        }
    }

    // GET /clasificaciones/{id}/pdf
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
    // DELETE /clasificaciones/docente/{idClasificacionDocente}
    public function destroyDocente($idClasificacionDocente)
    {
        $ccd = DB::table('CLASIFICACION_DOCENTE')
            ->where('ID_CLASIFICACION_DOCENTE', $idClasificacionDocente)
            ->first();

        if (!$ccd) {
            return response()->json(['ok' => false, 'error' => 'Registro de docente no encontrado'], 404);
        }

        try {
            DB::transaction(function () use ($idClasificacionDocente) {
                DB::table('CLASIFICACION_MATERIA')
                    ->where('ID_CLASIFICACION_DOCENTE', $idClasificacionDocente)
                    ->delete();

                DB::table('CLASIFICACION_TITULO')
                    ->where('ID_CLASIFICACION_DOCENTE', $idClasificacionDocente)
                    ->delete();

                DB::table('CLASIFICACION_DOCENTE')
                    ->where('ID_CLASIFICACION_DOCENTE', $idClasificacionDocente)
                    ->delete();
            });

            return response()->json(['ok' => true, 'mensaje' => 'Docente eliminado de la clasificación']);

        } catch (\Throwable $e) {
            $mensajeSeguro = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x80-\xFF]/', '?', $e->getMessage());

            \Log::error('Error al eliminar docente de clasificación', [
                'id_clasificacion_docente' => $idClasificacionDocente,
                'mensaje' => $mensajeSeguro,
            ]);

            return response()->json(['ok' => false, 'error' => $mensajeSeguro], 500);
        }
    }

    // PUT /clasificaciones/{id}/aplicar
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
            $errorInfo = $e->errorInfo ?? null;

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

    // GET /api/clasificaciones/materias-registradas
    public function materiasRegistradas(Request $request)
    {
        $request->validate([
            'docente' => 'required|numeric',
            'gestion' => 'required',
            'periodo' => 'nullable|string',
        ]);

        $docente = $request->query('docente');
        $gestion = $request->query('gestion');
        $periodo = $request->query('periodo');

        $query = DB::table('CLASIFICACION_MATERIA as cm')
            ->join('CLASIFICACION_DOCENTE as ccd', 'ccd.ID_CLASIFICACION_DOCENTE', '=', 'cm.ID_CLASIFICACION_DOCENTE')
            ->join('CLASIFICACION_DOCUMENTO as cdoc', 'cdoc.ID_DOCUMENTO', '=', 'ccd.ID_DOCUMENTO')
            ->where('ccd.COD_DOCENTE', $docente)
            ->where('cdoc.GESTION', $gestion)
            ->whereNotNull('cm.COD_MATERIA')
            ->select(
                'cm.ID_DETALLE as id_detalle',
                'cm.COD_MATERIA as cod_materia',
                'cm.COD_PLAN as cod_plan',
                'cm.GRUPO as grupo',
                'cm.NOTA as nota',
                'cm.DETALLE as detalle',
                'cdoc.ID_DOCUMENTO as id_documento',
                'cdoc.PERIODO as periodo',
                'cdoc.CATEGORIA as categoria',
                'ccd.ID_CLASIFICACION_DOCENTE as id_clasificacion_docente'
            );

        if ($periodo) {
            $query->where('cdoc.PERIODO', $periodo);
        }

        return response()->json($query->get());
    }

    // GET /api/categorias
    public function categorias()
    {
        $categorias = DB::table('CLASIFICACION_DOCUMENTO')
            ->select('CATEGORIA')
            ->whereNotNull('CATEGORIA')
            ->where('CATEGORIA', '<>', '')
            ->distinct()
            ->orderBy('CATEGORIA')
            ->pluck('CATEGORIA')
            ->values();

        return response()->json($categorias);
    }

    // GET /clasificaciones/docente/{codDocente}/categorias
    public function categoriasDocente($codDocente)
    {
        $categorias = DB::table('CLASIFICACION_DOCENTE as ccd')
            ->join('CLASIFICACION_DOCUMENTO as cdoc', 'cdoc.ID_DOCUMENTO', '=', 'ccd.ID_DOCUMENTO')
            ->where('ccd.COD_DOCENTE', $codDocente)
            ->whereNotNull('cdoc.CATEGORIA')
            ->where('cdoc.CATEGORIA', '<>', '')
            ->select('cdoc.CATEGORIA')
            ->distinct()
            ->orderBy('cdoc.CATEGORIA')
            ->pluck('cdoc.CATEGORIA');

        return response()->json([
            'ok' => true,
            'tiene_documentos' => $categorias->isNotEmpty(),
            'categorias' => $categorias,
        ]);
    }

    // GET /clasificaciones/docente/{codDocente}/documentos?categorias=Diploma,Maestría
    public function documentosDocente(Request $request, $codDocente)
    {
        $request->validate([
            'categoria' => 'nullable|string|max:60',
            'categorias' => 'nullable|string|max:500',
        ]);

        $categoriasFiltro = [];

        if ($request->filled('categorias')) {
            $categoriasFiltro = array_values(array_filter(
                array_map('trim', explode(',', $request->query('categorias')))
            ));
        } elseif ($request->filled('categoria')) {
            $categoriasFiltro = [$request->query('categoria')];
        }

        $query = DB::table('CLASIFICACION_DOCENTE as ccd')
            ->join('CLASIFICACION_DOCUMENTO as cdoc', 'cdoc.ID_DOCUMENTO', '=', 'ccd.ID_DOCUMENTO')
            ->where('ccd.COD_DOCENTE', $codDocente)
            ->select(
                'ccd.ID_CLASIFICACION_DOCENTE',
                'cdoc.ID_DOCUMENTO',
                'cdoc.CATEGORIA',
                'cdoc.TIPO_DOCUMENTO',
                'cdoc.GESTION',
                'cdoc.PERIODO',
                'cdoc.DETALLE_GENERAL',
                'cdoc.RUTA_ARCHIVO',
                'cdoc.NOMBRE_ARCHIVO',
                'cdoc.FECHA_REGISTRO'
            );

        if (!empty($categoriasFiltro)) {
            $query->whereIn('cdoc.CATEGORIA', $categoriasFiltro);
        }

        $documentos = $query
            ->orderBy('cdoc.CATEGORIA')
            ->orderBy('cdoc.GESTION')
            ->orderBy('cdoc.PERIODO')
            ->get()
            ->values()
            ->map(function ($d, $i) {
                $d->nro = $i + 1;
                $d->tiene_archivo = !empty($d->RUTA_ARCHIVO);
                unset($d->RUTA_ARCHIVO);
                return $d;
            });

        return response()->json([
            'ok' => true,
            'total' => $documentos->count(),
            'documentos' => $documentos,
        ]);
    }

    // PUT /api/categorias
    public function actualizarCategoria(Request $request)
    {
        $request->validate([
            'anterior' => 'required|string|max:60',
            'nuevo' => 'required|string|max:60',
        ]);

        $anterior = trim($request->anterior);
        $nuevo = trim($request->nuevo);

        if ($anterior === '' || $nuevo === '') {
            return response()->json(['ok' => false, 'error' => 'El nombre no puede estar vacío'], 422);
        }

        if ($anterior === $nuevo) {
            return response()->json(['ok' => true, 'filas_actualizadas' => 0]);
        }

        $yaExiste = DB::table('CLASIFICACION_DOCUMENTO')
            ->whereRaw('LOWER(CATEGORIA) = ?', [mb_strtolower($nuevo)])
            ->where('CATEGORIA', '<>', $anterior)
            ->exists();

        if ($yaExiste) {
            return response()->json(['ok' => false, 'error' => 'Ya existe una categoría con ese nombre'], 422);
        }

        $filas = DB::table('CLASIFICACION_DOCUMENTO')
            ->where('CATEGORIA', $anterior)
            ->update(['CATEGORIA' => $nuevo]);

        return response()->json([
            'ok' => true,
            'mensaje' => 'Categoría actualizada correctamente',
            'filas_actualizadas' => $filas,
        ]);
    }

    // GET /api/reporte-docentes/tipos-titulo
    // Devuelve los tipos de título distintos que ya se usaron en CLASIFICACION_TITULO,
    // para alimentar el combobox del frontend (useTiposTitulo.js).
    public function tiposTitulo()
    {
        $tipos = DB::table('CLASIFICACION_TITULO')
            ->select('TIPO_TITULO')
            ->whereNotNull('TIPO_TITULO')
            ->where('TIPO_TITULO', '<>', '')
            ->distinct()
            ->orderBy('TIPO_TITULO')
            ->pluck('TIPO_TITULO')
            ->values();

        return response()->json($tipos);
    }

    // PUT /api/reporte-docentes/tipos-titulo
    // Body: { "anterior": "DIPLOMADO", "nuevo": "DIPLOMADO ESPECIALIZADO" }
    //
    // No existe una tabla propia de tipos de título: son los valores distintos
    // de CLASIFICACION_TITULO.TIPO_TITULO. "Editar" un tipo significa renombrarlo
    // en TODOS los títulos que ya lo usan.
    public function actualizarTipoTitulo(Request $request)
    {
        $request->validate([
            'anterior' => 'required|string|max:60',
            'nuevo' => 'required|string|max:60',
        ]);

        $anterior = trim($request->anterior);
        $nuevo = trim($request->nuevo);

        if ($anterior === '' || $nuevo === '') {
            return response()->json(['ok' => false, 'error' => 'El nombre no puede estar vacío'], 422);
        }

        if ($anterior === $nuevo) {
            return response()->json(['ok' => true, 'filas_actualizadas' => 0]);
        }

        $yaExiste = DB::table('CLASIFICACION_TITULO')
            ->whereRaw('LOWER(TIPO_TITULO) = ?', [mb_strtolower($nuevo)])
            ->where('TIPO_TITULO', '<>', $anterior)
            ->exists();

        if ($yaExiste) {
            return response()->json(['ok' => false, 'error' => 'Ya existe un tipo de título con ese nombre'], 422);
        }

        $filas = DB::table('CLASIFICACION_TITULO')
            ->where('TIPO_TITULO', $anterior)
            ->update(['TIPO_TITULO' => $nuevo]);

        return response()->json([
            'ok' => true,
            'mensaje' => 'Tipo de título actualizado correctamente',
            'filas_actualizadas' => $filas,
        ]);
    }
}