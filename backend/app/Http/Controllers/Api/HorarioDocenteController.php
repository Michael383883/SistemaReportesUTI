<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\PeriodoAcademicoService;

class HorarioDocenteController extends Controller
{

    protected PeriodoAcademicoService $periodo;
    public function __construct()
    {
        $this->periodo = new PeriodoAcademicoService(
            null,
            'periodo_academico_semestral'
        );
    }
    public function index(Request $request)
    {
        $request->validate([
            'anio' => 'nullable|integer',
            'periodo' => 'nullable|integer',
            'docente' => 'nullable|string',
            'plan' => 'nullable|string',
            'carrera' => 'nullable|string',
        ]);

        // input() funciona tanto para query string (?anio=2024&periodo=1)
        // como para JSON body ({"anio":2024,"periodo":1})
        $anio = $request->input('anio') ?: $this->periodo->anioActual();
        $periodo = $request->input('periodo') ?: $this->periodo->periodoActual();

        // ─── SUBCONSULTA INTERNA (= TBL en el SQL original) ──────────────────
        // Hace el GROUP BY SIN el join a NroInsMatGrpNE,
        // igual que la subquery del SQL original.
        $subquery = DB::table('HORARIOS2')
            ->select([
                'HORARIOS2.ANIO',
                'HORARIOS2.PERIODO',
                'GRUPOS.PLAN',

                DB::raw("
                    CASE [GRUPOS].[PLAN]
                        WHEN '059801' THEN 'ECO'
                        WHEN '109401' THEN 'ADM'
                        WHEN '089801' THEN 'CCP'
                        WHEN '125091' THEN 'COM'
                        WHEN '126091' THEN 'FIN'
                        ELSE 'NN'
                    END AS carrera
                "),

                'MATERIAS.NIVEL',
                'HORARIOS2.DOCENTE',
                'DOCENTES.APELLIDOS',
                'DOCENTES.NOMBRES',
                'GRUPOS.MATERIA',
                'MATERIAS.NOMBRE',
                'HORARIOS2.TIPO',

                DB::raw("
                    CASE [HORARIOS2].[TIPO]
                        WHEN 'C' THEN ''
                        WHEN 'P' THEN '[AUX]'
                        ELSE 'N'
                    END AS tipo2
                "),

                'GRUPOS.GRUPO',
                'HORARIOS2.DIA',

                DB::raw("
                    CASE [HORARIOS2].[DIA]
                        WHEN 'LU' THEN 1
                        WHEN 'MA' THEN 2
                        WHEN 'MI' THEN 3
                        WHEN 'JU' THEN 4
                        WHEN 'VI' THEN 5
                        WHEN 'SA' THEN 6
                        ELSE 0
                    END AS orden_dia
                "),

                'HORARIOS2.HORA',

                DB::raw("
                    CASE
                        WHEN [HORARIOS2].[HORA] = 645  THEN '06:45 - 08:15'
                        WHEN [HORARIOS2].[HORA] = 815  THEN '08:15 - 09:45'
                        WHEN [HORARIOS2].[HORA] = 945  THEN '09:45 - 11:15'
                        WHEN [HORARIOS2].[HORA] = 1115 THEN '11:15 - 12:45'
                        WHEN [HORARIOS2].[HORA] = 1245 THEN '12:45 - 14:15'
                        WHEN [HORARIOS2].[HORA] = 1415 THEN '14:15 - 15:45'
                        WHEN [HORARIOS2].[HORA] = 1545 THEN '15:45 - 17:15'
                        WHEN [HORARIOS2].[HORA] = 1715 THEN '17:15 - 18:45'
                        WHEN [HORARIOS2].[HORA] = 1845 THEN '18:45 - 20:15'
                        WHEN [HORARIOS2].[HORA] = 2015 THEN '20:15 - 21:45'
                        ELSE '00:00 - 00:00'
                    END AS horario
                "),

                'HORARIOS2.AMBIENTE',

                DB::raw("
                    SUM(
                        CASE
                            WHEN [HORARIOS2].[HORA] > 0
                                 AND [HORARIOS2].[GRUPO] = 'NN'
                            THEN 8
                            ELSE 2
                        END
                    ) AS carga_horaria
                "),

                'GRUPOS_COMPARTIDOS.COMP',
                'GRUPOS_COMPARTIDOS.COMPARTIDO',
                'GRUPOS_COMPARTIDOS.ORDEN',
            ])

            ->join('GRUPOS', function ($join) {
                $join->on('HORARIOS2.ANIO', '=', 'GRUPOS.ANIO')
                    ->on('HORARIOS2.PERIODO', '=', 'GRUPOS.PERIODO')
                    ->on('HORARIOS2.MATERIA', '=', 'GRUPOS.MATERIA')
                    ->on('HORARIOS2.GRUPO', '=', 'GRUPOS.GRUPO')
                    ->on('HORARIOS2.DOCENTE', '=', 'GRUPOS.DOCENTE');
            })

            ->join('MATERIAS', function ($join) {
                $join->on('GRUPOS.ANIO', '=', 'MATERIAS.ANIO')
                    ->on('GRUPOS.PERIODO', '=', 'MATERIAS.PERIODO')
                    ->on('GRUPOS.PLAN', '=', 'MATERIAS.PLAN')
                    ->on('GRUPOS.MATERIA', '=', 'MATERIAS.CODIGO');
            })

            ->join('DOCENTES', function ($join) {
                $join->on('HORARIOS2.DOCENTE', '=', 'DOCENTES.CODIGO');
            })

            ->leftJoin('GRUPOS_COMPARTIDOS', function ($join) {
                $join->on('GRUPOS.PLAN', '=', 'GRUPOS_COMPARTIDOS.PLAN')
                    ->on('GRUPOS.MATERIA', '=', 'GRUPOS_COMPARTIDOS.MATERIA')
                    ->on('GRUPOS.GRUPO', '=', 'GRUPOS_COMPARTIDOS.GRUPO')
                    ->on('GRUPOS.PRIMARIO', '=', 'GRUPOS_COMPARTIDOS.PRIMARIO');
            })

            ->where('HORARIOS2.ANIO', $anio)
            ->where('HORARIOS2.PERIODO', $periodo)
            ->where('HORARIOS2.TIPO', 'C')
            ->whereIn('GRUPOS.PLAN', ['109401', '125091', '089801', '126091', '059801'])
            ->where('GRUPOS.TIPO', 'N')
            ->where('GRUPOS.PRIMARIO', 'Y')
            ->whereNotIn('HORARIOS2.HORA', [730, 900, 1030, 1200, 1330, 1500, 1630, 1800, 1930, 2100])

            ->groupBy([
                'HORARIOS2.ANIO',
                'HORARIOS2.PERIODO',
                'GRUPOS.PLAN',
                'MATERIAS.NIVEL',
                'HORARIOS2.DOCENTE',
                'DOCENTES.APELLIDOS',
                'DOCENTES.NOMBRES',
                'GRUPOS.MATERIA',
                'MATERIAS.NOMBRE',
                'HORARIOS2.TIPO',
                'GRUPOS.GRUPO',
                'HORARIOS2.DIA',
                'HORARIOS2.HORA',
                'HORARIOS2.AMBIENTE',
                'GRUPOS_COMPARTIDOS.COMP',
                'GRUPOS_COMPARTIDOS.COMPARTIDO',
                'GRUPOS_COMPARTIDOS.ORDEN',
            ]);

        // Filtros opcionales dentro de la subconsulta
        if ($request->filled('docente')) {
            $subquery->where('HORARIOS2.DOCENTE', $request->docente);
        }

        if ($request->filled('plan')) {
            $subquery->where('GRUPOS.PLAN', $request->plan);
        }

        // ─── CONSULTA EXTERNA ────────────────────────────────────────────────
        // LEFT JOIN con NroInsMatGrpNE FUERA del GROUP BY,
        // igual que el SQL original usa "FROM (...) AS TBL LEFT JOIN NroInsMatGrpNE"
        $horarios = DB::table(DB::raw("({$subquery->toSql()}) AS TBL"))
            ->mergeBindings($subquery)
            ->select([
                'TBL.*',
                DB::raw('[NROINSMATGRPNE].[TOTAL NORMAL] AS total_normal'),
            ])
            ->leftJoin('NROINSMATGRPNE', function ($join) {
                $join->on('TBL.PLAN', '=', 'NROINSMATGRPNE.PLAN')
                    ->on('TBL.DOCENTE', '=', 'NROINSMATGRPNE.CODIGO')
                    ->on('TBL.MATERIA', '=', 'NROINSMATGRPNE.MATERIA')
                    ->on('TBL.GRUPO', '=', 'NROINSMATGRPNE.GRUPO');
            })
            ->orderBy('TBL.APELLIDOS')
            ->orderBy('TBL.NOMBRES')
            ->orderBy('TBL.ORDEN')
            ->orderBy('TBL.MATERIA')
            ->orderBy('TBL.GRUPO')
            ->orderBy('TBL.PLAN')
            ->orderBy('TBL.ORDEN_DIA')
            ->orderBy('TBL.COMPARTIDO')
            ->get();

        // ─── AGRUPACIÓN PHP ───────────────────────────────────────────────────
        $horariosAgrupados = $horarios
            ->groupBy('DOCENTE')
            ->map(function ($items) {
                $docente = $items->first();

                return [
                    'docente' => $docente->DOCENTE,
                    'apellidos' => $docente->APELLIDOS,
                    'nombres' => $docente->NOMBRES,
                    'nombre_completo' => $docente->APELLIDOS . ' ' . $docente->NOMBRES,
                    'total_horarios' => $items->count(),
                    'carga_horaria_total' => $items->sum('carga_horaria'),
                    'horarios' => $items->map(fn($item) => [
                        'carrera' => $item->carrera,
                        'plan' => $item->PLAN,
                        'nivel' => $item->NIVEL,
                        'materia' => $item->MATERIA,
                        'nombre_materia' => $item->NOMBRE,
                        'tipo' => $item->TIPO . $item->tipo2,
                        'grupo' => $item->GRUPO,
                        'dia' => $item->DIA,
                        'horario' => $item->horario,
                        'ambiente' => $item->AMBIENTE,
                        'carga_horaria' => $item->carga_horaria,
                        'compartido' => $item->COMPARTIDO,
                        'total_inscritos' => $item->total_normal,
                    ]),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $horariosAgrupados,
            'total_docentes' => $horariosAgrupados->count(),
            'filtros' => [
                'anio' => $anio,
                'periodo' => $periodo,
            ],
        ]);
    }

    public function show($codigo_docente)
    {
        request()->merge(['docente' => $codigo_docente]);
        return $this->index(request());
    }
}