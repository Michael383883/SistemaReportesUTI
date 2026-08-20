<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClasificacionDocente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReporteExcelController extends Controller
{
    // GET /api/reportes/docentes-clasificados/preview
    public function previsualizar(Request $request)
    {
        try {
            $gestionDesde = $request->query('gestion_desde', '2001');
            $gestionHasta = $request->query('gestion_hasta');
            $periodo = $request->query('periodo');
            $version = $request->query('version', '5ta Versión');

            // Ahora llegan como texto separado por comas: "Docentes Titulares,Acefala"
            $categorias = $this->parseListaCsv($request->query('categoria'));
            $tiposTitulo = $this->parseListaCsv($request->query('tipo_titulo'));

            $etiquetaGestion = $gestionHasta
                ? "{$gestionDesde} - {$gestionHasta}"
                : "Desde {$gestionDesde}";

            $data = $this->construirDatos($gestionDesde, $gestionHasta, $periodo, $categorias, $tiposTitulo);

            return response()->json([
                'ok' => true,
                'gestion' => $etiquetaGestion,
                'version' => $version,
                'total_filas' => count($data),
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Convierte "a,b, c" en ['a', 'b', 'c'], descartando vacíos. Si viene null/vacío, devuelve [].
    private function parseListaCsv(?string $valor): array
    {
        if (empty($valor)) {
            return [];
        }
        return array_values(array_filter(array_map('trim', explode(',', $valor))));
    }

    private const ORDEN_NIVELES = [
        'PRIMER NIVEL' => 1,
        'SEGUNDO NIVEL' => 2,
        'TERCER NIVEL' => 3,
    ];

    private const ETIQUETAS_TIPO_DOCUMENTO = [
        'TITULARIDAD_HISTORICA' => 'Titularidad Histórica',
        'TITULARIDAD_RCU_43_11' => 'Titularidad RCU 43/11',
        'EXAMEN_SUFICIENCIA' => 'Examen de Suficiencia',
        'CONCURSO_MERITOS' => 'Concurso de Méritos',
    ];

    // GET /api/reportes/docentes-clasificados/excel
    public function generarListadoDocentes(Request $request)
    {
        try {
            $gestionDesde = $request->query('gestion_desde', '2001');
            $gestionHasta = $request->query('gestion_hasta');
            $periodo = $request->query('periodo');
            $version = $request->query('version', '5ta Versión');

            $categorias = $this->parseListaCsv($request->query('categoria'));
            $tiposTitulo = $this->parseListaCsv($request->query('tipo_titulo'));

            $etiquetaGestion = $gestionHasta
                ? "{$gestionDesde} - {$gestionHasta}"
                : "Desde {$gestionDesde}";

            $data = $this->construirDatos($gestionDesde, $gestionHasta, $periodo, $categorias, $tiposTitulo);

            if (empty($data)) {
                return response()->json([
                    'ok' => false,
                    'error' => "No se encontraron registros desde la gestión {$gestionDesde}" . ($gestionHasta ? " hasta {$gestionHasta}" : ''),
                ], 404);
            }

            return $this->generarExcel($data, $etiquetaGestion, $version);
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * IMPORTANTE: trabaja a nivel CLASIFICACION_DOCENTE (documento + un docente).
     * GESTION, PERIODO, TIPO_DOCUMENTO, CATEGORIA, NIVEL, FOTOCOPIA_TITULAR viven en el
     * documento (documento()), las materias son propias del docente dentro del documento
     * (materias(), vía ID_CLASIFICACION_DOCENTE), las referencias son del documento
     * completo (documento->referencias), y los títulos (CLASIFICACION_TITULO) se traen
     * aparte por lote, agrupados por ID_CLASIFICACION_DOCENTE.
     *
     * $categoria filtra por CLASIFICACION_DOCUMENTO.CATEGORIA.
     * $tipoTitulo filtra por CLASIFICACION_TITULO.TIPO_TITULO (solo aparecen docentes
     * que tengan al menos un título de esa categoría).
     */
    private function construirDatos(
        string $gestionDesde,
        ?string $gestionHasta = null,
        ?int $periodo = null,
        array $categorias = [],
        array $tiposTitulo = []
    ): array {
        $query = ClasificacionDocente::with([
            'docente',
            'documento.referencias',
            'materias',
        ])
            ->whereHas('documento', function ($q) use ($gestionDesde, $gestionHasta, $periodo, $categorias) {
                $q->whereRaw('CAST(GESTION AS INT) >= ?', [(int) $gestionDesde]);

                if ($gestionHasta !== null) {
                    $q->whereRaw('CAST(GESTION AS INT) <= ?', [(int) $gestionHasta]);
                }

                if ($periodo !== null) {
                    $q->where('PERIODO', $periodo);
                }

                if (!empty($categoria)) {
                    $q->whereIn('CATEGORIA', $categorias);
                }
            });

        // Filtro por categoría de título: se resuelve con una subconsulta contra
        // CLASIFICACION_TITULO en vez de un JOIN, para no duplicar filas si un
        // docente llegara a tener más de un título en el mismo documento.
        if (!empty($tiposTitulo)) {
            $query->whereIn('ID_CLASIFICACION_DOCENTE', function ($sub) use ($tiposTitulo) {
                $sub->select('ID_CLASIFICACION_DOCENTE')
                    ->from('CLASIFICACION_TITULO')
                    ->whereIn('TIPO_TITULO', $tiposTitulo);
            });
        }

        $clasificaciones = $query->get();

        // CLASIFICACION_TITULO no tiene relación Eloquent cargada aquí, se trae
        // en un solo query por lote (evita N+1) y se agrupa por ID_CLASIFICACION_DOCENTE.
        $idsClasificacion = $clasificaciones->pluck('ID_CLASIFICACION_DOCENTE');
        $titulosPorClasificacion = DB::table('CLASIFICACION_TITULO')
            ->whereIn('ID_CLASIFICACION_DOCENTE', $idsClasificacion)
            ->get()
            ->groupBy('ID_CLASIFICACION_DOCENTE');

        $porDocente = $clasificaciones->groupBy('COD_DOCENTE');

        $gruposOrdenados = $porDocente->sort(function ($grupoA, $grupoB) {
            $nivelA = self::ORDEN_NIVELES[$grupoA->first()->documento->NIVEL] ?? 999;
            $nivelB = self::ORDEN_NIVELES[$grupoB->first()->documento->NIVEL] ?? 999;

            if ($nivelA !== $nivelB) {
                return $nivelA <=> $nivelB;
            }

            return $this->nombreDocente($grupoA->first()) <=> $this->nombreDocente($grupoB->first());
        });

        $data = [];
        $contadorPorNivel = [];

        foreach ($gruposOrdenados as $clasificacionesDelDocente) {
            $primeraClasificacion = $clasificacionesDelDocente->first();
            $nombreDocente = $this->nombreDocente($primeraClasificacion);
            $nivel = $primeraClasificacion->documento->NIVEL;

            $contadorPorNivel[$nivel] = ($contadorPorNivel[$nivel] ?? 0) + 1;
            $primeraFilaDocente = true;

            $indiceInicioGrupo = count($data);

            foreach ($clasificacionesDelDocente as $clasificacion) {
                $documento = $clasificacion->documento;
                $materiasReales = $clasificacion->materias; // antes de meter el placeholder
                $materias = $materiasReales;

                if ($materias->isEmpty()) {
                    $materias = collect([
                        (object) [
                            'NOMBRE_MATERIA' => 'NO REGENTA MATERIA EN LA FCE',
                            'CARGA_HORARIA' => null,
                        ]
                    ]);
                }

                $referencias = $documento->referencias->pluck('NRO_REFERENCIA')->filter()->values();
                $obs2 = $referencias->get(0, '');
                $obs3 = $referencias->count() > 1 ? $referencias->slice(1)->implode(' - ') : '';

                $titulosDeEsteDocente = $titulosPorClasificacion->get($clasificacion->ID_CLASIFICACION_DOCENTE, collect());

                // ─── Columna DETALLE fusionada: Tipo de documento - Descripción general
                //     - Nota(s) de materia - Referencia(s) - Título(s) ───
                $partesDetalle = [];

                $tipoDocumentoLabel = self::ETIQUETAS_TIPO_DOCUMENTO[$documento->TIPO_DOCUMENTO]
                    ?? $documento->TIPO_DOCUMENTO
                    ?? '';
                if ($tipoDocumentoLabel !== '') {
                    $partesDetalle[] = $tipoDocumentoLabel;
                }

                if (!empty($documento->DETALLE_GENERAL)) {
                    $partesDetalle[] = $documento->DETALLE_GENERAL;
                }

                $notasMaterias = $materiasReales
                    ->pluck('NOTA')
                    ->filter(fn($n) => $n !== null && $n !== '')
                    ->values();
                if ($notasMaterias->isNotEmpty()) {
                    $partesDetalle[] = 'Nota: ' . $notasMaterias->implode(', ');
                }

                if ($referencias->isNotEmpty()) {
                    $partesDetalle[] = $referencias->implode(' - ');
                }

                if ($titulosDeEsteDocente->isNotEmpty()) {
                    $textosTitulo = $titulosDeEsteDocente->map(function ($t) {
                        $nombre = $t->NOMBRE_TITULO ?? '';
                        $tipo = $t->TIPO_TITULO ?? '';
                        return trim($nombre . ($tipo ? " ({$tipo})" : ''));
                    })->filter()->values();

                    if ($textosTitulo->isNotEmpty()) {
                        $partesDetalle[] = 'Título: ' . $textosTitulo->implode(' - ');
                    }
                }

                $detalleCombinado = implode(' - ', $partesDetalle);

                $primeraFilaClasificacion = true;
                $esGeneral = !empty($documento->DETALLE_GENERAL);

                foreach ($materias as $materia) {
                    $data[] = [
                        'N' => $primeraFilaDocente ? $contadorPorNivel[$nivel] : null,
                        'NOMBRE_DOCENTE' => $nombreDocente,
                        'NOMBRE_MATERIA' => $materia->NOMBRE_MATERIA ?: 'NO REGENTA MATERIA EN LA FCE',
                        'CH' => $materia->CARGA_HORARIA ?? null,
                        'DETALLE' => $primeraFilaClasificacion ? $detalleCombinado : '',
                        'CATEGORIA' => $primeraFilaDocente ? $this->formatearCategoria($documento->CATEGORIA) : '',
                        'NIVEL' => $primeraFilaDocente ? $nivel : '',
                        'FOTOCOPIA_TITULAR' => ($primeraFilaDocente && $documento->FOTOCOPIA_TITULAR)
                            ? 'PRESENTO FOTOCOPIA'
                            : '',
                        'OBS2' => $primeraFilaClasificacion ? $obs2 : '',
                        'OBS3' => $primeraFilaClasificacion ? $obs3 : '',
                        'NEGRITA' => $primeraFilaClasificacion && $esGeneral,
                    ];

                    $primeraFilaDocente = false;
                    $primeraFilaClasificacion = false;
                }
            }

            $indiceUltimaFila = count($data) - 1;
            if ($indiceUltimaFila >= $indiceInicioGrupo) {
                $data[$indiceUltimaFila]['FIN_GRUPO'] = true;
            }

            $data[$indiceInicioGrupo]['INICIO_GRUPO'] = true;
            $data[$indiceInicioGrupo]['FILAS_GRUPO'] = $indiceUltimaFila - $indiceInicioGrupo + 1;
        }

        return $data;
    }

    private function nombreDocente(ClasificacionDocente $clasificacion): string
    {
        $docente = $clasificacion->docente;

        return trim(($docente->APELLIDOS ?? '') . ' ' . ($docente->NOMBRES ?? ''));
    }

    private function formatearCategoria(?string $categoria): string
    {
        if ($categoria === null) {
            return '';
        }

        if (mb_strtolower(trim($categoria)) === mb_strtolower('Sin Examen de suficiencia')) {
            return '';
        }

        return $categoria;
    }

    /**
     * Layout de columnas:
     * B Nº | C NOMBRE DOCENTE | D NOMBRE MATERIA | E CH | F DETALLE
     * G CATEGORIA | H NIVEL | I (separador vacío) | J FOTOCOPIA TITULAR | K OBS2 | L OBS3
     */
    private function generarExcel(array $data, string $gestion, string $version)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Escalafon');
        $sheet->setShowGridlines(false);

        $fechaHoy = now()->format('d/m/Y');

        // === ENCABEZADO ===
        $sheet->setCellValue('B1', 'Universidad Mayor de San Simón');
        $sheet->setCellValue('B2', 'Facultad de Ciencias Económicas');

        $sheet->setCellValue('B3', "LISTA DE DOCENTES CLASIFICADOS EN PRIMER NIVEL, SEGUNDO NIVEL Y TERCER NIVEL - {$gestion} ({$version}) {$fechaHoy}");
        $sheet->mergeCells('B3:H3');
        $sheet->getStyle('B3')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('B3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('B4', 'Nota: Este es un documento preliminar el mismo puede ser modificado de acuerdo a la solicitud debidamente documentado con Resolución.');
        $sheet->mergeCells('B4:H4');
        $sheet->getStyle('B4')->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);

        // === CABECERA DE TABLA (fila 5) ===
        $headers = [
            'B' => 'Nº',
            'C' => 'NOMBRE DOCENTE',
            'D' => 'NOMBRE MATERIA',
            'E' => 'CH',
            'F' => 'DETALLE',
            'G' => 'CATEGORIA',
            'H' => 'NIVEL',
            'J' => 'FOTOCOPIA TITULAR',
            'K' => 'OBS 2',
            'L' => 'OBS3',
        ];

        foreach ($headers as $col => $texto) {
            $sheet->setCellValue($col . '5', $texto);
        }
        $sheet->getStyle('B5:H5')->getFont()->setBold(true);
        $sheet->getStyle('J5:L5')->getFont()->setBold(true);
        $sheet->getStyle('B5:H5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('J5:L5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('B5:H5')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFD9D9D9');
        $sheet->getStyle('J5:L5')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFD9D9D9');
        $sheet->getStyle('B5:H5')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('J5:L5')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getRowDimension(5)->setRowHeight(20);

        // === FILAS DE DATOS ===
        $fila = 6;

        foreach ($data as $item) {
            $sheet->setCellValue('B' . $fila, $item['N']);
            $sheet->setCellValue('C' . $fila, $item['NOMBRE_DOCENTE']);
            $sheet->setCellValue('D' . $fila, $item['NOMBRE_MATERIA']);
            $sheet->setCellValue('E' . $fila, $item['CH']);
            $sheet->setCellValue('F' . $fila, $item['DETALLE']);
            $sheet->setCellValue('G' . $fila, $item['CATEGORIA']);
            $sheet->setCellValue('H' . $fila, $item['NIVEL']);
            $sheet->setCellValue('J' . $fila, $item['FOTOCOPIA_TITULAR']);
            $sheet->setCellValue('K' . $fila, $item['OBS2']);
            $sheet->setCellValue('L' . $fila, $item['OBS3']);

            $sheet->getStyle('B' . $fila . ':H' . $fila)->getAlignment()
                ->setWrapText(true)
                ->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('J' . $fila . ':L' . $fila)->getAlignment()
                ->setWrapText(true)
                ->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->getStyle('E' . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->getStyle('B' . $fila . ':H' . $fila)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle('J' . $fila . ':L' . $fila)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);

            if (!empty($item['INICIO_GRUPO']) && $item['FILAS_GRUPO'] > 1) {
                $filaInicio = $fila;
                $filaFin = $fila + $item['FILAS_GRUPO'] - 1;

                $sheet->mergeCells('B' . $filaInicio . ':B' . $filaFin);
                $sheet->mergeCells('C' . $filaInicio . ':C' . $filaFin);

                $sheet->getStyle('B' . $filaInicio . ':B' . $filaFin)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle('C' . $filaInicio . ':C' . $filaFin)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);
            }

            if (!empty($item['FIN_GRUPO'])) {
                $sheet->getStyle('B' . $fila . ':H' . $fila)->getBorders()->getBottom()
                    ->setBorderStyle(Border::BORDER_MEDIUM);
                $sheet->getStyle('J' . $fila . ':L' . $fila)->getBorders()->getBottom()
                    ->setBorderStyle(Border::BORDER_MEDIUM);
            }

            $fila++;
        }

        // === AUTOFILTRO (CATEGORIA e NIVEL: G e H) ===
        $ultimaFila = $fila - 1;
        if ($ultimaFila >= 5) {
            $sheet->setAutoFilter('G5:H' . $ultimaFila);
        }

        // === ANCHOS DE COLUMNA ===
        $sheet->getColumnDimension('A')->setWidth(4);
        $sheet->getColumnDimension('B')->setWidth(5);
        $sheet->getColumnDimension('C')->setWidth(37.5);
        $sheet->getColumnDimension('D')->setWidth(38.7);
        $sheet->getColumnDimension('E')->setWidth(6);
        $sheet->getColumnDimension('F')->setWidth(55);
        $sheet->getColumnDimension('G')->setWidth(18);
        $sheet->getColumnDimension('H')->setWidth(15.3);
        $sheet->getColumnDimension('I')->setWidth(1.5);
        $sheet->getColumnDimension('J')->setWidth(23.6);
        $sheet->getColumnDimension('K')->setWidth(16);
        $sheet->getColumnDimension('L')->setWidth(20);

        $sheet->freezePane('B6');

        // === GENERAR ARCHIVO ===
        $writer = new Xlsx($spreadsheet);
        $filename = "LISTA_DOCENTES_CLASIFICADOS_" . str_replace('/', '-', $gestion) . ".xlsx";

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }
}