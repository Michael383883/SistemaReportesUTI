<?php

namespace App\Http\Controllers\Api;

use App\Models\ResolucionDetalle;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResolucionDetalleController extends Controller
{
    // GET /resoluciones/listado
    // Lista las 10 resoluciones (PDF) más recientes, una fila por archivo.
    // No se une con RESOLUCION_DETALLE: esa tabla puede tener varias filas
    // por resolución (un docente/materia por fila) y eso duplicaba los
    // resultados. Aquí solo nos interesa el archivo, no a quién está
    // enlazado.
    public function listado()
    {
        $listado = DB::table('RESOLUCIONES_PDF as rp')
            ->select(
                'rp.ID_RESOLUCION',
                'rp.NRO_RESOLUCION',
                'rp.DESCRIPCION',
                'rp.ANIO',
                'rp.PERIODO',
                'rp.NOMBRE_ARCHIVO',
                'rp.FECHA_SUBIDA'
            )
            ->orderBy('rp.FECHA_SUBIDA', 'desc')
            ->limit(10)
            ->get();

        return response()->json($listado);
    }

    // GET /resoluciones/{id}/detalles
    public function index($id_resolucion)
    {
        $detalles = DB::table('RESOLUCION_DETALLE as rd')
            ->join('DOCENTES as d', 'd.CODIGO', '=', 'rd.COD_DOCENTE')
            ->where('rd.ID_RESOLUCION', $id_resolucion)
            ->select(
                'rd.ID_DETALLE',
                'rd.ID_RESOLUCION',
                'rd.COD_DOCENTE',
                // SQL Server usa + para concatenar y LTRIM+RTRIM en lugar de TRIM+||
                DB::raw("LTRIM(RTRIM(d.NOMBRES + ' ' + ISNULL(d.APELLIDOS, ''))) AS NOMBRE_COMPLETO"),
                'rd.COD_PLAN',
                'rd.COD_MATERIA',
                'rd.GRUPO',
                'rd.TIPO',
                'rd.TIPO_INGRESO',   // ← agregar esto
                'rd.OBSERVACION'
            )
            ->orderBy('d.APELLIDOS')
            ->get();

        return response()->json($detalles);
    }

    // GET /detalles/{id}
    public function show($id)
    {
        $detalle = ResolucionDetalle::with('resolucion')
            ->findOrFail($id);

        return response()->json($detalle);
    }

    // POST /resoluciones/{id}/detalles  (un solo detalle)
    public function store(Request $request, $id_resolucion)
    {
        $request->validate([
            'cod_docente' => 'required|numeric',
            'cod_plan' => 'required|string|max:10',
            'cod_materia' => 'required|string|max:10',
            'grupo' => 'nullable|string|max:5',
            'tipo' => 'nullable|string|max:2',
            'observacion' => 'nullable|string|max:200',
        ]);

        $detalle = ResolucionDetalle::create([
            'ID_RESOLUCION' => $id_resolucion,
            'COD_DOCENTE' => $request->cod_docente,
            'COD_PLAN' => $request->cod_plan,
            'COD_MATERIA' => $request->cod_materia,
            'GRUPO' => $request->grupo,
            'TIPO' => $request->tipo,
            'OBSERVACION' => $request->observacion,
        ]);

        return response()->json($detalle, 201);
    }

    // POST /resoluciones/{id}/detalles/bulk  (múltiples detalles)
    //
    // IMPORTANTE: devuelve los ID_DETALLE recién insertados (ids_detalle).
    // El frontend debe guardar esos IDs y pasarlos a aplicarEnGrupos(),
    // para que esa operación solo afecte lo que se acaba de asignar en
    // esta sesión y no vuelva a tocar/reportar detalles históricos que
    // ya existían antes para la misma resolución (una resolución puede
    // acumular detalles de varias sesiones de asignación distintas).
    public function storeBulk(Request $request, $id_resolucion)
    {
        $request->validate([
            'detalles' => 'required|array|min:1',
            'detalles.*.cod_docente' => 'required|numeric',
            'detalles.*.cod_plan' => 'required|string|max:10',
            'detalles.*.cod_materia' => 'required|string|max:10',
            'detalles.*.grupo' => 'nullable|string|max:5',
            'detalles.*.tipo' => 'nullable|string|max:2',
            'detalles.*.tipo_ingreso' => 'nullable|string|max:30',
            'detalles.*.observacion' => 'nullable|string|max:200',
        ]);

        $idsInsertados = [];

        // SQL Server no devuelve IDs en un insert masivo de forma simple
        // como Postgres/MySQL (no hay "RETURNING"), así que insertamos
        // fila por fila dentro de una transacción: es la forma confiable
        // de saber exactamente qué ID_DETALLE le tocó a cada fila nueva.
        DB::transaction(function () use ($request, $id_resolucion, &$idsInsertados) {
            foreach ($request->detalles as $item) {
                $idDetalle = DB::table('RESOLUCION_DETALLE')->insertGetId([
                    'ID_RESOLUCION' => $id_resolucion,
                    'COD_DOCENTE' => $item['cod_docente'],
                    'COD_PLAN' => $item['cod_plan'],
                    'COD_MATERIA' => $item['cod_materia'],
                    'GRUPO' => $item['grupo'] ?? null,
                    'TIPO' => $item['tipo'] ?? null,
                    'TIPO_INGRESO' => $item['tipo_ingreso'] ?? null,
                    'OBSERVACION' => $item['observacion'] ?? null,
                ], 'ID_DETALLE');

                $idsInsertados[] = $idDetalle;
            }
        });

        return response()->json([
            'message' => 'Detalles insertados correctamente',
            'total' => count($idsInsertados),
            'ids_detalle' => $idsInsertados, // ← clave: el frontend lo necesita para aplicarEnGrupos
        ], 201);
    }

    // PUT /resoluciones/{id}/aplicar
    //
    // Acepta opcionalmente "ids_detalle" en el body: si viene, el UPDATE
    // y el SELECT de verificación se limitan a esos ID_DETALLE puntuales
    // (los recién insertados en esta sesión de asignación). Si no viene
    // (compatibilidad con llamadas antiguas), cae al comportamiento
    // anterior de aplicar sobre TODOS los detalles históricos de la
    // resolución — pero ya no debería usarse así desde el frontend.
    public function aplicarEnGrupos(Request $request, $id)
    {
        $idsDetalle = $request->input('ids_detalle', []);
        $filtrarPorIds = is_array($idsDetalle) && count($idsDetalle) > 0;

        try {
            $placeholders = $filtrarPorIds
                ? implode(',', array_fill(0, count($idsDetalle), '?'))
                : null;

            $filtroIdDetalleSql = $filtrarPorIds
                ? "AND dr.ID_DETALLE IN ($placeholders)"
                : '';

            $paramsUpdate = $filtrarPorIds
                ? array_merge([$id], $idsDetalle)
                : [$id];

            $actualizados = DB::update("
                UPDATE g
                SET
                    g.RESOLUCION   = rp.NRO_RESOLUCION,
                    g.DESIGNACION  = rp.DESCRIPCION,
                    g.TIPO_INGRESO = dr.TIPO_INGRESO
                FROM GRUPOS g
                JOIN RESOLUCION_DETALLE dr
                    ON  g.DOCENTE                          = dr.COD_DOCENTE
                    AND g.[PLAN]  COLLATE Modern_Spanish_CI_AS = dr.COD_PLAN   COLLATE Modern_Spanish_CI_AS
                    AND g.MATERIA COLLATE Modern_Spanish_CI_AS = dr.COD_MATERIA COLLATE Modern_Spanish_CI_AS
                    AND g.[GRUPO] COLLATE Modern_Spanish_CI_AS = dr.[GRUPO]     COLLATE Modern_Spanish_CI_AS
                    AND g.[TIPO]  COLLATE Modern_Spanish_CI_AS = dr.[TIPO]      COLLATE Modern_Spanish_CI_AS
                JOIN RESOLUCIONES_PDF rp
                    ON rp.ID_RESOLUCION = dr.ID_RESOLUCION
                WHERE
                    rp.ID_RESOLUCION = ?
                    AND g.ANIO    = rp.ANIO
                    AND g.PERIODO COLLATE Modern_Spanish_CI_AS = CAST(rp.PERIODO AS NVARCHAR(10)) COLLATE Modern_Spanish_CI_AS
                    {$filtroIdDetalleSql}
            ", $paramsUpdate);

            $paramsSelect = $filtrarPorIds
                ? array_merge([$id], $idsDetalle)
                : [$id];

            $gruposActualizados = DB::select("
                SELECT
                    g.ANIO, g.PERIODO, g.[PLAN], g.MATERIA, g.[GRUPO],
                    g.DOCENTE, g.[TIPO], g.TIPO_INGRESO, g.RESOLUCION, g.DESIGNACION
                FROM GRUPOS g
                JOIN RESOLUCION_DETALLE dr
                    ON  g.DOCENTE                          = dr.COD_DOCENTE
                    AND g.[PLAN]  COLLATE Modern_Spanish_CI_AS = dr.COD_PLAN   COLLATE Modern_Spanish_CI_AS
                    AND g.MATERIA COLLATE Modern_Spanish_CI_AS = dr.COD_MATERIA COLLATE Modern_Spanish_CI_AS
                    AND g.[GRUPO] COLLATE Modern_Spanish_CI_AS = dr.[GRUPO]     COLLATE Modern_Spanish_CI_AS
                    AND g.[TIPO]  COLLATE Modern_Spanish_CI_AS = dr.[TIPO]      COLLATE Modern_Spanish_CI_AS
                JOIN RESOLUCIONES_PDF rp
                    ON rp.ID_RESOLUCION = dr.ID_RESOLUCION
                WHERE
                    rp.ID_RESOLUCION = ?
                    AND g.ANIO    = rp.ANIO
                    AND g.PERIODO COLLATE Modern_Spanish_CI_AS = CAST(rp.PERIODO AS NVARCHAR(10)) COLLATE Modern_Spanish_CI_AS
                    {$filtroIdDetalleSql}
            ", $paramsSelect);

            return response()->json([
                'ok' => true,
                'filas_afectadas' => $actualizados,
                'grupos' => $gruposActualizados,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}