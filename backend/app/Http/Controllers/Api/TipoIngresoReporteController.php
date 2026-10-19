<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Reporte "Tipo de ingreso".
 *
 * Docente "activo" = al menos un grupo TIPO='N' en ese ANIO + PERIODO.
 * Una fila por docente + plan + materia + grupo (GRUPOS puede tener varias
 * filas por grupo, por eso se agrupa).
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
    private const COLL = 'COLLATE Modern_Spanish_CI_AS';

    /**
     * GET /api/reportes/tipo-ingreso/categorias
     * Categorías distintas de CLASIFICACION_DOCUMENTO + ACÉFALA siempre.
     */
    public function categorias()
    {
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
    }

    /**
     * GET /api/reportes/tipo-ingreso?anio=2026&periodo=1&categorias[]=ACÉFALA&categorias[]=__SIN__
     *
     * categorias vacío     => todas
     * incluye __SIN__      => también grupos sin tipo de ingreso
     */
    public function index(Request $request)
    {
        $request->validate([
            'anio' => 'required|integer',
            'periodo' => 'required|string|max:30',
            'categorias' => 'nullable|array',
            'categorias.*' => 'nullable|string|max:60',
        ]);

        $anio = (int) $request->query('anio');
        $periodo = (string) $request->query('periodo');

        $cats = collect($request->query('categorias', []))
            ->map(fn($x) => trim((string) $x))
            ->filter()
            ->unique()
            ->values();

        $c = self::COLL;
        $tabla = self::MATERIAS_TABLA;
        $mPlan = self::MATERIAS_COL_PLAN;
        $mCod = self::MATERIAS_COL_CODIGO;
        $mNom = self::MATERIAS_COL_NOMBRE;

        $params = [$anio, $periodo];
        $filtroCategoria = '';

        if ($cats->isNotEmpty()) {
            $incluirSin = $cats->contains(self::SIN_CATEGORIA);
            $reales = $cats->reject(fn($x) => $x === self::SIN_CATEGORIA)->values();

            $partes = [];
            if ($reales->isNotEmpty()) {
                $ph = $reales->map(fn() => "UPPER(?) {$c}")->implode(', ');
                $partes[] = "UPPER(LTRIM(RTRIM(g.TIPO_INGRESO))) {$c} IN ({$ph})";
                foreach ($reales as $x) {
                    $params[] = $x;
                }
            }
            if ($incluirSin) {
                $partes[] = "(g.TIPO_INGRESO IS NULL OR LTRIM(RTRIM(g.TIPO_INGRESO)) = '')";
            }
            $filtroCategoria = 'AND (' . implode(' OR ', $partes) . ')';
        }

        $sql = "
            SELECT
                g.DOCENTE                                   AS cod_docente,
                d.APELLIDOS                                 AS apellidos,
                d.NOMBRES                                   AS nombres,
                g.[PLAN]                                    AS [plan],
                g.MATERIA                                   AS materia_codigo,
                MAX(COALESCE(m.[{$mNom}], g.MATERIA))       AS materia,
                g.[GRUPO]                                   AS grp,
                g.TIPO_INGRESO                              AS categoria,
                MAX(CAST(g.RESOLUCION  AS NVARCHAR(500)))   AS resolucion,
                MAX(CAST(g.DESIGNACION AS NVARCHAR(500)))   AS detalle,
                MAX(doc.ID_DOCUMENTO)                       AS id,
                MAX(CASE WHEN doc.RUTA_ARCHIVO IS NULL OR doc.RUTA_ARCHIVO = ''
                         THEN 0 ELSE 1 END)                 AS tiene_archivo
            FROM GRUPOS g
            JOIN DOCENTES d
                ON d.CODIGO = g.DOCENTE
            LEFT JOIN [{$tabla}] m
                ON  m.[{$mPlan}] {$c} = g.[PLAN]  {$c}
                AND m.[{$mCod}]  {$c} = g.MATERIA {$c}
            OUTER APPLY (
                SELECT TOP 1 cdoc.ID_DOCUMENTO, cdoc.RUTA_ARCHIVO
                FROM CLASIFICACION_MATERIA cm
                JOIN CLASIFICACION_DOCENTE ccd
                    ON ccd.ID_CLASIFICACION_DOCENTE = cm.ID_CLASIFICACION_DOCENTE
                JOIN CLASIFICACION_DOCUMENTO cdoc
                    ON cdoc.ID_DOCUMENTO = ccd.ID_DOCUMENTO
                WHERE cm.COD_PLAN    {$c} = g.[PLAN]  {$c}
                  AND cm.COD_MATERIA {$c} = g.MATERIA {$c}
                  AND CAST(cm.[GRUPO] AS VARCHAR(10)) {$c} = g.[GRUPO] {$c}
                  AND ccd.COD_DOCENTE = g.DOCENTE
                  AND cdoc.GESTION    {$c} = CAST(g.ANIO AS VARCHAR(10)) {$c}
                  AND cdoc.PERIODO    {$c} = g.PERIODO {$c}
                  AND UPPER(cdoc.CATEGORIA) {$c} = UPPER(g.TIPO_INGRESO) {$c}
                ORDER BY cdoc.ID_DOCUMENTO DESC
            ) doc
            WHERE g.ANIO = CAST(? AS NUMERIC(5,0))
              AND g.PERIODO {$c} = ? {$c}
              AND g.[TIPO]  {$c} = 'N'
              AND g.DOCENTE IS NOT NULL
              {$filtroCategoria}
            GROUP BY g.DOCENTE, d.APELLIDOS, d.NOMBRES, g.[PLAN], g.MATERIA,
                     g.[GRUPO], g.TIPO_INGRESO
            ORDER BY d.APELLIDOS, d.NOMBRES, g.[PLAN], g.MATERIA, g.[GRUPO]
        ";

        $filas = collect(DB::select($sql, $params))->map(function ($r) {
            $r->tiene_archivo = (bool) $r->tiene_archivo;
            return $r;
        });

        return response()->json($filas->values());
    }
}