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
 *   categoria            opcional   default "ACÉFALA" (compara sin tildes ni mayúsculas)
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
            . Str::slug(Str::ascii($filtros['categoria']), '_')
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
        $request->merge(['planes' => array_values((array) $planes)]);

        $request->validate([
            'anio' => 'required|integer|min:2000|max:2100',
            'periodo' => 'required|string|in:1,2,3,4',
            'categoria' => 'nullable|string|max:30',
            'planes' => 'nullable|array',
            'planes.*' => ['string', Rule::in(array_keys(self::CARRERAS))],
            'incluir_compartidas' => 'nullable|boolean',
            'con_asignacion' => 'nullable|boolean',
        ]);

        $categoria = trim((string) $request->input('categoria', ''));

        return [
            'anio' => (int) $request->input('anio'),
            'periodo' => (string) $request->input('periodo'),
            'categoria' => $categoria !== '' ? $categoria : self::CATEGORIA_DEFECTO,
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
        $filas = $this->consultarGrupos($f['anio'], $f['periodo'], $f['categoria']);
        $secciones = $this->armarSecciones($filas, $f['planes'], $f['incluir_compartidas']);

        $totalFilas = 0;
        $totalMaterias = 0;
        foreach ($secciones as $s) {
            $totalFilas += count($s['filas']);
            $totalMaterias += $s['total_materias'];
        }

        return [
            'titulo' => $this->titulo($f['categoria'], $f['anio'], $f['periodo']),
            'anio' => $f['anio'],
            'periodo' => $f['periodo'],
            'categoria' => $f['categoria'],
            'planes_filtro' => $f['planes'],
            'total_materias' => $totalMaterias,   // sin contar las filas compartidas
            'total_filas' => $totalFilas,
            'secciones' => $secciones,
        ];
    }

    private function titulo(string $categoria, int $anio, string $periodo): string
    {
        $esAcefala = Str::upper(Str::ascii($categoria)) === 'ACEFALA';
        $texto = $esAcefala ? 'ACÉFALAS' : mb_strtoupper($categoria);

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
     *  - docente_anterior: último docente DISTINTO del actual que dictó ese
     *    mismo plan+materia+grupo en una gestión anterior (sin verano/invierno).
     *  - ch: carga horaria = 2 por cada registro de HORARIOS2 (tipo 'C'), con la
     *    misma regla de horas excluidas que ReporteDocenteController@horario.
     *  - comp / comparte / orden_comparte: de GRUPOS_COMPARTIDOS.
     *  - ID_DOCUMENTO / NOMBRE_ARCHIVO: documento que respalda la designación.
     *    Se obtiene siguiendo el mismo vínculo que usa ClasificacionDocenteController@aplicarEnGrupos:
     *    GRUPOS → CLASIFICACION_MATERIA → CLASIFICACION_DOCENTE → CLASIFICACION_DOCUMENTO
     *    (mismo plan, materia, grupo, docente, gestión, periodo y categoría).
     *    Solo se toman documentos que tengan archivo PDF subido (RUTA_ARCHIVO).
     */
    private function consultarGrupos(int $anio, string $periodo, string $categoria): array
    {
        $planesSql = implode(',', array_map(fn($p) => "'" . $p . "'", array_keys(self::CARRERAS)));

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
            ANT.NOMBRE                             AS docente_anterior,
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
            SELECT TOP 1 LTRIM(RTRIM(DA.APELLIDOS + ' ' + DA.NOMBRES)) AS NOMBRE
            FROM GRUPOS AS G2
            INNER JOIN DOCENTES AS DA
                ON DA.CODIGO = G2.DOCENTE
            WHERE G2.[PLAN]    = GRUPOS.[PLAN]
              AND G2.MATERIA   = GRUPOS.MATERIA
              AND G2.GRUPO     = GRUPOS.GRUPO
              AND G2.PRIMARIO  = 'Y'
              AND G2.TIPO      = 'N'
              AND G2.PERIODO NOT IN ('3','4')
              AND (G2.ANIO < GRUPOS.ANIO
                   OR (G2.ANIO = GRUPOS.ANIO AND G2.PERIODO < GRUPOS.PERIODO))
              AND (GRUPOS.DOCENTE IS NULL OR G2.DOCENTE <> GRUPOS.DOCENTE)
            ORDER BY G2.ANIO DESC, G2.PERIODO DESC
        ) AS ANT

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
              = UPPER(LTRIM(RTRIM(:categoria))) COLLATE Modern_Spanish_CI_AI

        ORDER BY GRUPOS.[PLAN], GRUPOS.MATERIA, GRUPOS.GRUPO
        SQL;

        return DB::connection('sqlsrv')->select($sql, [
            'anio' => $anio,
            'periodo' => $periodo,
            'categoria' => $categoria,
        ]);
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
            'docente_anterior' => trim((string) $f->docente_anterior),
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

            // Agrupa por docente anterior (los vacíos al final), luego materia y grupo
            usort($lista, function ($a, $b) {
                $ka = [$a[0]['docente_anterior'] === '' ? 1 : 0, $a[0]['docente_anterior'], $a[0]['cod_materia'], $a[0]['grupo']];
                $kb = [$b[0]['docente_anterior'] === '' ? 1 : 0, $b[0]['docente_anterior'], $b[0]['cod_materia'], $b[0]['grupo']];
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
    // ═════════════════════════════════════════════════════════════
    private function escribirHoja(Worksheet $sheet, array $reporte, bool $conAsignacion): void
    {
        $NEGRO = 'FF000000';
        $GRIS_HEAD = 'FFD9D9D9';
        $GRIS_SEP = 'FFBFBFBF';

        $ultimaCol = 'P';
        $columnasSeparadoras = ['D', 'I', 'N'];

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
            'Docente Anterior',
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
                    $fila['nro'],
                    $fila['docente_anterior'],
                    null,
                    null,
                    null,
                    $fila['nombre_materia'],
                    null,
                    $fila['ch'],
                    null,
                    $conAsignacion ? $fila['docente_actual'] : null,
                    null,
                    $fila['compartido'],
                    $conAsignacion ? $fila['resolucion'] : null,
                    null,
                    $conAsignacion ? $fila['designacion'] : null,
                    $fila['tipo_ingreso'],
                ], null, "A{$r}");

                // Texto explícito para no perder ceros a la izquierda (grupo "01", códigos)
                $sheet->setCellValueExplicit("C{$r}", $fila['plan_abrev'] . "\n" . $fila['cod_plan'], DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("E{$r}", $fila['cod_materia'], DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("G{$r}", $fila['grupo'], DataType::TYPE_STRING);

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

            foreach (['A', 'C', 'E', 'G', 'H', 'K', 'L', 'P'] as $c) {
                $sheet->getStyle("{$c}{$primera}:{$c}{$ultima}")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
            foreach (['C', 'L', 'P'] as $c) {
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
            'B' => 28,
            'C' => 9,
            'D' => 1.5,
            'E' => 11,
            'F' => 32,
            'G' => 7,
            'H' => 5,
            'I' => 1.5,
            'J' => 30,
            'K' => 14,
            'L' => 18,
            'M' => 22,
            'N' => 1.5,
            'O' => 30,
            'P' => 14,
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