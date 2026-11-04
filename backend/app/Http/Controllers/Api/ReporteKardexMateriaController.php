<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Reporte "Kardex Materia": lista de materias (grupos) de una gestión cuyo
 * TIPO_INGRESO sea una categoría dada (por defecto ACÉFALA), agrupada por
 * carrera (plan) y exportable a Excel con el formato de la lista manual.
 *
 * Endpoints sugeridos (ver rutas al final del archivo):
 *   GET /api/reporte-kardex-materia/planes
 *   GET /api/reporte-kardex-materia/listado   (JSON, para vista previa)
 *   GET /api/reporte-kardex-materia/excel     (descarga .xlsx)
 *
 * Parámetros (listado y excel):
 *   anio                 requerido  ej. 2026
 *   periodo              requerido  1 | 2 | 3 | 4
 *   categorias[]         opcional   varias; default "ACÉFALA" (compara sin tildes ni mayúsculas)
 *   planes[]             opcional   códigos de plan; vacío = todas las carreras
 *   incluir_compartidas  opcional   default 1: incluye las filas "(*) COMPARTIDO" bajo su materia
 *   con_asignacion       opcional   default 1: rellena Nombre Docente / Resolución / Designación
 *                                   (0 = deja esas columnas en blanco, como formulario a llenar)
 *
 * Cada fila de /listado incluye además `id_documento` y `nombre_archivo`
 * (documento que respalda la designación) para que el frontend abra el PDF.
 */
class ReporteKardexMateriaController extends Controller
{
    private const CATEGORIA_DEFECTO = 'ACÉFALA';

    /**
     * Carreras (planes) y su orden de aparición en el Excel.
     * Mismos códigos que usa ReporteDocenteController@horario.
     */
    private const CARRERAS = [
        '109401' => ['abrev' => 'ADM', 'nombre' => 'ADMINISTRACIÓN DE EMPRESAS'],
        '125091' => ['abrev' => 'COM', 'nombre' => 'INGENIERÍA COMERCIAL'],
        '126091' => ['abrev' => 'FIN', 'nombre' => 'INGENIERÍA FINANCIERA'],
        '089801' => ['abrev' => 'CCP', 'nombre' => 'CONTADURÍA PÚBLICA'],
        '059801' => ['abrev' => 'ECO', 'nombre' => 'ECONOMÍA'],
    ];

    // ─────────────────────────────────────────────────────────────
    // GET /reporte-kardex-materia/planes
    // ─────────────────────────────────────────────────────────────
    public function planes()
    {
        $lista = [];
        foreach (self::CARRERAS as $codigo => $c) {
            $lista[] = [
                'cod_plan' => $codigo,
                'abrev' => $c['abrev'],
                'nombre' => $c['nombre'],
            ];
        }

        return response()->json($lista);
    }

    // ─────────────────────────────────────────────────────────────
    // GET /reporte-kardex-materia/listado
    // ─────────────────────────────────────────────────────────────
    public function listado(Request $request)
    {
        $filtros = $this->filtros($request);
        $reporte = $this->construirReporte($filtros);

        return response()->json(['success' => true] + $reporte);
    }

    // ─────────────────────────────────────────────────────────────
    // GET /reporte-kardex-materia/excel
    // ─────────────────────────────────────────────────────────────
    public function excel(Request $request)
    {
        $filtros = $this->filtros($request);
        $reporte = $this->construirReporte($filtros);

        if ($reporte['total_filas'] === 0) {
            return response()->json([
                'success' => false,
                'message' => 'No hay materias con esa categoría en la gestión y carreras indicadas.',
            ], 404);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Materias');
        $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(9);

        $this->escribirHoja($sheet, $reporte, $filtros['con_asignacion']);

        $sufijoPlanes = empty($filtros['planes'])
            ? ''
            : '_' . implode('-', array_map(fn($p) => self::CARRERAS[$p]['abrev'], $filtros['planes']));

        $nombreArchivo = 'kardex_materias_'
            . Str::slug(Str::ascii(implode('-', $filtros['categorias'])), '_')
            . '_' . $filtros['anio'] . '_' . $filtros['periodo']
            . $sufijoPlanes . '.xlsx';

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $nombreArchivo, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    // ═════════════════════════════════════════════════════════════
    //  FILTROS
    // ═════════════════════════════════════════════════════════════
    private function filtros(Request $request): array
    {
        // Acepta planes[]=a&planes[]=b  o  planes=a,b
        $planes = $request->query('planes', []);
        if (is_string($planes)) {
            $planes = array_filter(array_map('trim', explode(',', $planes)));
        }
        // Acepta categorias[]=a&categorias[]=b  (o el antiguo categoria=a)
        $categorias = (array) $request->query('categorias', []);
        if ($request->filled('categoria')) {
            $categorias[] = (string) $request->query('categoria');
        }
        $categorias = array_values(array_unique(array_filter(
            array_map(fn($c) => trim((string) $c), $categorias),
            fn($c) => $c !== ''
        )));

        $request->merge([
            'planes' => array_values((array) $planes),
            'categorias' => $categorias,
        ]);

        $request->validate([
            'anio' => 'required|integer|min:2000|max:2100',
            'periodo' => 'required|string|in:1,2,3,4',
            'categorias' => 'nullable|array|max:20',
            'categorias.*' => 'string|max:60',
            'planes' => 'nullable|array',
            'planes.*' => ['string', Rule::in(array_keys(self::CARRERAS))],
            'incluir_compartidas' => 'nullable|boolean',
            'con_asignacion' => 'nullable|boolean',
        ]);

        return [
            'anio' => (int) $request->input('anio'),
            'periodo' => (string) $request->input('periodo'),
            // Sin selección = ACÉFALA
            'categorias' => !empty($categorias) ? $categorias : [self::CATEGORIA_DEFECTO],
            'planes' => array_values(array_unique($request->input('planes', []))),
            'incluir_compartidas' => $request->boolean('incluir_compartidas', true),
            'con_asignacion' => $request->boolean('con_asignacion', true),
        ];
    }

    // ═════════════════════════════════════════════════════════════
    //  CONSTRUCCIÓN DEL REPORTE
    // ═════════════════════════════════════════════════════════════
    private function construirReporte(array $f): array
    {
        $filas = $this->consultarGrupos($f['anio'], $f['periodo'], $f['categorias']);
        $secciones = $this->armarSecciones($filas, $f['planes'], $f['incluir_compartidas']);

        $totalFilas = 0;
        $totalMaterias = 0;
        foreach ($secciones as $s) {
            $totalFilas += count($s['filas']);
            $totalMaterias += $s['total_materias'];
        }

        return [
            'titulo' => $this->titulo($f['categorias'], $f['anio'], $f['periodo']),
            'anio' => $f['anio'],
            'periodo' => $f['periodo'],
            'categoria' => implode(', ', $f['categorias']),
            'categorias' => $f['categorias'],
            'planes_filtro' => $f['planes'],
            'total_materias' => $totalMaterias,   // sin contar las filas compartidas
            'total_filas' => $totalFilas,
            'secciones' => $secciones,
        ];
    }

    private function titulo(array $categorias, int $anio, string $periodo): string
    {
        $textos = array_map(function ($c) {
            $esAcefala = in_array(Str::upper(Str::ascii($c)), ['ACEFALA', 'ACEFALAS'], true);
            return $esAcefala ? 'ACÉFALAS' : mb_strtoupper($c);
        }, $categorias);
        $texto = implode(' / ', $textos);

        $periodoLabel = match ($periodo) {
            '3' => 'VERANO',
            '4' => 'INVIERNO',
            default => $periodo,
        };

        return "LISTA DE MATERIAS {$texto} - GESTIÓN {$periodoLabel}/{$anio}";
    }

    /**
     * Una fila por GRUPO (materia + plan + grupo) de la gestión con la
     * categoría pedida en GRUPOS.TIPO_INGRESO.
     *
     *  - ch: carga horaria = 2 por cada registro de HORARIOS2 (tipo 'C'), con la
     *    misma regla de horas excluidas que ReporteDocenteController@horario.
     *  - comp / comparte / orden_comparte: de GRUPOS_COMPARTIDOS.
     *  - ID_DOCUMENTO / NOMBRE_ARCHIVO: documento que respalda la designación.
     *    Se obtiene siguiendo el mismo vínculo que usa ClasificacionDocenteController@aplicarEnGrupos:
     *    GRUPOS → CLASIFICACION_MATERIA → CLASIFICACION_DOCENTE → CLASIFICACION_DOCUMENTO
     *    (mismo plan, materia, grupo, docente, gestión, periodo y categoría).
     *    Solo se toman documentos que tengan archivo PDF subido (RUTA_ARCHIVO).
     */
    private function consultarGrupos(int $anio, string $periodo, array $categorias): array
    {
        $planesSql = implode(',', array_map(fn($p) => "'" . $p . "'", array_keys(self::CARRERAS)));

        // Una categoría = un parámetro enlazado (:cat0, :cat1, ...)
        $bindings = ['anio' => $anio, 'periodo' => $periodo];
        $categoriasSql = [];
        foreach (array_values($categorias) as $i => $cat) {
            $categoriasSql[] = "UPPER(LTRIM(RTRIM(:cat{$i}))) COLLATE Modern_Spanish_CI_AI";
            $bindings["cat{$i}"] = $cat;
        }
        $categoriasIn = implode(', ', $categoriasSql);

        $sql = <<<SQL
        SELECT
            GRUPOS.ANIO,
            GRUPOS.PERIODO,
            GRUPOS.[PLAN]                          AS cod_plan,
            GRUPOS.MATERIA                         AS cod_materia,
            ISNULL(MATERIAS.NOMBRE, 'SIN NOMBRE')  AS nombre_materia,
            GRUPOS.GRUPO                           AS grupo,
            GRUPOS.DOCENTE                         AS cod_docente,
            LTRIM(RTRIM(DOC.APELLIDOS + ' ' + DOC.NOMBRES)) AS docente_actual,
            ISNULL(HOR.CH, 0)                      AS ch,
            GRUPOS.RESOLUCION,
            GRUPOS.DESIGNACION,
            GRUPOS.TIPO_INGRESO,
            ISNULL(CAST(GC.COMP AS VARCHAR(10)), '')       AS comp,
            ISNULL(CAST(GC.COMPARTIDO AS VARCHAR(50)), '') AS comparte,
            GC.ORDEN                               AS orden_comparte,

            -- Documento (PDF) que respalda la designación
            DOCX.ID_DOCUMENTO                      AS ID_DOCUMENTO,
            DOCX.NOMBRE_ARCHIVO                    AS NOMBRE_ARCHIVO

        FROM GRUPOS

        INNER JOIN MATERIAS
            ON  MATERIAS.CODIGO  = GRUPOS.MATERIA
            AND MATERIAS.ANIO    = GRUPOS.ANIO
            AND MATERIAS.PERIODO = GRUPOS.PERIODO
            AND MATERIAS.[PLAN]  = GRUPOS.[PLAN]

        LEFT JOIN DOCENTES AS DOC
            ON DOC.CODIGO = GRUPOS.DOCENTE

        OUTER APPLY (
            SELECT TOP 1 GC2.COMP, GC2.COMPARTIDO, GC2.ORDEN
            FROM GRUPOS_COMPARTIDOS AS GC2
            WHERE GC2.[PLAN]   = GRUPOS.[PLAN]
              AND GC2.MATERIA  = GRUPOS.MATERIA
              AND GC2.GRUPO    = GRUPOS.GRUPO
              AND GC2.PRIMARIO = GRUPOS.PRIMARIO
            ORDER BY GC2.ORDEN
        ) AS GC

        OUTER APPLY (
            SELECT COUNT(*) * 2 AS CH
            FROM HORARIOS2 AS H
            WHERE H.ANIO    = GRUPOS.ANIO
              AND H.PERIODO = GRUPOS.PERIODO
              AND H.MATERIA = GRUPOS.MATERIA
              AND H.GRUPO   = GRUPOS.GRUPO
              AND H.TIPO    = 'C'
              AND H.HORA NOT IN (730, 900, 1030, 1200, 1330, 1500, 1630, 1800, 1930, 2100)
        ) AS HOR

        -- Documento que respalda la designación (el más reciente con PDF)
        OUTER APPLY (
            SELECT TOP 1
                cdoc.ID_DOCUMENTO,
                cdoc.NOMBRE_ARCHIVO
            FROM CLASIFICACION_MATERIA AS cm
            INNER JOIN CLASIFICACION_DOCENTE AS ccd
                ON ccd.ID_CLASIFICACION_DOCENTE = cm.ID_CLASIFICACION_DOCENTE
            INNER JOIN CLASIFICACION_DOCUMENTO AS cdoc
                ON cdoc.ID_DOCUMENTO = ccd.ID_DOCUMENTO
            WHERE cm.COD_PLAN    COLLATE Modern_Spanish_CI_AS = GRUPOS.[PLAN]  COLLATE Modern_Spanish_CI_AS
              AND cm.COD_MATERIA COLLATE Modern_Spanish_CI_AS = GRUPOS.MATERIA COLLATE Modern_Spanish_CI_AS
              AND CAST(cm.[GRUPO] AS VARCHAR(10)) COLLATE Modern_Spanish_CI_AS
                  = CAST(GRUPOS.GRUPO AS VARCHAR(10)) COLLATE Modern_Spanish_CI_AS
              AND ccd.COD_DOCENTE = GRUPOS.DOCENTE
              AND CAST(cdoc.GESTION AS VARCHAR(10)) = CAST(GRUPOS.ANIO AS VARCHAR(10))
              AND cdoc.PERIODO COLLATE Modern_Spanish_CI_AS = GRUPOS.PERIODO COLLATE Modern_Spanish_CI_AS
              AND UPPER(LTRIM(RTRIM(COALESCE(cdoc.CATEGORIA, '')))) COLLATE Modern_Spanish_CI_AI
                  = UPPER(LTRIM(RTRIM(COALESCE(GRUPOS.TIPO_INGRESO, '')))) COLLATE Modern_Spanish_CI_AI
              AND cdoc.RUTA_ARCHIVO IS NOT NULL
            ORDER BY cdoc.FECHA_REGISTRO DESC, cdoc.ID_DOCUMENTO DESC
        ) AS DOCX

        WHERE GRUPOS.PRIMARIO = 'Y'
          AND GRUPOS.TIPO     = 'N'
          AND GRUPOS.ANIO     = :anio
          AND GRUPOS.PERIODO  = :periodo
          AND GRUPOS.[PLAN] IN ({$planesSql})
          AND UPPER(LTRIM(RTRIM(COALESCE(GRUPOS.TIPO_INGRESO, '')))) COLLATE Modern_Spanish_CI_AI
              IN ({$categoriasIn})

        ORDER BY GRUPOS.[PLAN], GRUPOS.MATERIA, GRUPOS.GRUPO
        SQL;

        return DB::connection('sqlsrv')->select($sql, $bindings);
    }

    private function normalizarFila(object $f): array
    {
        $plan = trim((string) $f->cod_plan);
        $esHija = trim((string) $f->comp) === '1';

        return [
            'cod_plan' => $plan,
            'plan_abrev' => self::CARRERAS[$plan]['abrev'] ?? '',
            'cod_materia' => trim((string) $f->cod_materia),
            'nombre_materia' => trim((string) $f->nombre_materia),
            'grupo' => trim((string) $f->grupo),
            'ch' => (int) $f->ch,
            'cod_docente' => trim((string) $f->cod_docente),
            'docente_actual' => trim((string) $f->docente_actual),
            'resolucion' => trim((string) $f->RESOLUCION),
            'designacion' => trim((string) $f->DESIGNACION),
            'tipo_ingreso' => mb_strtoupper(trim((string) $f->TIPO_INGRESO)),
            'compartido' => $esHija ? '(*) COMPARTIDO' : '',
            'comparte' => trim((string) $f->comparte),
            'orden_comparte' => $f->orden_comparte !== null ? (int) $f->orden_comparte : null,
            'es_hija' => $esHija,

            // Documento que respalda la designación (para el botón "PDF" del frontend)
            'id_documento' => $f->ID_DOCUMENTO !== null ? (int) $f->ID_DOCUMENTO : null,
            'nombre_archivo' => $f->NOMBRE_ARCHIVO !== null ? trim((string) $f->NOMBRE_ARCHIVO) : null,
        ];
    }

    /**
     * Agrupa las filas en "bloques" (materia principal + sus compartidas),
     * los reparte por carrera según el plan de la PRINCIPAL y numera.
     *
     * Vínculo de compartidas: mismas GRUPOS_COMPARTIDOS.COMPARTIDO y mismo
     * docente. Dentro del bloque, la fila con COMP <> 1 (por ORDEN) es la
     * principal; las COMP = 1 son las compartidas ("hijas", CH = 0).
     */
    private function armarSecciones(array $filas, array $planesFiltro, bool $incluirCompartidas): array
    {
        $bloques = [];
        $conjuntos = [];

        foreach ($filas as $f) {
            $fila = $this->normalizarFila($f);

            if ($fila['comparte'] === '') {
                $bloques[] = [$fila];
            } else {
                $conjuntos[$fila['comparte'] . '|' . $fila['cod_docente']][] = $fila;
            }
        }

        foreach ($conjuntos as $miembros) {
            usort(
                $miembros,
                fn($a, $b) =>
                    [$a['es_hija'], $a['orden_comparte'] ?? 0] <=> [$b['es_hija'], $b['orden_comparte'] ?? 0]
            );
            $bloques[] = $miembros;
        }

        // Reparto por carrera (plan de la principal) + filtro de planes
        $porPlan = [];
        foreach ($bloques as $miembros) {
            $planPrincipal = $miembros[0]['cod_plan'];

            if (!empty($planesFiltro) && !in_array($planPrincipal, $planesFiltro, true)) {
                continue;
            }

            $porPlan[$planPrincipal][] = $miembros;
        }

        $secciones = [];

        foreach (self::CARRERAS as $codPlan => $carrera) {
            if (empty($porPlan[$codPlan])) {
                continue;
            }

            $lista = $porPlan[$codPlan];

            // Orden por materia y grupo
            usort($lista, function ($a, $b) {
                $ka = [$a[0]['cod_materia'], $a[0]['grupo']];
                $kb = [$b[0]['cod_materia'], $b[0]['grupo']];
                return $ka <=> $kb;
            });

            $filasSeccion = [];
            $nro = 0;

            foreach ($lista as $miembros) {
                $nro++;
                $visibles = $incluirCompartidas ? $miembros : [$miembros[0]];

                foreach ($visibles as $i => $m) {
                    if ($i > 0) {
                        $m['ch'] = 0; // la carga horaria se cuenta una sola vez (en la principal)
                    }
                    $m['nro'] = $i === 0 ? $nro : null;
                    $m['rowspan'] = $i === 0 ? count($visibles) : 0;
                    $filasSeccion[] = $m;
                }
            }

            $secciones[] = [
                'cod_plan' => $codPlan,
                'abrev' => $carrera['abrev'],
                'carrera' => $carrera['nombre'],
                'total_materias' => $nro,
                'filas' => $filasSeccion,
            ];
        }

        return $secciones;
    }

    // ═════════════════════════════════════════════════════════════
    //  EXCEL
    //  Columnas: A Nº | B Plan | C (sep) | D SIS-Mat | E Materia | F Grupo | G CH
    //            | H (sep) | I Docente | J D-H-A | K Compartido | L Resolución
    //            | M (sep) | N Designación | O Tipo
    // ═════════════════════════════════════════════════════════════
    private function escribirHoja(Worksheet $sheet, array $reporte, bool $conAsignacion): void
    {
        $NEGRO = 'FF000000';
        $GRIS_HEAD = 'FFD9D9D9';
        $GRIS_SEP = 'FFBFBFBF';

        $ultimaCol = 'O';
        $columnasSeparadoras = ['C', 'H', 'M'];

        // Título
        $sheet->mergeCells("A1:{$ultimaCol}1");
        $sheet->setCellValue('A1', $reporte['titulo']);
        $sheet->getStyle('A1')->getFont()->setName('Times New Roman')->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(28);

        $encabezados = [
            'Nº',
            'Plan',
            '',
            'SIS-Mat',
            'Nombre Materia',
            'Grupo',
            'CH',
            '',
            'Nombre Docente',
            'D- H - A',
            'Compartido',
            'RESOLUCION',
            '',
            'Designación',
            'TIPO DESIG',
        ];

        $r = 3;

        foreach ($reporte['secciones'] as $idx => $sec) {
            // Cada carrera en su propia hoja al imprimir
            if ($idx > 0) {
                $sheet->setBreak('A' . ($r - 1), Worksheet::BREAK_ROW);
            }

            // Título de carrera
            $sheet->setCellValue("A{$r}", 'CARRERA ' . $sec['carrera']);
            $sheet->getStyle("A{$r}")->getFont()->setBold(true)->setSize(13);
            $sheet->getRowDimension($r)->setRowHeight(22);
            $r++;

            // Encabezado de tabla
            $sheet->fromArray($encabezados, null, "A{$r}");
            $rangoHead = "A{$r}:{$ultimaCol}{$r}";
            $sheet->getStyle($rangoHead)->getFont()->setBold(true)->setSize(10);
            $sheet->getStyle($rangoHead)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($GRIS_HEAD);
            $sheet->getStyle($rangoHead)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER)
                ->setWrapText(true);
            $sheet->getStyle($rangoHead)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB($NEGRO);
            foreach ($columnasSeparadoras as $c) {
                $sheet->getStyle("{$c}{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($GRIS_SEP);
            }
            $sheet->getRowDimension($r)->setRowHeight(20);
            $r++;

            $primera = $r;

            foreach ($sec['filas'] as $fila) {
                $sheet->fromArray([
                    $fila['nro'],                                            // A
                    null,                                                    // B Plan (explícito abajo)
                    null,                                                    // C sep
                    null,                                                    // D SIS-Mat (explícito abajo)
                    $fila['nombre_materia'],                                 // E
                    null,                                                    // F Grupo (explícito abajo)
                    $fila['ch'],                                             // G
                    null,                                                    // H sep
                    $conAsignacion ? $fila['docente_actual'] : null,         // I
                    null,                                                    // J D-H-A
                    $fila['compartido'],                                     // K
                    $conAsignacion ? $fila['resolucion'] : null,             // L
                    null,                                                    // M sep
                    $conAsignacion ? $fila['designacion'] : null,            // N
                    $fila['tipo_ingreso'],                                   // O
                ], null, "A{$r}");

                // Texto explícito para no perder ceros a la izquierda (grupo "01", códigos)
                $sheet->setCellValueExplicit("B{$r}", $fila['plan_abrev'] . "\n" . $fila['cod_plan'], DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("D{$r}", $fila['cod_materia'], DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("F{$r}", $fila['grupo'], DataType::TYPE_STRING);

                if ($fila['rowspan'] > 1) {
                    $sheet->mergeCells("A{$r}:A" . ($r + $fila['rowspan'] - 1));
                }

                $sheet->getRowDimension($r)->setRowHeight(30);
                $r++;
            }

            $ultima = $r - 1;
            $rangoDatos = "A{$primera}:{$ultimaCol}{$ultima}";

            $sheet->getStyle($rangoDatos)->getAlignment()
                ->setVertical(Alignment::VERTICAL_CENTER)
                ->setWrapText(true);
            $sheet->getStyle($rangoDatos)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB($NEGRO);

            foreach (['A', 'B', 'D', 'F', 'G', 'J', 'K', 'O'] as $c) {
                $sheet->getStyle("{$c}{$primera}:{$c}{$ultima}")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
            foreach (['B', 'K', 'O'] as $c) {
                $sheet->getStyle("{$c}{$primera}:{$c}{$ultima}")->getFont()->setBold(true);
            }
            foreach ($columnasSeparadoras as $c) {
                $sheet->getStyle("{$c}{$primera}:{$c}{$ultima}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($GRIS_SEP);
            }

            $r += 2; // línea en blanco entre carreras
        }

        $anchos = [
            'A' => 5,
            'B' => 9,
            'C' => 1.5,
            'D' => 11,
            'E' => 32,
            'F' => 7,
            'G' => 5,
            'H' => 1.5,
            'I' => 30,
            'J' => 14,
            'K' => 18,
            'L' => 22,
            'M' => 1.5,
            'N' => 30,
            'O' => 14,
        ];
        foreach ($anchos as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        $fechaGeneracion = now()->format('d/m/Y h:i:s A');
        $sheet->getHeaderFooter()->setOddFooter(
            '&L&7Procesado UTI - Facultad de Ciencias Económicas' .
            '&C&7Página &P de &N' .
            '&R&7' . $fechaGeneracion
        );
        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        $sheet->getPageMargins()->setTop(0.5)->setBottom(0.6)->setLeft(0.4)->setRight(0.4);
    }
}