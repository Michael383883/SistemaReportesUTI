<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class DashboardTalleresController extends Controller
{
    /**
     * GET /api/secretaria-talleres/dashboard/kpis
     *
     * Retorna KPIs para el dashboard de secretaría de talleres:
     *   - Estudiantes (total, por taller, por nivel, recientes)
     *   - Docentes    (total, activos, sin carga, horas promedio, recientes)
     *   - Talleres    (total activos, por plan)
     *   - Alertas
     */
    public function kpis(): JsonResponse
    {
        try {
            $gestion = $this->getGestionActual(); // ['anio' => '2026', 'periodo' => '1']

            return response()->json([
                'success' => true,
                'data' => [
                    'estudiantes'  => $this->getEstudiantesKPIs($gestion),
                    'docentes'     => $this->getDocentesKPIs($gestion),
                    'talleres'     => $this->getTalleresKPIs($gestion),
                    'alertas'      => $this->getAlertas($gestion),
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error dashboard talleres: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener KPIs: ' . $e->getMessage()
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ESTUDIANTES
    // ─────────────────────────────────────────────────────────────────────────

    private function getEstudiantesKPIs(array $gestion): array
    {
        $anio    = $gestion['anio'];
        $periodo = $gestion['periodo'];

        // Total inscritos en talleres
        $total = DB::table('kardex_ext')
            ->join('grupos', function ($j) {
                $j->on('grupos.anio',    '=', 'kardex_ext.anio')
                  ->on('grupos.periodo', '=', 'kardex_ext.periodo')
                  ->on('grupos.plan',    '=', 'kardex_ext.plan')
                  ->on('grupos.materia', '=', 'kardex_ext.materia')
                  ->on('grupos.grupo',   '=', 'kardex_ext.grupo');
            })
            ->join('materias', function ($j) {
                $j->on('materias.anio',    '=', 'grupos.anio')
                  ->on('materias.periodo', '=', 'grupos.periodo')
                  ->on('materias.plan',    '=', 'grupos.plan')
                  ->on('materias.codigo',  '=', 'grupos.materia');
            })
            ->where('kardex_ext.anio',    $anio)
            ->where('kardex_ext.periodo', $periodo)
            ->whereNull('kardex_ext.cancelado')
            ->where('grupos.primario', 'Y')
            ->where('grupos.tipo', 'N')
            ->whereIn('kardex_ext.tipo_examen', ['N', 'E'])
            ->where(DB::raw('UPPER(materias.nombre)'), 'LIKE', '%TALLER%')
            ->distinct('kardex_ext.estudiante')
            ->count('kardex_ext.estudiante');

        // Por taller (nombre materia → cantidad estudiantes)
        $porTaller = DB::table('kardex_ext')
            ->join('grupos', function ($j) {
                $j->on('grupos.anio',    '=', 'kardex_ext.anio')
                  ->on('grupos.periodo', '=', 'kardex_ext.periodo')
                  ->on('grupos.plan',    '=', 'kardex_ext.plan')
                  ->on('grupos.materia', '=', 'kardex_ext.materia')
                  ->on('grupos.grupo',   '=', 'kardex_ext.grupo');
            })
            ->join('materias', function ($j) {
                $j->on('materias.anio',    '=', 'grupos.anio')
                  ->on('materias.periodo', '=', 'grupos.periodo')
                  ->on('materias.plan',    '=', 'grupos.plan')
                  ->on('materias.codigo',  '=', 'grupos.materia');
            })
            ->where('kardex_ext.anio',    $anio)
            ->where('kardex_ext.periodo', $periodo)
            ->whereNull('kardex_ext.cancelado')
            ->where('grupos.primario', 'Y')
            ->where('grupos.tipo', 'N')
            ->whereIn('kardex_ext.tipo_examen', ['N', 'E'])
            ->where(DB::raw('UPPER(materias.nombre)'), 'LIKE', '%TALLER%')
            ->select('materias.nombre as taller', DB::raw('COUNT(DISTINCT kardex_ext.estudiante) as cantidad'))
            ->groupBy('materias.nombre')
            ->orderByDesc('cantidad')
            ->get()
            ->map(fn($r) => ['taller' => $r->taller, 'cantidad' => (int) $r->cantidad]);

        // Por nivel (nivel de la materia)
        $coloresNivel = ['#6366f1', '#8b5cf6', '#a78bfa', '#c4b5fd'];
        $porNivel = DB::table('kardex_ext')
            ->join('grupos', function ($j) {
                $j->on('grupos.anio',    '=', 'kardex_ext.anio')
                  ->on('grupos.periodo', '=', 'kardex_ext.periodo')
                  ->on('grupos.plan',    '=', 'kardex_ext.plan')
                  ->on('grupos.materia', '=', 'kardex_ext.materia')
                  ->on('grupos.grupo',   '=', 'kardex_ext.grupo');
            })
            ->join('materias', function ($j) {
                $j->on('materias.anio',    '=', 'grupos.anio')
                  ->on('materias.periodo', '=', 'grupos.periodo')
                  ->on('materias.plan',    '=', 'grupos.plan')
                  ->on('materias.codigo',  '=', 'grupos.materia');
            })
            ->where('kardex_ext.anio',    $anio)
            ->where('kardex_ext.periodo', $periodo)
            ->whereNull('kardex_ext.cancelado')
            ->where('grupos.primario', 'Y')
            ->where('grupos.tipo', 'N')
            ->whereIn('kardex_ext.tipo_examen', ['N', 'E'])
            ->where(DB::raw('UPPER(materias.nombre)'), 'LIKE', '%TALLER%')
            ->select('materias.nivel', DB::raw('COUNT(DISTINCT kardex_ext.estudiante) as cantidad'))
            ->groupBy('materias.nivel')
            ->orderBy('materias.nivel')
            ->get()
            ->values()
            ->map(function ($r, $i) use ($coloresNivel) {
                return [
                    'nivel'    => 'Nivel ' . $r->nivel,
                    'cantidad' => (int) $r->cantidad,
                    'color'    => $coloresNivel[$i % count($coloresNivel)],
                ];
            });

        // Recientes (últimos inscritos)
        $recientes = DB::table('kardex_ext')
            ->join('biograficos', 'biograficos.codigo', '=', 'kardex_ext.estudiante')
            ->join('grupos', function ($j) {
                $j->on('grupos.anio',    '=', 'kardex_ext.anio')
                  ->on('grupos.periodo', '=', 'kardex_ext.periodo')
                  ->on('grupos.plan',    '=', 'kardex_ext.plan')
                  ->on('grupos.materia', '=', 'kardex_ext.materia')
                  ->on('grupos.grupo',   '=', 'kardex_ext.grupo');
            })
            ->join('materias', function ($j) {
                $j->on('materias.anio',    '=', 'grupos.anio')
                  ->on('materias.periodo', '=', 'grupos.periodo')
                  ->on('materias.plan',    '=', 'grupos.plan')
                  ->on('materias.codigo',  '=', 'grupos.materia');
            })
            ->where('kardex_ext.anio',    $anio)
            ->where('kardex_ext.periodo', $periodo)
            ->whereNull('kardex_ext.cancelado')
            ->where('grupos.primario', 'Y')
            ->where('grupos.tipo', 'N')
            ->whereIn('kardex_ext.tipo_examen', ['N', 'E'])
            ->where(DB::raw('UPPER(materias.nombre)'), 'LIKE', '%TALLER%')
            ->select(
                'kardex_ext.estudiante as codigo',
                DB::raw("biograficos.apellidos || ' ' || biograficos.nombres AS nombre"),
                'materias.nombre as taller',
                DB::raw("'Nivel ' || materias.nivel AS nivel"),
                'kardex_ext.created_at as fecha'
            )
            ->orderByDesc('kardex_ext.created_at')
            ->limit(10)
            ->get()
            ->map(fn($r) => [
                'codigo' => $r->codigo,
                'nombre' => $r->nombre,
                'taller' => $r->taller,
                'nivel'  => $r->nivel,
                'fecha'  => $r->fecha ? substr($r->fecha, 0, 10) : date('Y-m-d'),
            ]);

        return [
            'total'     => $total,
            'inscritos' => $total,
            'porTaller' => $porTaller,
            'porNivel'  => $porNivel,
            'recientes' => $recientes,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DOCENTES
    // ─────────────────────────────────────────────────────────────────────────

    private function getDocentesKPIs(array $gestion): array
    {
        $anio    = $gestion['anio'];
        $periodo = $gestion['periodo'];

        // Base: docentes que dictan talleres en este período
        $docentes = DB::table('grupos')
            ->join('docentes', 'docentes.codigo', '=', 'grupos.docente')
            ->join('materias', function ($j) {
                $j->on('materias.anio',    '=', 'grupos.anio')
                  ->on('materias.periodo', '=', 'grupos.periodo')
                  ->on('materias.plan',    '=', 'grupos.plan')
                  ->on('materias.codigo',  '=', 'grupos.materia');
            })
            ->where('grupos.anio',    $anio)
            ->where('grupos.periodo', $periodo)
            ->where('grupos.primario', 'Y')
            ->where('grupos.tipo', 'N')
            ->where(DB::raw('UPPER(materias.nombre)'), 'LIKE', '%TALLER%')
            ->select(
                'docentes.codigo',
                DB::raw("docentes.apellidos || ' ' || docentes.nombres AS nombre"),
                'materias.nombre as taller',
                DB::raw('COALESCE(materias.horas, 0) AS horas')
            )
            ->get();

        $total         = $docentes->unique('codigo')->count();
        $activos       = $docentes->where('horas', '>', 0)->unique('codigo')->count();
        $sinCarga      = $total - $activos;
        $horasPromedio = $total > 0
            ? round($docentes->unique('codigo')->avg('horas'), 1)
            : 0;

        // Por taller (primer docente asignado)
        $porTaller = DB::table('grupos')
            ->join('docentes', 'docentes.codigo', '=', 'grupos.docente')
            ->join('materias', function ($j) {
                $j->on('materias.anio',    '=', 'grupos.anio')
                  ->on('materias.periodo', '=', 'grupos.periodo')
                  ->on('materias.plan',    '=', 'grupos.plan')
                  ->on('materias.codigo',  '=', 'grupos.materia');
            })
            ->where('grupos.anio',    $anio)
            ->where('grupos.periodo', $periodo)
            ->where('grupos.primario', 'Y')
            ->where('grupos.tipo', 'N')
            ->where(DB::raw('UPPER(materias.nombre)'), 'LIKE', '%TALLER%')
            ->select(
                'materias.nombre as taller',
                DB::raw("docentes.apellidos || ' ' || docentes.nombres AS docente"),
                DB::raw('COALESCE(materias.horas, 0) AS horas')
            )
            ->groupBy('materias.nombre', 'docentes.apellidos', 'docentes.nombres', 'materias.horas')
            ->orderBy('materias.nombre')
            ->get()
            ->map(fn($r) => [
                'taller'  => $r->taller,
                'docente' => $r->docente,
                'horas'   => (int) $r->horas,
            ]);

        // Distribución de carga horaria
        $cargaHoraria = [
            ['rango' => '0h',     'cantidad' => $sinCarga, 'color' => '#ef4444'],
            ['rango' => '1-10h',  'cantidad' => $docentes->where('horas', '>=', 1)->where('horas', '<=', 10)->unique('codigo')->count(), 'color' => '#f59e0b'],
            ['rango' => '11-20h', 'cantidad' => $docentes->where('horas', '>=', 11)->where('horas', '<=', 20)->unique('codigo')->count(), 'color' => '#10b981'],
            ['rango' => '21-30h', 'cantidad' => $docentes->where('horas', '>=', 21)->where('horas', '<=', 30)->unique('codigo')->count(), 'color' => '#0d9488'],
        ];

        // Recientes (últimos grupos creados)
        $recientes = DB::table('grupos')
            ->join('docentes', 'docentes.codigo', '=', 'grupos.docente')
            ->join('materias', function ($j) {
                $j->on('materias.anio',    '=', 'grupos.anio')
                  ->on('materias.periodo', '=', 'grupos.periodo')
                  ->on('materias.plan',    '=', 'grupos.plan')
                  ->on('materias.codigo',  '=', 'grupos.materia');
            })
            ->where('grupos.anio',    $anio)
            ->where('grupos.periodo', $periodo)
            ->where('grupos.primario', 'Y')
            ->where('grupos.tipo', 'N')
            ->where(DB::raw('UPPER(materias.nombre)'), 'LIKE', '%TALLER%')
            ->select(
                'docentes.codigo',
                DB::raw("docentes.apellidos || ' ' || docentes.nombres AS nombre"),
                DB::raw("COALESCE(docentes.titulo, '') AS grado"),
                'materias.nombre as taller',
                DB::raw('COALESCE(materias.horas, 0) AS horas'),
                'grupos.created_at as fecha'
            )
            ->orderByDesc('grupos.created_at')
            ->limit(5)
            ->get()
            ->map(fn($r) => [
                'codigo' => $r->codigo,
                'nombre' => $r->nombre,
                'grado'  => $r->grado,
                'taller' => $r->taller,
                'horas'  => (int) $r->horas,
                'fecha'  => $r->fecha ? substr($r->fecha, 0, 10) : date('Y-m-d'),
            ]);

        return [
            'total'        => $total,
            'activos'      => $activos,
            'sinCarga'     => $sinCarga,
            'horasPromedio' => $horasPromedio,
            'porTaller'    => $porTaller,
            'cargaHoraria' => $cargaHoraria,
            'recientes'    => $recientes,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TALLERES
    // ─────────────────────────────────────────────────────────────────────────

    private function getTalleresKPIs(array $gestion): array
    {
        $anio    = $gestion['anio'];
        $periodo = $gestion['periodo'];

        $total = DB::table('materias')
            ->where('anio',    $anio)
            ->where('periodo', $periodo)
            ->where(DB::raw('UPPER(nombre)'), 'LIKE', '%TALLER%')
            ->distinct('codigo')
            ->count('codigo');

        $porPlan = DB::table('materias')
            ->join('planes', function ($j) {
                $j->on('planes.codigo', '=', 'materias.plan')
                  ->on('planes.anio',   '=', 'materias.anio');
            })
            ->where('materias.anio',    $anio)
            ->where('materias.periodo', $periodo)
            ->where(DB::raw('UPPER(materias.nombre)'), 'LIKE', '%TALLER%')
            ->select('materias.plan', DB::raw('COUNT(DISTINCT materias.codigo) as cantidad'))
            ->groupBy('materias.plan')
            ->orderByDesc('cantidad')
            ->get()
            ->map(fn($r) => [
                'plan'     => $r->plan,
                'cantidad' => (int) $r->cantidad,
            ]);

        return [
            'total'   => $total,
            'activos' => $total,
            'porPlan' => $porPlan,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ALERTAS
    // ─────────────────────────────────────────────────────────────────────────

    private function getAlertas(array $gestion): array
    {
        $alertas = [];
        $id      = 1;

        // Docentes sin carga en talleres
        $docentesKPIs = $this->getDocentesKPIs($gestion);
        if ($docentesKPIs['sinCarga'] > 0) {
            $alertas[] = [
                'id'      => $id++,
                'tipo'    => 'warning',
                'mensaje' => "{$docentesKPIs['sinCarga']} docentes sin carga horaria asignada en talleres",
                'accion'  => '/secretaria-talleres/docentes?estado=sin-carga',
            ];
        }

        // Talleres sin estudiantes inscritos
        $talleresSinEst = DB::table('materias')
            ->leftJoin('kardex_ext', function ($j) use ($gestion) {
                $j->on('kardex_ext.materia', '=', 'materias.codigo')
                  ->on('kardex_ext.anio',    '=', 'materias.anio')
                  ->on('kardex_ext.periodo', '=', 'materias.periodo')
                  ->on('kardex_ext.plan',    '=', 'materias.plan')
                  ->whereNull('kardex_ext.cancelado');
            })
            ->where('materias.anio',    $gestion['anio'])
            ->where('materias.periodo', $gestion['periodo'])
            ->where(DB::raw('UPPER(materias.nombre)'), 'LIKE', '%TALLER%')
            ->select('materias.codigo', DB::raw('COUNT(kardex_ext.estudiante) as total_est'))
            ->groupBy('materias.codigo')
            ->havingRaw('COUNT(kardex_ext.estudiante) = 0')
            ->count();

        if ($talleresSinEst > 0) {
            $alertas[] = [
                'id'      => $id++,
                'tipo'    => 'warning',
                'mensaje' => "{$talleresSinEst} talleres sin estudiantes inscritos este período",
                'accion'  => '/secretaria-talleres/talleres',
            ];
        }

        // Info: período actual
        $alertas[] = [
            'id'      => $id++,
            'tipo'    => 'info',
            'mensaje' => "Período {$gestion['anio']}-{$gestion['periodo']} en curso",
            'accion'  => null,
        ];

        return $alertas;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Retorna la gestión activa (anio/periodo).
     * Ajustar si tienes una tabla de configuración.
     */
    private function getGestionActual(): array
    {
        // Ejemplo: leer de tabla configuracion o usar valor fijo
        return [
            'anio'    => '2026',
            'periodo' => '1',
        ];
    }
}