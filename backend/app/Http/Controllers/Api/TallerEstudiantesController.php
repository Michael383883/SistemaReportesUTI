<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class TallerEstudiantesController extends Controller
{
    /**
     * GET /api/talleres/estudiantes
     *
     * Query params opcionales:
     *   anio     string  ej. "2026"
     *   periodo  string  ej. "1"
     *   plan     string  ej. "109401"
     *   materia  string  ej. "1301054"
     *   grupo    string  ej. "00"
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'anio' => 'nullable|string|max:4',
            'periodo' => 'nullable|string|max:1',
            'plan' => 'nullable|string|max:10',
            'materia' => 'nullable|string|max:10',
            'grupo' => 'nullable|string|max:5',
        ]);

        $anio = $request->input('anio', '2026');
        $periodo = $request->input('periodo', '1');
        $plan = $request->input('plan');
        $materia = $request->input('materia');
        $grupo = $request->input('grupo');

        $query = DB::table('grupos')
            ->join('docentes', 'docentes.codigo', '=', 'grupos.docente')
            ->join('kardex_ext', function ($join) {
                $join->on('kardex_ext.anio', '=', 'grupos.anio')
                    ->on('kardex_ext.periodo', '=', 'grupos.periodo')
                    ->on('kardex_ext.plan', '=', 'grupos.plan')
                    ->on('kardex_ext.materia', '=', 'grupos.materia')
                    ->on('kardex_ext.grupo', '=', 'grupos.grupo');
            })
            ->join('biograficos', 'biograficos.codigo', '=', 'kardex_ext.estudiante')
            ->join('materias', function ($join) {
                $join->on('materias.anio', '=', 'grupos.anio')
                    ->on('materias.periodo', '=', 'grupos.periodo')
                    ->on('materias.plan', '=', 'grupos.plan')
                    ->on('materias.codigo', '=', 'grupos.materia');
            })
            ->select([
                'grupos.anio',
                'grupos.periodo',
                'grupos.plan',
                DB::raw("docentes.codigo          AS codigo_docente"),
                DB::raw("docentes.apellidos || ' ' || docentes.nombres AS docente"),
                'materias.nivel',
                'grupos.materia',
                DB::raw("materias.nombre          AS nombre_materia"),
                'grupos.grupo',
                DB::raw("biograficos.codigo       AS codigo"),
                DB::raw("biograficos.apellidos || ' ' || biograficos.nombres AS nom_estudiante"),
            ])
            // Filtros fijos de "talleres"
            ->whereNull('kardex_ext.cancelado')
            ->where('grupos.primario', 'Y')
            ->where('grupos.tipo', 'N')
            ->whereIn('kardex_ext.tipo_examen', ['N', 'E'])
            // Filtro de materia por nombre (solo talleres)
            ->where(DB::raw('UPPER(materias.nombre)'), 'LIKE', '%TALLER%')
            // Parámetros dinámicos
            ->where('kardex_ext.anio', $anio)
            ->where('kardex_ext.periodo', $periodo);

        if ($plan) {
            $query->where('grupos.plan', $plan);
        }

        if ($materia) {
            $query->where('grupos.materia', $materia);
        }

        if ($grupo) {
            $query->where('grupos.grupo', $grupo);
        }

        $query->orderBy('grupos.materia')
            ->orderBy('grupos.grupo')
            ->orderBy(DB::raw("biograficos.apellidos || ' ' || biograficos.nombres"));

        $estudiantes = $query->get();

        return response()->json($estudiantes);
    }

    /**
     * GET /api/talleres/materias
     *
     * Devuelve las materias de tipo TALLER para un año/periodo/plan.
     * Query params: anio, periodo, plan
     */
    public function materias(Request $request): JsonResponse
    {
        $anio = $request->input('anio', '2026');
        $periodo = $request->input('periodo', '1');
        $plan = $request->input('plan');

        $query = DB::table('materias')
            ->select('materias.codigo', 'materias.nombre', 'materias.nivel', 'materias.plan')
            ->where('materias.anio', $anio)
            ->where('materias.periodo', $periodo)
            ->where(DB::raw('UPPER(materias.nombre)'), 'LIKE', '%TALLER%')
            ->orderBy('materias.nombre');

        if ($plan) {
            $query->where('materias.plan', $plan);
        }

        return response()->json($query->get());
    }

    /**
     * GET /api/talleres/grupos
     *
     * Devuelve los grupos disponibles para un plan + materia.
     * Query params: anio, periodo, plan, materia
     */
    public function grupos(Request $request): JsonResponse
    {
        $anio = $request->input('anio', '2026');
        $periodo = $request->input('periodo', '1');
        $plan = $request->input('plan');
        $materia = $request->input('materia');

        $query = DB::table('grupos')
            ->select(
                'grupos.grupo',
                'grupos.materia',
                'grupos.plan',
                DB::raw("docentes.apellidos || ' ' || docentes.nombres AS docente")
            )
            ->join('docentes', 'docentes.codigo', '=', 'grupos.docente')
            ->where('grupos.anio', $anio)
            ->where('grupos.periodo', $periodo)
            ->where('grupos.primario', 'Y')
            ->where('grupos.tipo', 'N')
            ->orderBy('grupos.grupo');

        if ($plan)
            $query->where('grupos.plan', $plan);
        if ($materia)
            $query->where('grupos.materia', $materia);

        return response()->json($query->get());
    }

    /**
     * GET /api/estudiantes/{codigo}/contacto
     */
    public function contacto(string $codigo): JsonResponse
    {
        $contacto = DB::table('biograficos')
            ->select(
                'codigo',
                DB::raw("apellidos || ' ' || nombres AS nombre"),
                'email',
                'celular'
            )
            ->where('codigo', $codigo)
            ->first();

        if (!$contacto) {
            return response()->json(['mensaje' => 'Estudiante no encontrado'], 404);
        }

        return response()->json($contacto);
    }
}