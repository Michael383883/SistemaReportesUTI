<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardAdminController extends Controller
{
    public function kpis(): JsonResponse
    {
        try {

            $gestion = $this->getGestionActual();
            $anio = $gestion['anio'];
            $periodo = $gestion['periodo'];

            // =====================================================
            // RESUMEN
            // =====================================================
            $resumen = [

                'total_docentes' => DB::table('DOCENTES')->count(),

                'docentes_activos' => DB::table('HORARIOS2 as h')
                    ->join('GRUPOS as g', function ($join) {
                        $join->on('h.ANIO', '=', 'g.ANIO')
                            ->on('h.PERIODO', '=', 'g.PERIODO')
                            ->on('h.MATERIA', '=', 'g.MATERIA')
                            ->on('h.GRUPO', '=', 'g.GRUPO')
                            ->on('h.DOCENTE', '=', 'g.DOCENTE');
                    })
                    ->where('h.ANIO', $anio)
                    ->where('h.PERIODO', $periodo)
                    ->where('h.TIPO', 'C')
                    ->where('g.PRIMARIO', 'Y')
                    ->distinct()
                    ->count('h.DOCENTE'),

                'total_materias' => DB::table('MATERIAS')
                    ->where('ANIO', $anio)
                    ->where('PERIODO', $periodo)
                    ->distinct()
                    ->count('CODIGO'),

                'total_grupos' => DB::table('GRUPOS')
                    ->where('ANIO', $anio)
                    ->where('PERIODO', $periodo)
                    ->count(),

                'total_resoluciones' => DB::table('RESOLUCIONES_PDF')->count(),
            ];

            // =====================================================
            // TOP DOCENTES
            // =====================================================

            $top_docentes = DB::table('HORARIOS2 as h')
                ->join('DOCENTES as d', 'd.CODIGO', '=', 'h.DOCENTE')
                ->join('GRUPOS as g', function ($join) {
                    $join->on('h.ANIO', '=', 'g.ANIO')
                        ->on('h.PERIODO', '=', 'g.PERIODO')
                        ->on('h.MATERIA', '=', 'g.MATERIA')
                        ->on('h.GRUPO', '=', 'g.GRUPO')
                        ->on('h.DOCENTE', '=', 'g.DOCENTE');
                })
                ->where('h.ANIO', $anio)
                ->where('h.PERIODO', $periodo)
                ->where('h.TIPO', 'C')
                ->where('g.PRIMARIO', 'Y')
                ->select(
                    'd.CODIGO',
                    DB::raw("(d.APELLIDOS + ' ' + d.NOMBRES) AS NOMBRE"),
                    DB::raw('COUNT(*) AS TOTAL_HORAS')
                )
                ->groupBy(
                    'd.CODIGO',
                    'd.APELLIDOS',
                    'd.NOMBRES'
                )
                ->orderByDesc('TOTAL_HORAS')
                ->limit(10)
                ->get();

            // =====================================================
            // RESOLUCIONES RECIENTES
            // =====================================================
            $resoluciones_recientes = DB::table('RESOLUCIONES_PDF')
                ->select(
                    'ID_RESOLUCION',
                    'NRO_RESOLUCION',
                    'DESCRIPCION',
                    'NOMBRE_ARCHIVO',
                    'FECHA_SUBIDA'
                )
                ->orderByDesc('FECHA_SUBIDA')
                ->limit(8)
                ->get()
                ->map(fn($r) => [
                    'id' => $r->ID_RESOLUCION,
                    'numero' => $r->NRO_RESOLUCION,
                    'descripcion' => $r->DESCRIPCION,
                    'archivo' => $r->NOMBRE_ARCHIVO,
                    'fecha' => $r->FECHA_SUBIDA,
                    'url_pdf' => url("/api/resoluciones/{$r->ID_RESOLUCION}/pdf")
                ]);
            // =====================================================
            // DISTRIBUCION POR TIPO DE MATERIA
            // =====================================================

            $distribucion_tipo = DB::table('MATERIAS')
                ->where('ANIO', $anio)
                ->where('PERIODO', $periodo)
                ->select(
                    'TIPO',
                    DB::raw('COUNT(*) AS CANTIDAD')
                )
                ->groupBy('TIPO')
                ->get();

            // =====================================================
            // EVOLUCION INSCRITOS
            // =====================================================

            $evolucion_inscritos = DB::table('KARDEX_EXT')
                ->select(
                    'ANIO',
                    'PERIODO',
                    DB::raw('COUNT(DISTINCT ESTUDIANTE) AS TOTAL')
                )
                ->groupBy(
                    'ANIO',
                    'PERIODO'
                )
                ->orderBy('ANIO')
                ->orderBy('PERIODO')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'gestion' => [
                        'anio' => $anio,
                        'periodo' => $periodo
                    ],
                    'resumen' => $resumen,
                    'top_docentes' => $top_docentes,
                    'resoluciones_recientes' => $resoluciones_recientes,
                    'distribucion_tipo' => $distribucion_tipo,
                    'evolucion_inscritos' => $evolucion_inscritos
                ]
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos del dashboard.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function getGestionActual(): array
    {
        return [
            'anio' => 2026,
            'periodo' => 1
        ];
    }
}