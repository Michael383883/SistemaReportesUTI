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
                'd.APELLIDOS',
                'd.NOMBRES',
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
                // id de un CLASIFICACION_DOCUMENTO existente cuyo archivo se reutilizará
                'id_documento_origen' => 'nullable|integer|exists:CLASIFICACION_DOCUMENTO,ID_DOCUMENTO',
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
                    DB::rollBack();
                    return response()->json(['ok' => false, 'error' => 'Archivo inválido'], 400);
                }

                $carpeta = 'clasificacion_docente/' . ($request->gestion ?: 'sin_gestion');
                $nombreLimpio = preg_replace('/[^A-Za-z0-9\-_\.]/', '-', $archivo->getClientOriginalName());
                $rutaArchivo = $archivo->storeAs($carpeta, $nombreLimpio, 'public');
                $nombreArchivo = $archivo->getClientOriginalName();
                $fotocopia = true;

            } elseif ($request->filled('id_documento_origen')) {
                // No se sube archivo nuevo; se reutiliza la ruta del PDF ya
                // almacenado en otro documento.
                $docOrigen = DB::table('CLASIFICACION_DOCUMENTO')
                    ->select('RUTA_ARCHIVO', 'NOMBRE_ARCHIVO', 'FOTOCOPIA_TITULAR')
                    ->where('ID_DOCUMENTO', $request->id_documento_origen)
                    ->first();

                if (!$docOrigen || !$docOrigen->RUTA_ARCHIVO) {
                    throw new \Exception('El documento origen no tiene un archivo PDF asociado para reutilizar.');
                }

                $rutaArchivo = $docOrigen->RUTA_ARCHIVO;
                $nombreArchivo = $docOrigen->NOMBRE_ARCHIVO;
                $fotocopia = (bool) $docOrigen->FOTOCOPIA_TITULAR;
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

                // Materias sin docente propio (ej. "No regenta") se cuelgan del primer docente
                if (!$idClasifDocenteMateria && !empty($mapaDocenteId)) {
                    $idClasifDocenteMateria = reset($mapaDocenteId);
                }

                DB::table('CLASIFICACION_MATERIA')->insert([
                    'ID_DOCUMENTO' => $idDocumento,
                    'ID_CLASIFICACION_DOCENTE' => $idClasifDocenteMateria,
                    'COD_MATERIA' => $m['cod_materia'] ?? null,
                    'NOMBRE_MATERIA' => $m['nombre_materia'],
                    'COD_PLAN' => $m['cod_plan'] ?? null,
                    'GRUPO' => isset($m['grupo']) && $m['grupo'] !== null ? (string) $m['grupo'] : null,
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

            Log::error('Error ClasificacionDocente', [
                'mensaje' => $mensajeSeguro,
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json(['ok' => false, 'error' => $mensajeSeguro], 500);
        }
    }

    // PUT /clasificaciones/{id}
    //
    // ── MODELO "EDICIÓN SUPREMA DEL DOCUMENTO" ──
    // El formulario junta las materias de TODOS los docentes vinculados
    // (principal + hermanos) en un solo array `materias`, que es la lista
    // completa y definitiva.
    //
    //  - AGREGAR: docente nuevo en `materias` => se crea su vínculo.
    //  - ACTUALIZAR: solo se reemplazan las materias de los docentes que
    //    aparecen en el payload (más el principal).
    //  - QUITAR: un hermano que TENÍA materias y ya no aparece se desvincula,
    //    previa confirmación explícita (`confirmar_desvinculacion=1`); sin ella
    //    se responde 409 y no se cambia nada.
    //  - Un hermano que solo tiene TÍTULO (no editable desde este form) nunca
    //    se desvincula por esta vía.
    //  - Las materias que el usuario quitó se limpian también en GRUPOS.
    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        $rutaViejaABorrar = null;

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
                'confirmar_desvinculacion' => 'nullable|boolean',
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

            // ── Detectar hermanos que se quedan sin materias ──
            $hermanosADesvincular = collect();

            if (!$desvincular) {
                $docentesActuales = DB::table('CLASIFICACION_DOCENTE')
                    ->where('ID_DOCUMENTO', $idDocumentoOriginal)
                    ->get();

                $codigosEnPayload = collect($materias)
                    ->pluck('docente.cod_docente')
                    ->filter()
                    ->push($ccd->COD_DOCENTE) // el principal nunca se desvincula por esta vía
                    ->when($titulo && !empty($titulo['cod_docente']), fn($c) => $c->push($titulo['cod_docente']))
                    ->unique()
                    ->map(fn($c) => (string) $c);

                $hermanosADesvincular = $docentesActuales
                    ->filter(function ($d) use ($codigosEnPayload, $id) {
                        return (string) $d->ID_CLASIFICACION_DOCENTE !== (string) $id
                            && !$codigosEnPayload->contains((string) $d->COD_DOCENTE);
                    })
                    // FIX: si el hermano NO tenía materias (solo título, que no se
                    // edita desde este form) no se toca: no se "quitó" nada.
                    ->filter(function ($d) {
                        return DB::table('CLASIFICACION_MATERIA')
                            ->where('ID_CLASIFICACION_DOCENTE', $d->ID_CLASIFICACION_DOCENTE)
                            ->exists();
                    })
                    ->values();

                if ($hermanosADesvincular->isNotEmpty() && !$request->boolean('confirmar_desvinculacion')) {
                    DB::rollBack();

                    $nombresADesvincular = DB::table('DOCENTES')
                        ->whereIn('CODIGO', $hermanosADesvincular->pluck('COD_DOCENTE'))
                        ->get()
                        ->keyBy('CODIGO');

                    $idsConTitulo = DB::table('CLASIFICACION_TITULO')
                        ->whereIn('ID_CLASIFICACION_DOCENTE', $hermanosADesvincular->pluck('ID_CLASIFICACION_DOCENTE'))
                        ->pluck('ID_CLASIFICACION_DOCENTE')
                        ->map(fn($v) => (string) $v)
                        ->all();

                    return response()->json([
                        'ok' => false,
                        'tipo' => 'confirmar_desvinculacion',
                        'mensaje' => 'Al guardar, se va(n) a desvincular ' . $hermanosADesvincular->count()
                            . ' docente(s) de este documento porque ya no tienen materias asignadas. Confirma para continuar.',
                        'docentes_a_desvincular' => $hermanosADesvincular->map(function ($d) use ($nombresADesvincular, $idsConTitulo) {
                            $doc = $nombresADesvincular->get($d->COD_DOCENTE);
                            return [
                                'id_clasificacion_docente' => $d->ID_CLASIFICACION_DOCENTE,
                                'cod_docente' => $d->COD_DOCENTE,
                                'nombre' => $doc ? trim("{$doc->APELLIDOS} {$doc->NOMBRES}") : null,
                                'tiene_titulo' => in_array((string) $d->ID_CLASIFICACION_DOCENTE, $idsConTitulo, true),
                            ];
                        })->values(),
                    ], 409);
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

            $docActual = DB::table('CLASIFICACION_DOCUMENTO')->where('ID_DOCUMENTO', $idDocumentoOriginal)->first();

            // ── ¿Cambió GESTION o PERIODO? Si sí, hay que limpiar GRUPOS con la
            // combinación VIEJA (para todos los docentes del documento). ──
            $gestionCambio = $docActual && (
                (string) ($docActual->GESTION ?? '') !== (string) ($request->gestion ?? '')
                || (string) ($docActual->PERIODO ?? '') !== (string) ($request->periodo ?? '')
            );

            $materiasAntiguasParaLimpiar = collect();
            if ($gestionCambio) {
                $materiasAntiguasParaLimpiar = DB::table('CLASIFICACION_MATERIA as cm')
                    ->join('CLASIFICACION_DOCENTE as ccd2', 'ccd2.ID_CLASIFICACION_DOCENTE', '=', 'cm.ID_CLASIFICACION_DOCENTE')
                    ->where('cm.ID_DOCUMENTO', $idDocumentoOriginal)
                    ->whereNotNull('cm.COD_MATERIA')
                    ->select('cm.*', 'ccd2.COD_DOCENTE as COD_DOCENTE_MATERIA')
                    ->get();
            }

            if ($request->hasFile('archivo_pdf')) {
                $archivo = $request->file('archivo_pdf');
                if (!$archivo->isValid()) {
                    DB::rollBack();
                    return response()->json(['ok' => false, 'error' => 'Archivo inválido'], 400);
                }

                // FIX: el PDF viejo NO se borra aquí (si algo falla después y se
                // hace rollback, el documento quedaría sin archivo). Se borra
                // tras el commit y solo si ningún otro documento lo comparte.
                if (!$desvincular && $docActual && $docActual->RUTA_ARCHIVO) {
                    $rutaViejaABorrar = $docActual->RUTA_ARCHIVO;
                }

                $carpeta = 'clasificacion_docente/' . ($request->gestion ?: 'sin_gestion');
                $nombreLimpio = preg_replace('/[^A-Za-z0-9\-_\.]/', '-', $archivo->getClientOriginalName());
                $rutaArchivo = $archivo->storeAs($carpeta, $nombreLimpio, 'public');

                $datosDocumento['RUTA_ARCHIVO'] = $rutaArchivo;
                $datosDocumento['NOMBRE_ARCHIVO'] = $archivo->getClientOriginalName();
                $datosDocumento['FOTOCOPIA_TITULAR'] = true;
            } elseif ($desvincular && $docActual) {
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

            // ── Ejecutar la desvinculación de hermanos confirmada ──
            if (!$desvincular && $hermanosADesvincular->isNotEmpty()) {
                foreach ($hermanosADesvincular as $h) {
                    $materiasDelHermano = DB::table('CLASIFICACION_MATERIA')
                        ->where('ID_DOCUMENTO', $idDocumentoOriginal)
                        ->where('ID_CLASIFICACION_DOCENTE', $h->ID_CLASIFICACION_DOCENTE)
                        ->whereNotNull('COD_MATERIA')
                        ->get();

                    if ($materiasDelHermano->isNotEmpty() && $docActual) {
                        $this->limpiarGruposPorMateriasAntiguas(
                            $materiasDelHermano,
                            $h->COD_DOCENTE,
                            $docActual->GESTION,
                            $docActual->PERIODO
                        );
                    }

                    DB::table('CLASIFICACION_MATERIA')
                        ->where('ID_CLASIFICACION_DOCENTE', $h->ID_CLASIFICACION_DOCENTE)
                        ->delete();

                    DB::table('CLASIFICACION_TITULO')
                        ->where('ID_CLASIFICACION_DOCENTE', $h->ID_CLASIFICACION_DOCENTE)
                        ->delete();

                    DB::table('CLASIFICACION_DOCENTE')
                        ->where('ID_CLASIFICACION_DOCENTE', $h->ID_CLASIFICACION_DOCENTE)
                        ->delete();
                }
            }

            // ── Mapa docente -> ID_CLASIFICACION_DOCENTE (agrega hermanos nuevos) ──
            $mapaDocenteId = [(string) $ccd->COD_DOCENTE => $id];

            foreach ($materias as $m) {
                $codDocenteMateria = $m['docente']['cod_docente'] ?? null;
                if (!$codDocenteMateria || isset($mapaDocenteId[(string) $codDocenteMateria])) {
                    continue;
                }

                $idExistente = DB::table('CLASIFICACION_DOCENTE')
                    ->where('ID_DOCUMENTO', $idDocumento)
                    ->where('COD_DOCENTE', $codDocenteMateria)
                    ->value('ID_CLASIFICACION_DOCENTE');

                $mapaDocenteId[(string) $codDocenteMateria] = $idExistente ?: DB::table('CLASIFICACION_DOCENTE')->insertGetId([
                    'ID_DOCUMENTO' => $idDocumento,
                    'COD_DOCENTE' => $codDocenteMateria,
                ], 'ID_CLASIFICACION_DOCENTE');
            }

            // ── Agrupar materias entrantes por docente destino ──
            $materiasPorDestino = [];
            foreach ($materias as $m) {
                $codDocenteMateria = $m['docente']['cod_docente'] ?? null;
                $esDocentePrincipal = !$codDocenteMateria || (string) $codDocenteMateria === (string) $ccd->COD_DOCENTE;
                $idClasifDestino = $esDocentePrincipal ? $id : ($mapaDocenteId[(string) $codDocenteMateria] ?? null);

                if (!$idClasifDestino) {
                    continue; // seguridad: docente sin resolver
                }

                $materiasPorDestino[$idClasifDestino][] = $m;
            }

            // El principal siempre se incluye (soporta "vaciar mis materias").
            $idsAEliminar = array_unique(array_merge([$id], array_keys($materiasPorDestino)));

            // FIX: foto de las materias ANTES de borrarlas, para saber cuáles
            // quitó el usuario y limpiarlas también en GRUPOS.
            $materiasAntes = DB::table('CLASIFICACION_MATERIA as cm')
                ->join('CLASIFICACION_DOCENTE as dd', 'dd.ID_CLASIFICACION_DOCENTE', '=', 'cm.ID_CLASIFICACION_DOCENTE')
                ->where('cm.ID_DOCUMENTO', $idDocumentoOriginal)
                ->whereIn('cm.ID_CLASIFICACION_DOCENTE', $idsAEliminar)
                ->whereNotNull('cm.COD_MATERIA')
                ->select('cm.*', 'dd.COD_DOCENTE as COD_DOCENTE_MATERIA')
                ->get();

            DB::table('CLASIFICACION_MATERIA')
                ->where('ID_DOCUMENTO', $idDocumentoOriginal)
                ->whereIn('ID_CLASIFICACION_DOCENTE', $idsAEliminar)
                ->delete();

            $materiasInsertadas = 0;
            $orden = 0;

            foreach ($materiasPorDestino as $idClasifDestino => $listaMaterias) {
                foreach ($listaMaterias as $m) {
                    DB::table('CLASIFICACION_MATERIA')->insert([
                        'ID_DOCUMENTO' => $idDocumento,
                        'ID_CLASIFICACION_DOCENTE' => $idClasifDestino,
                        'COD_MATERIA' => $m['cod_materia'] ?? null,
                        'NOMBRE_MATERIA' => $m['nombre_materia'],
                        'COD_PLAN' => $m['cod_plan'] ?? null,
                        'GRUPO' => isset($m['grupo']) && $m['grupo'] !== null ? (string) $m['grupo'] : null,
                        'NOTA' => $this->notaSanitizada($m['nota'] ?? null),
                        'DETALLE' => $m['detalle'] ?? null,
                        'ORDEN' => $orden,
                    ]);
                    $materiasInsertadas++;
                    $orden++;
                }
            }

            // ── Limpieza en GRUPOS por cambio de gestión/periodo (todos los docentes) ──
            if ($gestionCambio && $materiasAntiguasParaLimpiar->isNotEmpty() && $docActual) {
                $porDocente = $materiasAntiguasParaLimpiar->groupBy('COD_DOCENTE_MATERIA');
                foreach ($porDocente as $codDocenteMateria => $materiasDeEseDocente) {
                    $this->limpiarGruposPorMateriasAntiguas(
                        $materiasDeEseDocente,
                        $codDocenteMateria,
                        $docActual->GESTION,
                        $docActual->PERIODO
                    );
                }
            }

            // ── FIX: limpieza en GRUPOS de las materias que el usuario QUITÓ ──
            // (si cambió gestión/periodo ya se limpió todo arriba).
            if (!$gestionCambio && $docActual && $materiasAntes->isNotEmpty()) {
                $clave = fn($idDest, $plan, $mat, $grupo) =>
                    $idDest . '|' . trim((string) $plan) . '|' . trim((string) $mat) . '|' . trim((string) $grupo);

                $clavesNuevas = collect($materiasPorDestino)
                    ->flatMap(function ($lista, $idDest) use ($clave) {
                        return collect($lista)->map(fn($m) => $clave(
                            $idDest,
                            $m['cod_plan'] ?? '',
                            $m['cod_materia'] ?? '',
                            $m['grupo'] ?? ''
                        ));
                    })
                    ->flip();

                $removidas = $materiasAntes->filter(function ($o) use ($clavesNuevas, $clave) {
                    return !$clavesNuevas->has(
                        $clave($o->ID_CLASIFICACION_DOCENTE, $o->COD_PLAN, $o->COD_MATERIA, $o->GRUPO)
                    );
                });

                foreach ($removidas->groupBy('COD_DOCENTE_MATERIA') as $codDoc => $ms) {
                    $this->limpiarGruposPorMateriasAntiguas(
                        $ms,
                        $codDoc,
                        $docActual->GESTION,
                        $docActual->PERIODO
                    );
                }
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

            // Referencias: son por documento (compartidas).
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

            // FIX: borrado del PDF anterior DESPUÉS del commit, y solo si
            // ningún otro documento lo usa y no es el mismo archivo recién guardado.
            if ($rutaViejaABorrar && $rutaViejaABorrar !== ($datosDocumento['RUTA_ARCHIVO'] ?? null)) {
                try {
                    $this->borrarArchivoSiHuerfano($rutaViejaABorrar, $idDocumentoOriginal);
                } catch (\Throwable $eArchivo) {
                    Log::warning('No se pudo borrar el PDF anterior', [
                        'ruta' => $rutaViejaABorrar,
                        'error' => $eArchivo->getMessage(),
                    ]);
                }
            }

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
                'hermanos_desvinculados' => $hermanosADesvincular->count(),
                // Si viene true, conviene avisar que vuelva a "Aplicar en GRUPOS"
                // para la gestión/periodo nueva.
                'gestion_o_periodo_cambio' => $gestionCambio,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['ok' => false, 'tipo' => 'validacion', 'errores' => $e->errors()], 422);
        } catch (\Throwable $e) {
            DB::rollBack();
            $mensajeSeguro = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x80-\xFF]/', '?', $e->getMessage());
            Log::error('Error al actualizar ClasificacionDocente', [
                'mensaje' => $mensajeSeguro,
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);
            return response()->json(['ok' => false, 'error' => $mensajeSeguro], 500);
        }
    }

    /**
     * Sanitiza NOTA antes de insertarla en una columna numeric.
     * null, '' o no numérico => null; cualquier otro numérico se devuelve tal cual.
     */
    private function notaSanitizada($valor)
    {
        if ($valor === null || $valor === '' || !is_numeric($valor)) {
            return null;
        }
        return $valor;
    }

    /**
     * Borra el archivo físico SOLO si ningún otro documento lo referencia
     * (el PDF puede compartirse vía id_documento_origen en store()).
     */
    private function borrarArchivoSiHuerfano($ruta, $excluirIdDocumento)
    {
        if (!$ruta) {
            return;
        }

        $enUso = DB::table('CLASIFICACION_DOCUMENTO')
            ->where('RUTA_ARCHIVO', $ruta)
            ->where('ID_DOCUMENTO', '!=', $excluirIdDocumento)
            ->exists();

        if (!$enUso && Storage::disk('public')->exists($ruta)) {
            Storage::disk('public')->delete($ruta);
        }
    }

    /**
     * Limpia (NULL) RESOLUCION/DESIGNACION/TIPO_INGRESO en GRUPOS para un
     * conjunto de materias. Se usa desde update() (cambio de gestión/periodo,
     * hermano desvinculado, materia quitada), destroy() y destroyDocente().
     *
     * No lanza excepción hacia arriba: si un UPDATE falla se loguea y se continúa.
     *
     * @param \Illuminate\Support\Collection|array $materiasAntiguas filas de CLASIFICACION_MATERIA (COD_MATERIA, COD_PLAN, GRUPO)
     */
    private function limpiarGruposPorMateriasAntiguas($materiasAntiguas, $codDocente, $gestionAntigua, $periodoAntigua)
    {
        if (!$gestionAntigua || !$codDocente) {
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
                Log::warning('No se pudo limpiar GRUPOS', [
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
    // FIX: todo en una transacción (GRUPOS + hijos + documento), hijos primero
    // (compatible con FK sin cascada), try/catch con 500 controlado, y el PDF
    // solo se borra si ningún otro documento lo comparte.
    public function destroy($id)
    {
        $doc = DB::table('CLASIFICACION_DOCUMENTO')->where('ID_DOCUMENTO', $id)->first();

        if (!$doc) {
            return response()->json(['ok' => false, 'error' => 'Documento no encontrado'], 404);
        }

        try {
            DB::transaction(function () use ($id, $doc) {
                $vinculos = DB::table('CLASIFICACION_DOCENTE')->where('ID_DOCUMENTO', $id)->get();

                foreach ($vinculos as $v) {
                    $materias = DB::table('CLASIFICACION_MATERIA')
                        ->where('ID_DOCUMENTO', $id)
                        ->where('ID_CLASIFICACION_DOCENTE', $v->ID_CLASIFICACION_DOCENTE)
                        ->whereNotNull('COD_MATERIA')
                        ->get();

                    if ($materias->isNotEmpty()) {
                        $this->limpiarGruposPorMateriasAntiguas($materias, $v->COD_DOCENTE, $doc->GESTION, $doc->PERIODO);
                    }
                }

                DB::table('CLASIFICACION_MATERIA')->where('ID_DOCUMENTO', $id)->delete();
                DB::table('CLASIFICACION_TITULO')->where('ID_DOCUMENTO', $id)->delete();
                DB::table('CLASIFICACION_REFERENCIA')->where('ID_DOCUMENTO', $id)->delete();
                DB::table('CLASIFICACION_DOCENTE')->where('ID_DOCUMENTO', $id)->delete();
                DB::table('CLASIFICACION_DOCUMENTO')->where('ID_DOCUMENTO', $id)->delete();
            });

            try {
                $this->borrarArchivoSiHuerfano($doc->RUTA_ARCHIVO, $id);
            } catch (\Throwable $eArchivo) {
                Log::warning('No se pudo borrar el archivo físico del documento', [
                    'id' => $id,
                    'ruta' => $doc->RUTA_ARCHIVO,
                    'error' => $eArchivo->getMessage(),
                ]);
            }

            return response()->json(['ok' => true, 'mensaje' => 'Documento eliminado correctamente']);

        } catch (\Throwable $e) {
            $mensajeSeguro = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x80-\xFF]/', '?', $e->getMessage());
            Log::error('Error al eliminar documento', ['id' => $id, 'mensaje' => $mensajeSeguro]);
            return response()->json(['ok' => false, 'error' => $mensajeSeguro], 500);
        }
    }

    // DELETE /clasificaciones/docente/{idClasificacionDocente}
    // FIX: limpieza de GRUPOS dentro de la transacción; si era el último
    // docente, el documento (y sus hijos) se elimina también para no dejarlo huérfano.
    public function destroyDocente($idClasificacionDocente)
    {
        $ccd = DB::table('CLASIFICACION_DOCENTE')
            ->where('ID_CLASIFICACION_DOCENTE', $idClasificacionDocente)
            ->first();

        if (!$ccd) {
            return response()->json(['ok' => false, 'error' => 'Registro de docente no encontrado'], 404);
        }

        $doc = DB::table('CLASIFICACION_DOCUMENTO')->where('ID_DOCUMENTO', $ccd->ID_DOCUMENTO)->first();
        $rutaABorrar = null;
        $documentoEliminado = false;

        try {
            DB::transaction(function () use ($idClasificacionDocente, $ccd, $doc, &$rutaABorrar, &$documentoEliminado) {
                $materias = DB::table('CLASIFICACION_MATERIA')
                    ->where('ID_CLASIFICACION_DOCENTE', $idClasificacionDocente)
                    ->whereNotNull('COD_MATERIA')
                    ->get();

                if ($doc && $materias->isNotEmpty()) {
                    $this->limpiarGruposPorMateriasAntiguas($materias, $ccd->COD_DOCENTE, $doc->GESTION, $doc->PERIODO);
                }

                DB::table('CLASIFICACION_MATERIA')->where('ID_CLASIFICACION_DOCENTE', $idClasificacionDocente)->delete();
                DB::table('CLASIFICACION_TITULO')->where('ID_CLASIFICACION_DOCENTE', $idClasificacionDocente)->delete();
                DB::table('CLASIFICACION_DOCENTE')->where('ID_CLASIFICACION_DOCENTE', $idClasificacionDocente)->delete();

                $quedan = DB::table('CLASIFICACION_DOCENTE')->where('ID_DOCUMENTO', $ccd->ID_DOCUMENTO)->exists();
                if (!$quedan) {
                    DB::table('CLASIFICACION_MATERIA')->where('ID_DOCUMENTO', $ccd->ID_DOCUMENTO)->delete();
                    DB::table('CLASIFICACION_TITULO')->where('ID_DOCUMENTO', $ccd->ID_DOCUMENTO)->delete();
                    DB::table('CLASIFICACION_REFERENCIA')->where('ID_DOCUMENTO', $ccd->ID_DOCUMENTO)->delete();
                    DB::table('CLASIFICACION_DOCUMENTO')->where('ID_DOCUMENTO', $ccd->ID_DOCUMENTO)->delete();
                    $rutaABorrar = $doc->RUTA_ARCHIVO ?? null;
                    $documentoEliminado = true;
                }
            });

            if ($documentoEliminado) {
                try {
                    $this->borrarArchivoSiHuerfano($rutaABorrar, $ccd->ID_DOCUMENTO);
                } catch (\Throwable $eArchivo) {
                    Log::warning('No se pudo borrar el archivo físico', ['error' => $eArchivo->getMessage()]);
                }
            }

            return response()->json([
                'ok' => true,
                'mensaje' => $documentoEliminado
                    ? 'Docente eliminado; el documento se eliminó porque no quedaban docentes'
                    : 'Docente eliminado de la clasificación',
                'documento_eliminado' => $documentoEliminado,
            ]);

        } catch (\Throwable $e) {
            $mensajeSeguro = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x80-\xFF]/', '?', $e->getMessage());

            Log::error('Error al eliminar docente de clasificación', [
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
            $cacheDocente = []; // cod_docente => ID_CLASIFICACION_DOCENTE

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
            'ids_materia' => $idsInsertados, // se pasa directo a aplicarEnGrupos()
        ], 201);
    }
}