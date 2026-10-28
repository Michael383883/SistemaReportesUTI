<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * ORDEN DE PRELACIÓN POR MATERIA
 *
 * Estructura equivalente al PDF histórico: Nº | MATERIA | CARRERA | DOCENTE | CATEGORIA | OBSERVACIONES
 *
 * ── Algoritmo ─────────────────────────────────────────────────────────────
 *  1. UNIVERSO DE MATERIAS: las que se dictan en (anio, periodo) según GRUPOS
 *     (planes válidos, TIPO='N', PRIMARIO='Y'). Se agrupan por nombre normalizado
 *     (sin tildes/puntuación), porque la misma materia tiene códigos distintos por plan.
 *     La CARRERA sale de los planes donde se dicta (ej. CP-ECO-ADM).
 *  2. DOCENTES ACTIVOS: solo docentes con al menos un grupo en ese anio/periodo
 *     (alcance=docente, por defecto) o que dicten ESA materia (alcance=materia).
 *     Quien no imparte en el periodo NO entra.
 *  3. CANDIDATOS CON DOCUMENTO: CLASIFICACION_MATERIA -> CLASIFICACION_DOCENTE ->
 *     CLASIFICACION_DOCUMENTO, con GESTION <= anio consultado. Se excluyen categorías
 *     que no dan prelación (Acéfala, Sin Examen de Suficiencia).
 *  4. UN DOCENTE = UNA FILA POR MATERIA: se queda su mejor documento
 *     (menor tier; a igual tier, el más antiguo; a igual año, mejor nota).
 *  5. SIN DOCUMENTO (fallback): docentes que hoy dictan la materia pero no tienen
 *     documento válido. Se arman desde GRUPOS: años consecutivos que regentan la
 *     materia ("Regenta la asignatura N años consecutivos, SIN PRELACIÓN"), y se
 *     usa TIPO_INGRESO/RESOLUCION/DESIGNACION de GRUPOS si existen.
 *  6. ORDEN dentro de cada materia: tier -> año del documento ASC (más antiguo
 *     primero) -> nota DESC -> años consecutivos DESC -> nombre.
 *
 *  7. FILTRO DE MATERIAS: solo entran las de MATERIAS_PERMITIDAS (por defecto). Con
 *     ?solo_listado=0 se incluyen todas. El preview devuelve 'materias_fuera_del_listado'
 *     para ver qué materias del periodo quedaron descartadas y ajustar el listado.
 *
 * Compatible con bases SQL Server con nivel de compatibilidad 90 (sin TRY_CAST).
 */
class ReportePrelacionMateriaController extends Controller
{
    // Planes -> carrera (siglas del PDF). Ajustar si cambian.
    private const PLANES = [
        '059801' => 'ECO',
        '109401' => 'ADM',
        '089801' => 'CP',   // Contaduría Pública (en el otro reporte: CCP)
        '125091' => 'IC',   // Ingeniería Comercial (en el otro reporte: COM)
        '126091' => 'IF',   // Ingeniería Financiera (en el otro reporte: FIN)
    ];

    // Orden de siglas al armar "CP-ECO-ADM-IF-IC"
    private const ORDEN_CARRERAS = ['CP', 'ECO', 'ADM', 'IF', 'IC'];

    // Jerarquía por TIPO_DOCUMENTO (menor = aparece primero). Ajustable.
    private const TIER_POR_TIPO = [
        'TITULARIDAD_HISTORICA' => 1,
        'TITULARIDAD_RCU_43_11' => 2,
        'CONCURSO_MERITOS' => 3,
        'EXAMEN_SUFICIENCIA' => 4,
    ];
    private const TIER_DOC_DESCONOCIDO = 4;
    private const TIER_SIN_DOCUMENTO = 9;

    private const ETIQUETAS_TIPO = [
        'TITULARIDAD_HISTORICA' => 'Titularidad Histórica',
        'TITULARIDAD_RCU_43_11' => 'Titularidad RCU 43/11',
        'EXAMEN_SUFICIENCIA' => 'Examen de Suficiencia',
        'CONCURSO_MERITOS' => 'Concurso de Méritos',
    ];

    // Categorías (normalizadas, sin tildes) que NO dan derecho a prelación.
    private const CATEGORIAS_EXCLUIDAS = ['ACEFALA', 'SIN EXAMEN DE SUFICIENCIA'];

    // Alias de materias: 'NOMBRE NORMALIZADO' => 'NOMBRE CANÓNICO NORMALIZADO'.
    // Úsalo para unir materias con nombres distintos (ej. 'CALCULO APLICADO' => 'CALCULO II').
    private const EQUIVALENCIAS = [
        // 'CALCULO APLICADO' => 'CALCULO II',
    ];

    // Materias que entran al reporte (listado extraído del PDF histórico, ya depurado).
    // Se compara por nombre normalizado (sin tildes/puntuación, números romanos corregidos y
    // abreviaturas expandidas: CONTAB -> CONTABILIDAD, ECO -> ECONOMIA, ADM -> ADMINISTRACION...).
    // Agrega o quita nombres aquí. Con ?solo_listado=0 se ignora este filtro.
    private const MATERIAS_PERMITIDAS = [
        'ADMINISTRACION',
        'ADMINISTRACION DE COOPERATIVAS (FINANZAS III)',
        'ADMINISTRACION DE CREDITOS Y TESORERIA',
        'ADMINISTRACION DE PROYECTOS',
        'ADMINISTRACION FINANCIERA II',
        'ADMINISTRACION GENERAL',
        'ADMINISTRACION DE EMPRESAS',
        'ADMINISTRACION PUBLICA',
        'GESTION DE LA ADMINISTRACION II',
        'ALGEBRA',
        'ALGEBRA APLICADA',
        'ALGEBRA LINEAL',
        'ANALISIS E INT. DE ESTAD. FINANC.',
        'ANAL. DE ESTAD. FINANC.',
        'APOYO ESTADISTICO',
        'APOYO METODOLOGICO',
        'AUDITORIA DE GESTION',
        'AUDITORIA FORENSE',
        'AUDITORIA I',
        'AUDITORIA II',
        'AUDITORIA TRIBUTARIA I',
        'AUDITORIA TRIBUTARIA II',
        'CALCULO APLICADO',
        'CALCULO II',
        'CALCULO',
        'CALCULO I',
        'CALIDAD TOTAL Y REINGENIERIA',
        'COMERCIO EXTERIOR',
        'COMERCIO INTERNACIONAL',
        'ECO INTERNACIONAL I',
        'COMPORTAMIENTO DEL CONSUMIDOR',
        'CONTAB. AGROPERCUARIA',
        'CONTAB. AGROPECUARIA',
        'CONTAB. DE BANCOS Y ENT. FINANC.',
        'CONTAB. DE SERVICIOS',
        'CONTAB. PETROLERA Y MINERA',
        'CONTABILIDAD I',
        'CONTABILIDAD BASICA',
        'CONTABILIDAD GERENCIAL',
        'CONTABILIDAD FINANCIERA I',
        'CONTABILIDAD II',
        'CONTABILIDAD FINANCIERA II',
        'CONTABILIDAD PARA LA GESTION',
        'CONTABILIDAD III',
        'CONTABILIDAD NACIONAL E INSUMO-PRODUCTO',
        'COSTOS I',
        'CONTABILIDAD DE GESTION',
        'TEORIA DE COSTOS',
        'INGENIERIA DE COSTOS',
        'COSTOS EMPRESARIALES',
        'COSTOS II',
        'COSTOS PARA LA TOMA DE DECISIONES',
        'DEMOGRAFIA',
        'DERECHO COMERCIAL',
        'LEGISLACION EMPRESARIAL',
        'DERECHO DEL TRABAJO',
        'LEGISLACION LABORAL',
        'DERECHO COMERCIAL Y LABORAL',
        'DERECHO TRIBUTARIO',
        'LEGISLACION TRIBUTARIA',
        'DESARROLLO ECONOMICO I',
        'DESARROLLO ECONOMICO II',
        'DIRECCION EMPRESARIAL',
        'ECOLOGIA Y MEDIO AMBIENTE',
        'ECONOMETRIA I',
        'ECONOMETRIA',
        'ECONOMETRIA Y SIMULACIONES',
        'ECONOMETRIA II',
        'ECONOMIA AGRARIA',
        'ECONOMIA DE LOS RR.NN. Y M.A.',
        'ECONOMIA FINANCIERA I',
        'ECONOMIA FINANCIERA II',
        'ECONOMIA FISCAL',
        'TEORIA FISCAL',
        'ECONOMIA GENERAL',
        'ECONOMIA INDUSTRIAL',
        'ECONOMIA INTERNACIONAL II',
        'ECONOMIA MONETARIA',
        'TEORIA MONETARIA',
        'ECONOMIA MUNDIAL CONTEMPORANEA',
        'ECONOMIA POLITICA I',
        'ECONOMIA POLITICA II',
        'EPISTEMOLOGIA DE LA ECONOMIA',
        'ESTADISTICA APLICADA',
        'ESTADISTICA I',
        'ESTADISTICA II',
        'ESTRATEGIA EMPRESARIAL',
        'ETICA PROFESIONAL',
        'EVALUACION DE PROYECTOS',
        'FINANZAS DE EMPRESAS',
        'FINANZAS DE EMFRESAS',
        'FINANZAS I',
        'ADMINISTRACION FINANCIERA',
        'ADMINISTRACION FINANCIERA I',
        'FINANZAS',
        'FINANZAS II',
        'FINANZAS III (ADMINISTRACION DE COOPERATIVAS)',
        'FINANZAS INTERNACIONALES',
        'FINANZAS IV',
        'FINANZAS V',
        'FINANZAS VI',
        'FORMULACION DE PROYECTOS',
        'PREP. Y EVAL. DE PROYECTOS',
        'GABINETE DE AUDITORIA',
        'GABINETE DE CONTABILIDAD',
        'GERENCIA FINANCIERA',
        'GERENCIA OPERATIVA',
        'GERENCIA Y ADM. DE INSTITUCIONES FINANCIERAS',
        'GESTION DE LA ADMINISTRAC. I',
        'GESTION DE LA ADMINISTRACION I',
        'GESTION DE LA CALIDAD',
        'GESTION DE PROYECTOS',
        'GESTION DE PROYECTOS DE INVERSION',
        'GESTION DE PROYECTOS SOCIALES',
        'GESTION DE RIESGOS',
        'GESTION FINANCIERA',
        'HISTORIA DEL PENSAMIENTO ECONOMICO',
        'HISTORIA ECONOMICA DE AMERICA LATINA',
        'HISTORIA ECO. GRAL Y DE BOLIVIA',
        'SEMINARIO BASICO',
        'INFORMATICA I',
        'INFORMATICA',
        'PROGRAMACION BASICA',
        'INFORMATICA II',
        'INGENIERIA FINANCIERA I',
        'INGENIERIA FINANCIERA II',
        'INGLES I',
        'INGLES CONTABLE',
        'INGLES II',
        'INGLES FINANCIERO',
        'INGLES TECNICO',
        'INTRODUCCION A LA INGENIERIA COMERCIAL',
        'INVESTIGACION DE MERCADOS II',
        'INGENIERIA DE NUEVOS PRODUCTOS',
        'INVESTIGACION OPERATIVA',
        'INVESTIGACION OPERATIVA I',
        'INVESTIGACION OPERATIVA II',
        'JUEGO DE BOLSA',
        'LOGISTICA PARA OPERACIONES',
        'MACROECONOMIA I',
        'MACROECONOMIA',
        'MACROECONOMIA II',
        'MARKETING DE SERVICIOS Y RELACIONAL',
        'MATEMATICA FINANCIERA',
        'MERCADO DE CAPITALES',
        'MERCADO DE VALORES',
        'MERCADOS FINANCIEROS Y CREDITOS BANCARIOS I',
        'MERCADOTECNIA I',
        'MERCADOTECNIA',
        'MERCADOTECNIA II',
        'MERCADOTECNIA III',
        'INVESTIGACION DE MERCADOS',
        'MERCADOTECNIA IV',
        'SEMINARIO COMERCIAL',
        'MERCADOTECNIA V',
        'MERCADOTECNIA VI',
        'MERCADOTECNIA VII',
        'MERCADOTECNIA Y NEGOCIOS DIGITALES',
        'METODOL. DE INVESTIG. I',
        'MET. Y TEC. DE INVESTIG.',
        'SEMINARIO DE INVESTIG.',
        'METODOLOGIA DE INVESTIGACION II',
        'MICROECONOMIA I',
        'MICROECONOMIA',
        'ECO DE EMPRESAS I',
        'ECO DE EMPRESAS',
        'MICROECONOMIA II',
        'ECO DE EMPRESAS II',
        'MODELOS DE SIMULACION A LARGO PLAZO',
        'MUESTREO',
        'NEUROMARKETING Y MARKETING LATERAL',
        'ORGANIZACION Y METODOS',
        'PROCEDIMIENTOS ADMINISTRATIVOS',
        'PERSONAL I',
        'ADM. DE PERSONAL',
        'GESTION DE RECURSOS HUMANOS',
        'GESTION DE RRHH I',
        'RECURSOS HUMANOS',
        'PERSONAL II',
        'GESTION DE RRHH II',
        'PERSONAL III',
        'SEGURIDAD E HIGIENE INDUSTRIAL',
        'PERSONAL IV',
        'PERSONAL V',
        'PERSONAL VI',
        'PLANIFICACION',
        'POLITICA ECONOMICA',
        'POLITICA EMPRESARIAL',
        'POLITICAS MACROECONOMICAS',
        'PRACTICA EMPRESARIAL',
        'PRACTICA PROFESIONAL',
        'PRESUPUESTOS EMPRESARIALES',
        'PRESUPUESTOS',
        'PRESUPUESTOS FISCALES',
        'PRODUCCION I',
        'PROCESOS INDUSTRIALES',
        'PRODUCCION II',
        'PROGRAMAS ESTADISTICOS',
        'PSICOLOGIA ORGANIZACIONAL',
        'PUBLICACION Y COMUNICACION MASIVA',
        'REGIMEN TRIBUT. Y ADUANERO',
        'SEMINARIO DE ESTADISTICA',
        'SEMINARIO DE MENCION',
        'SEMINARIO SUPERIOR',
        'SISTEMAS ADMINISTRATIVOS',
        'SISTEMAS DE INFORMACION',
        'SISTEMAS DE INFORMACION PARA LA GESTION I',
        'SISTEMAS DE REGULACION FINANCIERA',
        'SISTEMAS Y CANALES DE DISTRIBUCION',
        'SOCIOLOGIA ECONOMICA',
        'SOCIOLOGIA ORGANIZACIONAL',
        'TALLER',
        'TALLER DE COSTOS',
        'TALLER DE DISEÑO DE PLANES MERCADOLOGICOS',
        'TALLER DE LENGUAJE Y REDACCION',
        'TALLER I',
        'TALLER II',
        'MODALIDADES DE TITULACION',
        'TALLER DE TITULACION',
        'TEORIA DE LAS DECISIONES',
        'TEORIA DE LAS INSTITUCIONES ECONOMICAS',
        'TEORIA Y TECNICAS DE LA ORGANIZACION',
        'TEORIAS ECONOMICAS ACTUALES',
        'TRIBUTACION',
        'VALORACION ECONOMICA DE EMPRESAS',
    ];

    // Abreviaturas del PDF -> palabra completa (solo para comparar contra el listado).
    private const ABREVIATURAS = [
        'CONTAB' => 'CONTABILIDAD',
        'ECO' => 'ECONOMIA',
        'ADM' => 'ADMINISTRACION',
        'ADMINISTRAC' => 'ADMINISTRACION',
        'ESTAD' => 'ESTADISTICA',
        'INVESTIG' => 'INVESTIGACION',
        'METODOL' => 'METODOLOGIA',
        'GRAL' => 'GENERAL',
    ];

    // ═════════════════════════════ ENDPOINTS ═════════════════════════════

    // GET /api/reportes/prelacion-materias/preview?anio=2026&periodo=1
    // Opcionales: materia, carrera (csv: CP,ECO), alcance=docente|materia,
    //             incluir_sin_documento=1|0, mostrar_referencias=1|0
    public function previsualizar(Request $request)
    {
        try {
            [$anio, $periodo, $opts] = $this->leerParametros($request);
            $res = $this->construirPrelacion($anio, $periodo, $opts);

            return response()->json([
                'ok' => true,
                'anio' => $anio,
                'periodo' => $periodo,
                'total_materias' => $res['total_materias'],
                'total_filas' => count($res['data']),
                'filas_sin_documento' => $res['sin_documento'],
                'materias_fuera_del_listado' => $res['fuera_listado'],
                'data' => $res['data'],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // GET /api/reportes/prelacion-materias/excel (mismos parámetros)
    public function generarExcelPrelacion(Request $request)
    {
        try {
            [$anio, $periodo, $opts] = $this->leerParametros($request);
            $res = $this->construirPrelacion($anio, $periodo, $opts);

            if (empty($res['data'])) {
                return response()->json([
                    'ok' => false,
                    'error' => "No hay materias/docentes activos en {$periodo}-{$anio}",
                ], 404);
            }

            return $this->escribirExcel($res['data'], $anio, $periodo);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ═════════════════════════════ PARÁMETROS ═════════════════════════════

    private function leerParametros(Request $request): array
    {
        $anio = (int) $request->query('anio', date('Y'));
        $periodo = (int) $request->query('periodo', 1);

        if (!in_array($periodo, [1, 2, 3, 4], true)) {
            throw new \InvalidArgumentException('El parámetro periodo debe ser 1, 2, 3 o 4.');
        }

        $alcance = $request->query('alcance', 'docente');
        if (!in_array($alcance, ['docente', 'materia'], true)) {
            throw new \InvalidArgumentException("alcance debe ser 'docente' o 'materia'.");
        }

        $carreras = array_values(array_filter(array_map(
            fn($c) => mb_strtoupper(trim($c)),
            explode(',', (string) $request->query('carrera', ''))
        )));

        return [
            $anio,
            $periodo,
            [
                'materia' => trim((string) $request->query('materia', '')),
                'carreras' => $carreras,
                'alcance' => $alcance,
                'incluir_sin_documento' => filter_var($request->query('incluir_sin_documento', true), FILTER_VALIDATE_BOOLEAN),
                'mostrar_referencias' => filter_var($request->query('mostrar_referencias', false), FILTER_VALIDATE_BOOLEAN),
                'solo_listado' => filter_var($request->query('solo_listado', true), FILTER_VALIDATE_BOOLEAN),
            ]
        ];
    }

    // ═════════════════════════════ NÚCLEO ═════════════════════════════

    private function construirPrelacion(int $anio, int $periodo, array $opts): array
    {
        // ── 1) Materias que se dictan en el periodo + docentes activos ──
        $ofrecidas = $this->gruposActivos('g', $anio, $periodo)
            ->join('MATERIAS as m', function ($j) {
                $j->on('m.ANIO', '=', 'g.ANIO')
                    ->on('m.PERIODO', '=', 'g.PERIODO')
                    ->on('m.PLAN', '=', 'g.PLAN')
                    ->on('m.CODIGO', '=', 'g.MATERIA');
            })
            ->select(
                'g.PLAN as cod_plan',
                'g.MATERIA as cod_materia',
                'm.NOMBRE as nombre',
                'g.DOCENTE as docente',
                'g.TIPO_INGRESO as tipo_ingreso',
                'g.RESOLUCION as resolucion',
                'g.DESIGNACION as designacion'
            )
            ->get();

        $docentesActivos = [];   // cod => true (cualquier materia del periodo)
        $materias = [];          // key => info
        $mapaCodigo = [];        // "plan|codmateria" => key
        $filtroMateria = $opts['materia'] !== '' ? $this->clave($opts['materia']) : null;
        $fueraListado = [];      // materias del periodo descartadas por no estar en MATERIAS_PERMITIDAS

        foreach ($ofrecidas as $r) {
            $cod = (string) $r->docente;
            $docentesActivos[$cod] = true;

            $carrera = self::PLANES[trim((string) $r->cod_plan)] ?? null;
            if ($carrera === null) {
                continue;
            }
            if (!empty($opts['carreras']) && !in_array($carrera, $opts['carreras'], true)) {
                continue;
            }

            $key = $this->clave($r->nombre);
            if ($opts['solo_listado'] && !$this->materiaPermitida($r->nombre)) {
                $fueraListado[$key] = trim((string) $r->nombre);
                continue;
            }
            if (
                $filtroMateria !== null
                && !str_contains($key, $filtroMateria)
                && mb_strtoupper(trim((string) $r->cod_materia)) !== mb_strtoupper($opts['materia'])
            ) {
                continue;
            }

            $materias[$key] ??= [
                'nombre' => trim((string) $r->nombre),
                'carreras' => [],
                'codigos' => [],
                'actuales' => [],
            ];
            $materias[$key]['carreras'][$carrera] = true;
            $materias[$key]['codigos'][trim((string) $r->cod_plan) . '|' . trim((string) $r->cod_materia)] = true;
            $materias[$key]['actuales'][$cod] ??= [
                'tipo_ingreso' => trim((string) $r->tipo_ingreso),
                'resolucion' => trim((string) $r->resolucion),
                'designacion' => trim((string) $r->designacion),
            ];

            $mapaCodigo[trim((string) $r->cod_plan) . '|' . trim((string) $r->cod_materia)] = $key;
        }

        if (empty($materias)) {
            $fuera = array_values($fueraListado);
            sort($fuera);
            return ['data' => [], 'total_materias' => 0, 'sin_documento' => 0, 'fuera_listado' => $fuera];
        }

        $nombresDocentes = $this->nombresDocentes(array_keys($docentesActivos));

        // ── 2) Documentos de clasificación de docentes ACTIVOS ──
        // Se usa un CASE en lugar de TRY_CAST para ser compatible con nivel de compatibilidad 90.
        // Solo castea cuando GESTION contiene únicamente dígitos (1 a 4); el resto queda NULL
        // y NULL <= anio es falso, por lo que esos documentos se excluyen.
        $filasDoc = DB::table('CLASIFICACION_MATERIA as cm')
            ->join('CLASIFICACION_DOCENTE as ccd', 'ccd.ID_CLASIFICACION_DOCENTE', '=', 'cm.ID_CLASIFICACION_DOCENTE')
            ->join('CLASIFICACION_DOCUMENTO as cdoc', 'cdoc.ID_DOCUMENTO', '=', 'ccd.ID_DOCUMENTO')
            ->whereRaw(
                "(CASE WHEN LTRIM(RTRIM(CAST(cdoc.GESTION AS VARCHAR(20)))) NOT LIKE '%[^0-9]%' "
                . "AND LEN(LTRIM(RTRIM(CAST(cdoc.GESTION AS VARCHAR(20))))) BETWEEN 1 AND 4 "
                . "THEN CAST(LTRIM(RTRIM(CAST(cdoc.GESTION AS VARCHAR(20)))) AS INT) ELSE NULL END) <= ?",
                [$anio]
            )
            ->whereIn(
                'ccd.COD_DOCENTE',
                $this->gruposActivos('ga', $anio, $periodo)->select('ga.DOCENTE')
            )
            ->select(
                'ccd.COD_DOCENTE',
                'cm.COD_PLAN',
                'cm.COD_MATERIA',
                'cm.NOMBRE_MATERIA',
                'cm.NOTA',
                'cm.DETALLE as detalle_materia',
                'cdoc.ID_DOCUMENTO',
                'cdoc.CATEGORIA',
                'cdoc.TIPO_DOCUMENTO',
                'cdoc.GESTION',
                'cdoc.PERIODO',
                'cdoc.DETALLE_GENERAL',
                'cdoc.OBSERVACION',
                'cdoc.NOMBRE_ARCHIVO'
            )
            ->get();

        $referencias = [];
        if ($opts['mostrar_referencias'] && $filasDoc->isNotEmpty()) {
            foreach ($filasDoc->pluck('ID_DOCUMENTO')->unique()->chunk(1000) as $chunk) {
                $refs = DB::table('CLASIFICACION_REFERENCIA')
                    ->whereIn('ID_DOCUMENTO', $chunk->all())
                    ->get(['ID_DOCUMENTO', 'NRO_REFERENCIA']);
                foreach ($refs as $r) {
                    if (!empty($r->NRO_REFERENCIA)) {
                        $referencias[$r->ID_DOCUMENTO][] = $r->NRO_REFERENCIA;
                    }
                }
            }
        }

        // ── 3) Mejor documento por (materia, docente) ──
        $candidatos = [];   // key => cod => candidato
        $totalDocs = [];    // key|cod => nº de documentos para esa materia

        foreach ($filasDoc as $f) {
            $key = $mapaCodigo[trim((string) $f->COD_PLAN) . '|' . trim((string) $f->COD_MATERIA)]
                ?? $this->clave($f->NOMBRE_MATERIA);

            if (!isset($materias[$key])) {
                continue; // la materia no se dicta en el periodo
            }

            $cod = (string) $f->COD_DOCENTE;

            if ($opts['alcance'] === 'materia' && !isset($materias[$key]['actuales'][$cod])) {
                continue;
            }
            if (in_array($this->clave($f->CATEGORIA), self::CATEGORIAS_EXCLUIDAS, true)) {
                continue;
            }

            $tier = self::TIER_POR_TIPO[$f->TIPO_DOCUMENTO] ?? self::TIER_DOC_DESCONOCIDO;
            $anioDoc = is_numeric($f->GESTION) ? (int) $f->GESTION : 9999;
            $nota = is_numeric($f->NOTA) ? (float) $f->NOTA : null;

            $c = [
                'orden' => [$tier, $anioDoc, -($nota ?? -1), (int) $f->ID_DOCUMENTO],
                'tier' => $tier,
                'anio_doc' => $anioDoc,
                'nota' => $nota,
                'categoria' => trim((string) $f->CATEGORIA),
                'tipo_documento' => $f->TIPO_DOCUMENTO,
                'observaciones' => $this->textoObservacion($f, $referencias[$f->ID_DOCUMENTO] ?? [], $nota),
                'id_documento' => $f->ID_DOCUMENTO,
                'nombre_archivo' => $f->NOMBRE_ARCHIVO,
            ];

            $totalDocs["$key|$cod"] = ($totalDocs["$key|$cod"] ?? 0) + 1;

            if (!isset($candidatos[$key][$cod]) || $c['orden'] < $candidatos[$key][$cod]['orden']) {
                $candidatos[$key][$cod] = $c;
            }
        }

        // ── 4) Historial en GRUPOS -> años consecutivos regentando la materia ──
        $aniosPor = []; // key => cod => [anio => true]
        $hist = DB::table('GRUPOS as g')
            ->join('MATERIAS as m', function ($j) {
                $j->on('m.ANIO', '=', 'g.ANIO')
                    ->on('m.PERIODO', '=', 'g.PERIODO')
                    ->on('m.PLAN', '=', 'g.PLAN')
                    ->on('m.CODIGO', '=', 'g.MATERIA');
            })
            ->whereIn('g.PLAN', $this->planesValidos())
            ->where('g.TIPO', 'N')
            ->where('g.PRIMARIO', 'Y')
            ->where('g.ANIO', '<=', $anio)
            ->whereIn('g.DOCENTE', $this->gruposActivos('gb', $anio, $periodo)->select('gb.DOCENTE'))
            ->distinct()
            ->get(['g.ANIO as anio', 'g.DOCENTE as docente', 'm.NOMBRE as nombre']);

        foreach ($hist as $h) {
            $k = $this->clave($h->nombre);
            if (isset($materias[$k])) {
                $aniosPor[$k][(string) $h->docente][(int) $h->anio] = true;
            }
        }

        // ── 5) Fallback sin documento + armado final ──
        $data = [];
        $sinDocumento = 0;
        $n = 0;

        uasort($materias, fn($a, $b) => strcmp($this->clave($a['nombre']), $this->clave($b['nombre'])));

        foreach ($materias as $key => $mat) {
            $lista = $candidatos[$key] ?? [];

            foreach ($lista as $cod => &$c) {
                [$c['consecutivos'], $c['ultimo_anio']] = $this->consecutivos($aniosPor[$key][$cod] ?? []);
                $c['tiene_documento'] = true;
                $c['otros_documentos'] = max(0, ($totalDocs["$key|$cod"] ?? 1) - 1);
            }
            unset($c);

            if ($opts['incluir_sin_documento']) {
                foreach ($mat['actuales'] as $cod => $g) {
                    if (isset($lista[$cod])) {
                        continue;
                    }
                    [$cons, $ultimo] = $this->consecutivos($aniosPor[$key][$cod] ?? []);

                    $partes = [];
                    if ($g['resolucion'] !== '') {
                        $partes[] = $g['resolucion'];
                    }
                    if ($g['designacion'] !== '') {
                        $partes[] = $g['designacion'];
                    }
                    $partes[] = $cons > 0
                        ? "Imparte la asignatura {$cons} " . ($cons === 1 ? 'año' : 'años') . ' consecutivos, SIN PRELACIÓN'
                        : 'Sin documento de clasificación, SIN PRELACIÓN';

                    $lista[$cod] = [
                        'orden' => [self::TIER_SIN_DOCUMENTO, 9999, 0, 0],
                        'tier' => self::TIER_SIN_DOCUMENTO,
                        'anio_doc' => 9999,
                        'nota' => null,
                        'categoria' => $this->clave($g['tipo_ingreso']) !== '' ? $g['tipo_ingreso'] : '',
                        'tipo_documento' => null,
                        'observaciones' => implode(' - ', $partes),
                        'id_documento' => null,
                        'nombre_archivo' => null,
                        'consecutivos' => $cons,
                        'ultimo_anio' => $ultimo,
                        'tiene_documento' => false,
                        'otros_documentos' => 0,
                    ];
                }
            }

            if (empty($lista)) {
                continue; // materia sin ningún docente elegible
            }

            // Orden: tier -> año del documento ASC -> nota DESC -> años consecutivos DESC -> nombre
            $ordenados = collect($lista)->map(function ($c, $cod) use ($nombresDocentes) {
                $c['cod'] = (string) $cod;
                $c['nombre_docente'] = $nombresDocentes[(string) $cod] ?? ("Docente {$cod}");
                return $c;
            })->sort(function ($a, $b) {
                return $a['tier'] <=> $b['tier']
                    ?: $a['anio_doc'] <=> $b['anio_doc']
                    ?: ($b['nota'] ?? -1) <=> ($a['nota'] ?? -1)
                    ?: $b['consecutivos'] <=> $a['consecutivos']
                    ?: strcmp($this->clave($a['nombre_docente']), $this->clave($b['nombre_docente']));
            })->values();

            $n++;
            $carrera = $this->formatearCarreras(array_keys($mat['carreras']));
            $total = $ordenados->count();

            foreach ($ordenados as $i => $c) {
                if (!$c['tiene_documento']) {
                    $sinDocumento++;
                }

                $fila = [
                    'N' => $i === 0 ? $n : null,
                    'MATERIA' => $i === 0 ? $mat['nombre'] : '',
                    'CARRERA' => $i === 0 ? $carrera : '',
                    'COD_DOCENTE' => $c['cod'],
                    'DOCENTE' => $c['nombre_docente'],
                    'ORDEN' => $i + 1,
                    'CATEGORIA' => $c['categoria'],
                    'TIPO_DOCUMENTO' => $c['tipo_documento'],
                    'GESTION_DOCUMENTO' => $c['anio_doc'] === 9999 ? null : $c['anio_doc'],
                    'OBSERVACIONES' => $c['observaciones'],
                    'ANIOS_CONSECUTIVOS' => $c['consecutivos'],
                    'ULTIMO_ANIO_REGENTADO' => $c['ultimo_anio'],
                    'DOCENTE_DICTA_ESTA_MATERIA' => isset($mat['actuales'][$c['cod']]),
                    'SIN_DOCUMENTO' => !$c['tiene_documento'],
                    'OTROS_DOCUMENTOS' => $c['otros_documentos'],
                    'ID_DOCUMENTO' => $c['id_documento'],
                    'NOMBRE_ARCHIVO' => $c['nombre_archivo'],
                ];

                if ($i === 0) {
                    $fila['INICIO_GRUPO'] = true;
                    $fila['FILAS_GRUPO'] = $total;
                }
                if ($i === $total - 1) {
                    $fila['FIN_GRUPO'] = true;
                }

                $data[] = $fila;
            }
        }

        $fuera = array_values($fueraListado);
        sort($fuera);

        return [
            'data' => $data,
            'total_materias' => $n,
            'sin_documento' => $sinDocumento,
            'fuera_listado' => $fuera,
        ];
    }

    // ═════════════════════════════ HELPERS ═════════════════════════════

    // Códigos de plan siempre como string (PHP convierte claves numéricas a int).
    private function planesValidos(): array
    {
        return array_map('strval', array_keys(self::PLANES));
    }

    // Normalización para comparar contra MATERIAS_PERMITIDAS: clave() + abreviaturas + romanos mal OCR ("ll" -> "II").
    private function claveFiltro(?string $texto): string
    {
        $tokens = explode(' ', $this->clave($texto));
        foreach ($tokens as &$t) {
            if (preg_match('/^L{1,3}$/', $t)) {
                $t = str_repeat('I', strlen($t));   // l, ll, lll -> I, II, III
            } elseif (isset(self::ABREVIATURAS[$t])) {
                $t = self::ABREVIATURAS[$t];
            }
        }
        unset($t);

        return implode(' ', $tokens);
    }

    private function materiaPermitida(?string $nombre): bool
    {
        static $set = null;
        if ($set === null) {
            $set = [];
            foreach (self::MATERIAS_PERMITIDAS as $m) {
                $set[$this->claveFiltro($m)] = true;
            }
        }

        return isset($set[$this->claveFiltro($nombre)]);
    }

    // Grupos activos del periodo (misma regla que obtenerDocentesActivos()).
    private function gruposActivos(string $alias, int $anio, int $periodo)
    {
        return DB::table("GRUPOS as {$alias}")
            ->where("{$alias}.ANIO", $anio)
            ->where("{$alias}.PERIODO", $periodo)
            ->whereIn("{$alias}.PLAN", $this->planesValidos())
            ->where("{$alias}.TIPO", 'N')
            ->where("{$alias}.PRIMARIO", 'Y');
    }

    private function nombresDocentes(array $codigos): array
    {
        $out = [];
        foreach (array_chunk($codigos, 1000) as $chunk) {
            foreach (DB::table('DOCENTES')->whereIn('CODIGO', $chunk)->get(['CODIGO', 'APELLIDOS', 'NOMBRES']) as $d) {
                $out[(string) $d->CODIGO] = trim(($d->APELLIDOS ?? '') . ' ' . ($d->NOMBRES ?? ''));
            }
        }
        return $out;
    }

    // Normaliza nombres: mayúsculas, sin tildes ni puntuación, espacios colapsados, con alias.
    private function clave(?string $texto): string
    {
        $s = mb_strtoupper(trim((string) $texto), 'UTF-8');
        $s = strtr($s, ['Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U']);
        $s = preg_replace('/[^A-Z0-9Ñ ]+/u', ' ', $s);
        $s = trim(preg_replace('/\s+/', ' ', $s));

        return self::EQUIVALENCIAS[$s] ?? $s;
    }

    private function formatearCarreras(array $carreras): string
    {
        usort($carreras, function ($a, $b) {
            $ia = array_search($a, self::ORDEN_CARRERAS, true);
            $ib = array_search($b, self::ORDEN_CARRERAS, true);
            return ($ia === false ? 99 : $ia) <=> ($ib === false ? 99 : $ib);
        });
        return implode('-', $carreras);
    }

    /**
     * Años consecutivos regentando la materia, contando hacia atrás desde el último
     * año en que la dictó. Devuelve [nº años consecutivos, último año].
     */
    private function consecutivos(array $anios): array
    {
        if (empty($anios)) {
            return [0, null];
        }

        $lista = array_keys($anios);
        rsort($lista);
        $ultimo = $lista[0];
        $cont = 1;

        for ($i = 1; $i < count($lista); $i++) {
            if ($lista[$i] === $lista[$i - 1] - 1) {
                $cont++;
            } else {
                break;
            }
        }

        return [$cont, $ultimo];
    }

    private function textoObservacion(object $f, array $refs, ?float $nota): string
    {
        $partes = [];

        $tipo = self::ETIQUETAS_TIPO[$f->TIPO_DOCUMENTO] ?? $f->TIPO_DOCUMENTO;
        if (!empty($tipo)) {
            $partes[] = $tipo;
        }
        if (is_numeric($f->GESTION)) {
            $partes[] = 'Año ' . (int) $f->GESTION;
        }
        if (!empty($f->DETALLE_GENERAL)) {
            $partes[] = trim($f->DETALLE_GENERAL);
        }
        if (!empty($f->detalle_materia)) {
            $partes[] = trim($f->detalle_materia);
        }
        if ($nota !== null) {
            $partes[] = 'Nota ' . number_format($nota, 2, ',', '');
        }
        if (!empty($f->OBSERVACION)) {
            $partes[] = trim($f->OBSERVACION);
        }
        if (!empty($refs)) {
            $partes[] = implode(' / ', array_unique($refs));
        }

        return implode(' - ', $partes);
    }

    // ═════════════════════════════ EXCEL ═════════════════════════════

    private function escribirExcel(array $data, int $anio, int $periodo)
    {
        $romano = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV'][$periodo] ?? (string) $periodo;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Prelacion');
        $sheet->setShowGridlines(false);

        $sheet->setCellValue('B1', 'UNIVERSIDAD MAYOR DE SAN SIMÓN');
        $sheet->setCellValue('B2', 'FACULTAD DE CIENCIAS ECONÓMICAS');

        $sheet->setCellValue('B4', "ORDEN DE PRELACIÓN VIGENTE A PARTIR SEMESTRE {$romano}-{$anio}");
        $sheet->mergeCells('B4:G4');
        $sheet->getStyle('B4')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('B4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('B5', 'DIRECCIÓN ACADÉMICA');
        $sheet->mergeCells('B5:G5');
        $sheet->getStyle('B5')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('B5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $headers = ['B' => 'Nº', 'C' => 'MATERIA', 'D' => 'CARRERA', 'E' => 'DOCENTE', 'F' => 'CATEGORIA', 'G' => 'OBSERVACIONES'];
        foreach ($headers as $col => $texto) {
            $sheet->setCellValue($col . '7', $texto);
        }
        $sheet->getStyle('B7:G7')->getFont()->setBold(true);
        $sheet->getStyle('B7:G7')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('B7:G7')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9D9D9');
        $sheet->getStyle('B7:G7')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $fila = 8;
        foreach ($data as $item) {
            $sheet->setCellValue('B' . $fila, $item['N']);
            $sheet->setCellValue('C' . $fila, $item['MATERIA']);
            $sheet->setCellValue('D' . $fila, $item['CARRERA']);
            $sheet->setCellValue('E' . $fila, $item['DOCENTE']);
            $sheet->setCellValue('F' . $fila, $item['CATEGORIA']);
            $sheet->setCellValue('G' . $fila, $item['OBSERVACIONES']);

            $rango = "B{$fila}:G{$fila}";
            $sheet->getStyle($rango)->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle($rango)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle('B' . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('D' . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if (!empty($item['INICIO_GRUPO']) && ($item['FILAS_GRUPO'] ?? 1) > 1) {
                $fin = $fila + $item['FILAS_GRUPO'] - 1;
                foreach (['B', 'C', 'D'] as $col) {
                    $sheet->mergeCells("{$col}{$fila}:{$col}{$fin}");
                    $sheet->getStyle("{$col}{$fila}:{$col}{$fin}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                }
            }

            if (!empty($item['SIN_DOCUMENTO'])) {
                $sheet->getStyle('E' . $fila . ':G' . $fila)->getFont()->getColor()->setARGB('FF7F7F7F');
            }

            if (!empty($item['FIN_GRUPO'])) {
                $sheet->getStyle($rango)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
            }

            $fila++;
        }

        $widths = ['A' => 3, 'B' => 5, 'C' => 38, 'D' => 14, 'E' => 36, 'F' => 28, 'G' => 70];
        foreach ($widths as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }
        $sheet->freezePane('B8');

        $filename = "ORDEN_PRELACION_{$periodo}-{$anio}.xlsx";
        $tempPath = tempnam(sys_get_temp_dir(), 'xlsx_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($tempPath);

        return response()->download($tempPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}

