<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\PeriodoAcademico;
use Illuminate\Support\Facades\Log;

class HorarioAdminController extends Controller
{
    /**
     * Retorna la carga horaria completa de docentes con grupos compartidos.
     * Equivalente al reporte "Carga Horaria Docentes FCE"
     */

    public function index(Request $request)
    {
        // Red de seguridad: sube el limite de tiempo solo para esta ruta,
        // mientras confirmamos que el problema de fondo (bloqueo / indices)
        // esta resuelto. Una vez confirmado que corre rapido, puedes bajarlo.
        set_time_limit(60);

        $tInicio = microtime(true);
        Log::info('========== INICIO index() ==========');

        $anio = (int) $request->query('anio', date('Y'));
        $periodo = (int) $request->query('periodo');
        $docente = $request->query('docente'); // opcional, se filtra en PHP

        Log::info('Parametros recibidos', [
            'anio' => $anio,
            'periodo' => $periodo,
            'docente' => $docente,
        ]);

        // ---------------------------------------------------------------
        // QUERY 1: Horarios (solo depende de anio + periodo, sin docente)
        // Los CAST explicitos evitan que PDO mande el parametro como
        // NVARCHAR y SQL Server tenga que convertir cada fila (lo cual
        // invalida el uso de indices).
        // ---------------------------------------------------------------
        $sqlHorarios = "
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
                g.GRUPO, g.TIPO_INGRESO, h.DIA,
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
        SUM(CASE WHEN h.HORA > 0 AND h.GRUPO = 'NN' THEN 8 ELSE 2 END) AS CARGA_HORARIA,
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
    WHERE h.ANIO = CAST(:anio AS INT)
      AND h.PERIODO = CAST(:periodo AS INT)
      AND h.TIPO = 'C'
      AND g.[PLAN] IN ('109401','125091','089801','126091','059801')
      AND g.TIPO = 'N'
      AND g.PRIMARIO = 'Y'
      AND h.HORA NOT IN (730,900,1030,1200,1330,1500,1630,1800,1930,2100)
        GROUP BY
        h.ANIO, h.PERIODO, g.[PLAN], m.NIVEL,
        h.DOCENTE, d.APELLIDOS, d.NOMBRES,
        g.MATERIA, m.NOMBRE, h.TIPO, g.GRUPO, g.TIPO_INGRESO, h.DIA, h.HORA,
        h.AMBIENTE, gc.COMP, gc.COMPARTIDO, gc.ORDEN
    ORDER BY d.APELLIDOS, d.NOMBRES, gc.ORDEN, g.MATERIA,
             g.GRUPO, g.[PLAN]
    ";

        $tA = microtime(true);
        $horarios = collect(DB::select($sqlHorarios, [
            'anio' => $anio,
            'periodo' => $periodo,
        ]));
        Log::info('Query horarios OK', [
            'segundos' => round(microtime(true) - $tA, 4),
            'filas' => $horarios->count(),
        ]);

        // ---------------------------------------------------------------
        // QUERY 2: Inscritos (independiente, tambien solo anio + periodo)
        // Separarla de la query de horarios evita que un JOIN pesado en
        // KARDEX_EXT bloquee o ralentice todo el resultado si algo falla
        // solo en esta parte.
        // ---------------------------------------------------------------
        $sqlInscritos = "
    SELECT
        g.ANIO, g.PERIODO, g.[PLAN], g.MATERIA, g.GRUPO,
        COUNT(k.ESTUDIANTE) AS INSCRITOS
    FROM GRUPOS g
    LEFT JOIN KARDEX_EXT k
        ON g.ANIO = k.ANIO AND g.PERIODO = k.PERIODO
        AND g.[PLAN] = k.[PLAN] AND g.MATERIA = k.MATERIA
        AND g.GRUPO = k.GRUPO
        AND (k.TIPO_EXAMEN = 'N' OR k.TIPO_EXAMEN IS NULL)
        AND k.CANCELADO IS NULL
    WHERE g.ANIO = CAST(:anio AS INT)
      AND g.PERIODO = CAST(:periodo AS INT)
      AND g.[PLAN] IN ('109401','125091','089801','126091','059801')
      AND g.PRIMARIO = 'Y'
      AND g.TIPO = 'N'
    GROUP BY g.ANIO, g.PERIODO, g.[PLAN], g.MATERIA, g.GRUPO
    ";

        $tB = microtime(true);
        $inscritos = collect(DB::select($sqlInscritos, [
            'anio' => $anio,
            'periodo' => $periodo,
        ]));
        Log::info('Query inscritos OK', [
            'segundos' => round(microtime(true) - $tB, 4),
            'filas' => $inscritos->count(),
        ]);

        // ---------------------------------------------------------------
        // Merge en PHP: mucho mas barato que hacerlo en SQL con dos
        // subconsultas anidadas, y mas facil de debuggear si algo falla.
        // ---------------------------------------------------------------
        $inscritosMap = $inscritos->keyBy(function ($row) {
            return $row->PLAN . '|' . $row->MATERIA . '|' . $row->GRUPO;
        });

        $data = $horarios->map(function ($row) use ($inscritosMap) {
            $key = $row->PLAN . '|' . $row->MATERIA . '|' . $row->GRUPO;
            $row->TOTAL_NORMAL = $inscritosMap->get($key)->INSCRITOS ?? 0;
            return $row;
        });

        // Filtro opcional por docente, ya en memoria (rapido, sin tocar la BD de nuevo)
        if ($docente) {
            $data = $data->filter(fn($row) => (string) $row->DOCENTE === (string) $docente)->values();
        }

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

        Log::info('========== FIN index() ==========', [
            'tiempo_total_segundos' => round(microtime(true) - $tInicio, 4),
            'docentes' => $grouped->count(),
        ]);

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
     *
     * FIX: el TOTAL_NORMAL ya no viene de NroInsMatGrpNE (tabla externa,
     * posible desactualizada). Ahora se calcula en una query separada
     * directo desde KARDEX_EXT, con el mismo filtro que usa
     * listaInscritos() (CANCELADO IS NULL, TIPO_EXAMEN = 'N'), y se
     * mergea en PHP. Se hace aparte y no con JOIN directo en el SQL
     * principal porque KARDEX_EXT tiene una fila por estudiante: si se
     * uniera directo contra el query de horarios, cada fila se
     * multiplicaría por estudiante ANTES del GROUP BY y el
     * SUM(CARGA_HORARIA) quedaría inflado.
     *
     * NUEVO: se devuelve GRUPOS.TIPO_INGRESO (columna "Tipo" del reporte).
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
            GRUPOS.TIPO_INGRESO,

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
            GRUPOS_COMPARTIDOS.ORDEN

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
            GRUPOS.TIPO_INGRESO,
            GRUPOS_COMPARTIDOS.COMP,
            GRUPOS_COMPARTIDOS.COMPARTIDO,
            GRUPOS_COMPARTIDOS.ORDEN

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

        // ── Inscritos NORMALES por docente+plan+materia+grupo ───────────────
        // Fuente directa KARDEX_EXT, excluyendo cancelados y solo
        // TIPO_EXAMEN = 'N' (igual que el "subtotal" regular de
        // listaInscritos(); los especiales de examen de mesa no entran
        // en este total, a propósito).
        $docenteFilterGrupos = $docente ? "AND GRUPOS.DOCENTE = :docente" : "";

        $sqlInscritosResumen = "
        SELECT
            GRUPOS.DOCENTE,
            GRUPOS.[PLAN],
            GRUPOS.MATERIA,
            GRUPOS.GRUPO,
            COUNT(KARDEX_EXT.ESTUDIANTE) AS TOTAL_NORMAL
        FROM GRUPOS
        INNER JOIN KARDEX_EXT
            ON KARDEX_EXT.ANIO     = GRUPOS.ANIO
            AND KARDEX_EXT.PERIODO = GRUPOS.PERIODO
            AND KARDEX_EXT.[PLAN]  = GRUPOS.[PLAN]
            AND KARDEX_EXT.MATERIA = GRUPOS.MATERIA
            AND KARDEX_EXT.GRUPO   = GRUPOS.GRUPO
        WHERE GRUPOS.ANIO    = :anio
          AND GRUPOS.PERIODO = :periodo
          AND GRUPOS.[PLAN]  IN ('109401','125091','089801','126091','059801')
          AND GRUPOS.PRIMARIO = 'Y'
          AND GRUPOS.TIPO      = 'N'
          AND KARDEX_EXT.CANCELADO   IS NULL
          AND KARDEX_EXT.TIPO_EXAMEN = 'N'
          $docenteFilterGrupos
        GROUP BY GRUPOS.DOCENTE, GRUPOS.[PLAN], GRUPOS.MATERIA, GRUPOS.GRUPO
        ";

        $inscritosResumen = collect(DB::select($sqlInscritosResumen, $bindings));

        $inscritosResumenMap = $inscritosResumen->keyBy(function ($row) {
            return $row->DOCENTE . '|' . $row->PLAN . '|' . $row->MATERIA . '|' . $row->GRUPO;
        });

        $data = $data->map(function ($row) use ($inscritosResumenMap) {
            $key = $row->DOCENTE . '|' . $row->PLAN . '|' . $row->MATERIA . '|' . $row->GRUPO;
            $row->TOTAL_NORMAL = $inscritosResumenMap->get($key)->TOTAL_NORMAL ?? 0;
            return $row;
        });

        unset($inscritosResumen, $inscritosResumenMap);

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
     *
     * FIX (materias/grupos sin inscritos no aparecían):
     * sqlDocentes y sqlMaterias arrancaban desde DOCENTES/GRUPOS pero
     * hacían INNER JOIN contra KARDEX_EXT, y ademas filtraban columnas
     * de KARDEX_EXT en el WHERE (CANCELADO IS NULL, TIPO_EXAMEN IN (...)).
     * Eso descartaba cualquier grupo sin ningun registro en KARDEX_EXT
     * -ej. una materia con 0 inscritos, como el caso de
     * "1302057 - TALLER GRP 52"-, porque ni siquiera llegaba a generar
     * una fila.
     *
     * La correccion: LEFT JOIN KARDEX_EXT con las condiciones de filtro
     * (CANCELADO, TIPO_EXAMEN) movidas al ON en vez del WHERE. Si se
     * dejan en el WHERE, SQL Server las evalua contra NULL cuando no hay
     * match y el LEFT JOIN se comporta como INNER JOIN de todas formas.
     * sqlInscritos se deja igual (INNER JOIN), porque ahi si solo
     * queremos filas cuando existe al menos un estudiante inscrito.
     *
     * NUEVO (columna C / compartidos): cada materia devuelve
     *   - comp (bool)            → COMP = 1  (hijo)  /  0 (padre o no compartida)
     *   - descripcion_comp       → GRUPOS_COMPARTIDOS.COMPARTIDO
     *   - orden_comp             → GRUPOS_COMPARTIDOS.ORDEN (M1, M2...). Dentro de un
     *                              docente, mismo ORDEN = mismo grupo compartido
     *                              (COMP 0 = origen, COMP 1 = derivadas). Es la
     *                              clave que usa el frontend (igual que el reporte
     *                              de horarios).
     *   - rol_comp               → 'padre' | 'hijo' | null
     *   - padre_clave            → "plan-cod_materia-grupo" del padre (solo hijos)
     *   - comparte_con[]         → materias con las que comparte (carrera, código,
     *                              nombre, grupo, rol)
     * Padre = COMP 0, hijo = COMP 1. Se relacionan por MATERIA + GRUPO entre
     * planes distintos:
     *   - Matriciales: el padre también tiene fila en GRUPOS_COMPARTIDOS.
     *   - "Comparte de XXX" simples: el padre es un grupo normal de GRUPOS en
     *     el plan que indica el texto (ADM, COM, ECO, FIN, CONTA→CCP).
     *
     * NUEVO (columna Tipo): cada materia devuelve además
     *   - tipo_ingreso           → GRUPOS.TIPO_INGRESO (null si no tiene)
     */
    public function listaInscritos(Request $request)
    {
        $periodoActual = $this->periodoActualSegunFecha();

        $anio = (int) $request->query('anio', $periodoActual['anio']);
        $periodo = (int) $request->query('periodo', $periodoActual['periodo']);
        $docente = $request->query('docente');

        if (!in_array($periodo, [1, 2, 3, 4], true)) {
            return response()->json([
                'message' => 'El parámetro periodo debe ser 1, 2, 3 o 4.',
            ], 422);
        }

        $docenteFilter = $docente ? "AND DOCENTES.CODIGO = :docente" : "";

        $bindings = [
            'anio' => $anio,
            'periodo' => $periodo,
        ];

        if ($docente) {
            $bindings['docente'] = $docente;
        }

        // El filtro de horario válido solo aplica a periodos regulares (1 y 2),
        // que tienen clases con horario asignado en HORARIOS2.
        // Los periodos 3 y 4 son exámenes de mesa / intersemestral: los alumnos
        // se inscriben directo en KARDEX_EXT sin que exista un grupo con horario
        // real cargado, así que exigir HORARIOS2 ahí descarta todo por error.
        $periodosConHorarioRegular = [1, 2];

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

        $filtroHorario = in_array($periodo, $periodosConHorarioRegular, true)
            ? "AND $horarioValidoExists"
            : "";

        // ── 1. Docentes involucrados ──────────────────────────────────────────
        // LEFT JOIN KARDEX_EXT (antes INNER): un docente con grupos asignados
        // pero sin ningun inscrito en KARDEX_EXT debe seguir apareciendo en
        // la lista. Las condiciones de KARDEX_EXT van en el ON, no en el WHERE.
        $sqlDocentes = "
    SELECT DISTINCT
        DOCENTES.CODIGO   AS COD_DOCENTE,
        DOCENTES.APELLIDOS,
        DOCENTES.NOMBRES
    FROM DOCENTES
    INNER JOIN GRUPOS
        ON DOCENTES.CODIGO = GRUPOS.DOCENTE
    LEFT JOIN KARDEX_EXT
        ON KARDEX_EXT.ANIO     = GRUPOS.ANIO
        AND KARDEX_EXT.PERIODO = GRUPOS.PERIODO
        AND KARDEX_EXT.[PLAN]  = GRUPOS.[PLAN]
        AND KARDEX_EXT.MATERIA = GRUPOS.MATERIA
        AND KARDEX_EXT.GRUPO   = GRUPOS.GRUPO
        AND KARDEX_EXT.CANCELADO   IS NULL
        AND KARDEX_EXT.TIPO_EXAMEN IN ('N', 'E')
    WHERE GRUPOS.ANIO            = :anio
      AND GRUPOS.PERIODO         = :periodo
      AND GRUPOS.[PLAN]          IN ('109401','125091','089801','126091','059801')
      AND GRUPOS.PRIMARIO        = 'Y'
      AND GRUPOS.TIPO            = 'N'
      $filtroHorario
      $docenteFilter
    ORDER BY DOCENTES.APELLIDOS, DOCENTES.NOMBRES
    ";

        $docentes = DB::select($sqlDocentes, $bindings);

        // ── 2. Materias por docente (conteo de inscritos, separado regular/especial) ──
        // Mismo fix: LEFT JOIN KARDEX_EXT con condiciones en el ON, para que
        // una materia/grupo sin inscritos siga generando su fila (con
        // SUBTOTAL_REGULAR y SUBTOTAL_ESPECIAL en 0 en vez de desaparecer).
        //
        // NUEVO: se agrega COMPARTIDO (descripción) con MAX() para no tocar
        // el GROUP BY, igual que ya se hacía con COMP.
        // NUEVO: TIPO_INGRESO (GRUPOS.TIPO_INGRESO) también con MAX().
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
        MAX(GRUPOS_COMPARTIDOS.COMP)       AS COMP,
        MAX(GRUPOS_COMPARTIDOS.COMPARTIDO) AS COMPARTIDO,
        MAX(GRUPOS_COMPARTIDOS.ORDEN)      AS ORDEN_COMP,
        MAX(GRUPOS.TIPO_INGRESO)           AS TIPO_INGRESO,
        SUM(CASE WHEN KARDEX_EXT.TIPO_EXAMEN = 'N' THEN 1 ELSE 0 END) AS SUBTOTAL_REGULAR,
        SUM(CASE WHEN KARDEX_EXT.TIPO_EXAMEN = 'E' THEN 1 ELSE 0 END) AS SUBTOTAL_ESPECIAL
    FROM DOCENTES
    INNER JOIN GRUPOS
        ON DOCENTES.CODIGO = GRUPOS.DOCENTE
    LEFT JOIN KARDEX_EXT
        ON KARDEX_EXT.ANIO     = GRUPOS.ANIO
        AND KARDEX_EXT.PERIODO = GRUPOS.PERIODO
        AND KARDEX_EXT.[PLAN]  = GRUPOS.[PLAN]
        AND KARDEX_EXT.MATERIA = GRUPOS.MATERIA
        AND KARDEX_EXT.GRUPO   = GRUPOS.GRUPO
        AND KARDEX_EXT.CANCELADO   IS NULL
        AND KARDEX_EXT.TIPO_EXAMEN IN ('N', 'E')
    INNER JOIN MATERIAS
        ON GRUPOS.ANIO     = MATERIAS.ANIO
        AND GRUPOS.PERIODO = MATERIAS.PERIODO
        AND GRUPOS.[PLAN]  = MATERIAS.[PLAN]
        AND GRUPOS.MATERIA = MATERIAS.CODIGO
    LEFT JOIN GRUPOS_COMPARTIDOS
        ON GRUPOS.[PLAN]     = GRUPOS_COMPARTIDOS.[PLAN]
        AND GRUPOS.MATERIA   = GRUPOS_COMPARTIDOS.MATERIA
        AND GRUPOS.GRUPO     = GRUPOS_COMPARTIDOS.GRUPO
        AND GRUPOS.PRIMARIO  = GRUPOS_COMPARTIDOS.PRIMARIO
    WHERE GRUPOS.ANIO            = :anio
      AND GRUPOS.PERIODO         = :periodo
      AND GRUPOS.[PLAN]          IN ('109401','125091','089801','126091','059801')
      AND GRUPOS.PRIMARIO        = 'Y'
      AND GRUPOS.TIPO            = 'N'
      $filtroHorario
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

        // ── 2b. Relaciones padre / hijo de grupos compartidos ─────────────────
        // Sin filtro de docente ni de horario: el padre puede ser de otro
        // docente (o de otra carrera) y aun así hay que mostrarlo.
        $sqlCompartidos = "
    SELECT
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
        MAX(GRUPOS_COMPARTIDOS.COMP)       AS COMP,
        MAX(GRUPOS_COMPARTIDOS.COMPARTIDO) AS COMPARTIDO
    FROM GRUPOS
    INNER JOIN MATERIAS
        ON GRUPOS.ANIO     = MATERIAS.ANIO
        AND GRUPOS.PERIODO = MATERIAS.PERIODO
        AND GRUPOS.[PLAN]  = MATERIAS.[PLAN]
        AND GRUPOS.MATERIA = MATERIAS.CODIGO
    INNER JOIN GRUPOS_COMPARTIDOS
        ON GRUPOS.[PLAN]     = GRUPOS_COMPARTIDOS.[PLAN]
        AND GRUPOS.MATERIA   = GRUPOS_COMPARTIDOS.MATERIA
        AND GRUPOS.GRUPO     = GRUPOS_COMPARTIDOS.GRUPO
        AND GRUPOS.PRIMARIO  = GRUPOS_COMPARTIDOS.PRIMARIO
    WHERE GRUPOS.ANIO     = :anio
      AND GRUPOS.PERIODO  = :periodo
      AND GRUPOS.[PLAN]   IN ('109401','125091','089801','126091','059801')
      AND GRUPOS.PRIMARIO = 'Y'
      AND GRUPOS.TIPO     = 'N'
    GROUP BY
        GRUPOS.[PLAN],
        MATERIAS.CODIGO,
        MATERIAS.NOMBRE,
        GRUPOS.GRUPO
    ";

        $filasCompartidos = DB::select($sqlCompartidos, [
            'anio' => $anio,
            'periodo' => $periodo,
        ]);

        // Clusters: "MATERIA|GRUPO" => miembros (uno por plan)
        $clusters = [];
        foreach ($filasCompartidos as $r) {
            $clusters["{$r->COD_MATERIA}|{$r->GRUPO}"][] = [
                'plan' => $r->PLAN,
                'carrera' => $r->CARRERA,
                'cod_materia' => $r->COD_MATERIA,
                'nom_materia' => $r->NOM_MATERIA,
                'grupo' => $r->GRUPO,
                'comp' => (int) ($r->COMP ?? 0) === 1,
                'descripcion' => trim((string) ($r->COMPARTIDO ?? '')),
            ];
        }
        unset($filasCompartidos);

        // En el texto "Comparte de CONTA" la carrera no siempre usa el mismo
        // código que CARRERA (CONTA → CCP).
        $aliasCarrera = ['CONTA' => 'CCP', 'CONTADURIA' => 'CCP'];
        $planPorCarrera = [
            'ECO' => '059801',
            'ADM' => '109401',
            'CCP' => '089801',
            'COM' => '125091',
            'FIN' => '126091',
        ];

        // "Matricial ADM-COM - Comparte de ADM" / "Comparte de CONTA" → 'ADM' / 'CCP'
        $carreraDeTexto = function (string $texto) use ($aliasCarrera): ?string {
            if (!preg_match('/Comparte\s+de\s+([A-Za-z]+)/u', $texto, $mt)) {
                return null;
            }
            $cod = strtoupper($mt[1]);
            return $aliasCarrera[$cod] ?? $cod;
        };

        $relaciones = [];  // "plan|cod_materia|grupo" => info de relación
        $huerfanos = [];   // hijos cuyo padre NO tiene fila en GRUPOS_COMPARTIDOS

        foreach ($clusters as $miembros) {
            // 1) Padre de cada hijo dentro del cluster (matriciales: el padre
            //    también tiene su fila en GRUPOS_COMPARTIDOS con COMP = 0)
            $padreDe = []; // índice del hijo => índice del padre (o null)
            foreach ($miembros as $i => $m) {
                if (!$m['comp']) {
                    continue;
                }
                $candidatos = [];
                foreach ($miembros as $j => $p) {
                    if (!$p['comp'] && $p['plan'] !== $m['plan']) {
                        $candidatos[] = $j;
                    }
                }
                $elegido = $candidatos[0] ?? null;
                if (count($candidatos) > 1) {
                    $cod = $carreraDeTexto($m['descripcion']);
                    if ($cod !== null) {
                        foreach ($candidatos as $j) {
                            if ($miembros[$j]['carrera'] === $cod) {
                                $elegido = $j;
                                break;
                            }
                        }
                    }
                }
                $padreDe[$i] = $elegido;
            }

            // 2) Info de relación por miembro
            foreach ($miembros as $i => $m) {
                $kRel = "{$m['plan']}|{$m['cod_materia']}|{$m['grupo']}";

                if ($m['comp']) {
                    $j = $padreDe[$i] ?? null;
                    $relaciones[$kRel] = [
                        'rol' => 'hijo',
                        'padre_clave' => $j !== null
                            ? "{$miembros[$j]['plan']}-{$miembros[$j]['cod_materia']}-{$miembros[$j]['grupo']}"
                            : null,
                        'comparte_con' => $j !== null
                            ? [$this->formatoRelacionComp($miembros[$j], 'padre')]
                            : [],
                    ];
                    if ($j === null) {
                        $huerfanos[] = $m;
                    }
                } else {
                    $hijos = [];
                    foreach ($padreDe as $iHijo => $jPadre) {
                        if ($jPadre === $i) {
                            $hijos[] = $this->formatoRelacionComp($miembros[$iHijo], 'hijo');
                        }
                    }
                    if ($hijos) {
                        $relaciones[$kRel] = [
                            'rol' => 'padre',
                            'padre_clave' => null,
                            'comparte_con' => $hijos,
                        ];
                    }
                }
            }
        }
        unset($clusters);

        // 3) Hijos "simples" ("Comparte de ADM", "Comparte de CONTA"...):
        //    el padre es un grupo normal de GRUPOS (sin fila en
        //    GRUPOS_COMPARTIDOS). Se busca en el plan que indica el texto,
        //    con la misma MATERIA y el mismo GRUPO.
        if ($huerfanos) {
            $sqlGruposPlanes = "
    SELECT
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
        GRUPOS.GRUPO
    FROM GRUPOS
    INNER JOIN MATERIAS
        ON GRUPOS.ANIO     = MATERIAS.ANIO
        AND GRUPOS.PERIODO = MATERIAS.PERIODO
        AND GRUPOS.[PLAN]  = MATERIAS.[PLAN]
        AND GRUPOS.MATERIA = MATERIAS.CODIGO
    WHERE GRUPOS.ANIO    = :anio
      AND GRUPOS.PERIODO = :periodo
      AND GRUPOS.[PLAN]  IN ('109401','125091','089801','126091','059801')
      AND GRUPOS.TIPO    = 'N'
    GROUP BY
        GRUPOS.[PLAN],
        MATERIAS.CODIGO,
        MATERIAS.NOMBRE,
        GRUPOS.GRUPO
    ";

            $gruposMap = [];
            foreach (DB::select($sqlGruposPlanes, ['anio' => $anio, 'periodo' => $periodo]) as $g) {
                $gruposMap["{$g->PLAN}|{$g->COD_MATERIA}|{$g->GRUPO}"] = [
                    'plan' => $g->PLAN,
                    'carrera' => $g->CARRERA,
                    'cod_materia' => $g->COD_MATERIA,
                    'nom_materia' => $g->NOM_MATERIA,
                    'grupo' => $g->GRUPO,
                ];
            }

            foreach ($huerfanos as $h) {
                $carreraPadre = $carreraDeTexto($h['descripcion']);
                $planPadre = $carreraPadre !== null ? ($planPorCarrera[$carreraPadre] ?? null) : null;
                if ($planPadre === null || $planPadre === $h['plan']) {
                    continue;
                }

                $padre = $gruposMap["{$planPadre}|{$h['cod_materia']}|{$h['grupo']}"] ?? null;
                if ($padre === null) {
                    continue;
                }

                $kHijo = "{$h['plan']}|{$h['cod_materia']}|{$h['grupo']}";
                $relaciones[$kHijo]['padre_clave'] = "{$padre['plan']}-{$padre['cod_materia']}-{$padre['grupo']}";
                $relaciones[$kHijo]['comparte_con'] = [$this->formatoRelacionComp($padre, 'padre')];

                $kPadre = "{$padre['plan']}|{$padre['cod_materia']}|{$padre['grupo']}";
                if (!isset($relaciones[$kPadre])) {
                    $relaciones[$kPadre] = ['rol' => 'padre', 'padre_clave' => null, 'comparte_con' => []];
                }
                if ($relaciones[$kPadre]['rol'] === 'padre') {
                    $relaciones[$kPadre]['comparte_con'][] = $this->formatoRelacionComp($h, 'hijo');
                }
            }
            unset($gruposMap);
        }
        unset($huerfanos);

        // ── 3. Inscritos por materia ──────────────────────────────────────────
        // Se deja INNER JOIN a propósito: aquí solo interesa listar
        // estudiantes reales, no generar filas "vacías" por estudiante.
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
      $filtroHorario
      $docenteFilter
    ORDER BY
        DOCENTES.CODIGO,
        GRUPOS.[PLAN],
        MATERIAS.CODIGO,
        BIOGRAFICOS.APELLIDOS,
        BIOGRAFICOS.NOMBRES
    ";

        $inscritos = DB::select($sqlInscritos, $bindings);

        // ── 4. Armar estructura en PHP ──────────────────────────────────────
        $inscritosMap = [];
        foreach ($inscritos as $ins) {
            $key = "{$ins->COD_DOCENTE}|{$ins->PLAN}|{$ins->COD_MATERIA}|{$ins->GRUPO}";
            $bucket = $ins->TIPO_EXAMEN === 'E' ? 'especiales' : 'regulares';
            $inscritosMap[$key][$bucket][] = [
                'codigo' => $ins->COD_ESTUDIANTE,
                'nombre' => $ins->NOM_ESTUDIANTE,
            ];
        }
        unset($inscritos);

        $materiasMap = [];
        foreach ($materias as $mat) {
            $materiasMap[$mat->COD_DOCENTE][] = $mat;
        }
        unset($materias);

        $data = [];
        foreach ($docentes as $doc) {
            $codDoc = $doc->COD_DOCENTE;
            $carreras = [];
            $totalDocente = 0;
            $totalEspecialesDocente = 0;

            $porPlan = [];
            foreach (($materiasMap[$codDoc] ?? []) as $mat) {
                $porPlan[$mat->PLAN][] = $mat;
            }

            foreach ($porPlan as $plan => $mats) {
                $materiasArr = [];
                $subtotalCarrera = 0;
                $subtotalCarreraEspeciales = 0;

                foreach ($mats as $mat) {
                    $key = "{$codDoc}|{$mat->PLAN}|{$mat->COD_MATERIA}|{$mat->GRUPO}";
                    $listaRegulares = $inscritosMap[$key]['regulares'] ?? [];
                    $listaEspeciales = $inscritosMap[$key]['especiales'] ?? [];

                    // Descripción del compartido (ej. "Matricial ADM-ECO - Comparte de ADM").
                    // Puede venir NULL o vacía; en ese caso se devuelve null.
                    $descripcionComp = isset($mat->COMPARTIDO) ? trim((string) $mat->COMPARTIDO) : '';
                    $ordenComp = isset($mat->ORDEN_COMP) ? trim((string) $mat->ORDEN_COMP) : '';
                    $tipoIngreso = isset($mat->TIPO_INGRESO) ? trim((string) $mat->TIPO_INGRESO) : '';
                    $rel = $relaciones["{$mat->PLAN}|{$mat->COD_MATERIA}|{$mat->GRUPO}"] ?? null;

                    $materiasArr[] = [
                        'cod_materia' => $mat->COD_MATERIA,
                        'nom_materia' => $mat->NOM_MATERIA,
                        'grupo' => $mat->GRUPO,
                        'tipo_ingreso' => $tipoIngreso !== '' ? $tipoIngreso : null,
                        'comp' => (int) ($mat->COMP ?? 0) === 1,
                        'descripcion_comp' => $descripcionComp !== '' ? $descripcionComp : null,
                        'orden_comp' => $ordenComp !== '' ? $ordenComp : null,
                        'rol_comp' => $rel['rol'] ?? null,
                        'padre_clave' => $rel['padre_clave'] ?? null,
                        'comparte_con' => $rel['comparte_con'] ?? [],
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
                'total_inscritos' => $totalDocente,
                'total_examen_mesa' => $totalEspecialesDocente,
            ];
        }

        unset($inscritosMap, $materiasMap, $docentes, $relaciones);

        return response()->json([
            'anio' => $anio,
            'periodo' => $periodo,
            'data' => $data,
            'total_docentes' => count($data),
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
    /**
     * Formato de una materia relacionada (para "comparte_con").
     */
    private function formatoRelacionComp(array $m, string $rol): array
    {
        return [
            'rol' => $rol,
            'carrera' => $m['carrera'],
            'cod_materia' => $m['cod_materia'],
            'nom_materia' => $m['nom_materia'],
            'grupo' => $m['grupo'],
        ];
    }

    /**
     * Determina el período académico y año que corresponden a HOY,
     * usando los rangos de `periodos_academicos` (los mismos que edita
     * el admin en /periodos-academicos). Se usa como valor por defecto
     * cuando el request no especifica anio/periodo explícitamente.
     */
    private function periodoActualSegunFecha(): array
    {
        $rangos = PeriodoAcademico::obtenerRangos(); // ['1'=>['inicio'=>'MM-DD','fin'=>'MM-DD'], ...]
        $hoy = now();
        $anio = (int) $hoy->format('Y');
        $mesDia = $hoy->format('m-d');

        // Puede haber más de un periodo cuyo rango contiene la fecha de hoy
        // (los rangos se solapan a propósito, ver comentario original de
        // rangosPeriodos()). En ese caso gana el que empezó más recientemente.
        $candidatos = [];
        foreach ($rangos as $periodo => $rango) {
            if ($mesDia >= $rango['inicio'] && $mesDia <= $rango['fin']) {
                $candidatos[$periodo] = $rango['inicio'];
            }
        }

        if (empty($candidatos)) {
            // No cae en ningún rango definido (ej. vacaciones de fin de año,
            // entre el cierre de Semestre II y el inicio de Verano).
            // Ajusta este default si prefieres otro comportamiento.
            return ['periodo' => 2, 'anio' => $anio];
        }

        arsort($candidatos);
        $periodo = (int) array_key_first($candidatos);

        return ['periodo' => $periodo, 'anio' => $anio];
    }

}