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
 * El reporte junta DOS fuentes, para que nunca quede vacío si existe información:
 *
 *  A) GRUPOS (TIPO='N') del año + periodo  -> origen = 'GRUPO'
 *     Docente "activo" = tiene al menos un grupo TIPO='N' en ese ANIO + PERIODO.
 *     Categoría efectiva = GRUPOS.TIPO_INGRESO; si está vacío, la CATEGORIA del
 *     documento vinculado a esa materia/grupo.
 *
 *  B) Documentos (CLASIFICACION_DOCUMENTO) de esa gestión + periodo que tienen
 *     materia asignada pero NO coinciden con ningún grupo de A -> origen = 'DOCUMENTO'
 *     (así el documento se ve aunque GRUPOS no tenga el tipo de ingreso cargado).
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
    // se fuerza una collation explícita en TODAS las comparaciones y columnas del UNION.
    private const COLL_AI = 'COLLATE Modern_Spanish_CI_AI';

    /** Texto limpio: sin espacios laterales y en mayúsculas. */
    private function t(string $col, int $len = 50): string
    {
        return "UPPER(LTRIM(RTRIM(CAST({$col} AS VARCHAR({$len})))))";
    }

    /**
     * Grupo normalizado: '01', '1', ' 1 ' => '1'. Vacío/NULL => ''.
     * Si no es numérico queda como texto en mayúsculas.
     */
    private function grupo(string $col): string
    {
        $x = $this->t($col, 20);
        return "COALESCE((CASE WHEN LEN({$x}) BETWEEN 1 AND 9 AND {$x} NOT LIKE '%[^0-9]%'
                      THEN CAST(CAST({$x} AS INT) AS VARCHAR(20))
                      ELSE {$x} END), '')";
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
     * Categorías de los documentos (CLASIFICACION_DOCUMENTO) + la categoría por defecto.
     */
    public function categorias()
    {
        try {
            $docs = DB::table('CLASIFICACION_DOCUMENTO')
                ->whereNotNull('CATEGORIA')
                ->where('CATEGORIA', '<>', '')
                ->distinct()
                ->pluck('CATEGORIA');

            // Solo categorías oficiales: las de los documentos + la categoría por defecto.
            // Los valores sueltos de GRUPOS.TIPO_INGRESO NO crean categorías nuevas.
            $lista = $docs
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
     * incluye __SIN__  => también filas sin tipo de ingreso
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

        // 0) CTE cats_validas: categoría por defecto
        $params[] = self::CATEGORIA_DEFECTO;

        // 1) CTE grupos_norm: año + periodo
        $params[] = $anio;
        $params[] = mb_strtoupper($periodo);

        // 2) CTE docs: gestión + equivalencias de periodo
        $params[] = (string) $anio;
        $equiv = $this->equivalenciasPeriodo($periodo);
        $phPeriodoDoc = implode(', ', array_fill(0, count($equiv), '?'));
        foreach ($equiv as $e) {
            $params[] = $e;
        }

        // 3) Filtro final de categoría (sobre la categoría efectiva)
        $filtroCategoria = '';
        if ($cats->isNotEmpty()) {
            $incluirSin = $cats->contains(self::SIN_CATEGORIA);
            $reales = $cats->reject(fn($x) => $x === self::SIN_CATEGORIA)->values();

            $partes = [];
            if ($reales->isNotEmpty()) {
                $ph = $reales->map(fn() => "UPPER(LTRIM(RTRIM(CAST(? AS VARCHAR(60))))) {$ai}")->implode(', ');
                $partes[] = "r.categoria_efectiva {$ai} IN ({$ph})";
                foreach ($reales as $r) {
                    $params[] = $r;
                }
            }
            if ($incluirSin) {
                $partes[] = "r.categoria_efectiva = ''";
            }
            $filtroCategoria = 'WHERE (' . implode(' OR ', $partes) . ')';
        }

        // Fragmentos reutilizados
        $nombreMateria = fn(string $plan, string $mat) => "
            OUTER APPLY (
                SELECT TOP 1 m.[{$mN}] AS nombre
                FROM [{$tbl}] m
                WHERE " . $this->t("m.[{$mP}]") . " {$ai} = {$plan} {$ai}
                  AND " . $this->t("m.[{$mC}]") . " {$ai} = {$mat}  {$ai}
            ) mat";

        $sql = "
            WITH cats_validas AS (
                -- Únicas categorías oficiales: las de los documentos + la categoría por defecto
                SELECT UPPER(LTRIM(RTRIM(CAST(? AS VARCHAR(60))))) {$ai} AS cat
                UNION
                SELECT UPPER(LTRIM(RTRIM(CAST(c.CATEGORIA AS VARCHAR(60))))) {$ai}
                FROM CLASIFICACION_DOCUMENTO c
                WHERE c.CATEGORIA IS NOT NULL
                  AND LTRIM(RTRIM(c.CATEGORIA)) <> ''
            ),
            grupos_norm AS (
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
                GROUP BY x.DOCENTE, x.plan_, x.materia_, x.grupo_, x.anio_,
                         x.periodo_, x.TIPO_INGRESO
            ),
            docs AS (
                -- Documentos con materia asignada, de la gestión + periodo pedidos
                SELECT
                    ccd.COD_DOCENTE                                  AS docente,
                    " . $this->t('cm.COD_PLAN', 10) . "              AS plan_,
                    " . $this->t('cm.COD_MATERIA', 10) . "           AS materia_,
                    " . $this->grupo('cm.[GRUPO]') . "               AS grupo_norm,
                    cdoc.ID_DOCUMENTO,
                    cdoc.RUTA_ARCHIVO,
                    " . $this->t('cdoc.CATEGORIA', 50) . "           AS CATEGORIA
                FROM CLASIFICACION_MATERIA cm
                JOIN CLASIFICACION_DOCENTE ccd
                    ON ccd.ID_CLASIFICACION_DOCENTE = cm.ID_CLASIFICACION_DOCENTE
                JOIN CLASIFICACION_DOCUMENTO cdoc
                    ON cdoc.ID_DOCUMENTO = ccd.ID_DOCUMENTO
                WHERE " . $this->t('cdoc.GESTION', 10) . " {$ai} = CAST(? AS VARCHAR(10)) {$ai}
                  AND " . $this->t('cdoc.PERIODO', 30) . " {$ai} IN ({$phPeriodoDoc})
            )
            SELECT
                r.cod_docente, r.apellidos, r.nombres, r.[plan],
                r.materia_codigo, r.materia, r.grp,
                NULLIF(r.categoria_efectiva, '') AS categoria,
                r.resolucion, r.detalle, r.id, r.tiene_archivo, r.origen
            FROM (
                -- ═══ A) Grupos del periodo ═══
                SELECT
                    (LTRIM(RTRIM(CAST(b.DOCENTE AS VARCHAR(30)))) {$ai})        AS cod_docente,
                    (d.APELLIDOS {$ai})                                         AS apellidos,
                    (d.NOMBRES {$ai})                                           AS nombres,
                    (b.plan_ {$ai})                                             AS [plan],
                    (b.materia_ {$ai})                                          AS materia_codigo,
                    (COALESCE(mat.nombre {$ai}, b.materia_ {$ai}))              AS materia,
                    (b.grupo_ {$ai})                                            AS grp,
                    (UPPER(LTRIM(RTRIM(COALESCE(
                        -- tipo del grupo SOLO si es una categoría oficial; valores sueltos se ignoran
                        CASE WHEN b.TIPO_INGRESO {$ai} IN (SELECT cv.cat FROM cats_validas cv)
                             THEN b.TIPO_INGRESO {$ai} END,
                        doc.CATEGORIA {$ai},
                        ''
                    )))) {$ai})                                                 AS categoria_efectiva,
                    (b.resolucion {$ai})                                        AS resolucion,
                    (b.detalle {$ai})                                           AS detalle,
                    doc.ID_DOCUMENTO                                            AS id,
                    CASE WHEN doc.ID_DOCUMENTO IS NOT NULL
                          AND doc.RUTA_ARCHIVO IS NOT NULL
                          AND LTRIM(RTRIM(doc.RUTA_ARCHIVO)) <> ''
                         THEN 1 ELSE 0 END                                      AS tiene_archivo,
                    ('GRUPO' {$ai})                                             AS origen
                FROM base b
                JOIN DOCENTES d
                    ON d.CODIGO = b.DOCENTE
                " . $nombreMateria('UPPER(b.plan_)', 'UPPER(b.materia_)') . "

                -- Un único documento por grupo.
                -- Prioridad: con archivo > categoría coincide > grupo exacto > más reciente
                OUTER APPLY (
                    SELECT TOP 1 dd.ID_DOCUMENTO, dd.RUTA_ARCHIVO, dd.CATEGORIA
                    FROM docs dd
                    WHERE dd.docente = b.DOCENTE
                      AND dd.plan_ {$ai}    = UPPER(b.plan_)    {$ai}
                      AND dd.materia_ {$ai} = UPPER(b.materia_) {$ai}
                      AND (
                            dd.grupo_norm {$ai} = " . $this->grupo('b.grupo_') . " {$ai}
                         OR dd.grupo_norm = ''
                      )
                    ORDER BY
                        CASE WHEN dd.RUTA_ARCHIVO IS NULL
                               OR LTRIM(RTRIM(dd.RUTA_ARCHIVO)) = '' THEN 1 ELSE 0 END,
                        CASE WHEN dd.CATEGORIA {$ai} = b.TIPO_INGRESO {$ai}
                             THEN 0 ELSE 1 END,
                        CASE WHEN dd.grupo_norm = '' THEN 1 ELSE 0 END,
                        dd.ID_DOCUMENTO DESC
                ) doc

                UNION ALL

                -- ═══ B) Documentos del periodo que no coinciden con ningún grupo ═══
                SELECT
                    (LTRIM(RTRIM(CAST(dd.docente AS VARCHAR(30)))) {$ai})       AS cod_docente,
                    (d.APELLIDOS {$ai})                                         AS apellidos,
                    (d.NOMBRES {$ai})                                           AS nombres,
                    (dd.plan_ {$ai})                                            AS [plan],
                    (dd.materia_ {$ai})                                         AS materia_codigo,
                    (COALESCE(mat.nombre {$ai}, dd.materia_ {$ai}))             AS materia,
                    (dd.grupo_norm {$ai})                                       AS grp,
                    (UPPER(LTRIM(RTRIM(COALESCE(dd.CATEGORIA {$ai}, '')))) {$ai}) AS categoria_efectiva,
                    (CAST(NULL AS NVARCHAR(500)) {$ai})                         AS resolucion,
                    (CAST(NULL AS NVARCHAR(500)) {$ai})                         AS detalle,
                    dd.ID_DOCUMENTO                                             AS id,
                    CASE WHEN dd.RUTA_ARCHIVO IS NOT NULL
                          AND LTRIM(RTRIM(dd.RUTA_ARCHIVO)) <> ''
                         THEN 1 ELSE 0 END                                      AS tiene_archivo,
                    ('DOCUMENTO' {$ai})                                         AS origen
                FROM docs dd
                JOIN DOCENTES d
                    ON d.CODIGO = dd.docente
                " . $nombreMateria('dd.plan_', 'dd.materia_') . "
                WHERE NOT EXISTS (
                    SELECT 1
                    FROM base b2
                    WHERE b2.DOCENTE = dd.docente
                      AND UPPER(b2.plan_)    {$ai} = dd.plan_    {$ai}
                      AND UPPER(b2.materia_) {$ai} = dd.materia_ {$ai}
                      AND (
                            dd.grupo_norm = ''
                         OR " . $this->grupo('b2.grupo_') . " {$ai} = dd.grupo_norm {$ai}
                      )
                )
            ) r
            {$filtroCategoria}
            ORDER BY r.apellidos, r.nombres, r.[plan], r.materia_codigo, r.grp
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