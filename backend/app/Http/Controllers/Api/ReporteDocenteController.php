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

    private function materiasSinGrupo(
        int $docente,
        string $nombreDocente,
        bool $mostrarNota,
        ?int $anioExacto,
        ?int $ordenDesde,
        ?int $ordenHasta,
        array $noConcluidos
    ): array {
        $bindings = ['docente' => $docente];

        $filtroAnioExacto = '';
        if ($anioExacto !== null) {
            $filtroAnioExacto = "AND CAST(cdoc.GESTION AS INT) = :anio_exacto";
            $bindings['anio_exacto'] = $anioExacto;
        }

        $filtroRango = '';
        if ($ordenDesde !== null) {
            $filtroRango = "AND (
            (CAST(cdoc.GESTION AS INT) * 10 + CASE
                WHEN cdoc.PERIODO = '1' THEN 1
                WHEN cdoc.PERIODO = '4' THEN 2
                WHEN cdoc.PERIODO = '2' THEN 3
                WHEN cdoc.PERIODO = '3' THEN 4
                ELSE 1
            END) BETWEEN :orden_desde AND :orden_hasta
        )";
            $bindings['orden_desde'] = $ordenDesde;
            $bindings['orden_hasta'] = $ordenHasta;
        }

        $filtroNoConcluidos = '';
        if (!empty($noConcluidos)) {
            $placeholders = [];
            foreach ($noConcluidos as $i => $valor) {
                $key = "excl_{$i}";
                $placeholders[] = ":{$key}";
                $bindings[$key] = $valor;
            }
            $filtroNoConcluidos = "AND (LTRIM(RTRIM(cdoc.GESTION)) + '-' + cdoc.PERIODO) NOT IN (" . implode(',', $placeholders) . ")";
        }

        $sql = <<<SQL
        SELECT
            cm.ID_DETALLE,
            cm.COD_MATERIA AS materia_codigo,
            cm.NOMBRE_MATERIA,
            cm.DETALLE,
            cm.NOTA,
            cdoc.GESTION,
            cdoc.PERIODO,
            cdoc.TIPO_DOCUMENTO,
            cdoc.DETALLE_GENERAL,
            cdoc.CATEGORIA
        FROM CLASIFICACION_MATERIA AS cm
        INNER JOIN CLASIFICACION_DOCENTE AS ccd
            ON ccd.ID_CLASIFICACION_DOCENTE = cm.ID_CLASIFICACION_DOCENTE
        INNER JOIN CLASIFICACION_DOCUMENTO AS cdoc
            ON cdoc.ID_DOCUMENTO = cm.ID_DOCUMENTO
        WHERE ccd.COD_DOCENTE = :docente
          AND (cm.COD_PLAN IS NULL OR LTRIM(RTRIM(cm.COD_PLAN)) = '')
          AND (cm.GRUPO IS NULL OR LTRIM(RTRIM(cm.GRUPO)) = '')
          {$filtroAnioExacto}
          {$filtroRango}
          {$filtroNoConcluidos}
        ORDER BY cdoc.GESTION, cdoc.PERIODO, cm.ORDEN, cm.ID_DETALLE
        SQL;

        $filas = DB::connection('sqlsrv')->select($sql, $bindings);

        $resultado = [];
        foreach ($filas as $f) {
            $anio = (int) $f->GESTION;

            $materiaTexto = trim(($f->materia_codigo ?? '') . ' ' . ($f->NOMBRE_MATERIA ?: 'SIN NOMBRE'));

            $descripcion = $f->DETALLE ?: ($f->DETALLE_GENERAL ?? '');
            if ($mostrarNota && $f->NOTA !== null) {
                $descripcion .= ($descripcion !== '' ? ' , ' : '') . 'Nota: ' . $f->NOTA;
            }

            $obj = new \stdClass();
            $obj->nro = null;
            $obj->CODIGO = $docente;
            $obj->docente = $nombreDocente;
            $obj->gestion = $anio . '/' . match ($f->PERIODO) {
                '3' => '3 - Verano',
                '4' => '4 - Invierno',
                default => $f->PERIODO,
            };
            $obj->plan_abrev = null;
            $obj->materia = $materiaTexto;
            $obj->materia_codigo = $f->materia_codigo;
            $obj->compartido = '';
            $obj->comp = '';
            $obj->comparte = '';
            $obj->orden_comparte = null;
            $obj->grp = null;
            $obj->RESOLUCION = $f->TIPO_DOCUMENTO;
            $obj->DESIGNACION = $descripcion;
            $obj->TIEMPO = null;
            $obj->TIPO_INGRESO = $f->CATEGORIA;
            $obj->ANIO = $anio;
            $obj->PERIODO = $f->PERIODO;
            $obj->PLAN = null;

            $resultado[] = $obj;
        }

        return $resultado;
    }

    private function bloqueosManuales(): array
    {
        return PeriodoAcademico::bloqueosActivos();
    }

    private const CAMPOS_DOCENTE_PERMITIDOS = [
        'CI' => 'd.CI',
        'FECHA_NAC' => 'd.FECHA_NAC',
        'SEXO' => 'd.SEXO',
        'TITULO' => 'd.TITULO',
        'FECHA_NOMBRAMIENTO' => 'd.FECHA_NOMBRAMIENTO',
    ];

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

    private function ordenTemporal(?int $anio, ?string $periodo): ?int
    {
        if (!$anio)
            return null;

        $ordenPeriodo = match ($periodo) {
            '1' => 1,
            '4' => 2,
            '2' => 3,
            '3' => 4,
            default => 1,
        };

        return ($anio * 10) + $ordenPeriodo;
    }

    private function rangosPeriodos(): array
    {
        return PeriodoAcademico::obtenerRangos();
    }

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
            $ordenDesde = $anio
                ? $this->ordenTemporal((int) $anio, $periodo)
                : 0;

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

        $sql = <<<SQL
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

            GRUPOS.MATERIA AS materia_codigo,

            CASE
                WHEN GRUPOS.PERIODO IN ('3','4')
                     AND LEFT(LTRIM(RTRIM(GRUPOS.GRUPO)), 1) <> '0'
                THEN 'COMPARTIDO'
                ELSE ''
            END AS compartido,

            CASE
                WHEN GRUPOS.PERIODO IN ('3','4')
                     AND LEFT(LTRIM(RTRIM(GRUPOS.GRUPO)), 1) <> '0'
                THEN '0' + RIGHT(LTRIM(RTRIM(GRUPOS.GRUPO)), 1)
                ELSE NULL
            END AS grupo_padre,

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
        SQL;

        $materias = DB::connection('sqlsrv')->select($sql, $bindings);

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
        $anio = 2026;
        $periodo = 1;

        $docenteFiltro = $request->input('docente');

        $sql = <<<SQL
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
        SQL;

        if ($docenteFiltro) {
            $sql .= " AND HORARIOS2.DOCENTE = :docente";
        }

        $sql .= <<<SQL

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
        SQL;

        $bindings = [
            'anio' => $anio,
            'periodo' => $periodo,
        ];

        if ($docenteFiltro) {
            $bindings['docente'] = (string) $docenteFiltro;
        }

        $filas = DB::connection('sqlsrv')->select($sql, $bindings);

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

        return response()->json([
            'gestion' => [
                'anio' => $anio,
                'periodo' => $periodo,
            ],
            'docentes' => array_values($docentesMap),
        ]);
    }

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
            'incluir_sin_grupo' => 'nullable|boolean',
            'mostrar_nota_sin_grupo' => 'nullable|boolean',
        ]);

        $docente = $request->docente;
        $anio = $request->anio;
        $periodo = $request->periodo;
        $anioHasta = $request->anio_hasta;
        $periodoHasta = $request->periodo_hasta;
        $materia = $request->materia;
        $grupo = $request->grupo;

        $incluirSinGrupo = $request->boolean('incluir_sin_grupo');
        $mostrarNotaSinGrupo = $request->boolean('mostrar_nota_sin_grupo', true);

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
            $ordenDesde = $anio
                ? $this->ordenTemporal((int) $anio, $periodo)
                : 0;

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

        // ── Flag seguro: se interpola directamente (no como placeholder)
        //    porque ODBC de SQL Server no permite reutilizar el mismo
        //    placeholder nombrado más de una vez en la misma query. ──
        $mostrarNotaSql = $mostrarNotaSinGrupo ? 1 : 0;

        $sql = <<<SQL
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

            GRUPOS.MATERIA AS materia_codigo,

            CASE
                WHEN GRUPOS.GRUPO > '30'
                     AND GRUPOS.PERIODO IN ('3','4')
                THEN 'COMPARTIDO'
                ELSE ''
            END AS compartido,

            ISNULL(GC.COMP, '')        AS comp,
            ISNULL(GC.COMPARTIDO, '')  AS comparte,
            GC.ORDEN                    AS orden_comparte,

            GRUPOS.GRUPO AS grp,
            GRUPOS.RESOLUCION,

            CASE
                WHEN GRUPOS.TIPO_INGRESO COLLATE Modern_Spanish_CI_AI LIKE '%EXAMEN%SUFICIENCIA%'
                     AND CM_NOTA.NOTA IS NOT NULL
                     AND {$mostrarNotaSql} = 1
                THEN ISNULL(GRUPOS.DESIGNACION, '') + ' , Nota: ' + CAST(CM_NOTA.NOTA AS VARCHAR(10))
                WHEN GRUPOS.TIPO_INGRESO COLLATE Modern_Spanish_CI_AI LIKE '%EXAMEN%SUFICIENCIA%'
                     AND CM_NOTA.NOTA IS NULL
                     AND {$mostrarNotaSql} = 1
                THEN ISNULL(GRUPOS.DESIGNACION, '') + ' , Nota: N/D'
                ELSE GRUPOS.DESIGNACION
            END AS DESIGNACION,

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

        LEFT JOIN GRUPOS_COMPARTIDOS AS GC
            ON GC.[PLAN] = GRUPOS.[PLAN]
            AND GC.MATERIA = GRUPOS.MATERIA
            AND GC.GRUPO = GRUPOS.GRUPO
            AND GC.PRIMARIO = GRUPOS.PRIMARIO

        OUTER APPLY (
            SELECT TOP 1 CM.NOTA
            FROM CLASIFICACION_MATERIA AS CM
            INNER JOIN CLASIFICACION_DOCENTE AS CCD2
                ON CCD2.ID_CLASIFICACION_DOCENTE = CM.ID_CLASIFICACION_DOCENTE
            INNER JOIN CLASIFICACION_DOCUMENTO AS CDOC2
                ON CDOC2.ID_DOCUMENTO = CCD2.ID_DOCUMENTO
            WHERE CM.COD_PLAN    COLLATE Modern_Spanish_CI_AS = GRUPOS.[PLAN]  COLLATE Modern_Spanish_CI_AS
              AND CM.COD_MATERIA COLLATE Modern_Spanish_CI_AS = GRUPOS.MATERIA COLLATE Modern_Spanish_CI_AS
              AND CM.[GRUPO]      COLLATE Modern_Spanish_CI_AS = CAST(GRUPOS.[GRUPO] AS VARCHAR(10)) COLLATE Modern_Spanish_CI_AS
              AND CCD2.COD_DOCENTE = GRUPOS.DOCENTE
              AND CDOC2.GESTION COLLATE Modern_Spanish_CI_AS = CAST(GRUPOS.ANIO AS VARCHAR(4)) COLLATE Modern_Spanish_CI_AS
              AND CDOC2.PERIODO COLLATE Modern_Spanish_CI_AS = GRUPOS.PERIODO COLLATE Modern_Spanish_CI_AS
              AND CM.NOTA IS NOT NULL
            ORDER BY
                CASE
                    WHEN CDOC2.TIPO_DOCUMENTO  COLLATE Modern_Spanish_CI_AS = GRUPOS.RESOLUCION   COLLATE Modern_Spanish_CI_AS
                     AND CDOC2.DETALLE_GENERAL COLLATE Modern_Spanish_CI_AS = GRUPOS.DESIGNACION  COLLATE Modern_Spanish_CI_AS
                     AND CDOC2.CATEGORIA       COLLATE Modern_Spanish_CI_AS = GRUPOS.TIPO_INGRESO COLLATE Modern_Spanish_CI_AS
                    THEN 0
                    ELSE 1
                END,
                CM.ID_DETALLE DESC
        ) AS CM_NOTA

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
        SQL;

        $materias = DB::connection('sqlsrv')->select($sql, $bindings);

        if ($incluirSinGrupo) {
            $sinGrupo = $this->materiasSinGrupo(
                $docente,
                trim($docenteInfo->APELLIDOS . ' ' . $docenteInfo->NOMBRES),
                $mostrarNotaSinGrupo,
                $filtroSoloAnioExacto ? (int) $anio : null,
                $ordenDesde,
                $ordenHasta,
                $noConcluidos
            );

            $materias = array_merge($materias, $sinGrupo);

            usort(
                $materias,
                fn($a, $b) =>
                    $this->ordenTemporal((int) $a->ANIO, (string) $a->PERIODO)
                    <=> $this->ordenTemporal((int) $b->ANIO, (string) $b->PERIODO)
            );

            foreach ($materias as $i => $m) {
                $m->nro = $i + 1;
            }
        }

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

    public function excel(Request $request)
    {
        $request->validate($this->reglasFiltros());
        $data = $this->obtenerDatos($request);

        $camposDocente = $request->query('campos', []);
        $camposTitulo = $request->query('campos_titulo', []);
        $anio = $request->query('anio');
        $periodo = $request->query('periodo');

        $NEGRO = 'FF000000';
        $GRIS_HEAD_BG = 'FFF0F0F0';
        $GRIS_LINEA = 'FF8C8C8C';
        $GRIS_TEXTO = 'FF505050';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Docentes con Titulo');
        $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10);

        $encabezados = ['Nº', 'Código', 'Apellidos', 'Nombres', 'Tipo de Título'];
        foreach ($camposDocente as $c)
            $encabezados[] = self::ETIQUETAS[$c] ?? $c;
        foreach ($camposTitulo as $c)
            $encabezados[] = self::ETIQUETAS[$c] ?? $c;
        $totalColumnas = count($encabezados);
        $ultimaColumna = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($totalColumnas);

        $colsMinimoEncabezado = 9;
        $colEncabezadoFin = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(
            max($totalColumnas, $colsMinimoEncabezado)
        );

        $periodoLabel = match ((string) $periodo) {
            '1' => '1', '2' => '2', '3' => 'Verano', '4' => 'Invierno',
            default => $periodo,
        };
        $fechaGeneracion = now()->format('d/m/Y h:i:s A');

        $sheet->mergeCells('A1:C2');
        $sheet->setCellValue('A1', "UNIVERSIDAD MAYOR DE\nSAN SIMÓN");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(8)->getColor()->setARGB($NEGRO);
        $sheet->getStyle('A1')->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER)
            ->setWrapText(true);
        $sheet->getStyle('A1:C2')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_NONE);
        $sheet->getStyle('A1:C2')->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE);

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

        $sheet->mergeCells("A3:{$colEncabezadoFin}3");
        $sheet->setCellValue('A3', "Gestión Académica {$periodoLabel}/{$anio}");
        $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(10);
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(3)->setRowHeight(16);

        $sheet->setCellValue('A4', 'Nota: Este es un documento generado automáticamente a partir de los registros del sistema.');
        $sheet->getStyle('A4')->getFont()->setSize(8)->getColor()->setARGB($GRIS_TEXTO);

        $sheet->mergeCells("D4:{$colEncabezadoFin}4");
        $sheet->setCellValue('D4', $fechaGeneracion);
        $sheet->getStyle('D4')->getFont()->setSize(8)->getColor()->setARGB($GRIS_TEXTO);
        $sheet->getStyle('D4')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

        $sheet->getStyle("A4:{$colEncabezadoFin}4")->getBorders()->getBottom()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)
            ->getColor()->setARGB($GRIS_LINEA);

        $filaEncabezados = 6;
        $sheet->fromArray($encabezados, null, "A{$filaEncabezados}");
        $rangoEncabezado = "A{$filaEncabezados}:{$ultimaColumna}{$filaEncabezados}";

        $sheet->getStyle($rangoEncabezado)->getFont()->setBold(true)->setSize(9)->getColor()->setARGB($NEGRO);
        $sheet->getStyle($rangoEncabezado)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB($GRIS_HEAD_BG);
        $sheet->getStyle($rangoEncabezado)->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER)
            ->setWrapText(true);
        $sheet->getStyle($rangoEncabezado)->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)
            ->getColor()->setARGB($NEGRO);
        $sheet->getStyle($rangoEncabezado)->getBorders()->getBottom()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM)
            ->getColor()->setARGB($NEGRO);
        $sheet->getRowDimension($filaEncabezados)->setRowHeight(20);

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

            $sheet->getStyle($rangoDatos)->getBorders()->getHorizontal()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)
                ->getColor()->setARGB($GRIS_LINEA);

            $sheet->getStyle("A{$primeraFilaDatos}:A{$ultimaFilaDatos}")->getAlignment()
                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            $sheet->getStyle("A{$ultimaFilaDatos}:{$ultimaColumna}{$ultimaFilaDatos}")->getBorders()->getBottom()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM)
                ->getColor()->setARGB($NEGRO);
        }

        $filaTotal = $ultimaFilaDatos + 2;
        $sheet->mergeCells("A{$filaTotal}:{$ultimaColumna}{$filaTotal}");
        $sheet->setCellValue('A' . $filaTotal, 'Total de registros: ' . ($n - 1));
        $sheet->getStyle('A' . $filaTotal)->getFont()->setBold(true)->setSize(9);
        $sheet->getStyle('A' . $filaTotal)->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

        $sheet->freezePane('A' . $primeraFilaDatos);
        $sheet->setAutoFilter($rangoEncabezado);

        $sheet->getColumnDimension('A')->setWidth(6);
        foreach (range('B', $ultimaColumna) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->setShowGridlines(false);

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