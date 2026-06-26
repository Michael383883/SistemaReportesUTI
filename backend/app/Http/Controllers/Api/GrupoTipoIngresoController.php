<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GrupoTipoIngresoController extends Controller
{
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'cambios' => 'required|array|min:1',
            'cambios.*.cod_docente' => 'required|numeric',
            'cambios.*.cod_plan' => 'required|string|max:10',
            'cambios.*.cod_materia' => 'required|string|max:10',
            'cambios.*.grupo' => 'nullable|string|max:5',
            'cambios.*.gestion' => 'nullable|string|max:30',
            'cambios.*.tipo_ingreso' => 'nullable|string|max:30',
        ]);

        $totalGruposActualizados = 0;
        $totalDetallesActualizados = 0;
        $filasResultado = [];

        try {
            DB::transaction(function () use ($request, &$totalGruposActualizados, &$totalDetallesActualizados, &$filasResultado) {

                foreach ($request->cambios as $item) {
                    $codDocente = $item['cod_docente'];
                    $codPlan = $item['cod_plan'];
                    $codMateria = $item['cod_materia'];
                    $grupo = $item['grupo'] ?? null;
                    $gestion = $item['gestion'] ?? null;
                    $tipoIngreso = $item['tipo_ingreso'] ?? null;

                    // ── Parsear gestión → ANIO y PERIODO ──────────────────────
                    // Formatos posibles: "2022/1"  |  "2024/4 - Invierno"
                    [$anio, $periodo] = self::parseGestion($gestion);

                    // ── UPDATE GRUPOS — solo la fila exacta ───────────────────
                    $paramsGrupos = [$tipoIngreso, $codDocente, $codPlan, $codMateria, $grupo];
                    $whereGestion = '';

                    if ($anio !== null) {
                        $whereGestion .= ' AND ANIO    = ?';
                        $paramsGrupos[] = $anio;
                    }
                    if ($periodo !== null) {
                        $whereGestion .= ' AND PERIODO = ?';
                        $paramsGrupos[] = $periodo;
                    }

                    $actualizadosGrupos = DB::update("
                        UPDATE GRUPOS
                        SET    TIPO_INGRESO = ?
                        WHERE  DOCENTE                          = ?
                          AND  [PLAN]  COLLATE Modern_Spanish_CI_AS = ? COLLATE Modern_Spanish_CI_AS
                          AND  MATERIA COLLATE Modern_Spanish_CI_AS = ? COLLATE Modern_Spanish_CI_AS
                          AND  [GRUPO] COLLATE Modern_Spanish_CI_AS = ? COLLATE Modern_Spanish_CI_AS
                          {$whereGestion}
                    ", $paramsGrupos);

                    $totalGruposActualizados += $actualizadosGrupos;

                    // ── UPDATE RESOLUCION_DETALLE si existe ───────────────────
                    $actualizadosDetalle = DB::update("
                        UPDATE RESOLUCION_DETALLE
                        SET    TIPO_INGRESO = ?
                        WHERE  COD_DOCENTE                          = ?
                          AND  COD_PLAN    COLLATE Modern_Spanish_CI_AS = ? COLLATE Modern_Spanish_CI_AS
                          AND  COD_MATERIA COLLATE Modern_Spanish_CI_AS = ? COLLATE Modern_Spanish_CI_AS
                          AND  [GRUPO]     COLLATE Modern_Spanish_CI_AS = ? COLLATE Modern_Spanish_CI_AS
                    ", [$tipoIngreso, $codDocente, $codPlan, $codMateria, $grupo]);

                    $totalDetallesActualizados += $actualizadosDetalle;

                    $filasResultado[] = [
                        'cod_docente' => $codDocente,
                        'cod_plan' => $codPlan,
                        'cod_materia' => $codMateria,
                        'grupo' => $grupo,
                        'gestion' => $gestion,
                        'tipo_ingreso' => $tipoIngreso,
                        'grupos_actualizados' => $actualizadosGrupos,
                        'detalles_actualizados' => $actualizadosDetalle,
                    ];
                }
            });

            return response()->json([
                'ok' => true,
                'total_grupos_actualizados' => $totalGruposActualizados,
                'total_detalles_actualizados' => $totalDetallesActualizados,
                'filas' => $filasResultado,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Parsea el campo gestión del reporte y devuelve [anio, periodo].
     *
     * Ejemplos:
     *   "2022/1"          → [2022, 1]
     *   "2024/4 - Invierno" → [2024, 4]
     *   null / ""         → [null, null]
     */
    private static function parseGestion(?string $gestion): array
    {
        if (!$gestion)
            return [null, null];

        $parts = explode('/', trim($gestion), 2);

        $anio = isset($parts[0]) ? (int) trim($parts[0]) : null;
        // (int)"4 - Invierno" → 4  en PHP
        $periodo = isset($parts[1]) ? (int) trim($parts[1]) : null;

        return [$anio ?: null, $periodo ?: null];
    }
}