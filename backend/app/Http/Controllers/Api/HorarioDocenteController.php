<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HorarioDocenteController extends Controller
{
    /**
     * Obtiene los horarios de los docentes
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Validar parámetros opcionales
        $request->validate([
            'anio' => 'nullable|integer',
            'periodo' => 'nullable|integer',
            'docente' => 'nullable|string',
            'plan' => 'nullable|string',
            'carrera' => 'nullable|string',
        ]);

        // Construir la consulta base
        $query = DB::table('horarios2')
            ->select(
                'horarios2.anio',
                'horarios2.periodo',
                'grupos.plan',
                DB::raw("CASE grupos.plan
                    WHEN '059801' THEN 'ECO'
                    WHEN '109401' THEN 'ADM'
                    WHEN '089801' THEN 'CCP'
                    WHEN '125091' THEN 'COM'
                    WHEN '126091' THEN 'FIN'
                    ELSE 'NN'
                END AS carrera"),
                'materias.nivel',
                'horarios2.docente',
                'docentes.apellidos',
                'docentes.nombres',
                'grupos.materia',
                'materias.nombre',
                'horarios2.tipo',
                DB::raw("CASE
                    WHEN horarios2.tipo = 'C' THEN ''
                    WHEN horarios2.tipo = 'P' THEN '[AUX]'
                    ELSE 'N'
                END AS tipo2"),
                'grupos.grupo',
                'horarios2.dia',
                DB::raw("CASE horarios2.dia
                    WHEN 'LU' THEN 1
                    WHEN 'MA' THEN 2
                    WHEN 'MI' THEN 3
                    WHEN 'JU' THEN 4
                    WHEN 'VI' THEN 5
                    WHEN 'SA' THEN 6
                    ELSE 0
                END AS orden_dia"),
                'horarios2.hora',
                DB::raw("CASE
                    WHEN horarios2.hora = 645  THEN '06:45 - 08:15'
                    WHEN horarios2.hora = 815  THEN '08:15 - 09:45'
                    WHEN horarios2.hora = 945  THEN '09:45 - 11:15'
                    WHEN horarios2.hora = 1115 THEN '11:15 - 12:45'
                    WHEN horarios2.hora = 1245 THEN '12:45 - 14:15'
                    WHEN horarios2.hora = 1415 THEN '14:15 - 15:45'
                    WHEN horarios2.hora = 1545 THEN '15:45 - 17:15'
                    WHEN horarios2.hora = 1715 THEN '17:15 - 18:45'
                    WHEN horarios2.hora = 1845 THEN '18:45 - 20:15'
                    WHEN horarios2.hora = 2015 THEN '20:15 - 21:45'
                    ELSE '00:00 - 00:00'
                END AS horario"),
                'horarios2.ambiente',
                DB::raw("SUM(
                    CASE
                        WHEN horarios2.hora > 0
                             AND horarios2.grupo = 'NN'
                        THEN 8
                        ELSE 2
                    END
                ) AS carga_horaria"),
                'grupos_compartidos.comp',
                'grupos_compartidos.compartido',
                'grupos_compartidos.orden',
                // CORRECCIÓN: Usar el nombre real de la columna con espacio
                DB::raw('"nroinsmatgrpne"."total normal" AS total_normal')
            )
            ->join('grupos', function ($join) {
                $join->on('horarios2.anio', '=', DB::raw('grupos.anio::integer'))
                    ->on('horarios2.periodo', '=', DB::raw('grupos.periodo::integer'))
                    ->on('horarios2.materia', '=', 'grupos.materia')
                    ->on('horarios2.grupo', '=', 'grupos.grupo')
                    ->on('horarios2.docente', '=', 'grupos.docente');
            })
            ->join('materias', function ($join) {
                $join->on('grupos.anio', '=', 'materias.anio')
                    ->on('grupos.periodo', '=', 'materias.periodo')
                    ->on('grupos.plan', '=', 'materias.plan')
                    ->on('grupos.materia', '=', 'materias.codigo');
            })
            ->join('docentes', 'horarios2.docente', '=', 'docentes.codigo')
            ->leftJoin('grupos_compartidos', function ($join) {
                $join->on('grupos.plan', '=', 'grupos_compartidos.plan')
                    ->on('grupos.materia', '=', 'grupos_compartidos.materia')
                    ->on('grupos.grupo', '=', 'grupos_compartidos.grupo')
                    ->on('grupos.primario', '=', 'grupos_compartidos.primario');
            })
            ->leftJoin('nroinsmatgrpne', function ($join) {
                $join->on('grupos.plan', '=', 'nroinsmatgrpne.plan')
                    ->on('horarios2.docente', '=', 'nroinsmatgrpne.codigo')
                    ->on('grupos.materia', '=', 'nroinsmatgrpne.materia')
                    ->on('grupos.grupo', '=', 'nroinsmatgrpne.grupo');
            })
            ->where('horarios2.anio', $request->anio ?? 2024)
            ->where('horarios2.periodo', $request->periodo ?? 1)
            ->where('horarios2.tipo', 'C')
            ->whereIn('grupos.plan', [
                '109401',
                '125091',
                '089801',
                '126091',
                '059801'
            ])
            ->where('grupos.tipo', 'N')
            ->where('grupos.primario', 'Y')
            ->whereNotIn('horarios2.hora', [
                730,
                900,
                1030,
                1200,
                1330,
                1500,
                1630,
                1800,
                1930,
                2100
            ])
            ->groupBy(
                'horarios2.anio',
                'horarios2.periodo',
                'grupos.plan',
                'materias.nivel',
                'horarios2.docente',
                'docentes.apellidos',
                'docentes.nombres',
                'grupos.materia',
                'materias.nombre',
                'horarios2.tipo',
                'grupos.grupo',
                'horarios2.dia',
                'horarios2.hora',
                'horarios2.ambiente',
                'grupos_compartidos.comp',
                'grupos_compartidos.compartido',
                'grupos_compartidos.orden',
                // CORRECCIÓN: Usar el nombre real de la columna con espacio en GROUP BY
                DB::raw('"nroinsmatgrpne"."total normal"')
            )
            ->orderBy('docentes.apellidos')
            ->orderBy('docentes.nombres')
            ->orderBy('grupos_compartidos.orden')
            ->orderBy('grupos.materia')
            ->orderBy('grupos.grupo')
            ->orderBy('grupos.plan')
            ->orderBy('orden_dia')
            ->orderBy('grupos_compartidos.compartido');

        // Aplicar filtros adicionales si existen
        if ($request->has('docente')) {
            $query->where('horarios2.docente', $request->docente);
        }

        if ($request->has('plan')) {
            $query->where('grupos.plan', $request->plan);
        }

        // Ejecutar la consulta
        $horarios = $query->get();

        // Agrupar por docente para mejor organización
        $horariosAgrupados = $horarios->groupBy('docente')->map(function ($items) {
            $docente = $items->first();
            return [
                'docente' => $docente->docente,
                'apellidos' => $docente->apellidos,
                'nombres' => $docente->nombres,
                'nombre_completo' => $docente->apellidos . ' ' . $docente->nombres,
                'total_horarios' => $items->count(),
                'carga_horaria_total' => $items->sum('carga_horaria'),
                'horarios' => $items->map(function ($item) {
                    return [
                        'carrera' => $item->carrera,
                        'plan' => $item->plan,
                        'nivel' => $item->nivel,
                        'materia' => $item->materia,
                        'nombre_materia' => $item->nombre,
                        'tipo' => $item->tipo . $item->tipo2,
                        'grupo' => $item->grupo,
                        'dia' => $item->dia,
                        'horario' => $item->horario,
                        'ambiente' => $item->ambiente,
                        'carga_horaria' => $item->carga_horaria,
                        'compartido' => $item->compartido,
                        // CORRECCIÓN: Acceder al alias correcto
                        'total_inscritos' => $item->total_normal
                    ];
                })
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $horariosAgrupados,
            'total_docentes' => $horariosAgrupados->count(),
            'filtros' => [
                'anio' => $request->anio ?? 2024,
                'periodo' => $request->periodo ?? 1
            ]
        ]);
    }

    /**
     * Obtiene el horario de un docente específico
     * 
     * @param string $codigo_docente
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($codigo_docente)
    {
        $request = request();
        $request->merge(['docente' => $codigo_docente]);

        return $this->index($request);
    }
}