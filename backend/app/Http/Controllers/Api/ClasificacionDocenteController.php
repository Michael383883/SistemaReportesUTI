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
                    // FIX NOTA: antes era `$m['nota'] ?? null`, lo que dejaba pasar
                    // strings vacíos ('') tal cual hacia el INSERT. SQL Server
                    // truena al convertir '' a numeric. Ahora se sanitiza: vacío,
                    // null o no-numérico => null.
                    'NOTA' => $this->notaSanitizada($m['nota'] ?? null),
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

            $idDocumentoOriginal = $ccd->ID_DOCUMENTO;

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
                'solo_este_docente' => 'nullable|boolean',
            ]);

            $materias = [];
            if ($request->filled('materias')) {
                $materias = json_decode($request->materias, true);
                if (!is_array($materias))
                    throw new \Exception('materias inválidas: el JSON no es un array');
            }

            $titulo = null;
            if ($request->filled('titulo')) {
                $titulo = json_decode($request->titulo, true);
                if (!is_array($titulo))
                    throw new \Exception('titulo inválido: el JSON no es un objeto');
            }

            // ¿Hay otros docentes (hermanos) vinculados al mismo documento?
            $tieneHermanos = DB::table('CLASIFICACION_DOCENTE')
                ->where('ID_DOCUMENTO', $idDocumentoOriginal)
                ->where('ID_CLASIFICACION_DOCENTE', '!=', $id)
                ->exists();

            $desvincular = $request->boolean('solo_este_docente') && $tieneHermanos;

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

            $docActual = DB::table('CLASIFICACION_DOCUMENTO')->where('ID_DOCUMENTO', $idDocumentoOriginal)->first();

            // ── FIX: detecta si GESTION o PERIODO van a cambiar. Si cambian,
            // hay que limpiar en GRUPOS lo que estaba aplicado con la
            // combinación VIEJA antes de que se pierda la referencia (si no,
            // GRUPOS queda con resolución/designación "fantasma" de una
            // gestión/periodo que el documento ya no tiene, y el buscador
            // de materias, que sí compara CLASIFICACION_DOCUMENTO al pie de
            // la letra, deja de reconocerlas como "ya registradas"). ──
            $gestionCambio = $docActual && (
                (string) ($docActual->GESTION ?? '') !== (string) ($request->gestion ?? '')
                || (string) ($docActual->PERIODO ?? '') !== (string) ($request->periodo ?? '')
            );

            $materiasAntiguasParaLimpiar = [];
            if ($gestionCambio) {
                $materiasAntiguasParaLimpiar = DB::table('CLASIFICACION_MATERIA')
                    ->where('ID_DOCUMENTO', $idDocumentoOriginal)
                    ->where('ID_CLASIFICACION_DOCENTE', $id)
                    ->whereNotNull('COD_MATERIA')
                    ->get();
            }

            if ($request->hasFile('archivo_pdf')) {
                $archivo = $request->file('archivo_pdf');
                if (!$archivo->isValid()) {
                    DB::rollBack();
                    return response()->json(['ok' => false, 'error' => 'Archivo inválido'], 400);
                }

                // Solo borramos el archivo físico anterior si NO nos desvinculamos
                // (si nos desvinculamos, los hermanos lo siguen usando)
                if (!$desvincular && $docActual && $docActual->RUTA_ARCHIVO && Storage::disk('public')->exists($docActual->RUTA_ARCHIVO)) {
                    Storage::disk('public')->delete($docActual->RUTA_ARCHIVO);
                }

                $carpeta = 'clasificacion_docente/' . ($request->gestion ?: 'sin_gestion');
                $nombreLimpio = preg_replace('/[^A-Za-z0-9\-_\.]/', '-', $archivo->getClientOriginalName());
                $rutaArchivo = $archivo->storeAs($carpeta, $nombreLimpio, 'public');

                $datosDocumento['RUTA_ARCHIVO'] = $rutaArchivo;
                $datosDocumento['NOMBRE_ARCHIVO'] = $archivo->getClientOriginalName();
                $datosDocumento['FOTOCOPIA_TITULAR'] = true;
            } elseif ($desvincular && $docActual) {
                // Sin archivo nuevo: el documento nuevo apunta al mismo archivo físico
                $datosDocumento['RUTA_ARCHIVO'] = $docActual->RUTA_ARCHIVO;
                $datosDocumento['NOMBRE_ARCHIVO'] = $docActual->NOMBRE_ARCHIVO;
                $datosDocumento['FOTOCOPIA_TITULAR'] = $docActual->FOTOCOPIA_TITULAR;
            }

            if ($desvincular) {
                // ── Modo "solo este docente": documento nuevo e independiente ──
                $idDocumento = DB::table('CLASIFICACION_DOCUMENTO')->insertGetId(array_merge($datosDocumento, [
                    'FECHA_REGISTRO' => DB::raw('GETDATE()'),
                ]), 'ID_DOCUMENTO');

                DB::table('CLASIFICACION_DOCENTE')
                    ->where('ID_CLASIFICACION_DOCENTE', $id)
                    ->update(['ID_DOCUMENTO' => $idDocumento]);
            } else {
                $idDocumento = $idDocumentoOriginal;
                DB::table('CLASIFICACION_DOCUMENTO')
                    ->where('ID_DOCUMENTO', $idDocumento)
                    ->update($datosDocumento);
            }

            // Materias: siempre se borran/insertan filtradas por ID_CLASIFICACION_DOCENTE,
            // usando el documento (original o nuevo, según $desvincular)
            DB::table('CLASIFICACION_MATERIA')
                ->where('ID_DOCUMENTO', $idDocumentoOriginal)
                ->where('ID_CLASIFICACION_DOCENTE', $id)
                ->delete();

            // ── FIX: si cambió gestión/periodo, limpia en GRUPOS lo que había
            // quedado aplicado bajo la combinación VIEJA (docente + gestión
            // + periodo antiguos), usando las materias que capturamos arriba
            // antes de borrarlas. No revierte la transacción si falla: solo
            // se loguea, porque no es un dato crítico (se puede limpiar a
            // mano después con "Quitar de GRUPOS" desde el listado). ──
            if ($gestionCambio && !empty($materiasAntiguasParaLimpiar) && $docActual) {
                $this->limpiarGruposPorMateriasAntiguas(
                    $materiasAntiguasParaLimpiar,
                    $ccd->COD_DOCENTE,
                    $docActual->GESTION,
                    $docActual->PERIODO
                );
            }

            $materiasInsertadas = 0;
            foreach ($materias as $i => $m) {
                DB::table('CLASIFICACION_MATERIA')->insert([
                    'ID_DOCUMENTO' => $idDocumento,
                    'ID_CLASIFICACION_DOCENTE' => $id,
                    'COD_MATERIA' => $m['cod_materia'] ?? null,
                    'NOMBRE_MATERIA' => $m['nombre_materia'],
                    'COD_PLAN' => $m['cod_plan'] ?? null,
                    'GRUPO' => isset($m['grupo']) && $m['grupo'] !== null ? (string) $m['grupo'] : null,
                    // FIX NOTA: mismo motivo que en store(). Antes era
                    // `$m['nota'] ?? null`; ahora se sanitiza para nunca
                    // mandar '' (string vacío) a una columna numeric.
                    'NOTA' => $this->notaSanitizada($m['nota'] ?? null),
                    'DETALLE' => $m['detalle'] ?? null,
                    'ORDEN' => $i,
                ]);
                $materiasInsertadas++;
            }

            DB::table('CLASIFICACION_TITULO')
                ->where('ID_DOCUMENTO', $idDocumentoOriginal)
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

            // Referencias: son por documento (compartidas). Si nos desvinculamos,
            // se copian al documento nuevo sin tocar las del original (hermanos).
            $referenciasActualizadas = 0;
            if ($request->has('referencias')) {
                $referencias = json_decode($request->referencias, true) ?: [];

                if (!$desvincular) {
                    DB::table('CLASIFICACION_REFERENCIA')->where('ID_DOCUMENTO', $idDocumento)->delete();
                }
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
                'mensaje' => $desvincular
                    ? 'Clasificación actualizada correctamente (se independizó de los demás docentes del documento)'
                    : 'Clasificación actualizada correctamente',
                'id_documento' => $idDocumento,
                'id_clasificacion_docente' => (int) $id,
                'materias_actualizadas' => $materiasInsertadas,
                'titulo_actualizado' => $tituloActualizado,
                'referencias_actualizadas' => $referenciasActualizadas,
                'desvinculado' => $desvincular,
                // ── FIX: informativo para el frontend. Si esto viene true,
                // conviene avisar al usuario que vuelva a presionar
                // "Aplicar en GRUPOS" para la gestión/periodo nueva, ya que
                // la vieja se limpió pero la nueva no se aplica sola. ──
                'gestion_o_periodo_cambio' => $gestionCambio,
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

    /**
     * ── FIX NOTA ──
     * Sanitiza el valor de NOTA antes de insertarlo en una columna numeric.
     * El input del frontend es un <input type="text">, así que puede llegar
     * como: null, undefined (ausente => no llega la key), '' (string vacío
     * al borrar todo el contenido), o un string numérico como "85".
     *
     * SQL Server (columna NOTA numeric) no puede convertir '' a numeric y
     * tira: "Error al convertir el tipo de datos nvarchar a numeric."
     *
     * Reglas:
     *  - null, '' o no numérico  => null (sin calificación)
     *  - cualquier otro valor numérico (int, float, o string numérico) => se
     *    devuelve tal cual, para que el driver lo castee normalmente.
     *
     * @param mixed $valor
     * @return int|float|string|null
     */
    private function notaSanitizada($valor)
    {
        if ($valor === null || $valor === '' || !is_numeric($valor)) {
            return null;
        }
        return $valor;
    }

    /**
     * ── FIX ──
     * Limpia (pone en NULL) los campos RESOLUCION/DESIGNACION/TIPO_INGRESO en
     * GRUPOS para un conjunto de materias "antiguas" de un documento. Se usa
     * desde update() cuando la GESTION o el PERIODO de un documento cambian:
     * sin esto, GRUPOS se queda con datos aplicados bajo la gestión/periodo
     * viejos, mientras CLASIFICACION_DOCUMENTO ya tiene los nuevos — quedando
     * las dos fuentes desincronizadas (el bug que motivó este fix: el Kardex
     * en GRUPOS mostraba 2021/2 con resolución aplicada, pero el buscador de
     * materias, que lee CLASIFICACION_DOCUMENTO literal, decía PERIODO=1 y
     * no reconocía la materia como "ya registrada").
     *
     * No lanza excepción hacia arriba: si un UPDATE puntual falla, se loguea
     * como warning y se continúa. El residuo que pudiera quedar en GRUPOS es
     * recuperable a mano con "Quitar de GRUPOS" desde el listado.
     *
     * @param \Illuminate\Support\Collection|array $materiasAntiguas filas de CLASIFICACION_MATERIA (antes de borrarlas), deben incluir COD_MATERIA, COD_PLAN, GRUPO
     * @param int|null $codDocente
     * @param string|null $gestionAntigua
     * @param string|null $periodoAntigua
     */
    private function limpiarGruposPorMateriasAntiguas($materiasAntiguas, $codDocente, $gestionAntigua, $periodoAntigua)
    {
        if (!$gestionAntigua || !$codDocente) {
            // Sin gestión antigua o sin docente no hay forma segura de acotar
            // el UPDATE a las filas correctas de GRUPOS; mejor no tocar nada.
            return;
        }

        foreach ($materiasAntiguas as $m) {
            if (empty($m->COD_MATERIA) || empty($m->COD_PLAN)) {
                // Materias manuales (sin código) nunca se aplicaron a GRUPOS.
                continue;
            }

            try {
                DB::update("
                    UPDATE g
                    SET
                        g.RESOLUCION   = NULL,
                        g.DESIGNACION  = NULL,
                        g.TIPO_INGRESO = NULL
                    FROM GRUPOS g
                    WHERE
                        g.[PLAN]    COLLATE Modern_Spanish_CI_AS = ? COLLATE Modern_Spanish_CI_AS
                        AND g.MATERIA COLLATE Modern_Spanish_CI_AS = ? COLLATE Modern_Spanish_CI_AS
                        AND g.[GRUPO] COLLATE Modern_Spanish_CI_AS = ? COLLATE Modern_Spanish_CI_AS
                        AND g.DOCENTE = ?
                        AND g.ANIO    = CAST(? AS NUMERIC(5,0))
                        AND g.PERIODO COLLATE Modern_Spanish_CI_AS = ? COLLATE Modern_Spanish_CI_AS
                        AND g.[TIPO]  COLLATE Modern_Spanish_CI_AS = 'N'
                ", [
                    $m->COD_PLAN,
                    $m->COD_MATERIA,
                    (string) $m->GRUPO,
                    $codDocente,
                    $gestionAntigua,
                    $periodoAntigua,
                ]);
            } catch (\Throwable $e) {
                Log::warning('No se pudo limpiar GRUPOS por cambio de gestión/periodo', [
                    'cod_materia' => $m->COD_MATERIA,
                    'cod_plan' => $m->COD_PLAN,
                    'grupo' => $m->GRUPO,
                    'cod_docente' => $codDocente,
                    'gestion_antigua' => $gestionAntigua,
                    'periodo_antigua' => $periodoAntigua,
                    'error' => $e->getMessage(),
                ]);
            }
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


    // POST /clasificaciones/{idDocumento}/materias/bulk
// Agrega materias de otros docentes a un documento YA guardado.
// Si el docente no tiene fila en CLASIFICACION_DOCENTE para este
// documento, se crea. Si ya la tiene (porque se guardó junto con el
// formulario original o en una asignación previa), se reutiliza.
    public function agregarMateriasBulk(Request $request, $idDocumento)
    {
        $request->validate([
            'detalles' => 'required|array|min:1',
            'detalles.*.cod_docente' => 'required|numeric',
            'detalles.*.cod_plan' => 'required|string|max:10',
            'detalles.*.cod_materia' => 'required|string|max:10',
            'detalles.*.grupo' => 'nullable|string|max:5',
            'detalles.*.tipo_ingreso' => 'nullable|string|max:30',
            'detalles.*.observacion' => 'nullable|string|max:200',
        ]);

        $doc = DB::table('CLASIFICACION_DOCUMENTO')->where('ID_DOCUMENTO', $idDocumento)->first();
        if (!$doc) {
            return response()->json(['ok' => false, 'error' => 'Documento no encontrado'], 404);
        }

        $idsInsertados = [];

        DB::transaction(function () use ($request, $idDocumento, &$idsInsertados) {
            $cacheDocente = []; // cod_docente => ID_CLASIFICACION_DOCENTE (evita duplicar por fila)

            foreach ($request->detalles as $item) {
                $codDocente = $item['cod_docente'];

                if (!isset($cacheDocente[$codDocente])) {
                    $existente = DB::table('CLASIFICACION_DOCENTE')
                        ->where('ID_DOCUMENTO', $idDocumento)
                        ->where('COD_DOCENTE', $codDocente)
                        ->value('ID_CLASIFICACION_DOCENTE');

                    $cacheDocente[$codDocente] = $existente ?: DB::table('CLASIFICACION_DOCENTE')->insertGetId([
                        'ID_DOCUMENTO' => $idDocumento,
                        'COD_DOCENTE' => $codDocente,
                    ], 'ID_CLASIFICACION_DOCENTE');
                }

                $idDetalle = DB::table('CLASIFICACION_MATERIA')->insertGetId([
                    'ID_DOCUMENTO' => $idDocumento,
                    'ID_CLASIFICACION_DOCENTE' => $cacheDocente[$codDocente],
                    'COD_MATERIA' => $item['cod_materia'],
                    'NOMBRE_MATERIA' => $item['nombre_materia'] ?? $item['cod_materia'],
                    'COD_PLAN' => $item['cod_plan'],
                    'GRUPO' => $item['grupo'] ?? null,
                    'DETALLE' => $item['observacion'] ?? null,
                    'ORDEN' => 0,
                ], 'ID_DETALLE');

                $idsInsertados[] = $idDetalle;
            }
        });

        return response()->json([
            'ok' => true,
            'total' => count($idsInsertados),
            'ids_materia' => $idsInsertados, // ← se lo pasas directo a aplicarEnGrupos()
        ], 201);
    }
}