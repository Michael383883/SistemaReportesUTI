<?php

namespace App\Http\Controllers\Api;

use App\Models\ResolucionDetalle;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class ResolucionDetalleController extends Controller
{
    // GET /resoluciones/{id}/detalles  ← con nombre del docente via JOIN
    public function index($id_resolucion)
    {
        $detalles = DB::table('resolucion_detalle as rd')
            ->join('docentes as d', 'd.codigo', '=', 'rd.cod_docente')
            ->where('rd.id_resolucion', $id_resolucion)
            ->select(
                'rd.id_detalle',
                'rd.id_resolucion',
                'rd.cod_docente',
                DB::raw("TRIM(d.nombres || ' ' || COALESCE(d.apellidos, '')) AS nombre_completo"),
                'rd.cod_plan',
                'rd.cod_materia',
                'rd.grupo',
                'rd.tipo',
                'rd.observacion'
            )
            ->orderBy('d.apellidos')
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
            'id_resolucion' => $id_resolucion,
            'cod_docente' => $request->cod_docente,
            'cod_plan' => $request->cod_plan,
            'cod_materia' => $request->cod_materia,
            'grupo' => $request->grupo,
            'tipo' => $request->tipo,
            'observacion' => $request->observacion,
        ]);

        return response()->json($detalle, 201);
    }

    // POST /resoluciones/{id}/detalles/bulk  (múltiples detalles a la vez)
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
                'id_resolucion' => $id_resolucion,
                'cod_docente' => $item['cod_docente'],
                'cod_plan' => $item['cod_plan'],
                'cod_materia' => $item['cod_materia'],
                'grupo' => $item['grupo'] ?? null,
                'tipo' => $item['tipo'] ?? null,
                'observacion' => $item['observacion'] ?? null,
            ];
        });

        ResolucionDetalle::insert($data->toArray());

        return response()->json([
            'message' => 'Detalles insertados correctamente',
            'total' => $data->count(),
        ], 201);
    }

    // En ResolucionDetalleController.php

    public function aplicarEnGrupos($id)
    {
        try {
            // Ejecutar el UPDATE con los datos de la resolución
            $actualizados = DB::update("
            UPDATE grupos g
            SET 
                resolucion  = rp.nro_resolucion,
                designacion = rp.descripcion
            FROM resolucion_detalle dr
            JOIN resoluciones_pdf rp ON rp.id_resolucion = dr.id_resolucion
            WHERE 
                rp.id_resolucion = ?
                AND g.anio      = rp.anio
                AND g.periodo   = rp.periodo::varchar
                AND g.docente   = dr.cod_docente
                AND g.plan      = dr.cod_plan
                AND g.materia   = dr.cod_materia
                AND g.grupo     = dr.grupo
                AND g.tipo      = dr.tipo
        ", [$id]);

            // Traer los grupos que fueron actualizados para mostrarlos en el front
            $gruposActualizados = DB::select("
            SELECT 
                g.anio, g.periodo, g.plan, g.materia, g.grupo,
                g.docente, g.tipo, g.resolucion, g.designacion
            FROM grupos g
            JOIN resolucion_detalle dr ON 
                g.docente   = dr.cod_docente
                AND g.plan  = dr.cod_plan
                AND g.materia = dr.cod_materia
                AND g.grupo = dr.grupo
                AND g.tipo  = dr.tipo
            JOIN resoluciones_pdf rp ON rp.id_resolucion = dr.id_resolucion
            WHERE 
                rp.id_resolucion = ?
                AND g.anio      = rp.anio
                AND g.periodo   = rp.periodo::varchar
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