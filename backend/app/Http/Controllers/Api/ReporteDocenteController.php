<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use App\Models\PeriodoAcademico;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReporteDocenteController extends Controller
{

    private function bloqueosManuales(): array
    {
        return PeriodoAcademico::bloqueosActivos();
    }

    // Whitelist de columnas opcionales de DOCENTES que el reporte puede agregar
    private const CAMPOS_DOCENTE_PERMITIDOS = [
        'CI' => 'd.CI',
        'FECHA_NAC' => 'd.FECHA_NAC',
        'SEXO' => 'd.SEXO',
        'TITULO' => 'd.TITULO',
        'FECHA_NOMBRAMIENTO' => 'd.FECHA_NOMBRAMIENTO',
    ];

    // Whitelist de columnas opcionales de CLASIFICACION_TITULO
    private const CAMPOS_TITULO_PERMITIDOS = [
        'NOMBRE_TITULO' => 'ct.NOMBRE_TITULO',
        'UNIVERSIDAD' => 'ct.UNIVERSIDAD',
        'PAIS' => 'ct.PAIS',
        'FECHA_TITULO' => 'ct.FECHA_TITULO',
        'NUMERO' => 'ct.NUMERO',
    ];
    private const ETIQUETAS = [
        'CI' => 'CI',
        'FECHA_NAC' => 'Fecha Nac.',
        'SEXO' => 'Sexo',
        'TITULO' => 'Título (abrev.)',
        'FECHA_NOMBRAMIENTO' => 'Fecha Nombramiento',
        'NOMBRE_TITULO' => 'Nombre del Título',
        'UNIVERSIDAD' => 'Universidad',
        'PAIS' => 'País',
        'FECHA_TITULO' => 'Fecha de Título',
        'NUMERO' => 'Número',
    ];

    private function reglasFiltros(): array
    {
        return [
            'anio' => 'required|integer',
            'periodo' => 'required',
            'tipo_titulo' => 'nullable|string',
            'campos' => 'nullable|array',
            'campos.*' => Rule::in(array_keys(self::CAMPOS_DOCENTE_PERMITIDOS)),
            'campos_titulo' => 'nullable|array',
            'campos_titulo.*' => Rule::in(array_keys(self::CAMPOS_TITULO_PERMITIDOS)),
        ];
    }

    private function obtenerDatos(Request $request)
    {
        $anio = (int) $request->query('anio');
        $periodo = $request->query('periodo');
        $tipoTitulo = $request->query('tipo_titulo');
        $camposDocente = $request->query('campos', []);
        $camposTitulo = $request->query('campos_titulo', []);

        $selects = ['d.CODIGO', 'd.APELLIDOS', 'd.NOMBRES', 'ct.TIPO_TITULO'];
        foreach ($camposDocente as $campo)
            $selects[] = self::CAMPOS_DOCENTE_PERMITIDOS[$campo] . " AS $campo";
        foreach ($camposTitulo as $campo)
            $selects[] = self::CAMPOS_TITULO_PERMITIDOS[$campo] . " AS $campo";

        $query = DB::table('DOCENTES as d')
            ->join('CLASIFICACION_DOCENTE as ccd', 'ccd.COD_DOCENTE', '=', 'd.CODIGO')
            ->join('CLASIFICACION_TITULO as ct', 'ct.ID_CLASIFICACION_DOCENTE', '=', 'ccd.ID_CLASIFICACION_DOCENTE')
            ->selectRaw(implode(', ', $selects))
            ->whereExists(function ($sub) use ($anio, $periodo) {
                $sub->select(DB::raw(1))
                    ->from('GRUPOS as g')
                    ->whereColumn('g.DOCENTE', 'd.CODIGO')
                    ->where('g.ANIO', $anio)
                    ->where('g.PERIODO', $periodo)
                    ->where('g.TIPO', 'N');
            });

        if ($tipoTitulo) {
            $query->where('ct.TIPO_TITULO', $tipoTitulo);
        }

        return $query->distinct()->orderBy('d.APELLIDOS')->orderBy('d.NOMBRES')->get();
    }
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
        return PeriodoAcademico::obtenerRangos();
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

        $ordenDesde = null;
        $ordenHasta = null;
        $filtroSoloAnioExacto = false;

        if ($anio && !$periodo && !$anioHasta) {
            $filtroSoloAnioExacto = true;
        } elseif ($anio || $anioHasta) {
            // Si no viene "anio" (Desde vacío), no hay tope mínimo
            $ordenDesde = $anio
                ? $this->ordenTemporal((int) $anio, $periodo)
                : 0;

            // Prioridad a anioHasta; si no viene, usa fin del año "desde";
            // si tampoco hay "anio", en teoría no llegamos aquí (ya cubierto arriba)
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

        $noConcluidos = array_unique(array_merge(
            $this->periodosNoConcluidos(),
            PeriodoAcademico::bloqueosActivos()
        ));
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

        -- ── NUEVA REGLA: primer dígito del GRUPO decide padre/hijo ──────
        -- '0' por delante  → grupo padre (no compartido)
        -- cualquier otro dígito por delante → grupo hijo (compartido)
        -- Solo aplica en periodo Verano(3)/Invierno(4)
        CASE
            WHEN GRUPOS.PERIODO IN ('3','4')
                 AND LEFT(LTRIM(RTRIM(GRUPOS.GRUPO)), 1) <> '0'
            THEN 'COMPARTIDO'
            ELSE ''
        END AS compartido,

        -- Código del grupo padre correspondiente (solo informativo)
        CASE
            WHEN GRUPOS.PERIODO IN ('3','4')
                 AND LEFT(LTRIM(RTRIM(GRUPOS.GRUPO)), 1) <> '0'
            THEN '0' + RIGHT(LTRIM(RTRIM(GRUPOS.GRUPO)), 1)
            ELSE NULL
        END AS grupo_padre,

        -- Carrera dueña del prefijo con el que se comparte (solo informativo)
        CASE LEFT(LTRIM(RTRIM(GRUPOS.GRUPO)), 1)
            WHEN '4' THEN 'ADMINISTRACIÓN DE EMPRESAS'
            WHEN '5' THEN 'ING. COMERCIAL'
            WHEN '6' THEN 'CONTADURÍA PÚBLICA'
            WHEN '7' THEN 'ING. FINANCIERA'
            WHEN '8' THEN 'ECONOMÍA'
            ELSE NULL
        END AS comparte_con_carrera,

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
        } elseif ($anio || $anioHasta) {
            // Si no viene "anio" (Desde vacío), no hay tope mínimo
            $ordenDesde = $anio
                ? $this->ordenTemporal((int) $anio, $periodo)
                : 0;

            // Prioridad a anioHasta; si no viene, usa fin del año "desde"
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
        $noConcluidos = array_unique(array_merge(
            $this->periodosNoConcluidos(),
            PeriodoAcademico::bloqueosActivos()
        ));
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

    // GET /api/reporte-docentes/tipos-titulo
    // Devuelve los TIPO_TITULO distintos que existen registrados,
    // para armar el <select> de filtro en el frontend (Diplomado, Maestría, etc.)
    // GET /api/reporte-docentes/tipos-titulo
    public function tiposTitulo()
    {
        $tipos = DB::table('CLASIFICACION_TITULO')
            ->select('TIPO_TITULO')
            ->whereNotNull('TIPO_TITULO')
            ->distinct()
            ->orderBy('TIPO_TITULO')
            ->pluck('TIPO_TITULO');

        return response()->json($tipos);
    }

    // GET /api/reporte-docentes/con-titulo
    public function docentesConTitulo(Request $request)
    {
        $request->validate($this->reglasFiltros());
        $data = $this->obtenerDatos($request);

        return response()->json([
            'anio' => (int) $request->query('anio'),
            'periodo' => $request->query('periodo'),
            'tipo_titulo' => $request->query('tipo_titulo'),
            'total' => $data->count(),
            'data' => $data,
        ]);
    }

    // GET /api/reporte-docentes/con-titulo/excel

    public function excel(Request $request)
    {
        $request->validate($this->reglasFiltros());
        $data = $this->obtenerDatos($request);

        $camposDocente = $request->query('campos', []);
        $camposTitulo = $request->query('campos_titulo', []);
        $anio = $request->query('anio');
        $periodo = $request->query('periodo');

        // ── Paleta académica (igual al PDF: blanco/negro/gris) ────────────
        $NEGRO = 'FF000000';
        $GRIS_HEAD_BG = 'FFF0F0F0'; // equivalente a rgb(240,240,240) del PDF
        $GRIS_LINEA = 'FF8C8C8C'; // equivalente a rgb(140,140,140) del PDF
        $GRIS_TEXTO = 'FF505050';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Docentes con Titulo');
        $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10);

        // ── Encabezados dinámicos: "Nº" fijo + lo que venga en los filtros ──
        $encabezados = ['Nº', 'Código', 'Apellidos', 'Nombres', 'Tipo de Título'];
        foreach ($camposDocente as $c)
            $encabezados[] = self::ETIQUETAS[$c] ?? $c;
        foreach ($camposTitulo as $c)
            $encabezados[] = self::ETIQUETAS[$c] ?? $c;
        $totalColumnas = count($encabezados);
        $ultimaColumna = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($totalColumnas);
        // No importa cuántas columnas se agreguen: la TABLA (datos, encabezado de
        // columnas, autofiltro) siempre usa $ultimaColumna, así que se ajusta sola.

        // El bloque de encabezado (título, subtítulo, gestión, nota/fecha) necesita
        // un ancho mínimo para no cortar el texto cuando la tabla tiene pocas
        // columnas (ej. solo 5). Si la tabla es angosta, el encabezado igual
        // se extiende hasta esta columna mínima (decorativo, no afecta los datos).
        $colsMinimoEncabezado = 9; // suficiente para que el título completo entre
        $colEncabezadoFin = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(
            max($totalColumnas, $colsMinimoEncabezado)
        );

        $periodoLabel = match ((string) $periodo) {
            '1' => '1', '2' => '2', '3' => 'Verano', '4' => 'Invierno',
            default => $periodo,
        };
        $fechaGeneracion = now()->format('d/m/Y h:i:s A');

        // ── Bloque A1:C2: recuadro de logo/universidad, fondo blanco y sin contorno ──
        $sheet->mergeCells('A1:C2');
        $sheet->setCellValue('A1', "UNIVERSIDAD MAYOR DE\nSAN SIMÓN");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(8)->getColor()->setARGB($NEGRO);
        $sheet->getStyle('A1')->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER)
            ->setWrapText(true);
        $sheet->getStyle('A1:C2')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_NONE); // sin relleno, blanco
        $sheet->getStyle('A1:C2')->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE); // sin contorno

        // ── Bloque D1:colEncabezadoFin: título del reporte, al lado del recuadro anterior ──
        $sheet->mergeCells("D1:{$colEncabezadoFin}1");
        $sheet->setCellValue('D1', 'REPORTE DE DOCENTES CON TÍTULO');
        $sheet->getStyle('D1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('D1')->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        $sheet->mergeCells("D2:{$colEncabezadoFin}2");
        $sheet->setCellValue('D2', 'FACULTAD DE CIENCIAS ECONÓMICAS');
        $sheet->getStyle('D2')->getFont()->setBold(true)->setSize(10.5);
        $sheet->getStyle('D2')->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        $sheet->getRowDimension(1)->setRowHeight(24);
        $sheet->getRowDimension(2)->setRowHeight(18);

        // ── Fila 3: Gestión académica centrada (bold) ──
        $sheet->mergeCells("A3:{$colEncabezadoFin}3");
        $sheet->setCellValue('A3', "Gestión Académica {$periodoLabel}/{$anio}");
        $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(10);
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(3)->setRowHeight(16);

        // ── Fila 4: Nota a la izquierda + fecha a la derecha (misma fila, como el PDF) ──
        $sheet->setCellValue('A4', 'Nota: Este es un documento generado automáticamente a partir de los registros del sistema.');
        $sheet->getStyle('A4')->getFont()->setSize(8)->getColor()->setARGB($GRIS_TEXTO);

        $sheet->mergeCells("D4:{$colEncabezadoFin}4");
        $sheet->setCellValue('D4', $fechaGeneracion);
        $sheet->getStyle('D4')->getFont()->setSize(8)->getColor()->setARGB($GRIS_TEXTO);
        $sheet->getStyle('D4')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

        // Línea separadora bajo el bloque de encabezado (fila 4), a todo el ancho del bloque
        $sheet->getStyle("A4:{$colEncabezadoFin}4")->getBorders()->getBottom()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)
            ->getColor()->setARGB($GRIS_LINEA);

        // ── Encabezado de tabla (fila 6) ── usa $ultimaColumna (ancho real de datos)
        $filaEncabezados = 6;
        $sheet->fromArray($encabezados, null, "A{$filaEncabezados}");
        $rangoEncabezado = "A{$filaEncabezados}:{$ultimaColumna}{$filaEncabezados}";

        $sheet->getStyle($rangoEncabezado)->getFont()->setBold(true)->setSize(9)->getColor()->setARGB($NEGRO);
        $sheet->getStyle($rangoEncabezado)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB($GRIS_HEAD_BG); // cada celda pintada, no solo negrita
        $sheet->getStyle($rangoEncabezado)->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER)
            ->setWrapText(true);
        // Borde completo alrededor de cada celda del encabezado, para que se note el "recuadro pintado"
        $sheet->getStyle($rangoEncabezado)->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)
            ->getColor()->setARGB($NEGRO);
        $sheet->getStyle($rangoEncabezado)->getBorders()->getBottom()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM)
            ->getColor()->setARGB($NEGRO);
        $sheet->getRowDimension($filaEncabezados)->setRowHeight(20);

        // ── Filas de datos, con número de fila ──
        $fila = $filaEncabezados + 1;
        $primeraFilaDatos = $fila;
        $n = 1;
        foreach ($data as $row) {
            $valores = [$n, $row->CODIGO, $row->APELLIDOS, $row->NOMBRES, $row->TIPO_TITULO];
            foreach ($camposDocente as $c)
                $valores[] = $row->$c ?? '';
            foreach ($camposTitulo as $c)
                $valores[] = $row->$c ?? '';
            $sheet->fromArray($valores, null, 'A' . $fila);
            $fila++;
            $n++;
        }
        $ultimaFilaDatos = $fila - 1;

        if ($ultimaFilaDatos >= $primeraFilaDatos) {
            $rangoDatos = "A{$primeraFilaDatos}:{$ultimaColumna}{$ultimaFilaDatos}";

            $sheet->getStyle($rangoDatos)->getFont()->setSize(9)->getColor()->setARGB($NEGRO);
            $sheet->getStyle($rangoDatos)->getAlignment()
                ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

            // Solo línea horizontal fina bajo cada fila (sin cuadrícula vertical),
            // igual que la tabla del PDF: lineWidth bottom únicamente.
            $sheet->getStyle($rangoDatos)->getBorders()->getHorizontal()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)
                ->getColor()->setARGB($GRIS_LINEA);

            // Columna Nº centrada
            $sheet->getStyle("A{$primeraFilaDatos}:A{$ultimaFilaDatos}")->getAlignment()
                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            // Borde inferior más marcado al cierre de la tabla
            $sheet->getStyle("A{$ultimaFilaDatos}:{$ultimaColumna}{$ultimaFilaDatos}")->getBorders()->getBottom()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM)
                ->getColor()->setARGB($NEGRO);
        }

        // ── Fila de total, alineada a la derecha (como el "TOTAL" del PDF) ──
        $filaTotal = $ultimaFilaDatos + 2;
        $sheet->mergeCells("A{$filaTotal}:{$ultimaColumna}{$filaTotal}");
        $sheet->setCellValue('A' . $filaTotal, 'Total de registros: ' . ($n - 1));
        $sheet->getStyle('A' . $filaTotal)->getFont()->setBold(true)->setSize(9);
        $sheet->getStyle('A' . $filaTotal)->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

        // ── Ajustes generales ──
        $sheet->freezePane('A' . $primeraFilaDatos);
        $sheet->setAutoFilter($rangoEncabezado);

        $sheet->getColumnDimension('A')->setWidth(6); // Nº angosta
        foreach (range('B', $ultimaColumna) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->setShowGridlines(false); // se ven solo nuestras líneas, no la cuadrícula de Excel

        // ── Pie de página (equivalente al footer del PDF) ──
        $sheet->getHeaderFooter()->setOddFooter(
            '&L&7Procesado UTI - Facultad de Ciencias Económicas' .
            '&C&7Página &P de &N' .
            '&R&7' . $fechaGeneracion
        );
        $sheet->getPageSetup()
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        $sheet->getPageMargins()->setTop(0.5)->setBottom(0.6)->setLeft(0.4)->setRight(0.4);

        $nombreArchivo = 'reporte_docentes_titulo_' . $anio . '_' . $periodo . '.xlsx';

        $writer = new Xlsx($spreadsheet);
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $nombreArchivo, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}