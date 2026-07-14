<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HorarioAdminController extends Controller
{
    /**
     * Retorna la carga horaria completa de docentes con grupos compartidos.
     * Equivalente al reporte "Carga Horaria Docentes FCE"
     */

    public function index(Request $request)
    {
        $anio = (int) $request->query('anio', date('Y'));
        $periodo = (int) $request->query('periodo', 1);
        $docente = $request->query('docente');

        $docenteFilter = $docente ? "AND h.DOCENTE = :docente" : "";
        $bindings = ['anio' => $anio, 'periodo' => $periodo];
        if ($docente)
            $bindings['docente'] = $docente;

        $sql = "
        SELECT
            TBL.ANIO, TBL.PERIODO, TBL.[PLAN], TBL.CARRERA, TBL.NIVEL,
            TBL.DOCENTE, TBL.APELLIDOS, TBL.NOMBRES,
            TBL.MATERIA, TBL.NOMBRE, TBL.TIPO, TBL.TIPO2,
            TBL.GRUPO, TBL.DIA, TBL.ORDEN_DIA, TBL.HORA, TBL.HORARIO,
            TBL.AMBIENTE, TBL.[CARGA HORARIA] AS CARGA_HORARIA,
            TBL.COMP, TBL.COMPARTIDO, TBL.ORDEN,
            NroInsMatGrpNE.[TOTAL NORMAL] AS TOTAL_NORMAL
        FROM (
            SELECT
                h.ANIO, h.PERIODO, g.[PLAN],
                CASE g.[PLAN]
                    WHEN '059801' THEN 'ECO' WHEN '109401' THEN 'ADM'
                    WHEN '089801' THEN 'CCP' WHEN '125091' THEN 'COM'
                    WHEN '126091' THEN 'FIN' ELSE 'NN'
                END AS CARRERA,
                m.NIVEL, h.DOCENTE, d.APELLIDOS, d.NOMBRES,
                g.MATERIA, m.NOMBRE, h.TIPO,
                CASE WHEN h.TIPO = 'C' THEN '' WHEN h.TIPO = 'P' THEN '[AUX]' ELSE 'N' END AS TIPO2,
                g.GRUPO, h.DIA,
                CASE h.DIA
                    WHEN 'LU' THEN 1 WHEN 'MA' THEN 2 WHEN 'MI' THEN 3
                    WHEN 'JU' THEN 4 WHEN 'VI' THEN 5 WHEN 'SA' THEN 6 ELSE 0
                END AS ORDEN_DIA,
                h.HORA,
                CASE
                    WHEN h.HORA = 645  THEN '06:45 - 08:15'
                    WHEN h.HORA = 815  THEN '08:15 - 09:45'
                    WHEN h.HORA = 945  THEN '09:45 - 11:15'
                    WHEN h.HORA = 1115 THEN '11:15 - 12:45'
                    WHEN h.HORA = 1245 THEN '12:45 - 14:15'
                    WHEN h.HORA = 1415 THEN '14:15 - 15:45'
                    WHEN h.HORA = 1545 THEN '15:45 - 17:15'
                    WHEN h.HORA = 1715 THEN '17:15 - 18:45'
                    WHEN h.HORA = 1845 THEN '18:45 - 20:15'
                    WHEN h.HORA = 2015 THEN '20:15 - 21:45'
                    ELSE '00:00 - 00:00'
                END AS HORARIO,
                h.AMBIENTE,
                SUM(CASE WHEN h.HORA > 0 AND h.GRUPO = 'NN' THEN 8 ELSE 2 END) AS [CARGA HORARIA],
                gc.COMP, gc.COMPARTIDO, gc.ORDEN
            FROM HORARIOS2 h
            INNER JOIN GRUPOS g
                ON h.ANIO = g.ANIO AND h.PERIODO = g.PERIODO
                AND h.MATERIA = g.MATERIA AND h.GRUPO = g.GRUPO AND h.DOCENTE = g.DOCENTE
            INNER JOIN MATERIAS m
                ON g.ANIO = m.ANIO AND g.PERIODO = m.PERIODO
                AND g.[PLAN] = m.[PLAN] AND g.MATERIA = m.CODIGO
            INNER JOIN DOCENTES d ON h.DOCENTE = d.CODIGO
            LEFT JOIN GRUPOS_COMPARTIDOS gc
                ON g.[PLAN] = gc.[PLAN] AND g.MATERIA = gc.MATERIA
                AND g.GRUPO = gc.GRUPO AND g.PRIMARIO = gc.PRIMARIO
            WHERE h.ANIO = :anio
              AND h.PERIODO = :periodo
              AND h.TIPO IN ('C')
              AND g.[PLAN] IN ('109401','125091','089801','126091','059801')
              AND g.TIPO = 'N'
              AND g.PRIMARIO IN ('Y')
              AND h.HORA NOT IN (730,900,1030,1200,1330,1500,1630,1800,1930,2100)
              $docenteFilter
            GROUP BY
                h.ANIO, h.PERIODO, g.[PLAN], m.NIVEL,
                h.DOCENTE, d.APELLIDOS, d.NOMBRES,
                g.MATERIA, m.NOMBRE, h.TIPO, g.GRUPO, h.DIA, h.HORA,
                h.AMBIENTE, gc.COMP, gc.COMPARTIDO, gc.ORDEN
        ) AS TBL
        LEFT JOIN NroInsMatGrpNE
            ON TBL.[PLAN] = NroInsMatGrpNE.[PLAN]
            AND TBL.DOCENTE = NroInsMatGrpNE.CODIGO
            AND TBL.MATERIA = NroInsMatGrpNE.MATERIA
            AND TBL.GRUPO = NroInsMatGrpNE.GRUPO
        ORDER BY TBL.APELLIDOS, TBL.NOMBRES, TBL.ORDEN, TBL.MATERIA,
                 TBL.GRUPO, TBL.[PLAN], TBL.ORDEN_DIA, TBL.COMPARTIDO
    ";

        $data = collect(DB::select($sql, $bindings));

        $grouped = $data->groupBy('DOCENTE')->map(function ($rows) {
            $first = $rows->first();
            return [
                'docente' => $first->DOCENTE,
                'apellidos' => $first->APELLIDOS,
                'nombres' => $first->NOMBRES,
                'horarios' => $rows->values(),
                'total_ch' => $rows->sum('CARGA_HORARIA'),
            ];
        })->values();

        return response()->json([
            'anio' => $anio,
            'periodo' => $periodo,
            'total' => $grouped->count(),
            'data' => $grouped,
        ]);
    }
    /**
     * Retorna la carga horaria de un docente específico.
     */
    public function show(Request $request, $docente)
    {
        return $this->index($request->merge(['docente' => $docente]));
    }


    /**
     * Resumen de carga horaria docente.
     * No devuelve horarios por día, solo materias y CH.
     */
    public function resumen(Request $request)
    {
        $anio = (int) $request->query('anio', date('Y'));
        $periodo = (int) $request->query('periodo', 1);
        $docente = $request->query('docente');

        $docenteFilter = $docente ? "AND HORARIOS2.DOCENTE = :docente" : "";

        $bindings = [
            'anio' => $anio,
            'periodo' => $periodo,
        ];

        if ($docente) {
            $bindings['docente'] = $docente;
        }

        $sql = "
        SELECT
            HORARIOS2.ANIO,
            HORARIOS2.PERIODO,
            GRUPOS.[PLAN],

            CASE GRUPOS.[PLAN]
                WHEN '059801' THEN 'ECO'
                WHEN '109401' THEN 'ADM'
                WHEN '089801' THEN 'CCP'
                WHEN '125091' THEN 'COM'
                WHEN '126091' THEN 'FIN'
                ELSE 'NN'
            END AS CARRERA,

            MATERIAS.NIVEL,
            HORARIOS2.DOCENTE,
            DOCENTES.APELLIDOS,
            DOCENTES.NOMBRES,
            GRUPOS.MATERIA,
            MATERIAS.NOMBRE,
            HORARIOS2.TIPO,

            CASE
                WHEN HORARIOS2.TIPO = 'C' THEN ''
                WHEN HORARIOS2.TIPO = 'P' THEN '[AUX]'
                ELSE 'N'
            END AS TIPO2,

            GRUPOS.GRUPO,

            SUM(
                CASE
                    WHEN HORARIOS2.HORA > 0
                    AND HORARIOS2.GRUPO = 'NN'
                    THEN 8
                    ELSE 2
                END
            ) AS CARGA_HORARIA,

            GRUPOS_COMPARTIDOS.COMP,
            GRUPOS_COMPARTIDOS.COMPARTIDO,
            GRUPOS_COMPARTIDOS.ORDEN,

            ISNULL(NroInsMatGrpNE.[TOTAL NORMAL], 0) AS TOTAL_NORMAL

        FROM HORARIOS2

        INNER JOIN GRUPOS
            ON HORARIOS2.ANIO = GRUPOS.ANIO
            AND HORARIOS2.PERIODO = GRUPOS.PERIODO
            AND HORARIOS2.MATERIA = GRUPOS.MATERIA
            AND HORARIOS2.GRUPO = GRUPOS.GRUPO
            AND HORARIOS2.DOCENTE = GRUPOS.DOCENTE

        INNER JOIN MATERIAS
            ON GRUPOS.ANIO = MATERIAS.ANIO
            AND GRUPOS.PERIODO = MATERIAS.PERIODO
            AND GRUPOS.[PLAN] = MATERIAS.[PLAN]
            AND GRUPOS.MATERIA = MATERIAS.CODIGO

        INNER JOIN DOCENTES
            ON HORARIOS2.DOCENTE = DOCENTES.CODIGO

        LEFT JOIN GRUPOS_COMPARTIDOS
            ON GRUPOS.[PLAN] = GRUPOS_COMPARTIDOS.[PLAN]
            AND GRUPOS.MATERIA = GRUPOS_COMPARTIDOS.MATERIA
            AND GRUPOS.GRUPO = GRUPOS_COMPARTIDOS.GRUPO
            AND GRUPOS.PRIMARIO = GRUPOS_COMPARTIDOS.PRIMARIO

        LEFT JOIN NroInsMatGrpNE
            ON GRUPOS.[PLAN] = NroInsMatGrpNE.[PLAN]
            AND GRUPOS.DOCENTE = NroInsMatGrpNE.CODIGO
            AND GRUPOS.MATERIA = NroInsMatGrpNE.MATERIA
            AND GRUPOS.GRUPO = NroInsMatGrpNE.GRUPO

        WHERE HORARIOS2.ANIO = :anio
        AND HORARIOS2.PERIODO = :periodo
        AND HORARIOS2.TIPO IN ('C')
        AND GRUPOS.[PLAN] IN ('109401','125091','089801','126091','059801')
        AND GRUPOS.TIPO = 'N'
        AND GRUPOS.PRIMARIO = 'Y'
        AND HORARIOS2.HORA NOT IN (
            730,900,1030,1200,1330,
            1500,1630,1800,1930,2100
        )
        $docenteFilter

        GROUP BY
            HORARIOS2.ANIO,
            HORARIOS2.PERIODO,
            GRUPOS.[PLAN],
            MATERIAS.NIVEL,
            HORARIOS2.DOCENTE,
            DOCENTES.APELLIDOS,
            DOCENTES.NOMBRES,
            GRUPOS.MATERIA,
            MATERIAS.NOMBRE,
            HORARIOS2.TIPO,
            GRUPOS.GRUPO,
            GRUPOS_COMPARTIDOS.COMP,
            GRUPOS_COMPARTIDOS.COMPARTIDO,
            GRUPOS_COMPARTIDOS.ORDEN,
            NroInsMatGrpNE.[TOTAL NORMAL]

        ORDER BY
            DOCENTES.APELLIDOS,
            DOCENTES.NOMBRES,
            GRUPOS_COMPARTIDOS.ORDEN,
            GRUPOS.MATERIA,
            GRUPOS.GRUPO,
            GRUPOS.[PLAN],
            GRUPOS_COMPARTIDOS.COMPARTIDO
        ";

        $data = collect(DB::select($sql, $bindings));

        $grouped = $data->groupBy('DOCENTE')->map(function ($rows) {

            $first = $rows->first();

            return [
                'docente' => $first->DOCENTE,
                'apellidos' => $first->APELLIDOS,
                'nombres' => $first->NOMBRES,

                'materias' => $rows->values(),

                'total_ch' => $rows->sum('CARGA_HORARIA'),
            ];
        })->values();

        return response()->json([
            'anio' => $anio,
            'periodo' => $periodo,
            'total' => $grouped->count(),
            'data' => $grouped,
        ]);
    }
    /**
     * Resumen de un docente específico.
     */
    public function resumenDocente(Request $request, $docente)
    {
        return $this->resumen(
            $request->merge([
                'docente' => $docente
            ])
        );
    }


    /**
     * Lista de inscritos por docente, agrupados por carrera y materia.
     * Optimizado para evitar memory exhaustion - agrupamiento en SQL.
     */
    public function listaInscritos(Request $request)
    {
        $anio = (int) $request->query('anio', date('Y'));
        $periodo = (int) $request->query('periodo', 1);
        $docente = $request->query('docente');

        $docenteFilter = $docente ? "AND DOCENTES.CODIGO = :docente" : "";

        $bindings = [
            'anio' => $anio,
            'periodo' => $periodo,
        ];

        if ($docente) {
            $bindings['docente'] = $docente;
        }

        // Filtro reutilizable: el grupo debe tener al menos una hora de clase (TIPO='C')
        // en un horario válido (excluyendo las horas de "NN" tipo 730, 900, etc.)
        // Esto excluye grupos "Por Designar" (sin docente/horario real asignado),
        // igual que ya hace resumen().
        $horarioValidoExists = "
            EXISTS (
                SELECT 1
                FROM HORARIOS2 H
                WHERE H.ANIO = GRUPOS.ANIO
                  AND H.PERIODO = GRUPOS.PERIODO
                  AND H.MATERIA = GRUPOS.MATERIA
                  AND H.GRUPO = GRUPOS.GRUPO
                  AND H.DOCENTE = GRUPOS.DOCENTE
                  AND H.TIPO = 'C'
                  AND H.HORA NOT IN (
                      730,900,1030,1200,1330,
                      1500,1630,1800,1930,2100
                  )
            )
        ";

        // ── 1. Docentes involucrados ──────────────────────────────────────────
        $sqlDocentes = "
        SELECT DISTINCT
            DOCENTES.CODIGO   AS COD_DOCENTE,
            DOCENTES.APELLIDOS,
            DOCENTES.NOMBRES
        FROM DOCENTES
        INNER JOIN GRUPOS
            ON DOCENTES.CODIGO = GRUPOS.DOCENTE
        INNER JOIN KARDEX_EXT
            ON KARDEX_EXT.ANIO     = GRUPOS.ANIO
            AND KARDEX_EXT.PERIODO = GRUPOS.PERIODO
            AND KARDEX_EXT.[PLAN]  = GRUPOS.[PLAN]
            AND KARDEX_EXT.MATERIA = GRUPOS.MATERIA
            AND KARDEX_EXT.GRUPO   = GRUPOS.GRUPO
        WHERE GRUPOS.ANIO            = :anio
          AND GRUPOS.PERIODO         = :periodo
          AND KARDEX_EXT.CANCELADO   IS NULL
          AND KARDEX_EXT.TIPO_EXAMEN IN ('N', 'E')
          AND GRUPOS.[PLAN]          IN ('109401','125091','089801','126091','059801')
          AND GRUPOS.PRIMARIO        = 'Y'
          AND GRUPOS.TIPO            = 'N'
          AND $horarioValidoExists
          $docenteFilter
        ORDER BY DOCENTES.APELLIDOS, DOCENTES.NOMBRES
    ";

        $docentes = DB::select($sqlDocentes, $bindings);

        // ── 2. Materias por docente (conteo de inscritos, separado regular/especial) ──
        // TIPO_EXAMEN = 'N' -> alumno regular (cursa el semestre normal)
        // TIPO_EXAMEN = 'E' -> examen de mesa (caso especial, no inscripción regular)
        $sqlMaterias = "
        SELECT
            DOCENTES.CODIGO AS COD_DOCENTE,
            GRUPOS.[PLAN],
            CASE GRUPOS.[PLAN]
                WHEN '059801' THEN 'ECO'
                WHEN '109401' THEN 'ADM'
                WHEN '089801' THEN 'CCP'
                WHEN '125091' THEN 'COM'
                WHEN '126091' THEN 'FIN'
                ELSE 'NN'
            END AS CARRERA,
            MATERIAS.CODIGO  AS COD_MATERIA,
            MATERIAS.NOMBRE  AS NOM_MATERIA,
            GRUPOS.GRUPO,
            SUM(CASE WHEN KARDEX_EXT.TIPO_EXAMEN = 'N' THEN 1 ELSE 0 END) AS SUBTOTAL_REGULAR,
            SUM(CASE WHEN KARDEX_EXT.TIPO_EXAMEN = 'E' THEN 1 ELSE 0 END) AS SUBTOTAL_ESPECIAL
        FROM DOCENTES
        INNER JOIN GRUPOS
            ON DOCENTES.CODIGO = GRUPOS.DOCENTE
        INNER JOIN KARDEX_EXT
            ON KARDEX_EXT.ANIO     = GRUPOS.ANIO
            AND KARDEX_EXT.PERIODO = GRUPOS.PERIODO
            AND KARDEX_EXT.[PLAN]  = GRUPOS.[PLAN]
            AND KARDEX_EXT.MATERIA = GRUPOS.MATERIA
            AND KARDEX_EXT.GRUPO   = GRUPOS.GRUPO
        INNER JOIN MATERIAS
            ON GRUPOS.ANIO     = MATERIAS.ANIO
            AND GRUPOS.PERIODO = MATERIAS.PERIODO
            AND GRUPOS.[PLAN]  = MATERIAS.[PLAN]
            AND GRUPOS.MATERIA = MATERIAS.CODIGO
        WHERE GRUPOS.ANIO            = :anio
          AND GRUPOS.PERIODO         = :periodo
          AND KARDEX_EXT.CANCELADO   IS NULL
          AND KARDEX_EXT.TIPO_EXAMEN IN ('N', 'E')
          AND GRUPOS.[PLAN]          IN ('109401','125091','089801','126091','059801')
          AND GRUPOS.PRIMARIO        = 'Y'
          AND GRUPOS.TIPO            = 'N'
          AND $horarioValidoExists
          $docenteFilter
        GROUP BY
            DOCENTES.CODIGO,
            GRUPOS.[PLAN],
            MATERIAS.CODIGO,
            MATERIAS.NOMBRE,
            GRUPOS.GRUPO
        ORDER BY
            DOCENTES.CODIGO,
            GRUPOS.[PLAN],
            MATERIAS.NOMBRE
    ";

        $materias = DB::select($sqlMaterias, $bindings);

        // ── 3. Inscritos por materia ──────────────────────────────────────────
        $sqlInscritos = "
        SELECT
            DOCENTES.CODIGO  AS COD_DOCENTE,
            GRUPOS.[PLAN],
            MATERIAS.CODIGO  AS COD_MATERIA,
            GRUPOS.GRUPO,
            KARDEX_EXT.TIPO_EXAMEN,
            BIOGRAFICOS.CODIGO                               AS COD_ESTUDIANTE,
            BIOGRAFICOS.APELLIDOS + ' ' + BIOGRAFICOS.NOMBRES AS NOM_ESTUDIANTE
        FROM DOCENTES
        INNER JOIN GRUPOS
            ON DOCENTES.CODIGO = GRUPOS.DOCENTE
        INNER JOIN KARDEX_EXT
            ON KARDEX_EXT.ANIO     = GRUPOS.ANIO
            AND KARDEX_EXT.PERIODO = GRUPOS.PERIODO
            AND KARDEX_EXT.[PLAN]  = GRUPOS.[PLAN]
            AND KARDEX_EXT.MATERIA = GRUPOS.MATERIA
            AND KARDEX_EXT.GRUPO   = GRUPOS.GRUPO
        INNER JOIN BIOGRAFICOS
            ON BIOGRAFICOS.CODIGO = KARDEX_EXT.ESTUDIANTE
        INNER JOIN MATERIAS
            ON GRUPOS.ANIO     = MATERIAS.ANIO
            AND GRUPOS.PERIODO = MATERIAS.PERIODO
            AND GRUPOS.[PLAN]  = MATERIAS.[PLAN]
            AND GRUPOS.MATERIA = MATERIAS.CODIGO
        WHERE GRUPOS.ANIO            = :anio
          AND GRUPOS.PERIODO         = :periodo
          AND KARDEX_EXT.CANCELADO   IS NULL
          AND KARDEX_EXT.TIPO_EXAMEN IN ('N', 'E')
          AND GRUPOS.[PLAN]          IN ('109401','125091','089801','126091','059801')
          AND GRUPOS.PRIMARIO        = 'Y'
          AND GRUPOS.TIPO            = 'N'
          AND $horarioValidoExists
          $docenteFilter
        ORDER BY
            DOCENTES.CODIGO,
            GRUPOS.[PLAN],
            MATERIAS.CODIGO,
            BIOGRAFICOS.APELLIDOS,
            BIOGRAFICOS.NOMBRES
    ";

        $inscritos = DB::select($sqlInscritos, $bindings);

        // ── 4. Armar estructura en PHP con colecciones pequeñas ───────────────

        // Indexar inscritos por clave compuesta para acceso O(1), separando regular/especial
        $inscritosMap = [];
        foreach ($inscritos as $ins) {
            $key = "{$ins->COD_DOCENTE}|{$ins->PLAN}|{$ins->COD_MATERIA}|{$ins->GRUPO}";
            $bucket = $ins->TIPO_EXAMEN === 'E' ? 'especiales' : 'regulares';
            $inscritosMap[$key][$bucket][] = [
                'codigo' => $ins->COD_ESTUDIANTE,
                'nombre' => $ins->NOM_ESTUDIANTE,
            ];
        }
        unset($inscritos); // liberar memoria

        // Indexar materias por docente
        $materiasMap = [];
        foreach ($materias as $mat) {
            $materiasMap[$mat->COD_DOCENTE][] = $mat;
        }
        unset($materias); // liberar memoria

        // Construir respuesta final
        $data = [];
        foreach ($docentes as $doc) {
            $codDoc = $doc->COD_DOCENTE;
            $carreras = [];
            $totalDocente = 0; // solo regulares, para que cuadre con "Resumen"
            $totalEspecialesDocente = 0;

            // Agrupar materias del docente por plan/carrera
            $porPlan = [];
            foreach (($materiasMap[$codDoc] ?? []) as $mat) {
                $porPlan[$mat->PLAN][] = $mat;
            }

            foreach ($porPlan as $plan => $mats) {
                $materiasArr = [];
                $subtotalCarrera = 0; // solo regulares
                $subtotalCarreraEspeciales = 0;

                foreach ($mats as $mat) {
                    $key = "{$codDoc}|{$mat->PLAN}|{$mat->COD_MATERIA}|{$mat->GRUPO}";
                    $listaRegulares = $inscritosMap[$key]['regulares'] ?? [];
                    $listaEspeciales = $inscritosMap[$key]['especiales'] ?? [];

                    $materiasArr[] = [
                        'cod_materia' => $mat->COD_MATERIA,
                        'nom_materia' => $mat->NOM_MATERIA,
                        'grupo' => $mat->GRUPO,
                        'inscritos' => $listaRegulares,
                        'subtotal' => count($listaRegulares),
                        'inscritos_examen_mesa' => $listaEspeciales,
                        'subtotal_examen_mesa' => count($listaEspeciales),
                    ];

                    $subtotalCarrera += count($listaRegulares);
                    $subtotalCarreraEspeciales += count($listaEspeciales);
                }

                $carreras[] = [
                    'plan' => $plan,
                    'carrera' => $mats[0]->CARRERA,
                    'materias' => $materiasArr,
                    'subtotal' => $subtotalCarrera,
                    'subtotal_examen_mesa' => $subtotalCarreraEspeciales,
                ];

                $totalDocente += $subtotalCarrera;
                $totalEspecialesDocente += $subtotalCarreraEspeciales;
            }

            $data[] = [
                'cod_docente' => $codDoc,
                'apellidos' => $doc->APELLIDOS,
                'nombres' => $doc->NOMBRES,
                'carreras' => $carreras,
                'total_inscritos' => $totalDocente, // regulares, cuadra con Resumen
                'total_examen_mesa' => $totalEspecialesDocente,
            ];
        }

        unset($inscritosMap, $materiasMap, $docentes);

        return response()->json([
            'anio' => $anio,
            'periodo' => $periodo,
            'total_docentes' => count($data),
            'data' => $data,
        ]);
    }
    /**
     * Lista inscritos de un docente específico.
     */
    public function listaInscritosDocente(Request $request, $docente)
    {
        return $this->listaInscritos(
            $request->merge(['docente' => $docente])
        );
    }


}