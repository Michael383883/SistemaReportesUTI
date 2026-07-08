<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClasificacionDocente;
use Illuminate\Http\Request;
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

            $etiquetaGestion = $gestionHasta
                ? "{$gestionDesde} - {$gestionHasta}"
                : "Desde {$gestionDesde}";

            $data = $this->construirDatos($gestionDesde, $gestionHasta, $periodo);

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
    // Orden de los niveles para clasificar y ordenar el listado

    private const ORDEN_NIVELES = [
        'PRIMER NIVEL' => 1,
        'SEGUNDO NIVEL' => 2,
        'TERCER NIVEL' => 3,
    ];

    // Etiquetas legibles para TIPO_DOCUMENTO
    private const ETIQUETAS_TIPO_DOCUMENTO = [
        'TITULARIDAD_HISTORICA' => 'Titularidad Histórica',
        'TITULARIDAD_RCU_43_11' => 'Titularidad RCU 43/11',
        'EXAMEN_SUFICIENCIA' => 'Examen de Suficiencia',
        'CONCURSO_MERITOS' => 'Concurso de Méritos',
    ];

    public function generarListadoDocentes(Request $request)
    {
        try {
            $gestionDesde = $request->query('gestion_desde', '2001');
            $gestionHasta = $request->query('gestion_hasta'); // opcional
            $periodo = $request->query('periodo'); // opcional
            $version = $request->query('version', '5ta Versión');

            $etiquetaGestion = $gestionHasta
                ? "{$gestionDesde} - {$gestionHasta}"
                : "Desde {$gestionDesde}";

            $data = $this->construirDatos($gestionDesde, $gestionHasta, $periodo);

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

    private function construirDatos(string $gestionDesde, ?string $gestionHasta = null, ?int $periodo = null): array
    {
        $query = ClasificacionDocente::with([
            'docente',
            'materias',
            'referencias',
        ])
            ->whereRaw('CAST(GESTION AS INT) >= ?', [(int) $gestionDesde]);

        if ($gestionHasta !== null) {
            $query->whereRaw('CAST(GESTION AS INT) <= ?', [(int) $gestionHasta]);
        }

        if ($periodo !== null) {
            $query->where('PERIODO', $periodo);
        }

        $clasificaciones = $query->get();

        $porDocente = $clasificaciones->groupBy('COD_DOCENTE');

        $gruposOrdenados = $porDocente->sort(function ($grupoA, $grupoB) {
            $nivelA = self::ORDEN_NIVELES[$grupoA->first()->NIVEL] ?? 999;
            $nivelB = self::ORDEN_NIVELES[$grupoB->first()->NIVEL] ?? 999;

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
            $nivel = $primeraClasificacion->NIVEL;

            $contadorPorNivel[$nivel] = ($contadorPorNivel[$nivel] ?? 0) + 1;
            $primeraFilaDocente = true;

            // Para saber en qué índice de $data cae la última fila de este docente
            // (así podemos marcar el borde grueso de cierre del grupo)
            $indiceInicioGrupo = count($data);

            foreach ($clasificacionesDelDocente as $clasificacion) {
                $materias = $clasificacion->materias;

                if ($materias->isEmpty()) {
                    $materias = collect([
                        (object) [
                            'NOMBRE_MATERIA' => 'NO REGENTA MATERIA EN LA FCE',
                            'CARGA_HORARIA' => null,
                            'DETALLE' => $clasificacion->DETALLE_GENERAL,
                        ]
                    ]);
                }

                $referencias = $clasificacion->referencias->pluck('NRO_REFERENCIA')->filter()->values();
                $obs2 = $referencias->get(0, '');
                $obs3 = $referencias->count() > 1 ? $referencias->slice(1)->implode(' - ') : '';

                $primeraFilaClasificacion = true;
                $esGeneral = !empty($clasificacion->DETALLE_GENERAL);

                foreach ($materias as $materia) {
                    $data[] = [
                        'N' => $primeraFilaDocente ? $contadorPorNivel[$nivel] : null,
                        'NOMBRE_DOCENTE' => $nombreDocente,
                        'NOMBRE_MATERIA' => $materia->NOMBRE_MATERIA ?: 'NO REGENTA MATERIA EN LA FCE',
                        'CH' => $materia->CARGA_HORARIA,
                        'TIPO_DOCUMENTO' => $primeraFilaClasificacion
                            ? (self::ETIQUETAS_TIPO_DOCUMENTO[$clasificacion->TIPO_DOCUMENTO] ?? $clasificacion->TIPO_DOCUMENTO ?? '')
                            : '',
                        'DETALLE' => $primeraFilaClasificacion
                            ? ($clasificacion->DETALLE_GENERAL ?: ($materia->DETALLE ?? ''))
                            : ($materia->DETALLE ?? ''),
                        'CATEGORIA' => $primeraFilaDocente ? $this->formatearCategoria($clasificacion->CATEGORIA) : '',
                        'NIVEL' => $primeraFilaDocente ? $nivel : '',
                        'FOTOCOPIA_TITULAR' => ($primeraFilaDocente && $clasificacion->FOTOCOPIA_TITULAR)
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

            // Marca la última fila de este docente para dibujar el borde grueso de cierre
            $indiceUltimaFila = count($data) - 1;
            if ($indiceUltimaFila >= $indiceInicioGrupo) {
                $data[$indiceUltimaFila]['FIN_GRUPO'] = true;
            }

            // Marca el inicio del grupo para poder fusionar Nº y NOMBRE_DOCENTE al escribir el Excel
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

    /**
     * Oculta el texto "Sin Examen de suficiencia" en la columna CATEGORIA:
     * si el docente tiene esa categoría, la celda queda vacía.
     */
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
     * B Nº | C NOMBRE DOCENTE | D NOMBRE MATERIA | E CH | F TIPO DOCUMENTO | G DETALLE
     * H CATEGORIA | I NIVEL | J (separador vacío) | K FOTOCOPIA TITULAR | L OBS2 | M OBS3
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
        $sheet->mergeCells('B3:I3');
        $sheet->getStyle('B3')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('B3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('B4', 'Nota: Este es un documento preliminar el mismo puede ser modificado de acuerdo a la solicitud debidamente documentado con Resolución.');
        $sheet->mergeCells('B4:I4');
        $sheet->getStyle('B4')->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);

        // === CABECERA DE TABLA (fila 5) ===
        $headers = [
            'B' => 'Nº',
            'C' => 'NOMBRE DOCENTE',
            'D' => 'NOMBRE MATERIA',
            'E' => 'CH',
            'F' => 'TIPO DE DOCUMENTO',
            'G' => 'DETALLE',
            'H' => 'CATEGORIA',
            'I' => 'NIVEL',
            'K' => 'FOTOCOPIA TITULAR',
            'L' => 'OBS 2',
            'M' => 'OBS3',
        ];

        foreach ($headers as $col => $texto) {
            $sheet->setCellValue($col . '5', $texto);
        }
        $sheet->getStyle('B5:I5')->getFont()->setBold(true);
        $sheet->getStyle('K5:M5')->getFont()->setBold(true);
        $sheet->getStyle('B5:I5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('K5:M5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('B5:I5')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFD9D9D9');
        $sheet->getStyle('K5:M5')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFD9D9D9');
        $sheet->getStyle('B5:I5')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('K5:M5')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getRowDimension(5)->setRowHeight(20);

        // === FILAS DE DATOS ===
        $fila = 6;

        foreach ($data as $item) {
            $sheet->setCellValue('B' . $fila, $item['N']);
            $sheet->setCellValue('C' . $fila, $item['NOMBRE_DOCENTE']);
            $sheet->setCellValue('D' . $fila, $item['NOMBRE_MATERIA']);
            $sheet->setCellValue('E' . $fila, $item['CH']);
            $sheet->setCellValue('F' . $fila, $item['TIPO_DOCUMENTO']);
            $sheet->setCellValue('G' . $fila, $item['DETALLE']);
            $sheet->setCellValue('H' . $fila, $item['CATEGORIA']);
            $sheet->setCellValue('I' . $fila, $item['NIVEL']);
            $sheet->setCellValue('K' . $fila, $item['FOTOCOPIA_TITULAR']);
            $sheet->setCellValue('L' . $fila, $item['OBS2']);
            $sheet->setCellValue('M' . $fila, $item['OBS3']);

            $sheet->getStyle('B' . $fila . ':I' . $fila)->getAlignment()
                ->setWrapText(true)
                ->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('K' . $fila . ':M' . $fila)->getAlignment()
                ->setWrapText(true)
                ->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->getStyle('E' . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Grid uniforme fino
            $sheet->getStyle('B' . $fila . ':I' . $fila)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle('K' . $fila . ':M' . $fila)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);

            // Fusiona Nº y NOMBRE DOCENTE en todas las filas que pertenecen al mismo docente
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

            // Borde grueso de cierre al final de cada grupo de docente
            if (!empty($item['FIN_GRUPO'])) {
                $sheet->getStyle('B' . $fila . ':I' . $fila)->getBorders()->getBottom()
                    ->setBorderStyle(Border::BORDER_MEDIUM);
                $sheet->getStyle('K' . $fila . ':M' . $fila)->getBorders()->getBottom()
                    ->setBorderStyle(Border::BORDER_MEDIUM);
            }

            $fila++;
        }

        // === AUTOFILTRO (solo en CATEGORIA e NIVEL, que son columnas adyacentes: H e I) ===
        $ultimaFila = $fila - 1;
        if ($ultimaFila >= 5) {
            $sheet->setAutoFilter('H5:I' . $ultimaFila);
        }

        // === ANCHOS DE COLUMNA ===
        $sheet->getColumnDimension('A')->setWidth(4);
        $sheet->getColumnDimension('B')->setWidth(5);
        $sheet->getColumnDimension('C')->setWidth(37.5);
        $sheet->getColumnDimension('D')->setWidth(38.7);
        $sheet->getColumnDimension('E')->setWidth(6);
        $sheet->getColumnDimension('F')->setWidth(22);
        $sheet->getColumnDimension('G')->setWidth(45);
        $sheet->getColumnDimension('H')->setWidth(18);
        $sheet->getColumnDimension('I')->setWidth(15.3);
        $sheet->getColumnDimension('J')->setWidth(1.5);
        $sheet->getColumnDimension('K')->setWidth(23.6);
        $sheet->getColumnDimension('L')->setWidth(16);
        $sheet->getColumnDimension('M')->setWidth(20);

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