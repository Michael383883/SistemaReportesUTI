<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Reporte "Tipo de ingreso".
 *
 * Docente "activo" = al menos un grupo TIPO='N' en ese ANIO + PERIODO.
 * Una fila por docente + plan + materia + grupo + tipo de ingreso.
 *
 * Compatible con SQL Server 2008 (sin TRY_CAST, sin FORMAT, sin CONCAT).
 */
class TipoIngresoReporteController extends Controller
{
    // AJUSTAR si tu catálogo de materias se llama distinto
    private const MATERIAS_TABLA = 'MATERIAS';
    private const MATERIAS_COL_PLAN = 'PLAN';
    private const MATERIAS_COL_CODIGO = 'CODIGO';
    private const MATERIAS_COL_NOMBRE = 'NOMBRE';

    private const SIN_CATEGORIA = '__SIN__';
    private const CATEGORIA_DEFECTO = 'ACÉFALA';

    // GRUPOS.TIPO_INGRESO usa SQL_Latin1_General y el resto Modern_Spanish:
    // se fuerza una collation explícita en TODAS las comparaciones de texto.
    private const COLL_AI = 'COLLATE Modern_Spanish_CI_AI'; // sin mayúsculas ni tildes

    /** Texto limpio: sin espacios laterales y en mayúsculas. */
    private function t(string $col, int $len = 50): string
    {
        return "UPPER(LTRIM(RTRIM(CAST({$col} AS VARCHAR({$len})))))";
    }

    /**
     * Grupo normalizado: '01', '1', ' 1 ' => '1'.
     * Si no es numérico queda como texto en mayúsculas.
     * (Sin TRY_CAST: se valida con LIKE antes de castear.)
     */
    private function grupo(string $col): string
    {
        $x = $this->t($col, 20);
        return "(CASE WHEN LEN({$x}) BETWEEN 1 AND 9 AND {$x} NOT LIKE '%[^0-9]%'
                      THEN CAST(CAST({$x} AS INT) AS VARCHAR(20))
                      ELSE {$x} END)";
    }

    /** Equivalencias del periodo para comparar con CLASIFICACION_DOCUMENTO.PERIODO */
    private function equivalenciasPeriodo(string $periodo): array
    {
        $p = mb_strtoupper(trim($periodo));
        return match ($p) {
            '3', 'VERANO' => ['3', 'VERANO'],
            '4', 'INVIERNO' => ['4', 'INVIERNO'],
            default => [$p],
        };
    }

    /**
     * GET /api/reportes/tipo-ingreso/categorias
     */
    public function categorias()
    {
        try {
            $usadas = DB::table('CLASIFICACION_DOCUMENTO')
                ->whereNotNull('CATEGORIA')
                ->where('CATEGORIA', '<>', '')
                ->distinct()
                ->pluck('CATEGORIA');

            $lista = $usadas
                ->push(self::CATEGORIA_DEFECTO)
                ->map(fn($n) => trim((string) $n))
                ->filter()
                ->unique(fn($n) => mb_strtoupper($n))
                ->sort(fn($a, $b) => strcasecmp($a, $b))
                ->values();

            return response()->json($lista);
        } catch (\Throwable $e) {
            Log::error('TipoIngreso categorias: ' . $e->getMessage());
            return response()->json([self::CATEGORIA_DEFECTO]);
        }
    }

    /**
     * GET /api/reportes/tipo-ingreso?anio=2026&periodo=1&categorias[]=ACÉFALA&categorias[]=__SIN__
     *
     * categorias vacío => todas
     * incluye __SIN__  => también grupos sin tipo de ingreso
     */
    public function index(Request $request)
    {
        $request->validate([
            'anio' => 'required|integer|min:1900|max:2100',
            'periodo' => 'required|string|max:30',
            'categorias' => 'nullable',
            'categorias.*' => 'nullable|string|max:60',
        ]);

        $anio = (int) $request->input('anio');
        $periodo = trim((string) $request->input('periodo'));

        // Acepta array, string único o "A,B,C"
        $cats = collect(Arr::wrap($request->input('categorias', [])))
            ->flatMap(fn($x) => is_string($x) ? explode(',', $x) : [$x])
            ->map(fn($x) => trim((string) $x))
            ->filter()
            ->unique(fn($x) => mb_strtoupper($x))
            ->values();

        $ai = self::COLL_AI;
        $tbl = self::MATERIAS_TABLA;
        $mP = self::MATERIAS_COL_PLAN;
        $mC = self::MATERIAS_COL_CODIGO;
        $mN = self::MATERIAS_COL_NOMBRE;

        // ── Parámetros en el MISMO orden en que aparecen los ? en el SQL ──
        $params = [];

        // 1) Filtro de GRUPOS: año + periodo
        $params[] = $anio;
        $params[] = mb_strtoupper($periodo);

        // 2) Filtro de categorías
        $filtroCategoria = '';
        if ($cats->isNotEmpty()) {
            $incluirSin = $cats->contains(self::SIN_CATEGORIA);
            $reales = $cats->reject(fn($x) => $x === self::SIN_CATEGORIA)->values();

            $partes = [];
            if ($reales->isNotEmpty()) {
                $ph = $reales->map(fn() => "UPPER(LTRIM(RTRIM(CAST(? AS VARCHAR(60))))) {$ai}")->implode(', ');
                $partes[] = "x.TIPO_INGRESO {$ai} IN ({$ph})";
                foreach ($reales as $r) {
                    $params[] = $r;
                }
            }
            if ($incluirSin) {
                $partes[] = "x.TIPO_INGRESO = ''";
            }
            $filtroCategoria = 'WHERE (' . implode(' OR ', $partes) . ')';
        }

        // 3) Equivalencias de periodo para el documento
        $equiv = $this->equivalenciasPeriodo($periodo);
        $phPeriodoDoc = implode(', ', array_fill(0, count($equiv), '?'));
        foreach ($equiv as $e) {
            $params[] = $e;
        }

        $sql = "
            WITH grupos_norm AS (
                -- GRUPOS ya limpio (sin NULLs en TIPO_INGRESO)
                SELECT
                    g.DOCENTE,
                    LTRIM(RTRIM(g.[PLAN]))      AS plan_,
                    LTRIM(RTRIM(g.MATERIA))     AS materia_,
                    LTRIM(RTRIM(g.[GRUPO]))     AS grupo_,
                    CAST(g.ANIO AS INT)         AS anio_,
                    LTRIM(RTRIM(g.PERIODO))     AS periodo_,
                    UPPER(LTRIM(RTRIM(COALESCE(g.TIPO_INGRESO, '')))) AS TIPO_INGRESO,
                    CAST(g.RESOLUCION  AS NVARCHAR(500)) AS resolucion,
                    CAST(g.DESIGNACION AS NVARCHAR(500)) AS detalle
                FROM GRUPOS g
                WHERE g.ANIO = CAST(? AS NUMERIC(4,0))
                  AND UPPER(LTRIM(RTRIM(g.PERIODO))) {$ai} = ? {$ai}
                  AND UPPER(LTRIM(RTRIM(g.[TIPO]))) = 'N'
                  AND g.DOCENTE IS NOT NULL
            ),
            base AS (
                SELECT
                    x.DOCENTE, x.plan_, x.materia_, x.grupo_, x.anio_, x.periodo_,
                    x.TIPO_INGRESO,
                    MAX(x.resolucion) AS resolucion,
                    MAX(x.detalle)    AS detalle
                FROM grupos_norm x
                {$filtroCategoria}
                GROUP BY x.DOCENTE, x.plan_, x.materia_, x.grupo_, x.anio_,
                         x.periodo_, x.TIPO_INGRESO
            )
            SELECT
                b.DOCENTE                           AS cod_docente,
                d.APELLIDOS                         AS apellidos,
                d.NOMBRES                           AS nombres,
                b.plan_                             AS [plan],
                b.materia_                          AS materia_codigo,
                COALESCE(mat.nombre, b.materia_)    AS materia,
                b.grupo_                            AS grp,
                NULLIF(b.TIPO_INGRESO, '')          AS categoria,
                b.resolucion,
                b.detalle,
                doc.ID_DOCUMENTO                    AS id,
                CASE WHEN doc.ID_DOCUMENTO IS NOT NULL
                      AND doc.RUTA_ARCHIVO IS NOT NULL
                      AND LTRIM(RTRIM(doc.RUTA_ARCHIVO)) <> ''
                     THEN 1 ELSE 0 END              AS tiene_archivo
            FROM base b
            JOIN DOCENTES d
                ON d.CODIGO = b.DOCENTE

            -- Nombre de materia (TOP 1: evita duplicar filas si el catálogo repite)
            OUTER APPLY (
                SELECT TOP 1 m.[{$mN}] AS nombre
                FROM [{$tbl}] m
                WHERE " . $this->t("m.[{$mP}]") . " {$ai} = UPPER(b.plan_)     {$ai}
                  AND " . $this->t("m.[{$mC}]") . " {$ai} = UPPER(b.materia_)  {$ai}
            ) mat

            -- Un único documento por grupo.
            -- Prioridad: con archivo > categoría coincide > grupo exacto > más reciente
            OUTER APPLY (
                SELECT TOP 1 cdoc.ID_DOCUMENTO, cdoc.RUTA_ARCHIVO
                FROM CLASIFICACION_MATERIA cm
                JOIN CLASIFICACION_DOCENTE ccd
                    ON ccd.ID_CLASIFICACION_DOCENTE = cm.ID_CLASIFICACION_DOCENTE
                JOIN CLASIFICACION_DOCUMENTO cdoc
                    ON cdoc.ID_DOCUMENTO = ccd.ID_DOCUMENTO
                WHERE " . $this->t('cm.COD_PLAN', 10) . "    {$ai} = UPPER(b.plan_)    {$ai}
                  AND " . $this->t('cm.COD_MATERIA', 10) . " {$ai} = UPPER(b.materia_) {$ai}
                  AND ccd.COD_DOCENTE = b.DOCENTE
                  AND " . $this->t('cdoc.GESTION', 10) . "   {$ai} = CAST(b.anio_ AS VARCHAR(10)) {$ai}
                  AND " . $this->t('cdoc.PERIODO', 30) . "   {$ai} IN ({$phPeriodoDoc})
                  -- mismo grupo (01 = 1) o materia asignada sin grupo específico
                  AND (
                        " . $this->grupo('cm.[GRUPO]') . " {$ai} = " . $this->grupo('b.grupo_') . " {$ai}
                     OR cm.[GRUPO] IS NULL
                     OR LTRIM(RTRIM(cm.[GRUPO])) = ''
                  )
                ORDER BY
                    CASE WHEN cdoc.RUTA_ARCHIVO IS NULL
                           OR LTRIM(RTRIM(cdoc.RUTA_ARCHIVO)) = '' THEN 1 ELSE 0 END,
                    CASE WHEN " . $this->t('cdoc.CATEGORIA', 50) . " {$ai} = b.TIPO_INGRESO {$ai}
                         THEN 0 ELSE 1 END,
                    CASE WHEN cm.[GRUPO] IS NULL OR LTRIM(RTRIM(cm.[GRUPO])) = ''
                         THEN 1 ELSE 0 END,
                    cdoc.ID_DOCUMENTO DESC
            ) doc

            ORDER BY d.APELLIDOS, d.NOMBRES, b.plan_, b.materia_, b.grupo_
        ";

        try {
            $filas = collect(DB::select($sql, $params))->map(function ($r) {
                $r->tiene_archivo = (bool) $r->tiene_archivo;
                return $r;
            });

            return response()->json($filas->values());
        } catch (\Throwable $e) {
            Log::error('TipoIngreso index: ' . $e->getMessage(), [
                'anio' => $anio,
                'periodo' => $periodo,
                'categorias' => $cats->all(),
            ]);
            return response()->json([
                'message' => 'No se pudo generar el reporte de tipo de ingreso',
            ], 500);
        }
    }
}