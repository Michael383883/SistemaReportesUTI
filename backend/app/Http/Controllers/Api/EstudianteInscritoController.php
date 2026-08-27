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
     */
    public function index(Request $request)
    {
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

        $nivelesPlaceholders = implode(', ', array_map(
            fn($idx) => ":nivel_permitido_{$idx}",
            array_keys($nivelesPermitidos)
        ));

        // ── SELECT base con ROW_NUMBER() en vez de OFFSET/FETCH ──
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
            D.CODIGO AS COD_DOCENTE,
            D.APELLIDOS + ' ' + D.NOMBRES AS DOCENTE,
            B.CODIGO AS COD_ESTUDIANTE,
            B.APELLIDOS + ' ' + B.NOMBRES AS ESTUDIANTE,
            ROW_NUMBER() OVER (
                ORDER BY
                    M.NIVEL ASC,
                    K.[PLAN],
                    K.MATERIA,
                    K.GRUPO,
                    B.APELLIDOS,
                    B.NOMBRES
            ) AS RN
    ";

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

        // ── Paginación vía CTE + ROW_NUMBER() (compatible con SQL Server 2008+) ──
        $sqlData = "
        WITH PAGINADO AS (
            {$selectData}
            {$fromWhere}
        )
        SELECT * FROM PAGINADO
        WHERE RN BETWEEN :row_inicio AND :row_fin
        ORDER BY RN
    ";

        $bindingsData = $bindings + [
            'row_inicio' => $offset + 1,
            'row_fin' => $offset + $perPage,
        ];

        try {
            $total = (int) (DB::select($sqlCount, $bindings)[0]->TOTAL ?? 0);
            $resultados = DB::select($sqlData, $bindingsData);

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

        // ── Igual que en listaInscritos(): solo periodos regulares (1 y 2)
        // tienen horario real cargado en HORARIOS2. Los periodos 3/4 (mesa /
        // intersemestral) no lo exigen.
        $periodosConHorarioRegular = [1, 2];
        $filtroHorario = in_array((int) $periodo, $periodosConHorarioRegular, true)
            ? "AND EXISTS (
                SELECT 1
                FROM HORARIOS2 H
                WHERE H.ANIO = G.ANIO
                  AND H.PERIODO = G.PERIODO
                  AND H.MATERIA = G.MATERIA
                  AND H.GRUPO = G.GRUPO
                  AND H.DOCENTE = G.DOCENTE
                  AND H.TIPO = 'C'
                  AND H.HORA NOT IN (730,900,1030,1200,1330,1500,1630,1800,1930,2100)
           )"
            : "";

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
            $filtroHorario
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

        $baseCte .= " ) ";

        // ── resto de la función IGUAL (GROUP BY, paginación con ROW_NUMBER, etc.) ──
        $groupBySelect = "
    SELECT
        ANIO, PERIODO, [PLAN], CARRERA, NIVEL, NOMBRE_DOCENTE,
        MATERIA, NOMBRE, GRUPO, TIPO_EXAMEN,
        COUNT(*) AS INSCRITOS,
        SUM(CASE WHEN NOTA_FINAL >= 51 THEN 1 ELSE 0 END) AS APROBADOS,
        SUM(CASE WHEN NOTA_FINAL < 51  THEN 1 ELSE 0 END) AS REPROBADOS
    FROM BASE
    GROUP BY
        ANIO, PERIODO, [PLAN], CARRERA, NIVEL, NOMBRE_DOCENTE,
        MATERIA, NOMBRE, GRUPO, TIPO_EXAMEN
";

        $sqlCount = " {$baseCte} SELECT COUNT(*) AS TOTAL FROM ({$groupBySelect}) AS RESUMEN ";

        $sqlData = "
    {$baseCte},
    RESUMEN AS ( {$groupBySelect} ),
    PAGINADO AS (
        SELECT *,
            ROW_NUMBER() OVER (ORDER BY [PLAN], NIVEL ASC, MATERIA, GRUPO) AS RN
        FROM RESUMEN
    )
    SELECT * FROM PAGINADO
    WHERE RN BETWEEN :row_inicio AND :row_fin
    ORDER BY RN
";

        $bindingsData = $bindings + [
            'row_inicio' => $offset + 1,
            'row_fin' => $offset + $perPage,
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

    /**
     * Resumen de aprobados/reprobados por docente (sin paginación, no usa OFFSET/FETCH).
     */
    /**
     * Resumen de aprobados/reprobados por docente y carrera.
     */
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

        // ── Igual que en listaInscritos(): solo los periodos regulares (1 y 2)
        // tienen horario real cargado en HORARIOS2. Sin este filtro, GRUPOS.DOCENTE
        // puede quedar con un valor desactualizado y traer docentes que en
        // realidad no dictaron nada en esa gestion.
        $periodosConHorarioRegular = [1, 2];
        $filtroHorario = in_array((int) $periodo, $periodosConHorarioRegular, true)
            ? "AND EXISTS (
                SELECT 1
                FROM HORARIOS2 H
                WHERE H.ANIO = G.ANIO
                  AND H.PERIODO = G.PERIODO
                  AND H.MATERIA = G.MATERIA
                  AND H.GRUPO = G.GRUPO
                  AND H.DOCENTE = G.DOCENTE
                  AND H.TIPO = 'C'
                  AND H.HORA NOT IN (730,900,1030,1200,1330,1500,1630,1800,1930,2100)
           )"
            : "";

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
                $filtroHorario
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