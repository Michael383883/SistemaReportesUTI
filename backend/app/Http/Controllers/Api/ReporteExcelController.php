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

    private const SIN_TITULO = '__SIN_TITULO__';
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

            // Botón "Mostrar Referencias": si no está activo, el DETALLE no
            // incluye el número de referencia del documento (CLASIFICACION_REFERENCIA).
            $mostrarReferencias = filter_var($request->query('mostrar_referencias', false), FILTER_VALIDATE_BOOLEAN);

            $etiquetaGestion = $gestionHasta
                ? "{$gestionDesde} - {$gestionHasta}"
                : "Desde {$gestionDesde}";

            $data = $this->construirDatos($gestionDesde, $gestionHasta, $periodo, $categorias, $tiposTitulo, $mostrarReferencias);

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

            // Mismo flag que en previsualizar(), para que la descarga directa
            // (sin pasar por el preview) respete el botón "Mostrar Referencias".
            $mostrarReferencias = filter_var($request->query('mostrar_referencias', false), FILTER_VALIDATE_BOOLEAN);

            $etiquetaGestion = $gestionHasta
                ? "{$gestionDesde} - {$gestionHasta}"
                : "Desde {$gestionDesde}";

            $data = $this->construirDatos($gestionDesde, $gestionHasta, $periodo, $categorias, $tiposTitulo, $mostrarReferencias);

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
     * POST /api/reportes/docentes-clasificados/excel-personalizado
     *
     * Genera el Excel a partir de datos YA ARMADOS que llegan desde el frontend
     * (mismo shape que devuelve construirDatos()/preview, incluyendo N, COD_DOCENTE,
     * NOMBRE_DOCENTE, NOMBRE_MATERIA, CH, DETALLE, CATEGORIA, NIVEL, FOTOCOPIA_TITULAR,
     * OBS2, OBS3, NEGRITA, INICIO_GRUPO, FILAS_GRUPO, FIN_GRUPO, y opcionalmente
     * INICIO_MATERIA/FILAS_MATERIA cuando el usuario combinó materias en el preview).
     *
     * Se usa cuando en la vista previa del Excel se asignó automáticamente la
     * Carga Horaria (botón "Asignar Carga Horaria") y/o se combinaron materias
     * repetidas (botón "Combinar Materias"), y se quiere descargar el archivo
     * EXACTAMENTE con esos valores, sin que el backend los recalcule desde cero
     * (lo que perdería esos cambios hechos en el navegador). El estado del botón
     * "Mostrar Referencias" también viaja implícito aquí: el DETALLE de cada fila
     * ya trae o no trae la referencia, según lo que se veía en pantalla.
     */
    public function generarListadoDocentesDesdeDatos(Request $request)
    {
        try {
            $data = $request->input('data', []);
            $gestion = $request->input('gestion', '');
            $version = $request->input('version', '5ta Versión');

            if (empty($data) || !is_array($data)) {
                return response()->json([
                    'ok' => false,
                    'error' => 'No se recibieron datos para generar el Excel',
                ], 422);
            }

            return $this->generarExcel($data, $gestion, $version);
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
     * completo (documento->referencias, tabla CLASIFICACION_REFERENCIA), y los títulos
     * (CLASIFICACION_TITULO) se traen aparte por lote, agrupados por
     * ID_CLASIFICACION_DOCENTE.
     *
     * $categoria filtra por CLASIFICACION_DOCUMENTO.CATEGORIA.
     * $tipoTitulo filtra por CLASIFICACION_TITULO.TIPO_TITULO (solo aparecen docentes
     * que tengan al menos un título de esa categoría).
     * $mostrarReferencias controla si el número de referencia (CLASIFICACION_REFERENCIA)
     * se agrega al texto de DETALLE. Por defecto (false) no se incluye.
     */

    private function construirDatos(
        string $gestionDesde,
        ?string $gestionHasta = null,
        ?int $periodo = null,
        array $categorias = [],
        array $tiposTitulo = [],
        bool $mostrarReferencias = false
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

                if (!empty($categorias)) {
                    $q->whereIn('CATEGORIA', $categorias);
                }
            });

        if (!empty($tiposTitulo)) {
            // Separa el sentinel "sin título" de los tipos reales, porque
            // requieren lógica opuesta: unos filtran por whereIn, el otro por
            // whereNotIn contra CLASIFICACION_TITULO.
            $tiposReales = array_values(array_diff($tiposTitulo, [self::SIN_TITULO]));
            $quiereSinTitulo = in_array(self::SIN_TITULO, $tiposTitulo, true);

            $query->where(function ($q) use ($tiposReales, $quiereSinTitulo) {
                if (!empty($tiposReales)) {
                    $q->orWhereIn('ID_CLASIFICACION_DOCENTE', function ($sub) use ($tiposReales) {
                        $sub->select('ID_CLASIFICACION_DOCENTE')
                            ->from('CLASIFICACION_TITULO')
                            ->whereIn('TIPO_TITULO', $tiposReales);
                    });
                }

                if ($quiereSinTitulo) {
                    $q->orWhereNotIn('ID_CLASIFICACION_DOCENTE', function ($sub) {
                        $sub->select('ID_CLASIFICACION_DOCENTE')
                            ->from('CLASIFICACION_TITULO');
                    });
                }
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
                            'NOMBRE_MATERIA' => '-',
                            'CARGA_HORARIA' => null,
                        ]
                    ]);
                }

                $referencias = $documento->referencias->pluck('NRO_REFERENCIA')->filter()->values();

                // Las columnas reales en CLASIFICACION_DOCUMENTO son OBSERVACION y
                // OBSERVACION2 (no OBS2/OBS3 — esos son solo los nombres de columna
                // del Excel/preview). No se derivan de las referencias, que solo
                // alimentan el número de referencia usado en DETALLE (y solo cuando
                // el botón "Mostrar Referencias" está activo).
                $obs2 = $documento->OBSERVACION ?? '';
                $obs3 = $documento->OBSERVACION2 ?? '';

                $titulosDeEsteDocente = $titulosPorClasificacion->get($clasificacion->ID_CLASIFICACION_DOCENTE, collect());

                // ─── DETALLE general del documento: Tipo de documento - Descripción
                //     general - Referencia(s) - Título(s). NO incluye las notas de
                //     materia: esas se calculan por-materia dentro del loop de abajo,
                //     para que cada fila muestre la nota que le corresponde. ───
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

                // Botón "Mostrar Referencias": si no está activo, se omite el
                // número de referencia (CLASIFICACION_REFERENCIA) del DETALLE.
                if ($mostrarReferencias && $referencias->isNotEmpty()) {
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

                $detalleGeneral = implode(' - ', $partesDetalle);

                $primeraFilaClasificacion = true;
                $esGeneral = !empty($documento->DETALLE_GENERAL);

                foreach ($materias as $materia) {
                    // Nota propia de ESTA materia (una fila = una materia = su nota),
                    // formato "Recuperatorio, Nota: 85". Si tiene nota pero no
                    // observación, queda solo "Nota: 85".
                    $notaTexto = '';
                    $partesNota = [];

                    if (!empty($materia->DETALLE)) {
                        $partesNota[] = trim($materia->DETALLE);
                    }

                    if (isset($materia->NOTA) && $materia->NOTA !== null && $materia->NOTA !== '') {
                        $partesNota[] = 'Nota: ' . $materia->NOTA;
                    }

                    if (!empty($partesNota)) {
                        $notaTexto = implode(', ', $partesNota);
                    }

                    // El detalle general del documento (tipo, descripción, referencias,
                    // título) se repite en TODAS las filas de materia, y cada una
                    // agrega además su propia nota.
                    $detalleFila = $detalleGeneral;
                    if ($notaTexto !== '') {
                        $detalleFila = $detalleFila !== ''
                            ? $detalleFila . ' - ' . $notaTexto
                            : $notaTexto;
                    }

                    $data[] = [
                        'N' => $primeraFilaDocente ? $contadorPorNivel[$nivel] : null,
                        'COD_DOCENTE' => $clasificacion->COD_DOCENTE,
                        'NOMBRE_DOCENTE' => $nombreDocente,
                        'NOMBRE_MATERIA' => $materia->NOMBRE_MATERIA ?: '-',
                        'CH' => $materia->CARGA_HORARIA ?? null,
                        'DETALLE' => $detalleFila,
                        'CATEGORIA' => $this->formatearCategoria($documento->CATEGORIA),
                        'NIVEL' => $primeraFilaClasificacion ? $nivel : '',
                        'FOTOCOPIA_TITULAR' => $documento->FOTOCOPIA_TITULAR ? 'PRESENTO FOTOCOPIA' : '',
                        'OBS2' => $obs2,
                        'OBS3' => $obs3,
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

            // Combinar Materias: fusiona la columna D (NOMBRE_MATERIA) y también
            // la columna E (CH) cuando el frontend marcó un tramo de filas con la
            // misma materia para un mismo docente (botón "Combinar Materias").
            // El CH ya viene unificado (un solo valor, no sumado) desde el
            // frontend en la fila de inicio del tramo. Estas marcas solo llegan
            // por POST /excel-personalizado; el endpoint GET normal nunca las
            // trae, así que ahí este bloque no tiene efecto. NUNCA fusiona '-' ni
            // "NO REGENTA MATERIA EN LA FCE" porque el frontend no les asigna
            // FILAS_MATERIA > 1. No afecta ninguna otra columna.
            if (!empty($item['INICIO_MATERIA']) && ($item['FILAS_MATERIA'] ?? 1) > 1) {
                $filaInicioMateria = $fila;
                $filaFinMateria = $fila + $item['FILAS_MATERIA'] - 1;

                $sheet->mergeCells('D' . $filaInicioMateria . ':D' . $filaFinMateria);
                $sheet->mergeCells('E' . $filaInicioMateria . ':E' . $filaFinMateria);

                $sheet->getStyle('D' . $filaInicioMateria . ':D' . $filaFinMateria)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);

                $sheet->getStyle('E' . $filaInicioMateria . ':E' . $filaFinMateria)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
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
        $sheet->getColumnDimension('G')->setWidth(26);
        $sheet->getColumnDimension('H')->setWidth(15.3);
        $sheet->getColumnDimension('I')->setWidth(1.5);
        $sheet->getColumnDimension('J')->setWidth(23.6);
        $sheet->getColumnDimension('K')->setWidth(16);
        $sheet->getColumnDimension('L')->setWidth(20);

        $sheet->freezePane('B6');

        $writer = new Xlsx($spreadsheet);
        $filename = "LISTA_DOCENTES_CLASIFICADOS_" . str_replace('/', '-', $gestion) . ".xlsx";

        // Guardar a un archivo temporal en vez de php://output
        $tempPath = tempnam(sys_get_temp_dir(), 'xlsx_') . '.xlsx';
        $writer->save($tempPath);

        return response()->download($tempPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
    /**
     * Lista de docentes activos (con materia asignada) en una gestión dada.
     * "Activo" = tiene al menos un grupo/materia en GRUPOS para ese anio/periodo,
     * sin importar si ya tiene o no estudiantes inscritos.
     *
     * GET /api/reportes/docentes-activos?anio=2026&periodo=1
     */
    public function obtenerDocentesActivos(Request $request)
    {
        $anio = (int) $request->query('anio', date('Y'));
        $periodo = (int) $request->query('periodo', 1);

        if (!in_array($periodo, [1, 2, 3, 4], true)) {
            return response()->json([
                'message' => 'El parámetro periodo debe ser 1, 2, 3 o 4.',
            ], 422);
        }

        $bindings = [
            'anio' => $anio,
            'periodo' => $periodo,
        ];

        $sql = "
            SELECT DISTINCT
                DOCENTES.CODIGO    AS codigo,
                DOCENTES.APELLIDOS AS apellidos,
                DOCENTES.NOMBRES   AS nombres
            FROM GRUPOS
            INNER JOIN DOCENTES
                ON DOCENTES.CODIGO = GRUPOS.DOCENTE
            WHERE GRUPOS.ANIO     = :anio
              AND GRUPOS.PERIODO  = :periodo
              AND GRUPOS.[PLAN]   IN ('109401','125091','089801','126091','059801')
              AND GRUPOS.PRIMARIO = 'Y'
              AND GRUPOS.TIPO     = 'N'
            ORDER BY DOCENTES.APELLIDOS, DOCENTES.NOMBRES
        ";

        $docentes = DB::select($sql, $bindings);

        return response()->json([
            'anio' => $anio,
            'periodo' => $periodo,
            'total' => count($docentes),
            'data' => $docentes,
        ]);
    }

    /**
     * Carga horaria por docente y por materia, para un anio/periodo dado.
     * Misma lógica de CARGA_HORARIA que HorarioAdminController::resumen()
     * (SUM de 8 si HORA>0 y GRUPO='NN', 2 en caso contrario, por cada fila
     * de HORARIOS2 agrupada por materia/grupo/día/hora), pero agrupada aquí
     * a nivel docente -> materias, con el total de CH sumado por docente.
     *
     * GET /api/reportes/carga-horaria-docentes?anio=2026&periodo=1
     * GET /api/reportes/carga-horaria-docentes?anio=2026&periodo=1&docente=123
     */
    public function obtenerCargaHorariaDocentes(Request $request)
    {
        $anio = (int) $request->query('anio', date('Y'));
        $periodo = (int) $request->query('periodo', 1);
        $docente = $request->query('docente');

        if (!in_array($periodo, [1, 2, 3, 4], true)) {
            return response()->json([
                'message' => 'El parámetro periodo debe ser 1, 2, 3 o 4.',
            ], 422);
        }

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
                HORARIOS2.DOCENTE  AS codigo,
                DOCENTES.APELLIDOS AS apellidos,
                DOCENTES.NOMBRES   AS nombres,

                CASE GRUPOS.[PLAN]
                    WHEN '059801' THEN 'ECO'
                    WHEN '109401' THEN 'ADM'
                    WHEN '089801' THEN 'CCP'
                    WHEN '125091' THEN 'COM'
                    WHEN '126091' THEN 'FIN'
                    ELSE 'NN'
                END AS carrera,

                GRUPOS.MATERIA  AS cod_materia,
                MATERIAS.NOMBRE AS nom_materia,
                GRUPOS.GRUPO    AS grupo,
                gc.COMP         AS comp,

                SUM(
                    CASE
                        WHEN gc.COMP = 1 THEN 0
                        WHEN HORARIOS2.HORA > 0
                        AND HORARIOS2.GRUPO = 'NN'
                        THEN 8
                        ELSE 2
                    END
                ) AS carga_horaria

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
            LEFT JOIN GRUPOS_COMPARTIDOS gc
                ON GRUPOS.[PLAN] = gc.[PLAN]
                AND GRUPOS.MATERIA = gc.MATERIA
                AND GRUPOS.GRUPO = gc.GRUPO
                AND GRUPOS.PRIMARIO = gc.PRIMARIO

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
                HORARIOS2.DOCENTE, DOCENTES.APELLIDOS, DOCENTES.NOMBRES,
                GRUPOS.[PLAN], GRUPOS.MATERIA, MATERIAS.NOMBRE, GRUPOS.GRUPO, gc.COMP

            ORDER BY
                DOCENTES.APELLIDOS, DOCENTES.NOMBRES, GRUPOS.MATERIA, GRUPOS.GRUPO
        ";

        $filas = collect(DB::select($sql, $bindings));

        $data = $filas->groupBy('codigo')->map(function ($materias) {
            $first = $materias->first();

            return [
                'codigo' => $first->codigo,
                'apellidos' => $first->apellidos,
                'nombres' => $first->nombres,
                'materias' => $materias->map(function ($m) {
                    return [
                        'carrera' => $m->carrera,
                        'cod_materia' => $m->cod_materia,
                        'nom_materia' => $m->nom_materia,
                        'grupo' => $m->grupo,
                        'comp' => $m->comp,
                        'carga_horaria' => $m->carga_horaria,
                    ];
                })->values(),
                'total_ch' => $materias->sum('carga_horaria'),
            ];
        })->values();

        return response()->json([
            'anio' => $anio,
            'periodo' => $periodo,
            'total_docentes' => $data->count(),
            'data' => $data,
        ]);
    }
}