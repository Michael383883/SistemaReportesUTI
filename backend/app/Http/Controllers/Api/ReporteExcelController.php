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
    // Orden de los niveles para clasificar y ordenar el listado
    private const ORDEN_NIVELES = [
        'PRIMER NIVEL' => 1,
        'SEGUNDO NIVEL' => 2,
        'TERCER NIVEL' => 3,
    ];

    public function generarListadoDocentes(Request $request)
    {
        try {
            // Por defecto trae TODAS las gestiones desde 2001 en adelante.
            // Se puede acotar con ?gestion_desde=2015&gestion_hasta=2020 si se necesita.
            $gestionDesde = $request->query('gestion_desde', '2001');
            $gestionHasta = $request->query('gestion_hasta'); // opcional
            $periodo = $request->query('periodo'); // opcional: 1, 2, o null para traer ambos
            $version = $request->query('version', '4ta Versión');

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

    /**
     * Construye el arreglo de filas para el reporte, ya agrupado y ordenado
     * por docente -> clasificación -> materia, tal como debe verse en el Excel.
     */
    private function construirDatos(string $gestionDesde, ?string $gestionHasta = null, ?int $periodo = null): array
    {
        $query = ClasificacionDocente::with([
                'docente',
                'materias', // ya viene ordenada por ORDEN gracias a la relación del modelo
                'referencias',
            ])
            // CAST porque GESTION es varchar en la BD; así compara como número y no como texto
            ->whereRaw('CAST(GESTION AS INT) >= ?', [(int) $gestionDesde]);

        if ($gestionHasta !== null) {
            $query->whereRaw('CAST(GESTION AS INT) <= ?', [(int) $gestionHasta]);
        }

        if ($periodo !== null) {
            $query->where('PERIODO', $periodo);
        }

        $clasificaciones = $query->get();

        // Un docente puede tener varias clasificaciones (varias resoluciones/títulos)
        $porDocente = $clasificaciones->groupBy('COD_DOCENTE');

        // Ordenar los grupos: primero por NIVEL (PRIMER/SEGUNDO/TERCER) y luego por apellido/nombre
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

            foreach ($clasificacionesDelDocente as $clasificacion) {
                $materias = $clasificacion->materias;

                // Si la clasificación no tiene materias asociadas en la FCE
                if ($materias->isEmpty()) {
                    $materias = collect([(object) [
                        'NOMBRE_MATERIA' => 'NO REGENTA MATERIA EN LA FCE',
                        'CARGA_HORARIA' => null,
                        'DETALLE' => $clasificacion->DETALLE_GENERAL,
                    ]]);
                }

                // OBS2 = primera referencia, OBS3 = el resto (si hay más de una)
                $referencias = $clasificacion->referencias->pluck('NRO_REFERENCIA')->filter()->values();
                $obs2 = $referencias->get(0, '');
                $obs3 = $referencias->count() > 1 ? $referencias->slice(1)->implode(' - ') : '';

                $primeraFilaClasificacion = true;
                $esGeneral = !empty($clasificacion->DETALLE_GENERAL);

                foreach ($materias as $materia) {
                    $data[] = [
                        'N'                 => $primeraFilaDocente ? $contadorPorNivel[$nivel] : null,
                        'NOMBRE_DOCENTE'    => $nombreDocente,
                        'NOMBRE_MATERIA'    => $materia->NOMBRE_MATERIA ?: 'NO REGENTA MATERIA EN LA FCE',
                        'CH'                => $materia->CARGA_HORARIA,
                        'DETALLE'           => $primeraFilaClasificacion
                            ? ($clasificacion->DETALLE_GENERAL ?: ($materia->DETALLE ?? ''))
                            : ($materia->DETALLE ?? ''),
                        'NIVEL'             => $primeraFilaDocente ? $nivel : '',
                        'FOTOCOPIA_TITULAR' => ($primeraFilaDocente && $clasificacion->FOTOCOPIA_TITULAR)
                            ? 'PRESENTO FOTOCOPIA'
                            : '',
                        'OBS2'              => $primeraFilaClasificacion ? $obs2 : '',
                        'OBS3'              => $primeraFilaClasificacion ? $obs3 : '',
                        // Filas "titular" (con detalle general) se muestran en negrita
                        'NEGRITA'           => $primeraFilaClasificacion && $esGeneral,
                        // Se conserva por si luego quieres agregar la columna de vuelta
                        'CATEGORIA'         => $clasificacion->CATEGORIA,
                    ];

                    $primeraFilaDocente = false;
                    $primeraFilaClasificacion = false;
                }
            }
        }

        return $data;
    }

    private function nombreDocente(ClasificacionDocente $clasificacion): string
    {
        $docente = $clasificacion->docente;

        return trim(($docente->APELLIDOS ?? '') . ' ' . ($docente->NOMBRES ?? ''));
    }

    /**
     * Genera el Excel replicando exactamente el layout de la hoja "Escalafon":
     * columna A vacía, datos en B..G, columna H vacía (separador), I..K = FOTOCOPIA/OBS2/OBS3
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
        $sheet->mergeCells('B3:G3');
        $sheet->getStyle('B3')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('B3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('B4', 'Nota: Este es un documento preliminar el mismo puede ser modificado de acuerdo a la solicitud debidamente documentado con Resolución.');
        $sheet->mergeCells('B4:G4');
        $sheet->getStyle('B4')->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);

        // === CABECERA DE TABLA (fila 5) ===
        $headers = [
            'B' => 'Nº',
            'C' => 'NOMBRE DOCENTE',
            'D' => 'NOMBRE MATERIA',
            'E' => 'CH',
            'F' => 'DETALLE',
            'G' => 'NIVEL',
            'I' => 'FOTOCOPIA TITULAR',
            'J' => 'OBS 2',
            'K' => 'OBS3',
        ];

        foreach ($headers as $col => $texto) {
            $sheet->setCellValue($col . '5', $texto);
        }
        $sheet->getStyle('B5:K5')->getFont()->setBold(true);
        $sheet->getStyle('B5:K5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('B5:K5')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFD9D9D9');
        $sheet->getStyle('B5:G5')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('I5:K5')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getRowDimension(5)->setRowHeight(20);

        // === FILAS DE DATOS ===
        $fila = 6;

        foreach ($data as $item) {
            $sheet->setCellValue('B' . $fila, $item['N']);
            $sheet->setCellValue('C' . $fila, $item['NOMBRE_DOCENTE']);
            $sheet->setCellValue('D' . $fila, $item['NOMBRE_MATERIA']);
            $sheet->setCellValue('E' . $fila, $item['CH']);
            $sheet->setCellValue('F' . $fila, $item['DETALLE']);
            $sheet->setCellValue('G' . $fila, $item['NIVEL']);
            $sheet->setCellValue('I' . $fila, $item['FOTOCOPIA_TITULAR']);
            $sheet->setCellValue('J' . $fila, $item['OBS2']);
            $sheet->setCellValue('K' . $fila, $item['OBS3']);

            $sheet->getStyle('B' . $fila . ':K' . $fila)->getAlignment()
                ->setWrapText(true)
                ->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->getStyle('E' . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Grid uniforme, fino, en todas las celdas de la fila
            $sheet->getStyle('B' . $fila . ':K' . $fila)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);

            // Filas "titular" (con detalle general) van en negrita: docente, detalle y nivel
            if (!empty($item['NEGRITA'])) {
                $sheet->getStyle('C' . $fila)->getFont()->setBold(true);
                $sheet->getStyle('F' . $fila)->getFont()->setBold(true);
                $sheet->getStyle('G' . $fila)->getFont()->setBold(true);
            }

            $fila++;
        }

        // === ANCHOS DE COLUMNA (igual al Excel de referencia) ===
        $sheet->getColumnDimension('A')->setWidth(4);
        $sheet->getColumnDimension('B')->setWidth(5);
        $sheet->getColumnDimension('C')->setWidth(37.5);
        $sheet->getColumnDimension('D')->setWidth(38.7);
        $sheet->getColumnDimension('E')->setWidth(6);
        $sheet->getColumnDimension('F')->setWidth(60);
        $sheet->getColumnDimension('G')->setWidth(15.3);
        $sheet->getColumnDimension('H')->setWidth(1.5);
        $sheet->getColumnDimension('I')->setWidth(23.6);
        $sheet->getColumnDimension('J')->setWidth(16);
        $sheet->getColumnDimension('K')->setWidth(20);

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