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
    public function storeBulk(Request $request, $id_resolucion)
    {
        $request->validate([
            'detalles' => 'required|array|min:1',
            'detalles.*.cod_docente' => 'required|numeric',
            'detalles.*.cod_plan' => 'required|string|max:10',
            'detalles.*.cod_materia' => 'required|string|max:10',
            'detalles.*.grupo' => 'nullable|string|max:5',
            'detalles.*.tipo' => 'nullable|string|max:2',
            'detalles.*.observacion' => 'nullable|string|max:200',
        ]);

        $data = collect($request->detalles)->map(function ($item) use ($id_resolucion) {
            return [
                'ID_RESOLUCION' => $id_resolucion,
                'COD_DOCENTE' => $item['cod_docente'],
                'COD_PLAN' => $item['cod_plan'],
                'COD_MATERIA' => $item['cod_materia'],
                'GRUPO' => $item['grupo'] ?? null,
                'TIPO' => $item['tipo'] ?? null,
                'OBSERVACION' => $item['observacion'] ?? null,
            ];
        });

        ResolucionDetalle::insert($data->toArray());

        return response()->json([
            'message' => 'Detalles insertados correctamente',
            'total' => $data->count(),
        ], 201);
    }

    // PUT /resoluciones/{id}/aplicar
    public function aplicarEnGrupos($id)
    {
        try {
            $actualizados = DB::update("
    UPDATE g
    SET
        g.RESOLUCION  = rp.NRO_RESOLUCION,
        g.DESIGNACION = rp.DESCRIPCION
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
", [$id]);

            $gruposActualizados = DB::select("
    SELECT
        g.ANIO, g.PERIODO, g.[PLAN], g.MATERIA, g.[GRUPO],
        g.DOCENTE, g.[TIPO], g.RESOLUCION, g.DESIGNACION
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
", [$id]);
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