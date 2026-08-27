<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DashboardAdminController extends Controller
{
    public function kpis(): JsonResponse
    {
        try {
            $gestion = self::getGestionActual();
            $anio = $gestion['anio'];
            $periodo = $gestion['periodo'];

            $cacheKey = self::cacheKey($anio, $periodo);

            $data = Cache::remember($cacheKey, now()->addHours(6), function () use ($anio, $periodo) {
                return $this->buildDashboardData($anio, $periodo);
            });

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en DashboardAdminController@kpis: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos del dashboard.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function refreshKpis(): JsonResponse
    {
        self::limpiarCacheDashboard();

        return response()->json([
            'success' => true,
            'message' => 'Cache del dashboard limpiado. Se recalculará en la próxima carga.',
        ]);
    }

    private function buildDashboardData(int $anio, int $periodo): array
    {
        // =====================================================
        // RESUMEN
        // Solo consultas simples, sin JOIN a HORARIOS2/GRUPOS.
        // =====================================================
        $resumen = [

            // Cantidad de docentes registrados
            'total_docentes' => DB::table('DOCENTES')->count(),

            // Cuántas materias hay en la gestión actual (2026/1)
            'total_materias' => DB::table('MATERIAS')
                ->where('ANIO', $anio)
                ->where('PERIODO', $periodo)
                ->distinct()
                ->count('CODIGO'),

            // Cantidad de resoluciones
            'total_resoluciones' => DB::table('RESOLUCIONES_PDF')->count(),

            // Cantidad de documentos clasificados (CV, títulos, certificados, etc.)
            'total_documentos' => DB::table('CLASIFICACION_DOCUMENTO')->count(),
        ];

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
        // DOCUMENTOS RECIENTES (clasificación)
        // =====================================================
        $documentos_recientes = DB::table('CLASIFICACION_DOCUMENTO')
            ->select(
                'ID_DOCUMENTO',
                'TIPO_DOCUMENTO',
                'CATEGORIA',
                'NIVEL',
                'GESTION',
                'NOMBRE_ARCHIVO',
                'FECHA_REGISTRO'
            )
            ->orderByDesc('FECHA_REGISTRO')
            ->limit(8)
            ->get()
            ->map(fn($d) => [
                'id' => $d->ID_DOCUMENTO,
                'tipo' => $d->TIPO_DOCUMENTO,
                'categoria' => $d->CATEGORIA,
                'nivel' => $d->NIVEL,
                'gestion' => $d->GESTION,
                'archivo' => $d->NOMBRE_ARCHIVO,
                'fecha' => $d->FECHA_REGISTRO,
            ]);

        // =====================================================
        // DISTRIBUCION POR CATEGORIA DE DOCUMENTO
        // =====================================================
        $distribucion_documentos = DB::table('CLASIFICACION_DOCUMENTO')
            ->select(
                'CATEGORIA',
                DB::raw('COUNT(*) AS CANTIDAD')
            )
            ->groupBy('CATEGORIA')
            ->get();

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

        // NOTA: se quitó "top_docentes" (JOIN HORARIOS2 + DOCENTES + GRUPOS
        // sin índice, 1.6M filas). Esa era la consulta que aparecía en el DMV
        // corriendo 141+ segundos y la causa real del timeout. Si más adelante
        // la quieren de vuelta, lo ideal es agregar el índice compuesto sobre
        // HORARIOS2(ANIO,PERIODO,MATERIA,GRUPO,DOCENTE) y GRUPOS(mismas columnas)
        // antes de reactivarla, o exponerla en un endpoint aparte con su propio
        // cache para que no bloquee el resto del dashboard si tarda.

        return [
            'gestion' => [
                'anio' => $anio,
                'periodo' => $periodo
            ],
            'resumen' => $resumen,
            'resoluciones_recientes' => $resoluciones_recientes,
            'documentos_recientes' => $documentos_recientes,
            'distribucion_tipo' => $distribucion_tipo,
            'distribucion_documentos' => $distribucion_documentos,
        ];
    }

    /**
     * Gestión académica activa. Usado por kpis(), refreshKpis() y por
     * cualquier otro controller que necesite invalidar la caché del
     * dashboard tras crear/editar/borrar datos (ej. resoluciones, documentos).
     */
    public static function getGestionActual(): array
    {
        return [
            'anio' => 2026,
            'periodo' => 1
        ];
    }

    /**
     * Clave de cache determinística para una gestión dada.
     */
    private static function cacheKey(int $anio, int $periodo): string
    {
        return "dashboard_kpis_{$anio}_{$periodo}";
    }

    /**
     * Invalida la caché del dashboard para la gestión actual.
     * Llamar esto desde cualquier controller que modifique datos que
     * afecten al dashboard (resoluciones, documentos, docentes, materias, etc.)
     * justo después de guardar en la BD. Ejemplo:
     *
     *   use App\Http\Controllers\Api\DashboardAdminController;
     *   ...
     *   DashboardAdminController::limpiarCacheDashboard();
     */
    public static function limpiarCacheDashboard(): void
    {
        $gestion = self::getGestionActual();
        Cache::forget(self::cacheKey($gestion['anio'], $gestion['periodo']));
    }
}