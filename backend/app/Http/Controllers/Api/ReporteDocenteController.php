<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class ReporteDocenteController extends Controller
{
    // public function materiasDictadas(Request $request)


    // {
    //     $request->validate([
    //         'docente' => 'required|numeric',
    //         'anio' => 'nullable|numeric',
    //     ]);

    //     $docente = $request->docente;
    //     $anio = $request->anio;

    //     $docenteInfo = DB::connection('pgsql')->selectOne("
    //         SELECT codigo, nombres, apellidos 
    //         FROM docentes
    //         WHERE codigo = ?
    //     ", [$docente]);

    //     if (!$docenteInfo) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Docente no encontrado'
    //         ], 404);
    //     }

    //     $filtroAnio = $anio ? "AND g.anio = :anio" : "";

    //     $materias = DB::connection('pgsql')->select("
    //         SELECT
    //             ROW_NUMBER() OVER (
    //                 ORDER BY
    //                     g.anio,
    //                     CASE 
    //                         WHEN g.periodo = '1' THEN 1
    //                         WHEN g.periodo = '4' THEN 2
    //                         WHEN g.periodo = '2' THEN 3
    //                         WHEN g.periodo = '3' THEN 4
    //                         ELSE 5
    //                     END,
    //                     g.grupo ASC,
    //                     g.materia,
    //                     g.plan
    //             ) AS nro,

    //             g.anio::TEXT || '/' ||
    //             CASE 
    //                 WHEN g.periodo = '3' THEN '3 - Verano'
    //                 WHEN g.periodo = '4' THEN '4 - Invierno'
    //                 ELSE g.periodo
    //             END AS gestion,

    //             CASE 
    //                 WHEN p.nombre ILIKE '%ADMINISTRACION%' THEN 'ADM'
    //                 WHEN p.nombre ILIKE '%COMERCIAL%'      THEN 'COM'
    //                 WHEN p.nombre ILIKE '%FINANCIERA%'     THEN 'FIN'
    //                 WHEN p.nombre ILIKE '%ECONOMIA%'       THEN 'ECO'
    //                 WHEN p.nombre ILIKE '%CONTADURIA%'     THEN 'CON'
    //                 ELSE LEFT(p.nombre, 3)
    //             END AS plan,

    //             g.materia::TEXT || ' ' || COALESCE(m.nombre, 'SIN NOMBRE') AS materia,

    //             CASE 
    //                 WHEN g.grupo > '30' AND g.periodo IN ('3','4') THEN 'COMPARTIDO'
    //                 ELSE ''
    //             END AS compartido,

    //             g.grupo     AS grp,
    //             g.resolucion,
    //             g.designacion

    //         FROM grupos g

    //         INNER JOIN docentes d 
    //             ON d.codigo = g.docente

    //         INNER JOIN materias m 
    //             ON  m.codigo  = g.materia
    //             AND m.anio    = g.anio
    //             AND m.periodo = g.periodo
    //             AND m.plan    = g.plan

    //         LEFT JOIN planes p
    //             ON  p.codigo = g.plan
    //             AND p.anio   = g.anio

    //         WHERE d.codigo = :docente
    //           AND g.primario = 'Y'
    //           AND g.tipo = 'N'
    //           {$filtroAnio}

    //         ORDER BY
    //             g.anio,
    //             CASE 
    //                 WHEN g.periodo = '1' THEN 1
    //                 WHEN g.periodo = '4' THEN 2
    //                 WHEN g.periodo = '2' THEN 3
    //                 WHEN g.periodo = '3' THEN 4
    //                 ELSE 5
    //             END ASC,
    //             g.grupo ASC,
    //             g.materia,
    //             g.plan
    //     ", array_filter([
    //             'docente' => $docente,
    //             'anio' => $anio,
    //         ]));

    //     return response()->json([
    //         'success' => true,
    //         'docente' => [
    //             'codigo' => $docenteInfo->codigo,
    //             'nombre' => trim($docenteInfo->apellidos . ' ' . $docenteInfo->nombres),
    //         ],
    //         'anio_filtro' => $anio ?? 'todos',
    //         'total' => count($materias),
    //         'materias' => $materias,
    //     ]);
    // }



    public function materiasDictadas(Request $request)
    {
        $request->validate([
            'docente' => 'required|numeric',
            'anio' => 'nullable|numeric',
            'materia' => 'nullable|string|max:7',   // AÑADIR
            'grupo' => 'nullable|string|max:2',   // AÑADIR
        ]);

        $docente = $request->docente;
        $anio = $request->anio;
        $materia = $request->materia;   // AÑADIR
        $grupo = $request->grupo;     // AÑADIR

        $docenteInfo = DB::connection('pgsql')->selectOne("
        SELECT codigo, nombres, apellidos 
        FROM docentes
        WHERE codigo = ?
    ", [$docente]);

        if (!$docenteInfo) {
            return response()->json([
                'success' => false,
                'message' => 'Docente no encontrado'
            ], 404);
        }

        // AÑADIR los filtros opcionales
        $filtroAnio = $anio ? "AND g.anio    = :anio" : "";
        $filtroMateria = $materia ? "AND g.materia = :materia" : "";
        $filtroGrupo = $grupo ? "AND g.grupo   = :grupo" : "";

        $materias = DB::connection('pgsql')->select("
        SELECT
            ROW_NUMBER() OVER (
                ORDER BY
                    g.anio,
                    CASE 
                        WHEN g.periodo = '1' THEN 1
                        WHEN g.periodo = '4' THEN 2
                        WHEN g.periodo = '2' THEN 3
                        WHEN g.periodo = '3' THEN 4
                        ELSE 5
                    END,
                    g.grupo ASC,
                    g.materia,
                    g.plan
            ) AS nro,

            g.anio::TEXT || '/' ||
            CASE 
                WHEN g.periodo = '3' THEN '3 - Verano'
                WHEN g.periodo = '4' THEN '4 - Invierno'
                ELSE g.periodo
            END AS gestion,

            CASE 
                WHEN p.nombre ILIKE '%ADMINISTRACION%' THEN 'ADM'
                WHEN p.nombre ILIKE '%COMERCIAL%'      THEN 'COM'
                WHEN p.nombre ILIKE '%FINANCIERA%'     THEN 'FIN'
                WHEN p.nombre ILIKE '%ECONOMIA%'       THEN 'ECO'
                WHEN p.nombre ILIKE '%CONTADURIA%'     THEN 'CON'
                ELSE LEFT(p.nombre, 3)
            END AS plan,

            g.materia::TEXT || ' ' || COALESCE(m.nombre, 'SIN NOMBRE') AS materia,

            CASE 
                WHEN g.grupo > '30' AND g.periodo IN ('3','4') THEN 'COMPARTIDO'
                ELSE ''
            END AS compartido,

            g.grupo     AS grp,
            g.resolucion,
            g.designacion

        FROM grupos g

        INNER JOIN docentes d 
            ON d.codigo = g.docente

        INNER JOIN materias m 
            ON  m.codigo  = g.materia
            AND m.anio    = g.anio
            AND m.periodo = g.periodo
            AND m.plan    = g.plan

        LEFT JOIN planes p
            ON  p.codigo = g.plan
            AND p.anio   = g.anio

        WHERE d.codigo = :docente
          AND g.primario = 'Y'
          AND g.tipo = 'N'
          {$filtroAnio}
          {$filtroMateria}
          {$filtroGrupo}

        ORDER BY
            g.anio,
            CASE 
                WHEN g.periodo = '1' THEN 1
                WHEN g.periodo = '4' THEN 2
                WHEN g.periodo = '2' THEN 3
                WHEN g.periodo = '3' THEN 4
                ELSE 5
            END ASC,
            g.grupo ASC,
            g.materia,
            g.plan
    ", array_filter([
                'docente' => $docente,
                'anio' => $anio,
                'materia' => $materia,   // AÑADIR
                'grupo' => $grupo,     // AÑADIR
            ]));

        return response()->json([
            'success' => true,
            'docente' => [
                'codigo' => $docenteInfo->codigo,
                'nombre' => trim($docenteInfo->apellidos . ' ' . $docenteInfo->nombres),
            ],
            'anio_filtro' => $anio ?? 'todos',
            'materia_filtro' => $materia ?? 'todas',   // opcional, para debug
            'grupo_filtro' => $grupo ?? 'todos',   // opcional, para debug
            'total' => count($materias),
            'materias' => $materias,
        ]);
    }


    /**
     * POST /api/reporte-horario
     *
     * Body (opcional):
     *   { "docente": 123 }   → filtra un docente
     *   {}                   → todos los docentes
     */
    public function horario(Request $request): JsonResponse
    {
        // ─── 1. Gestión activa ────────────────────────────────────────────
        // Ajusta aquí si tienes una tabla de configuración para leer anio/periodo
        $anio = 2024;
        $periodo = 1;

        // ─── 2. Filtro opcional por docente ──────────────────────────────
        $docenteFiltro = $request->input('docente'); // null = todos

        // ─── 3. Consulta principal ────────────────────────────────────────
        $sql = "
            WITH params AS (
                SELECT :anio AS anio, :periodo AS periodo
            )
            SELECT
                h.anio,
                h.periodo,
                g.plan,
                CASE g.plan
                    WHEN '059801' THEN 'ECO'
                    WHEN '109401' THEN 'ADM'
                    WHEN '089801' THEN 'CCP'
                    WHEN '125091' THEN 'COM'
                    WHEN '126091' THEN 'FIN'
                    ELSE 'NN'
                END AS carrera,
                m.nivel,
                h.docente,
                d.apellidos,
                d.nombres,
                g.materia,
                m.nombre,
                h.tipo,
                CASE
                    WHEN h.tipo = 'C' THEN ''
                    WHEN h.tipo = 'P' THEN '[AUX]'
                    ELSE 'N'
                END AS tipo2,
                g.grupo,
                SUM(
                    CASE
                        WHEN h.hora > 0 AND h.grupo = 'NN' THEN 8
                        ELSE 2
                    END
                ) AS carga_horaria,
                gc.comp,
                gc.compartido,
                gc.orden

            FROM horarios2 h
            INNER JOIN grupos g
                ON  h.anio::text    = g.anio::text
                AND h.periodo::text = g.periodo::text
                AND h.materia::text = g.materia::text
                AND h.grupo::text   = g.grupo::text
                AND h.docente::text = g.docente::text
            INNER JOIN materias m
                ON  g.anio::text    = m.anio::text
                AND g.periodo::text = m.periodo::text
                AND g.plan::text    = m.plan::text
                AND g.materia::text = m.codigo::text
            INNER JOIN docentes d
                ON  h.docente::text = d.codigo::text
            LEFT OUTER JOIN grupos_compartidos gc
                ON  g.plan::text     = gc.plan::text
                AND g.materia::text  = gc.materia::text
                AND g.grupo::text    = gc.grupo::text
                AND g.primario::text = gc.primario::text

            WHERE h.anio::text    = (SELECT anio::text    FROM params)
              AND h.periodo::text = (SELECT periodo::text FROM params)
              AND h.tipo          IN ('C')
              AND g.plan::text    IN ('109401', '125091', '089801', '126091', '059801')
              AND g.tipo          = 'N'
              AND g.primario      = 'Y'
              AND h.hora::integer NOT IN (730,900,1030,1200,1330,1500,1630,1800,1930,2100)
              " . ($docenteFiltro ? "AND h.docente::text = :docente" : "") . "

            GROUP BY
                h.anio, h.periodo, g.plan, m.nivel,
                h.docente, d.apellidos, d.nombres,
                g.materia, m.nombre, h.tipo, g.grupo,
                gc.comp, gc.compartido, gc.orden

            ORDER BY
                d.apellidos, d.nombres,
                gc.orden, g.materia, g.grupo,
                g.plan, gc.compartido
        ";

        $bindings = [
            'anio' => $anio,
            'periodo' => $periodo,
        ];

        if ($docenteFiltro) {
            $bindings['docente'] = (string) $docenteFiltro;
        }

        $filas = DB::connection('pgsql')->select($sql, $bindings);

        // ─── 4. Agrupar por docente ───────────────────────────────────────
        // El front espera: { gestion, docentes: [{ codigo, apellidos, nombres, materias: [...] }] }

        $docentesMap = [];

        foreach ($filas as $fila) {
            $codigo = $fila->docente;

            if (!isset($docentesMap[$codigo])) {
                $docentesMap[$codigo] = [
                    'codigo' => $codigo,
                    'apellidos' => $fila->apellidos,
                    'nombres' => $fila->nombres,
                    'materias' => [],
                ];
            }

            $docentesMap[$codigo]['materias'][] = [
                'plan' => $fila->plan,
                'carrera' => $fila->carrera,
                'nivel' => $fila->nivel,
                'materia' => $fila->materia,
                'nombre' => $fila->nombre,
                'tipo' => $fila->tipo,
                'tipo2' => $fila->tipo2,
                'grupo' => $fila->grupo,
                'carga_horaria' => (int) $fila->carga_horaria,
                'comp' => $fila->comp,
                'compartido' => $fila->compartido,
                'orden' => $fila->orden,
            ];
        }

        // ─── 5. Respuesta final ───────────────────────────────────────────
        return response()->json([
            'gestion' => [
                'anio' => $anio,
                'periodo' => $periodo,
            ],
            'docentes' => array_values($docentesMap),
        ]);
    }

}