<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class ReporteDocenteController extends Controller
{
    /**
     * Convierte (anio, periodo) a un valor numérico ordenable.
     * Orden académico real: 1 (anual/1) → 4 (invierno) → 2 → 3 (verano)
     * Ej: 2016/1 → 20161 | 2016/4 → 20162 | 2016/2 → 20163 | 2016/3 → 20164
     */
    private function ordenTemporal(?int $anio, ?string $periodo): ?int
    {
        if (!$anio)
            return null;

        $ordenPeriodo = match ($periodo) {
            '1' => 1,
            '4' => 2,
            '2' => 3,
            '3' => 4,
            default => 1, // si no se especifica periodo, se toma desde el inicio del año
        };

        return ($anio * 10) + $ordenPeriodo;
    }

    /**
     * Rangos aproximados de cada periodo académico (mes-día).
     * Ajustables sin tocar el resto de la lógica.
     *
     * 3 -> Curso de Verano       (05 ene - 20 feb)
     * 1 -> Semestre I            (10 feb - 30 jun)
     * 4 -> Curso de Invierno     (01 jul - 15 ago, margen hasta mediados de agosto)
     * 2 -> Semestre II + cierre  (05 ago - 20 dic)
     */
    private function rangosPeriodos(): array
    {
        return [
            '3' => ['inicio' => '01-05', 'fin' => '02-20'],
            '1' => ['inicio' => '02-10', 'fin' => '06-30'],
            '4' => ['inicio' => '07-01', 'fin' => '08-15'],
            '2' => ['inicio' => '08-05', 'fin' => '12-20'],
        ];
    }

    /**
     * Determina qué combinaciones (anio-periodo) AÚN NO HAN CONCLUIDO
     * según la fecha actual del servidor, para excluirlas automáticamente
     * del reporte de materias dictadas.
     * Revisa el año actual y el siguiente (cubre el caso de que ya haya
     * datos cargados de verano del próximo año en diciembre).
     *
     * Reemplaza el antiguo filtro estático NOT IN ('2026-1').
     *
     * @return array Lista de strings "anio-periodo", ej: ['2026-1', '2026-2']
     */
    private function periodosNoConcluidos(): array
    {
        $hoy = now();
        $anioActual = (int) $hoy->format('Y');
        $rangos = $this->rangosPeriodos();

        $noConcluidos = [];

        foreach ([$anioActual, $anioActual + 1] as $anio) {
            foreach ($rangos as $periodo => $r) {
                $fin = Carbon::createFromFormat('Y-m-d', "{$anio}-{$r['fin']}")->endOfDay();

                if ($hoy->lte($fin)) {
                    // El periodo termina hoy o después -> todavía no concluye
                    $noConcluidos[] = "{$anio}-{$periodo}";
                }
            }
        }

        return $noConcluidos;
    }

    public function materiasDictadas(Request $request)
    {
        $request->validate([
            'docente' => 'required|numeric',
            'anio' => 'nullable|numeric',
            'periodo' => 'nullable|string|in:1,2,3,4',
            'anio_hasta' => 'nullable|numeric',
            'periodo_hasta' => 'nullable|string|in:1,2,3,4',
            'materia' => 'nullable|string|max:60',
            'grupo' => 'nullable|string|max:2',

            // ── Habilitación puntual de un periodo restringido ──────────────
            // El frontend manda esto SOLO cuando el usuario hace click en
            // "habilitar" para ese año/periodo específico. Si no se manda,
            // el comportamiento sigue siendo el de siempre (oculta lo no
            // concluido). No habilita todo lo pendiente, solo el que se
            // indique en anio_habilitado + periodo_habilitado.
            'habilitar_restriccion' => 'nullable|boolean',
            'anio_habilitado' => 'required_if:habilitar_restriccion,true|nullable|numeric',
            'periodo_habilitado' => 'required_if:habilitar_restriccion,true|nullable|string|in:1,2,3,4',
        ]);

        $docente = $request->docente;
        $anio = $request->anio;
        $periodo = $request->periodo;
        $anioHasta = $request->anio_hasta;
        $periodoHasta = $request->periodo_hasta;
        $materia = $request->materia;
        $grupo = $request->grupo;

        $habilitarRestriccion = $request->boolean('habilitar_restriccion');
        $anioHabilitado = $request->anio_habilitado;
        $periodoHabilitado = $request->periodo_habilitado;

        // CONSULTAR DOCENTE
        $docenteInfo = DB::connection('sqlsrv')->selectOne("
        SELECT 
            CODIGO,
            NOMBRES,
            APELLIDOS
        FROM DOCENTES
        WHERE CODIGO = ?
    ", [$docente]);

        if (!$docenteInfo) {
            return response()->json([
                'success' => false,
                'message' => 'Docente no encontrado'
            ], 404);
        }

        // ── Rango temporal (anio + periodo → anio_hasta + periodo_hasta) ──────
        // Si solo viene anio (sin periodo y sin anio_hasta), se filtra el año completo.
        $ordenDesde = null;
        $ordenHasta = null;
        $filtroSoloAnioExacto = false;

        if ($anio && !$periodo && !$anioHasta) {
            // Caso simple de siempre: un solo año, sin periodo ni rango
            $filtroSoloAnioExacto = true;
        } elseif ($anio) {
            $ordenDesde = $this->ordenTemporal((int) $anio, $periodo);
            $ordenHasta = $anioHasta
                ? $this->ordenTemporal((int) $anioHasta, $periodoHasta ?? '3') // '3' = verano, último periodo del año
                : $this->ordenTemporal((int) $anio, '3'); // si no hay anio_hasta, tope = fin del mismo año
        }

        // ── Materia: código exacto (numérico) o búsqueda por nombre ───────────
        $materiaEsCodigo = $materia && preg_match('/^\d+$/', $materia);

        // FILTROS OPCIONALES (SQL)
        $filtroAnioExacto = $filtroSoloAnioExacto ? "AND GRUPOS.ANIO = :anio" : "";

        $filtroRango = ($ordenDesde !== null)
            ? "AND (
                (GRUPOS.ANIO * 10 + CASE
                    WHEN GRUPOS.PERIODO = '1' THEN 1
                    WHEN GRUPOS.PERIODO = '4' THEN 2
                    WHEN GRUPOS.PERIODO = '2' THEN 3
                    WHEN GRUPOS.PERIODO = '3' THEN 4
                    ELSE 1
                END)
                BETWEEN :orden_desde AND :orden_hasta
            )"
            : "";

        $filtroMateria = "";
        if ($materia) {
            $filtroMateria = $materiaEsCodigo
                ? "AND GRUPOS.MATERIA = :materia"
                : "AND MATERIAS.NOMBRE LIKE :materia_like";
        }

        $filtroGrupo = $grupo ? "AND GRUPOS.GRUPO = :grupo" : "";

        $bindings = ['docente' => $docente];

        if ($filtroSoloAnioExacto) {
            $bindings['anio'] = $anio;
        }
        if ($ordenDesde !== null) {
            $bindings['orden_desde'] = $ordenDesde;
            $bindings['orden_hasta'] = $ordenHasta;
        }
        if ($materia) {
            if ($materiaEsCodigo) {
                $bindings['materia'] = $materia;
            } else {
                $bindings['materia_like'] = '%' . $materia . '%';
            }
        }
        if ($grupo) {
            $bindings['grupo'] = $grupo;
        }

        // ── Exclusión dinámica de periodos aún no concluidos ───────────────────
        // Reemplaza el antiguo filtro estático NOT IN ('2026-1').
        //
        // Comportamiento:
        // - Por defecto: se ocultan TODOS los periodos aún no concluidos.
        // - Si habilitar_restriccion=true y viene anio_habilitado/periodo_habilitado,
        //   se libera SOLO esa combinación puntual (si efectivamente estaba
        //   restringida); el resto de periodos no concluidos sigue oculto.
        $noConcluidos = $this->periodosNoConcluidos();
        $claveHabilitada = null;
        $restriccionFueHabilitada = false;

        if ($habilitarRestriccion && $anioHabilitado && $periodoHabilitado) {
            $claveHabilitada = "{$anioHabilitado}-{$periodoHabilitado}";

            if (in_array($claveHabilitada, $noConcluidos, true)) {
                // Se remueve únicamente esa combinación de la lista de exclusión
                $noConcluidos = array_values(array_diff($noConcluidos, [$claveHabilitada]));
                $restriccionFueHabilitada = true;
            }
            // Si $claveHabilitada no estaba en $noConcluidos (ej. ya concluyó
            // o el año/periodo no existe como restricción), no se hace nada:
            // no tiene sentido "habilitar" algo que no estaba restringido.
        }

        $filtroNoConcluidos = "";

        if (!empty($noConcluidos)) {
            $placeholders = [];
            foreach ($noConcluidos as $i => $valor) {
                $key = "excl_{$i}";
                $placeholders[] = ":{$key}";
                $bindings[$key] = $valor;
            }

            $filtroNoConcluidos = "AND (
                CONVERT(VARCHAR(4), GRUPOS.ANIO) + '-' + GRUPOS.PERIODO
            ) NOT IN (" . implode(',', $placeholders) . ")";
        }

        $materias = DB::connection('sqlsrv')->select("

        SELECT
            ROW_NUMBER() OVER (
                ORDER BY 
                    GRUPOS.ANIO,

                    CASE
                        WHEN GRUPOS.PERIODO = '1' THEN 1
                        WHEN GRUPOS.PERIODO = '4' THEN 2
                        WHEN GRUPOS.PERIODO = '2' THEN 3
                        WHEN GRUPOS.PERIODO = '3' THEN 4
                        ELSE 5
                    END,

                    GRUPOS.GRUPO ASC,
                    GRUPOS.MATERIA,
                    GRUPOS.[PLAN]
            ) AS nro,

            DOCENTES.CODIGO,

            (DOCENTES.APELLIDOS + ' ' + DOCENTES.NOMBRES) AS docente,

            CONVERT(VARCHAR(4), GRUPOS.ANIO) + '/' +

            CASE
                WHEN GRUPOS.PERIODO = '3' THEN '3 - Verano'
                WHEN GRUPOS.PERIODO = '4' THEN '4 - Invierno'
                ELSE GRUPOS.PERIODO
            END AS gestion,

            CASE
                WHEN [PLANES].NOMBRE LIKE '%ADMINISTRACION%' THEN 'ADM'
                WHEN [PLANES].NOMBRE LIKE '%COMERCIAL%' THEN 'COM'
                WHEN [PLANES].NOMBRE LIKE '%FINANCIERA%' THEN 'FIN'
                WHEN [PLANES].NOMBRE LIKE '%ECONOMIA%' THEN 'ECO'
                WHEN [PLANES].NOMBRE LIKE '%CONTADURIA%' THEN 'CON'
                ELSE LEFT([PLANES].NOMBRE, 3)
            END AS plan_abrev,

            GRUPOS.MATERIA + ' ' + ISNULL(MATERIAS.NOMBRE, 'SIN NOMBRE') AS materia,

            CASE
                WHEN GRUPOS.GRUPO > '30'
                     AND GRUPOS.PERIODO IN ('3','4')
                THEN 'COMPARTIDO'
                ELSE ''
            END AS compartido,

            GRUPOS.GRUPO AS grp,
            GRUPOS.RESOLUCION,
            GRUPOS.DESIGNACION,
            GRUPOS.TIEMPO,
            GRUPOS.TIPO_INGRESO, 
            GRUPOS.ANIO,
            GRUPOS.PERIODO,
            GRUPOS.[PLAN]

        FROM GRUPOS

        INNER JOIN DOCENTES
            ON DOCENTES.CODIGO = GRUPOS.DOCENTE

        INNER JOIN MATERIAS
            ON MATERIAS.CODIGO = GRUPOS.MATERIA
            AND MATERIAS.ANIO = GRUPOS.ANIO
            AND MATERIAS.PERIODO = GRUPOS.PERIODO
            AND MATERIAS.[PLAN] = GRUPOS.[PLAN]

        LEFT JOIN [PLANES]
            ON [PLANES].CODIGO = GRUPOS.[PLAN]
            AND [PLANES].ANIO = GRUPOS.ANIO

        WHERE DOCENTES.CODIGO = :docente
          AND GRUPOS.PRIMARIO = 'Y'
          AND GRUPOS.TIPO = 'N'

          {$filtroNoConcluidos}

          {$filtroAnioExacto}
          {$filtroRango}
          {$filtroMateria}
          {$filtroGrupo}

        ORDER BY
            GRUPOS.ANIO,

            CASE
                WHEN GRUPOS.PERIODO = '1' THEN 1
                WHEN GRUPOS.PERIODO = '4' THEN 2
                WHEN GRUPOS.PERIODO = '2' THEN 3
                WHEN GRUPOS.PERIODO = '3' THEN 4
                ELSE 5
            END ASC,

            GRUPOS.GRUPO ASC,
            GRUPOS.MATERIA,
            GRUPOS.[PLAN]

    ", $bindings);

        return response()->json([
            'success' => true,

            'docente' => [
                'codigo' => $docenteInfo->CODIGO,
                'nombre' => trim(
                    $docenteInfo->APELLIDOS . ' ' .
                    $docenteInfo->NOMBRES
                ),
            ],

            'anio_filtro' => $anio ?? 'todos',
            'periodo_filtro' => $periodo ?? null,
            'anio_hasta_filtro' => $anioHasta ?? null,
            'periodo_hasta_filtro' => $periodoHasta ?? null,
            'materia_filtro' => $materia ?? 'todas',
            'grupo_filtro' => $grupo ?? 'todos',

            // Info de la restricción, útil para que el frontend muestre
            // el botón de "habilitar" solo cuando aplica.
            'restriccion' => [
                'periodos_no_concluidos' => $this->periodosNoConcluidos(),
                'habilitacion_solicitada' => $claveHabilitada,
                'habilitacion_aplicada' => $restriccionFueHabilitada,
            ],

            'total' => count($materias),

            'materias' => $materias,
        ]);
    }


    public function horario(Request $request): JsonResponse
    {
        // ─── 1. Gestión activa ────────────────────────────────────────────
        $anio = 2026;
        $periodo = 1;

        // ─── 2. Filtro opcional por docente ──────────────────────────────
        $docenteFiltro = $request->input('docente'); // null = todos

        // ─── 3. Consulta SQL Server 2022 ─────────────────────────────────
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
                WHEN [HORARIOS2].[TIPO] = 'C' THEN ''
                WHEN [HORARIOS2].[TIPO] = 'P' THEN '[AUX]'
                ELSE 'N'
            END AS TIPO2,

            GRUPOS.GRUPO,

            SUM(
                CASE
                    WHEN [HORARIOS2].[HORA] > 0
                         AND [HORARIOS2].[GRUPO] = 'NN'
                    THEN 8
                    ELSE 2
                END
            ) AS CARGA_HORARIA,

            GRUPOS_COMPARTIDOS.COMP,
            GRUPOS_COMPARTIDOS.COMPARTIDO,
            GRUPOS_COMPARTIDOS.ORDEN

        FROM HORARIOS2

        INNER JOIN GRUPOS
            ON  HORARIOS2.ANIO    = GRUPOS.ANIO
            AND HORARIOS2.PERIODO = GRUPOS.PERIODO
            AND HORARIOS2.MATERIA = GRUPOS.MATERIA
            AND HORARIOS2.GRUPO   = GRUPOS.GRUPO
            AND HORARIOS2.DOCENTE = GRUPOS.DOCENTE

        INNER JOIN MATERIAS
            ON  GRUPOS.ANIO    = MATERIAS.ANIO
            AND GRUPOS.PERIODO = MATERIAS.PERIODO
            AND GRUPOS.[PLAN]  = MATERIAS.[PLAN]
            AND GRUPOS.MATERIA = MATERIAS.CODIGO

        INNER JOIN DOCENTES
            ON  HORARIOS2.DOCENTE = DOCENTES.CODIGO

        LEFT OUTER JOIN GRUPOS_COMPARTIDOS
            ON  GRUPOS.[PLAN]    = GRUPOS_COMPARTIDOS.[PLAN]
            AND GRUPOS.MATERIA   = GRUPOS_COMPARTIDOS.MATERIA
            AND GRUPOS.GRUPO     = GRUPOS_COMPARTIDOS.GRUPO
            AND GRUPOS.PRIMARIO  = GRUPOS_COMPARTIDOS.PRIMARIO

        WHERE
            HORARIOS2.ANIO     = :anio
            AND HORARIOS2.PERIODO  = :periodo
            AND HORARIOS2.TIPO     IN ('C')
            AND GRUPOS.[PLAN]      IN ('109401', '125091', '089801', '126091', '059801')
            AND GRUPOS.TIPO        = 'N'
            AND GRUPOS.PRIMARIO    IN ('Y')
            AND HORARIOS2.HORA     NOT IN (730, 900, 1030, 1200, 1330, 1500, 1630, 1800, 1930, 2100)
            " . ($docenteFiltro ? "AND HORARIOS2.DOCENTE = :docente" : "") . "

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

        $bindings = [
            'anio' => $anio,
            'periodo' => $periodo,
        ];

        if ($docenteFiltro) {
            $bindings['docente'] = (string) $docenteFiltro;
        }

        // Usar la conexión SQL Server (sqlsrv)
        $filas = DB::connection('sqlsrv')->select($sql, $bindings);

        // ─── 4. Agrupar por docente ───────────────────────────────────────
        $docentesMap = [];

        foreach ($filas as $fila) {
            $codigo = $fila->DOCENTE;

            if (!isset($docentesMap[$codigo])) {
                $docentesMap[$codigo] = [
                    'codigo' => $codigo,
                    'apellidos' => $fila->APELLIDOS,
                    'nombres' => $fila->NOMBRES,
                    'materias' => [],
                ];
            }

            $docentesMap[$codigo]['materias'][] = [
                'plan' => $fila->PLAN,
                'carrera' => $fila->CARRERA,
                'nivel' => $fila->NIVEL,
                'materia' => $fila->MATERIA,
                'nombre' => $fila->NOMBRE,
                'tipo' => $fila->TIPO,
                'tipo2' => $fila->TIPO2,
                'grupo' => $fila->GRUPO,
                'carga_horaria' => (int) $fila->CARGA_HORARIA,
                'comp' => $fila->COMP,
                'compartido' => $fila->COMPARTIDO,
                'orden' => $fila->ORDEN,
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



    /**
     * Segunda versión de materiasDictadas().
     *
     * Diferencia clave respecto al original:
     * - Se agrega LEFT JOIN a GRUPOS_COMPARTIDOS (igual que en el endpoint
     *   de horarios) para saber, de forma real, si un grupo/materia es
     *   compartido y con qué se comparte.
     * - Se agrega la columna `comparte`, que trae el dato de GRUPOS_COMPARTIDOS
     *   (GC.COMPARTIDO) en vez de la heurística vieja (GRUPO > '30' AND PERIODO IN ('3','4')).
     * - Se deja la columna vieja `compartido` tal cual, para no romper nada
     *   que ya dependa de ella en el frontend. Puedes eliminarla después de
     *   validar que `comparte` la reemplaza correctamente.
     *
     * OJO: asumí que GC.COMPARTIDO trae el valor útil a mostrar (código o
     * nombre de la materia/grupo con el que se comparte) y que GC.COMP es
     * el flag "sí/no comparte". Si la semántica real de esos campos es otra,
     * ajusta el SELECT de abajo (marcado con //*** AJUSTAR ***).
     */
    public function materiasDictadasCompartidas(Request $request)
    {
        $request->validate([
            'docente' => 'required|numeric',
            'anio' => 'nullable|numeric',
            'periodo' => 'nullable|string|in:1,2,3,4',
            'anio_hasta' => 'nullable|numeric',
            'periodo_hasta' => 'nullable|string|in:1,2,3,4',
            'materia' => 'nullable|string|max:60',
            'grupo' => 'nullable|string|max:2',

            'habilitar_restriccion' => 'nullable|boolean',
            'anio_habilitado' => 'required_if:habilitar_restriccion,true|nullable|numeric',
            'periodo_habilitado' => 'required_if:habilitar_restriccion,true|nullable|string|in:1,2,3,4',
        ]);

        $docente = $request->docente;
        $anio = $request->anio;
        $periodo = $request->periodo;
        $anioHasta = $request->anio_hasta;
        $periodoHasta = $request->periodo_hasta;
        $materia = $request->materia;
        $grupo = $request->grupo;

        $habilitarRestriccion = $request->boolean('habilitar_restriccion');
        $anioHabilitado = $request->anio_habilitado;
        $periodoHabilitado = $request->periodo_habilitado;

        // CONSULTAR DOCENTE
        $docenteInfo = DB::connection('sqlsrv')->selectOne("
        SELECT CODIGO, NOMBRES, APELLIDOS
        FROM DOCENTES
        WHERE CODIGO = ?
    ", [$docente]);

        if (!$docenteInfo) {
            return response()->json([
                'success' => false,
                'message' => 'Docente no encontrado'
            ], 404);
        }

        // ── Rango temporal ──────────────────────────────────────────────
        $ordenDesde = null;
        $ordenHasta = null;
        $filtroSoloAnioExacto = false;

        if ($anio && !$periodo && !$anioHasta) {
            $filtroSoloAnioExacto = true;
        } elseif ($anio) {
            $ordenDesde = $this->ordenTemporal((int) $anio, $periodo);
            $ordenHasta = $anioHasta
                ? $this->ordenTemporal((int) $anioHasta, $periodoHasta ?? '3')
                : $this->ordenTemporal((int) $anio, '3');
        }

        $materiaEsCodigo = $materia && preg_match('/^\d+$/', $materia);

        $filtroAnioExacto = $filtroSoloAnioExacto ? "AND GRUPOS.ANIO = :anio" : "";

        $filtroRango = ($ordenDesde !== null)
            ? "AND (
            (GRUPOS.ANIO * 10 + CASE
                WHEN GRUPOS.PERIODO = '1' THEN 1
                WHEN GRUPOS.PERIODO = '4' THEN 2
                WHEN GRUPOS.PERIODO = '2' THEN 3
                WHEN GRUPOS.PERIODO = '3' THEN 4
                ELSE 1
            END)
            BETWEEN :orden_desde AND :orden_hasta
        )"
            : "";

        $filtroMateria = "";
        if ($materia) {
            $filtroMateria = $materiaEsCodigo
                ? "AND GRUPOS.MATERIA = :materia"
                : "AND MATERIAS.NOMBRE LIKE :materia_like";
        }

        $filtroGrupo = $grupo ? "AND GRUPOS.GRUPO = :grupo" : "";

        $bindings = ['docente' => $docente];

        if ($filtroSoloAnioExacto) {
            $bindings['anio'] = $anio;
        }
        if ($ordenDesde !== null) {
            $bindings['orden_desde'] = $ordenDesde;
            $bindings['orden_hasta'] = $ordenHasta;
        }
        if ($materia) {
            if ($materiaEsCodigo) {
                $bindings['materia'] = $materia;
            } else {
                $bindings['materia_like'] = '%' . $materia . '%';
            }
        }
        if ($grupo) {
            $bindings['grupo'] = $grupo;
        }

        // ── Exclusión dinámica de periodos aún no concluidos ────────────
        $noConcluidos = $this->periodosNoConcluidos();
        $claveHabilitada = null;
        $restriccionFueHabilitada = false;

        if ($habilitarRestriccion && $anioHabilitado && $periodoHabilitado) {
            $claveHabilitada = "{$anioHabilitado}-{$periodoHabilitado}";

            if (in_array($claveHabilitada, $noConcluidos, true)) {
                $noConcluidos = array_values(array_diff($noConcluidos, [$claveHabilitada]));
                $restriccionFueHabilitada = true;
            }
        }

        $filtroNoConcluidos = "";

        if (!empty($noConcluidos)) {
            $placeholders = [];
            foreach ($noConcluidos as $i => $valor) {
                $key = "excl_{$i}";
                $placeholders[] = ":{$key}";
                $bindings[$key] = $valor;
            }

            $filtroNoConcluidos = "AND (
            CONVERT(VARCHAR(4), GRUPOS.ANIO) + '-' + GRUPOS.PERIODO
        ) NOT IN (" . implode(',', $placeholders) . ")";
        }

        $materias = DB::connection('sqlsrv')->select("
 
        SELECT
            ROW_NUMBER() OVER (
                ORDER BY
                    GRUPOS.ANIO,
                    CASE
                        WHEN GRUPOS.PERIODO = '1' THEN 1
                        WHEN GRUPOS.PERIODO = '4' THEN 2
                        WHEN GRUPOS.PERIODO = '2' THEN 3
                        WHEN GRUPOS.PERIODO = '3' THEN 4
                        ELSE 5
                    END,
                    GRUPOS.GRUPO ASC,
                    GRUPOS.MATERIA,
                    GRUPOS.[PLAN]
            ) AS nro,
 
            DOCENTES.CODIGO,
            (DOCENTES.APELLIDOS + ' ' + DOCENTES.NOMBRES) AS docente,
 
            CONVERT(VARCHAR(4), GRUPOS.ANIO) + '/' +
            CASE
                WHEN GRUPOS.PERIODO = '3' THEN '3 - Verano'
                WHEN GRUPOS.PERIODO = '4' THEN '4 - Invierno'
                ELSE GRUPOS.PERIODO
            END AS gestion,
 
            CASE
                WHEN [PLANES].NOMBRE LIKE '%ADMINISTRACION%' THEN 'ADM'
                WHEN [PLANES].NOMBRE LIKE '%COMERCIAL%' THEN 'COM'
                WHEN [PLANES].NOMBRE LIKE '%FINANCIERA%' THEN 'FIN'
                WHEN [PLANES].NOMBRE LIKE '%ECONOMIA%' THEN 'ECO'
                WHEN [PLANES].NOMBRE LIKE '%CONTADURIA%' THEN 'CON'
                ELSE LEFT([PLANES].NOMBRE, 3)
            END AS plan_abrev,
 
            GRUPOS.MATERIA + ' ' + ISNULL(MATERIAS.NOMBRE, 'SIN NOMBRE') AS materia,
 
            -- Heurística vieja, se deja por compatibilidad
            CASE
                WHEN GRUPOS.GRUPO > '30'
                     AND GRUPOS.PERIODO IN ('3','4')
                THEN 'COMPARTIDO'
                ELSE ''
            END AS compartido,
 
            -- *** NUEVO: dato real de GRUPOS_COMPARTIDOS (AJUSTAR si la semántica difiere) ***
            ISNULL(GC.COMP, '')        AS comp,
            ISNULL(GC.COMPARTIDO, '')  AS comparte,
            GC.ORDEN                    AS orden_comparte,
 
            GRUPOS.GRUPO AS grp,
            GRUPOS.RESOLUCION,
            GRUPOS.DESIGNACION,
            GRUPOS.TIEMPO,
            GRUPOS.TIPO_INGRESO,
            GRUPOS.ANIO,
            GRUPOS.PERIODO,
            GRUPOS.[PLAN]
 
        FROM GRUPOS
 
        INNER JOIN DOCENTES
            ON DOCENTES.CODIGO = GRUPOS.DOCENTE
 
        INNER JOIN MATERIAS
            ON MATERIAS.CODIGO = GRUPOS.MATERIA
            AND MATERIAS.ANIO = GRUPOS.ANIO
            AND MATERIAS.PERIODO = GRUPOS.PERIODO
            AND MATERIAS.[PLAN] = GRUPOS.[PLAN]
 
        LEFT JOIN [PLANES]
            ON [PLANES].CODIGO = GRUPOS.[PLAN]
            AND [PLANES].ANIO = GRUPOS.ANIO
 
        -- *** NUEVO: join real de compartidos ***
        LEFT JOIN GRUPOS_COMPARTIDOS AS GC
            ON GC.[PLAN] = GRUPOS.[PLAN]
            AND GC.MATERIA = GRUPOS.MATERIA
            AND GC.GRUPO = GRUPOS.GRUPO
            AND GC.PRIMARIO = GRUPOS.PRIMARIO
 
        WHERE DOCENTES.CODIGO = :docente
          AND GRUPOS.PRIMARIO = 'Y'
          AND GRUPOS.TIPO = 'N'
 
          {$filtroNoConcluidos}
          {$filtroAnioExacto}
          {$filtroRango}
          {$filtroMateria}
          {$filtroGrupo}
 
        ORDER BY
            GRUPOS.ANIO,
            CASE
                WHEN GRUPOS.PERIODO = '1' THEN 1
                WHEN GRUPOS.PERIODO = '4' THEN 2
                WHEN GRUPOS.PERIODO = '2' THEN 3
                WHEN GRUPOS.PERIODO = '3' THEN 4
                ELSE 5
            END ASC,
            GRUPOS.GRUPO ASC,
            GRUPOS.MATERIA,
            GRUPOS.[PLAN]
 
    ", $bindings);

        return response()->json([
            'success' => true,

            'docente' => [
                'codigo' => $docenteInfo->CODIGO,
                'nombre' => trim($docenteInfo->APELLIDOS . ' ' . $docenteInfo->NOMBRES),
            ],

            'anio_filtro' => $anio ?? 'todos',
            'periodo_filtro' => $periodo ?? null,
            'anio_hasta_filtro' => $anioHasta ?? null,
            'periodo_hasta_filtro' => $periodoHasta ?? null,
            'materia_filtro' => $materia ?? 'todas',
            'grupo_filtro' => $grupo ?? 'todos',

            'restriccion' => [
                'periodos_no_concluidos' => $this->periodosNoConcluidos(),
                'habilitacion_solicitada' => $claveHabilitada,
                'habilitacion_aplicada' => $restriccionFueHabilitada,
            ],

            'total' => count($materias),
            'materias' => $materias,
        ]);
    }


}