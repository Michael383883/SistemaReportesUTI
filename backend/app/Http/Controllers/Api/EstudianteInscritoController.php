<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EstudianteInscritoController extends Controller
{
    /**
     * Lista de estudiantes inscritos por anio/periodo, segun los planes
     * de estudio definidos (CON, ADM, COM, FIN, ECO).
     *
     * Parametros opcionales (query string):
     *   - anio      (default: 2026)
     *   - periodo   (default: 1)
     *   - plan      (filtra por un plan especifico, ej: 089801)
     *   - materia   (filtra por codigo de materia)
     *   - grupo     (filtra por grupo)
     *   - nivel     (filtra por un nivel especifico, ej: A, B, C...)
     *   - page      (default: 1) numero de pagina
     *   - per_page  (default: 100, maximo: 500) registros por pagina
     *
     * Ejemplo: GET /api/estudiantes-inscritos?anio=2026&periodo=1&nivel=A&page=1&per_page=100
     */
    public function index(Request $request)
    {
        // anio y periodo ahora son obligatorios, sin valor por defecto
        $request->validate([
            'anio' => 'required',
            'periodo' => 'required',
        ]);

        $anio = $request->query('anio');
        $periodo = $request->query('periodo');
        $plan = $request->query('plan');
        $materia = $request->query('materia');
        $grupo = $request->query('grupo');
        $nivel = $request->query('nivel');

        $page = max((int) $request->query('page', 1), 1);
        $perPage = (int) $request->query('per_page', 100);
        $perPage = $perPage > 0 ? min($perPage, 500) : 100;
        $offset = ($page - 1) * $perPage;

        $planesPermitidos = ['089801', '109401', '125091', '126091', '059801'];

        $nivelesPermitidos = [
            'A',
            'B',
            'C',
            'D',
            'E',
            'F',
            'G',
            'H',
            'I',
            'J',
            'L',
            'M',
            'N',
            'P',
            'Q',
            'R',
            'S',
            'T',
            'U',
            'V',
            'X',
        ];

        $selectData = "
        SELECT
            K.ANIO,
            K.PERIODO,
            K.[PLAN],
            M.NIVEL,
            CASE K.[PLAN]
                WHEN '089801' THEN 'CON'
                WHEN '109401' THEN 'ADM'
                WHEN '125091' THEN 'COM'
                WHEN '126091' THEN 'FIN'
                WHEN '059801' THEN 'ECO'
                ELSE K.[PLAN]
            END AS SIGLA_PLAN,
            K.MATERIA,
            M.NOMBRE AS NOMBRE_MATERIA,
            K.GRUPO,

            -- DOCENTE
            D.CODIGO AS COD_DOCENTE,
            D.APELLIDOS + ' ' + D.NOMBRES AS DOCENTE,

            -- ESTUDIANTE
            B.CODIGO AS COD_ESTUDIANTE,
            B.APELLIDOS + ' ' + B.NOMBRES AS ESTUDIANTE
    ";

        $nivelesPlaceholders = implode(', ', array_map(
            fn($idx) => ":nivel_permitido_{$idx}",
            array_keys($nivelesPermitidos)
        ));

        $fromWhere = "
        FROM KARDEX_EXT K
        INNER JOIN BIOGRAFICOS B
            ON B.CODIGO = K.ESTUDIANTE
        INNER JOIN MATERIAS M
            ON M.ANIO = K.ANIO
            AND M.PERIODO = K.PERIODO
            AND M.[PLAN] = K.[PLAN]
            AND M.CODIGO = K.MATERIA
        INNER JOIN GRUPOS G
            ON G.ANIO = K.ANIO
            AND G.PERIODO = K.PERIODO
            AND G.[PLAN] = K.[PLAN]
            AND G.MATERIA = K.MATERIA
            AND G.GRUPO = K.GRUPO
        INNER JOIN DOCENTES D
            ON D.CODIGO = G.DOCENTE
        WHERE
            K.ANIO = :anio
            AND K.PERIODO = :periodo
            AND K.CANCELADO IS NULL
            AND K.TIPO_EXAMEN IN ('N','E')
            AND G.PRIMARIO = 'Y'
            AND G.TIPO = 'N'
            AND K.[PLAN] IN ('089801','109401','125091','126091','059801')
            AND M.NIVEL IN ($nivelesPlaceholders)
    ";

        $bindings = [
            'anio' => $anio,
            'periodo' => $periodo,
        ];

        foreach ($nivelesPermitidos as $idx => $nivelPermitido) {
            $bindings["nivel_permitido_{$idx}"] = $nivelPermitido;
        }

        if ($plan) {
            if (!in_array($plan, $planesPermitidos)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El plan indicado no es valido.',
                ], 422);
            }
            $fromWhere .= " AND K.[PLAN] = :plan ";
            $bindings['plan'] = $plan;
        }

        if ($materia) {
            $fromWhere .= " AND K.MATERIA = :materia ";
            $bindings['materia'] = $materia;
        }

        if ($grupo) {
            $fromWhere .= " AND K.GRUPO = :grupo ";
            $bindings['grupo'] = $grupo;
        }

        if ($nivel) {
            if (!in_array($nivel, $nivelesPermitidos)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El nivel indicado no es valido.',
                ], 422);
            }
            $fromWhere .= " AND M.NIVEL = :nivel_filtro ";
            $bindings['nivel_filtro'] = $nivel;
        }

        $sqlCount = "SELECT COUNT(*) AS TOTAL " . $fromWhere;

        $sqlData = $selectData . $fromWhere . "
        ORDER BY
            M.NIVEL ASC,
            K.[PLAN],
            K.MATERIA,
            K.GRUPO,
            B.APELLIDOS,
            B.NOMBRES
        OFFSET :offset ROWS FETCH NEXT :per_page ROWS ONLY
            ";

        $bindingsData = $bindings + [
            'offset' => $offset,
            'per_page' => $perPage,
        ];

        try {
            $total = (int) (DB::select($sqlCount, $bindings)[0]->TOTAL ?? 0);
            $resultados = DB::select($sqlData, $bindingsData);

            // Agrupar por PLAN + MATERIA + GRUPO + NIVEL, con su docente y lista de estudiantes
            $grupos = [];

            foreach ($resultados as $fila) {
                $key = $fila->PLAN . '-' . $fila->MATERIA . '-' . $fila->GRUPO . '-' . $fila->NIVEL;

                if (!isset($grupos[$key])) {
                    $grupos[$key] = [
                        'anio' => $fila->ANIO,
                        'periodo' => $fila->PERIODO,
                        'plan' => $fila->PLAN,
                        'sigla_plan' => $fila->SIGLA_PLAN,
                        'nivel' => $fila->NIVEL,
                        'materia' => $fila->MATERIA,
                        'nombre_materia' => $fila->NOMBRE_MATERIA,
                        'grupo' => $fila->GRUPO,
                        'docente' => [
                            'cod_docente' => $fila->COD_DOCENTE,
                            'docente' => $fila->DOCENTE,
                        ],
                        'estudiantes' => [],
                    ];
                }

                $grupos[$key]['estudiantes'][] = [
                    'cod_estudiante' => $fila->COD_ESTUDIANTE,
                    'estudiante' => $fila->ESTUDIANTE,
                ];
            }

            $data = array_values($grupos);

            return response()->json([
                'success' => true,
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $perPage > 0 ? (int) ceil($total / $perPage) : 0,
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al obtener estudiantes inscritos: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ocurrio un error al consultar los estudiantes inscritos.',
            ], 500);
        }
    }

    /**
     * Resumen de inscritos/aprobados/reprobados por grupo, materia y docente.
     *
     * Parametros obligatorios (query string):
     *   - anio
     *   - periodo
     *
     * Parametros opcionales (query string):
     *   - plan      (filtra por un plan especifico, ej: 089801)
     *   - materia   (filtra por codigo de materia)
     *   - grupo     (filtra por grupo)
     *   - nivel     (filtra por un nivel especifico, ej: A, B, C...)
     *   - page      (default: 1) numero de pagina
     *   - per_page  (default: 100, maximo: 500) registros por pagina
     *
     * Ejemplo: GET /api/estudiantes-inscritos/resumen-grupo?anio=2025&periodo=2
     */
    public function resumenPorGrupo(Request $request)
    {
        $anio = $request->query('anio');
        $periodo = $request->query('periodo');
        $plan = $request->query('plan');
        $materia = $request->query('materia');
        $grupo = $request->query('grupo');
        $nivel = $request->query('nivel');

        if (!$anio || !$periodo) {
            return response()->json([
                'success' => false,
                'message' => 'Los parametros anio y periodo son obligatorios.',
            ], 422);
        }

        $page = max((int) $request->query('page', 1), 1);
        $perPage = (int) $request->query('per_page', 100);
        $perPage = $perPage > 0 ? min($perPage, 500) : 100;
        $offset = ($page - 1) * $perPage;

        $planesPermitidos = ['089801', '109401', '125091', '126091', '059801'];

        $nivelesPermitidos = [
            'A',
            'B',
            'C',
            'D',
            'E',
            'F',
            'G',
            'H',
            'I',
            'J',
            'L',
            'M',
            'N',
            'P',
            'Q',
            'R',
            'S',
            'T',
            'U',
            'V',
            'X',
        ];

        $nivelesPlaceholders = implode(', ', array_map(
            fn($idx) => ":nivel_permitido_{$idx}",
            array_keys($nivelesPermitidos)
        ));

        // CTE compartido entre el conteo total y la consulta paginada
        $baseCte = "
        WITH BASE AS (
            SELECT
                K.ANIO,
                K.PERIODO,
                K.[PLAN],
                CASE K.[PLAN]
                    WHEN '089801' THEN 'CON'
                    WHEN '109401' THEN 'ADM'
                    WHEN '125091' THEN 'COM'
                    WHEN '126091' THEN 'FIN'
                    WHEN '059801' THEN 'ECO'
                    ELSE K.[PLAN]
                END AS CARRERA,
                M.NIVEL,
                D.APELLIDOS + ' ' + D.NOMBRES AS NOMBRE_DOCENTE,
                K.MATERIA,
                M.NOMBRE,
                K.GRUPO,
                K.TIPO_EXAMEN,
                K.ESTUDIANTE,
                K.NOTA_FINAL
            FROM KARDEX_EXT K
            INNER JOIN BIOGRAFICOS B
                ON B.CODIGO = K.ESTUDIANTE
            INNER JOIN MATERIAS M
                ON M.ANIO = K.ANIO
                AND M.PERIODO = K.PERIODO
                AND M.[PLAN] = K.[PLAN]
                AND M.CODIGO = K.MATERIA
            INNER JOIN GRUPOS G
                ON G.ANIO = K.ANIO
                AND G.PERIODO = K.PERIODO
                AND G.[PLAN] = K.[PLAN]
                AND G.MATERIA = K.MATERIA
                AND G.GRUPO = K.GRUPO
            LEFT JOIN BIOGRAFICOS D
                ON D.CODIGO = G.DOCENTE
            WHERE
                K.ANIO = :anio
                AND K.PERIODO = :periodo
                AND K.CANCELADO IS NULL
                AND K.TIPO_EXAMEN IN ('N','E')
                AND G.PRIMARIO = 'Y'
                AND G.TIPO = 'N'
                AND K.[PLAN] IN ('089801','109401','125091','126091','059801')
                AND M.NIVEL IN ($nivelesPlaceholders)
    ";

        $bindings = [
            'anio' => $anio,
            'periodo' => $periodo,
        ];

        foreach ($nivelesPermitidos as $idx => $nivelPermitido) {
            $bindings["nivel_permitido_{$idx}"] = $nivelPermitido;
        }

        if ($plan) {
            if (!in_array($plan, $planesPermitidos)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El plan indicado no es valido.',
                ], 422);
            }
            $baseCte .= " AND K.[PLAN] = :plan ";
            $bindings['plan'] = $plan;
        }

        if ($materia) {
            $baseCte .= " AND K.MATERIA = :materia ";
            $bindings['materia'] = $materia;
        }

        if ($grupo) {
            $baseCte .= " AND K.GRUPO = :grupo ";
            $bindings['grupo'] = $grupo;
        }

        if ($nivel) {
            if (!in_array($nivel, $nivelesPermitidos)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El nivel indicado no es valido.',
                ], 422);
            }
            $baseCte .= " AND M.NIVEL = :nivel_filtro ";
            $bindings['nivel_filtro'] = $nivel;
        }

        $baseCte .= " ) "; // cierra el CTE

        $groupBySelect = "
        SELECT
            ANIO,
            PERIODO,
            [PLAN],
            CARRERA,
            NIVEL,
            NOMBRE_DOCENTE,
            MATERIA,
            NOMBRE,
            GRUPO,
            TIPO_EXAMEN,
            COUNT(*) AS INSCRITOS,
            SUM(CASE WHEN NOTA_FINAL >= 51 THEN 1 ELSE 0 END) AS APROBADOS,
            SUM(CASE WHEN NOTA_FINAL < 51  THEN 1 ELSE 0 END) AS REPROBADOS
        FROM BASE
        GROUP BY
            ANIO, PERIODO, [PLAN], CARRERA, NIVEL, NOMBRE_DOCENTE,
            MATERIA, NOMBRE, GRUPO, TIPO_EXAMEN
    ";

        $sqlCount = "
        {$baseCte}
        SELECT COUNT(*) AS TOTAL FROM ({$groupBySelect}) AS RESUMEN
    ";

        $sqlData = "
        {$baseCte}
        {$groupBySelect}
        ORDER BY
            [PLAN],
            NIVEL ASC,
            MATERIA,
            GRUPO
        OFFSET :offset ROWS FETCH NEXT :per_page ROWS ONLY
    ";

        $bindingsData = $bindings + [
            'offset' => $offset,
            'per_page' => $perPage,
        ];

        try {
            $total = (int) (DB::select($sqlCount, $bindings)[0]->TOTAL ?? 0);
            $resultados = DB::select($sqlData, $bindingsData);

            return response()->json([
                'success' => true,
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $perPage > 0 ? (int) ceil($total / $perPage) : 0,
                'data' => $resultados,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al obtener resumen de inscritos por grupo: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ocurrio un error al consultar el resumen de inscritos por grupo.',
            ], 500);
        }
    }



    public function resumenAprobadosReprobados(Request $request)
    {
        $anio = $request->query('anio');
        $periodo = $request->query('periodo');

        if (!$anio || !$periodo) {
            return response()->json([
                'success' => false,
                'message' => 'Los parametros anio y periodo son obligatorios.',
            ], 422);
        }

        $nivelesPermitidos = [
            'A',
            'B',
            'C',
            'D',
            'E',
            'F',
            'G',
            'H',
            'I',
            'J',
            'L',
            'M',
            'N',
            'P',
            'Q',
            'R',
            'S',
            'T',
            'U',
            'V',
            'X',
        ];

        $nivelesPlaceholders = implode(', ', array_map(
            fn($idx) => ":nivel_permitido_{$idx}",
            array_keys($nivelesPermitidos)
        ));

        // ── BASE: igual que resumenPorGrupo, pero exponiendo cod_docente,
        //          apellidos y nombres por separado (no concatenados) ──
        $sql = "
        WITH BASE AS (
            SELECT
                CASE K.[PLAN]
                    WHEN '089801' THEN 'CON'
                    WHEN '109401' THEN 'ADM'
                    WHEN '125091' THEN 'COM'
                    WHEN '126091' THEN 'FIN'
                    WHEN '059801' THEN 'ECO'
                    ELSE K.[PLAN]
                END AS CARRERA,
                D.CODIGO    AS COD_DOCENTE,
                D.APELLIDOS AS APELLIDOS,
                D.NOMBRES   AS NOMBRES,
                K.NOTA_FINAL
            FROM KARDEX_EXT K
            INNER JOIN BIOGRAFICOS B
                ON B.CODIGO = K.ESTUDIANTE
            INNER JOIN MATERIAS M
                ON M.ANIO = K.ANIO
                AND M.PERIODO = K.PERIODO
                AND M.[PLAN] = K.[PLAN]
                AND M.CODIGO = K.MATERIA
            INNER JOIN GRUPOS G
                ON G.ANIO = K.ANIO
                AND G.PERIODO = K.PERIODO
                AND G.[PLAN] = K.[PLAN]
                AND G.MATERIA = K.MATERIA
                AND G.GRUPO = K.GRUPO
            LEFT JOIN BIOGRAFICOS D
                ON D.CODIGO = G.DOCENTE
            WHERE
                K.ANIO = :anio
                AND K.PERIODO = :periodo
                AND K.CANCELADO IS NULL
                AND K.TIPO_EXAMEN IN ('N','E')
                AND G.PRIMARIO = 'Y'
                AND G.TIPO = 'N'
                AND K.[PLAN] IN ('089801','109401','125091','126091','059801')
                AND M.NIVEL IN ($nivelesPlaceholders)
                AND D.CODIGO IS NOT NULL
        )
        SELECT
            COD_DOCENTE,
            APELLIDOS,
            NOMBRES,
            CARRERA,
            COUNT(*) AS INSCRITOS,
            SUM(CASE WHEN NOTA_FINAL >= 51 THEN 1 ELSE 0 END) AS APROBADOS,
            SUM(CASE WHEN NOTA_FINAL < 51  THEN 1 ELSE 0 END) AS REPROBADOS
        FROM BASE
        GROUP BY COD_DOCENTE, APELLIDOS, NOMBRES, CARRERA
        ORDER BY APELLIDOS, NOMBRES, CARRERA
    ";

        $bindings = [
            'anio' => $anio,
            'periodo' => $periodo,
        ];
        foreach ($nivelesPermitidos as $idx => $nivelPermitido) {
            $bindings["nivel_permitido_{$idx}"] = $nivelPermitido;
        }

        try {
            $filas = DB::select($sql, $bindings);

            // ── Agrupar filas planas (docente+carrera) en la estructura anidada ──
            $porDocente = [];
            foreach ($filas as $f) {
                $cod = $f->COD_DOCENTE;

                if (!isset($porDocente[$cod])) {
                    $porDocente[$cod] = [
                        'cod_docente' => $cod,
                        'apellidos' => $f->APELLIDOS,
                        'nombres' => $f->NOMBRES,
                        'carreras' => [],
                        'total_inscritos' => 0,
                        'total_aprobados' => 0,
                        'total_reprobados' => 0,
                    ];
                }

                $porDocente[$cod]['carreras'][] = [
                    'carrera' => $f->CARRERA,
                    'subtotal_inscritos' => (int) $f->INSCRITOS,
                    'subtotal_aprobados' => (int) $f->APROBADOS,
                    'subtotal_reprobados' => (int) $f->REPROBADOS,
                ];

                $porDocente[$cod]['total_inscritos'] += (int) $f->INSCRITOS;
                $porDocente[$cod]['total_aprobados'] += (int) $f->APROBADOS;
                $porDocente[$cod]['total_reprobados'] += (int) $f->REPROBADOS;
            }

            $data = array_values($porDocente);

            return response()->json([
                'success' => true,
                'anio' => (int) $anio,
                'periodo' => (int) $periodo,
                'total_docentes' => count($data),
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al obtener resumen de aprobados/reprobados: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ocurrio un error al consultar el resumen de aprobados y reprobados.',
            ], 500);
        }
    }
}