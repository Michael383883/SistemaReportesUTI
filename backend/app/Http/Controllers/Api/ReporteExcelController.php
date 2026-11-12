<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClasificacionDocente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
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

            // Llegan como texto separado por comas: "Docentes Titulares,Acefala"
            $categorias = $this->parseListaCsv($request->query('categoria'));
            $tiposTitulo = $this->parseListaCsv($request->query('tipo_titulo'));

            // Categorías de documento que se quieren QUITAR del reporte
            // (ej. "Acefala,Suplencia"): se quitan sus materias y su CH.
            $excluirCategorias = $this->parseListaCsv($request->query('excluir_categoria'));

            // Botón "Mostrar Referencias"
            $mostrarReferencias = filter_var($request->query('mostrar_referencias', false), FILTER_VALIDATE_BOOLEAN);

            // Botones para ocultar parte del título en el DETALLE
            $ocultarNombreTitulo = filter_var($request->query('ocultar_nombre_titulo', false), FILTER_VALIDATE_BOOLEAN);
            $ocultarTipoTitulo = filter_var($request->query('ocultar_tipo_titulo', false), FILTER_VALIDATE_BOOLEAN);

            $etiquetaGestion = $gestionHasta
                ? "{$gestionDesde} - {$gestionHasta}"
                : "Desde {$gestionDesde}";

            $data = $this->construirDatos(
                $gestionDesde,
                $gestionHasta,
                $periodo,
                $categorias,
                $tiposTitulo,
                $mostrarReferencias,
                $excluirCategorias,
                $ocultarNombreTitulo,
                $ocultarTipoTitulo
            );

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
            $excluirCategorias = $this->parseListaCsv($request->query('excluir_categoria'));

            // Mismos flags que en previsualizar(), para que la descarga directa
            // (sin pasar por el preview) respete los botones.
            $mostrarReferencias = filter_var($request->query('mostrar_referencias', false), FILTER_VALIDATE_BOOLEAN);
            $ocultarNombreTitulo = filter_var($request->query('ocultar_nombre_titulo', false), FILTER_VALIDATE_BOOLEAN);
            $ocultarTipoTitulo = filter_var($request->query('ocultar_tipo_titulo', false), FILTER_VALIDATE_BOOLEAN);

            $etiquetaGestion = $gestionHasta
                ? "{$gestionDesde} - {$gestionHasta}"
                : "Desde {$gestionDesde}";

            $data = $this->construirDatos(
                $gestionDesde,
                $gestionHasta,
                $periodo,
                $categorias,
                $tiposTitulo,
                $mostrarReferencias,
                $excluirCategorias,
                $ocultarNombreTitulo,
                $ocultarTipoTitulo
            );

            if (empty($data)) {
                return response()->json([
                    'ok' => false,
                    'error' => "No se encontraron registros desde la gestión {$gestionDesde}" . ($gestionHasta ? " hasta {$gestionHasta}" : ''),
                ], 404);
            }

            // La antigüedad solo se calcula en el frontend (requiere "Solo docentes
            // activos"), por eso la descarga GET directa nunca lleva esa columna.
            return $this->generarExcel($data, $etiquetaGestion, $version, false);
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
     * Parámetros extra:
     *   - mostrar_antiguedad (1/0/true/false): agrega la columna "ANTIGÜEDAD" justo
     *     después de CH, tomando el valor de ANTIGUEDAD por fila (número como 2 o 2.5,
     *     o null).
     *
     * Se usa cuando en la vista previa del Excel se asignó automáticamente la
     * Carga Horaria, se combinaron materias repetidas, se quitaron categorías
     * (Acéfala/Suplencia...) o se ocultó parte del título, y se quiere descargar
     * el archivo EXACTAMENTE con esos valores, sin que el backend los recalcule.
     */
    public function generarListadoDocentesDesdeDatos(Request $request)
    {
        try {
            $data = $request->input('data', []);
            $gestion = $request->input('gestion', '');
            $version = $request->input('version', '5ta Versión');
            $mostrarAntiguedad = filter_var(
                $request->input('mostrar_antiguedad', false),
                FILTER_VALIDATE_BOOLEAN
            );

            if (empty($data) || !is_array($data)) {
                return response()->json([
                    'ok' => false,
                    'error' => 'No se recibieron datos para generar el Excel',
                ], 422);
            }

            return $this->generarExcel($data, $gestion, $version, $mostrarAntiguedad);
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
     * $categoria filtra por CLASIFICACION_DOCUMENTO.CATEGORIA (solo esas).
     * $excluirCategorias QUITA los documentos con esas CATEGORIA (y por tanto sus
     *   materias y su carga horaria); un docente que se queda sin documentos
     *   desaparece del reporte.
     * $tipoTitulo filtra por CLASIFICACION_TITULO.TIPO_TITULO.
     * $mostrarReferencias controla si el número de referencia se agrega al DETALLE.
     * $ocultarNombreTitulo / $ocultarTipoTitulo controlan qué parte del título
     *   (NOMBRE_TITULO / TIPO_TITULO) aparece en el DETALLE. Si se ocultan ambos,
     *   el título no se muestra.
     */

    private function construirDatos(
        string $gestionDesde,
        ?string $gestionHasta = null,
        ?int $periodo = null,
        array $categorias = [],
        array $tiposTitulo = [],
        bool $mostrarReferencias = false,
        array $excluirCategorias = [],
        bool $ocultarNombreTitulo = false,
        bool $ocultarTipoTitulo = false
    ): array {
        $query = ClasificacionDocente::with([
            'docente',
            'documento.referencias',
            'materias',
        ])
            ->whereHas('documento', function ($q) use ($gestionDesde, $gestionHasta, $periodo, $categorias, $excluirCategorias) {
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

                // Quitar categorías (Acéfala, Suplencia, ...). Los documentos sin
                // categoría (NULL) se conservan: NOT IN descartaría los NULL.
                if (!empty($excluirCategorias)) {
                    $q->where(function ($w) use ($excluirCategorias) {
                        $w->whereNull('CATEGORIA')
                            ->orWhereNotIn('CATEGORIA', $excluirCategorias);
                    });
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
                // del Excel/preview).
                $obs2 = $documento->OBSERVACION ?? '';
                $obs3 = $documento->OBSERVACION2 ?? '';

                $titulosDeEsteDocente = $titulosPorClasificacion->get($clasificacion->ID_CLASIFICACION_DOCENTE, collect());

                // ─── DETALLE general del documento: Tipo de documento - Descripción
                //     general - Referencia(s) - Título(s). NO incluye las notas de
                //     materia: esas se calculan por-materia dentro del loop de abajo. ───
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

                // Botón "Mostrar Referencias"
                if ($mostrarReferencias && $referencias->isNotEmpty()) {
                    $partesDetalle[] = $referencias->implode(' - ');
                }

                // Título: se puede ocultar el NOMBRE, la CATEGORÍA (tipo) o ambos.
                if ($titulosDeEsteDocente->isNotEmpty() && !($ocultarNombreTitulo && $ocultarTipoTitulo)) {
                    $textosTitulo = $titulosDeEsteDocente->map(function ($t) use ($ocultarNombreTitulo, $ocultarTipoTitulo) {
                        $nombre = $ocultarNombreTitulo ? '' : trim((string) ($t->NOMBRE_TITULO ?? ''));
                        $tipo = $ocultarTipoTitulo ? '' : trim((string) ($t->TIPO_TITULO ?? ''));

                        if ($nombre !== '' && $tipo !== '') {
                            return "{$nombre} ({$tipo})";
                        }

                        return $nombre !== '' ? $nombre : $tipo;
                    })->filter()->values();

                    if ($textosTitulo->isNotEmpty()) {
                        $partesDetalle[] = 'Título: ' . $textosTitulo->implode(' - ');
                    }
                }

                $detalleGeneral = implode(' - ', $partesDetalle);

                $primeraFilaClasificacion = true;
                $esGeneral = !empty($documento->DETALLE_GENERAL);

                foreach ($materias as $materia) {
                    // Nota propia de ESTA materia, formato "Recuperatorio, Nota: 85".
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

                    // El detalle general se repite en TODAS las filas de materia,
                    // y cada una agrega además su propia nota.
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
                        // Código de la materia (ej. 1803024). Se usa en Prelación para
                        // excluir las materias cuyo código empieza con 18.
                        // OJO: ajusta el nombre de la columna según tu tabla de materias.
                        'COD_MATERIA' => $materia->COD_MATERIA
                            ?? $materia->CODIGO_MATERIA
                            ?? $materia->MATERIA
                            ?? null,
                        'CH' => $materia->CARGA_HORARIA ?? null,
                        'DETALLE' => $detalleFila,
                        'CATEGORIA' => $this->formatearCategoria($documento->CATEGORIA),
                        // NIVEL se sigue enviando (se usa para ordenar/numerar), pero
                        // ya no se muestra como columna en la tabla, el PDF ni el Excel.
                        'NIVEL' => $primeraFilaClasificacion ? $nivel : '',
                        'FOTOCOPIA_TITULAR' => $documento->FOTOCOPIA_TITULAR ? 'PRESENTO FOTOCOPIA' : '',
                        'OBS2' => $obs2,
                        'OBS3' => $obs3,
                        'NEGRITA' => $primeraFilaClasificacion && $esGeneral,
                        // Para el botón "PDF" de la vista previa
                        'ID_DOCUMENTO' => $documento->ID_DOCUMENTO,
                        'NOMBRE_ARCHIVO' => $documento->NOMBRE_ARCHIVO,
                        'GESTION' => $documento->GESTION,
                        'PERIODO' => $documento->PERIODO,
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
     * Completa TOTAL_CH en la primera fila de cada docente cuando no viene
     * calculado (por ejemplo en la descarga GET directa, sin pasar por el
     * preview). Misma regla que el frontend: suma la CH de las filas del
     * docente, contando cada materia una sola vez; las CH '-' no suman.
     * Si TOTAL_CH ya viene (POST /excel-personalizado) se respeta tal cual.
     */
    private function completarTotalesCh(array $data): array
    {
        $n = count($data);
        $i = 0;

        while ($i < $n) {
            $item = $data[$i];
            $inicio = !empty($item['INICIO_GRUPO']);
            $filas = $inicio ? max(1, (int) ($item['FILAS_GRUPO'] ?? 1)) : 1;

            if ($inicio && !array_key_exists('TOTAL_CH', $item)) {
                $vistos = [];
                $total = 0;

                for ($k = $i; $k < min($n, $i + $filas); $k++) {
                    $ch = $data[$k]['CH'] ?? null;
                    if (!is_numeric($ch) || (float) $ch == 0.0) {
                        continue;
                    }

                    $clave = mb_strtoupper(trim((string) ($data[$k]['NOMBRE_MATERIA'] ?? '')));
                    if ($clave !== '' && $clave !== '-' && $clave !== 'NO REGENTA MATERIA EN LA FCE') {
                        if (isset($vistos[$clave])) {
                            continue;
                        }
                        $vistos[$clave] = true;
                    }

                    $total += (float) $ch;
                }

                $data[$i]['TOTAL_CH'] = $total;
            }

            $i += $filas;
        }

        return $data;
    }

    /**
     * Layout de columnas (sin la columna NIVEL).
     *
     * Sin antigüedad:
     *   B Nº | C NOMBRE DOCENTE | D NOMBRE MATERIA | E CH | F DETALLE
     *   G CATEGORIA | H T. CH | I (separador) | J FOTOCOPIA TITULAR | K OBS2 | L OBS3
     *
     * Con antigüedad ($mostrarAntiguedad = true), se inserta ANTIGÜEDAD después de CH
     * y todo lo posterior se corre una columna:
     *   B Nº | C DOCENTE | D MATERIA | E CH | F ANTIGÜEDAD | G DETALLE
     *   H CATEGORIA | I T. CH | J (separador) | K FOTOCOPIA TITULAR | L OBS2 | M OBS3
     *
     * Las letras se calculan dinámicamente a partir del orden de las columnas.
     */
    private function generarExcel(array $data, string $gestion, string $version, bool $mostrarAntiguedad = false)
    {
        $data = $this->completarTotalesCh($data);

        // ── Mapa dinámico de columnas ──
        $orden = ['n', 'docente', 'materia', 'ch'];
        if ($mostrarAntiguedad) {
            $orden[] = 'antig';
        }
        array_push($orden, 'detalle', 'categoria', 'totch', 'sep', 'fotocopia', 'obs2', 'obs3');

        $c = [];
        foreach ($orden as $i => $clave) {
            $c[$clave] = Coordinate::stringFromColumnIndex(2 + $i); // empieza en B
        }

        $rangoIzq = fn(int $f) => $c['n'] . $f . ':' . $c['totch'] . $f;
        $rangoDer = fn(int $f) => $c['fotocopia'] . $f . ':' . $c['obs3'] . $f;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Escalafon');
        $sheet->setShowGridlines(false);

        // === ENCABEZADO ===
        $sheet->setCellValue('B1', 'Universidad Mayor de San Simón');
        $sheet->setCellValue('B2', 'Facultad de Ciencias Económicas');

        $sheet->setCellValue('B3', 'LISTA DE DOCENTES Y LAS MATERIAS QUE IMPARTEN - PRELACIÓN');
        $sheet->mergeCells('B3:' . $c['totch'] . '3');
        $sheet->getStyle('B3')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('B3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('B4', 'Nota: Este es un documento preliminar aún no aprobado por la comisión revisora, el mismo puede ser modificado de acuerdo a la solicitud debidamente documentada con Resolución.');
        $sheet->mergeCells('B4:' . $c['totch'] . '4');
        $sheet->getStyle('B4')->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(4)->setRowHeight(32);

        // === CABECERA DE TABLA (fila 5) ===
        $headers = [
            'n' => 'Nº',
            'docente' => 'NOMBRE DOCENTE',
            'materia' => 'NOMBRE MATERIA',
            'ch' => 'CH',
            'detalle' => 'DETALLE',
            'categoria' => 'CATEGORIA',
            'totch' => 'T. CH',
            'fotocopia' => 'FOTOCOPIA TITULAR',
            'obs2' => 'OBS1',
            'obs3' => 'OBS2',
        ];
        if ($mostrarAntiguedad) {
            $headers['antig'] = 'ANTIGÜEDAD';
        }

        foreach ($headers as $clave => $texto) {
            $sheet->setCellValue($c[$clave] . '5', $texto);
        }
        foreach ([$rangoIzq(5), $rangoDer(5)] as $rango) {
            $sheet->getStyle($rango)->getFont()->setBold(true);
            $sheet->getStyle($rango)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle($rango)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFD9D9D9');
            $sheet->getStyle($rango)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        }
        $sheet->getRowDimension(5)->setRowHeight(20);

        // === FILAS DE DATOS ===
        $fila = 6;

        foreach ($data as $item) {
            // T. CH solo en la primera fila de cada docente; 0 se deja vacío.
            // Si viene TOTAL_CH_MES (x 4), se agrega debajo: "24" / "Mes 96".
            $totalCh = null;
            if (!empty($item['INICIO_GRUPO']) && !empty($item['TOTAL_CH'])) {
                $totalCh = $item['TOTAL_CH'];
                if (!empty($item['TOTAL_CH_MES'])) {
                    $totalCh = $item['TOTAL_CH'] . "\nMes " . $item['TOTAL_CH_MES'];
                }
            }

            $sheet->setCellValue($c['n'] . $fila, $item['N'] ?? null);
            $sheet->setCellValue($c['docente'] . $fila, $item['NOMBRE_DOCENTE'] ?? '');
            $sheet->setCellValue($c['materia'] . $fila, $item['NOMBRE_MATERIA'] ?? '');
            $sheet->setCellValue($c['ch'] . $fila, $item['CH'] ?? null);

            if ($mostrarAntiguedad) {
                // Solo en la primera fila de cada materia (si se combinaron materias);
                // numérico (2 o 2.5) o vacío cuando es null.
                $antig = $item['ANTIGUEDAD'] ?? null;
                $sheet->setCellValue(
                    $c['antig'] . $fila,
                    is_numeric($antig) ? (float) $antig : null
                );
            }

            $sheet->setCellValue($c['detalle'] . $fila, $item['DETALLE'] ?? '');
            $sheet->setCellValue($c['categoria'] . $fila, $item['CATEGORIA'] ?? '');
            $sheet->setCellValue($c['totch'] . $fila, $totalCh);
            $sheet->setCellValue($c['fotocopia'] . $fila, $item['FOTOCOPIA_TITULAR'] ?? '');
            $sheet->setCellValue($c['obs2'] . $fila, $item['OBS2'] ?? '');
            $sheet->setCellValue($c['obs3'] . $fila, $item['OBS3'] ?? '');

            $sheet->getStyle($rangoIzq($fila))->getAlignment()
                ->setWrapText(true)
                ->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle($rangoDer($fila))->getAlignment()
                ->setWrapText(true)
                ->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->getStyle($c['ch'] . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($c['n'] . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($c['totch'] . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($c['totch'] . $fila)->getFont()->setBold(true);
            if ($mostrarAntiguedad) {
                $sheet->getStyle($c['antig'] . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            $sheet->getStyle($rangoIzq($fila))->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle($rangoDer($fila))->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);

            // Fusiona Nº, Docente y T. CH a lo largo de todas las filas del docente
            if (!empty($item['INICIO_GRUPO']) && ($item['FILAS_GRUPO'] ?? 1) > 1) {
                $filaInicio = $fila;
                $filaFin = $fila + $item['FILAS_GRUPO'] - 1;

                foreach (['n', 'docente', 'totch'] as $clave) {
                    $sheet->mergeCells($c[$clave] . $filaInicio . ':' . $c[$clave] . $filaFin);
                }

                foreach (['n', 'totch'] as $clave) {
                    $sheet->getStyle($c[$clave] . $filaInicio . ':' . $c[$clave] . $filaFin)->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                        ->setVertical(Alignment::VERTICAL_CENTER);
                }
                $sheet->getStyle($c['docente'] . $filaInicio . ':' . $c['docente'] . $filaFin)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);
            }

            // Combinar Materias: fusiona NOMBRE_MATERIA, CH y (si aplica) ANTIGÜEDAD
            // cuando el frontend marcó un tramo de filas con la misma materia
            // (solo llega por POST /excel-personalizado).
            if (!empty($item['INICIO_MATERIA']) && ($item['FILAS_MATERIA'] ?? 1) > 1) {
                $filaInicioMateria = $fila;
                $filaFinMateria = $fila + $item['FILAS_MATERIA'] - 1;

                $columnasMateria = ['materia', 'ch'];
                if ($mostrarAntiguedad) {
                    $columnasMateria[] = 'antig';
                }

                foreach ($columnasMateria as $clave) {
                    $sheet->mergeCells($c[$clave] . $filaInicioMateria . ':' . $c[$clave] . $filaFinMateria);
                }

                $sheet->getStyle($c['materia'] . $filaInicioMateria . ':' . $c['materia'] . $filaFinMateria)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);

                foreach (array_diff($columnasMateria, ['materia']) as $clave) {
                    $sheet->getStyle($c[$clave] . $filaInicioMateria . ':' . $c[$clave] . $filaFinMateria)->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                        ->setVertical(Alignment::VERTICAL_CENTER);
                }
            }

            if (!empty($item['FIN_GRUPO'])) {
                $sheet->getStyle($rangoIzq($fila))->getBorders()->getBottom()
                    ->setBorderStyle(Border::BORDER_MEDIUM);
                $sheet->getStyle($rangoDer($fila))->getBorders()->getBottom()
                    ->setBorderStyle(Border::BORDER_MEDIUM);
            }

            $fila++;
        }

        // === AUTOFILTRO (CATEGORIA y T. CH) ===
        $ultimaFila = $fila - 1;
        if ($ultimaFila >= 5) {
            $sheet->setAutoFilter($c['categoria'] . '5:' . $c['totch'] . $ultimaFila);
        }

        // === ANCHOS DE COLUMNA ===
        $sheet->getColumnDimension('A')->setWidth(4);
        $anchos = [
            'n' => 5,
            'docente' => 37.5,
            'materia' => 38.7,
            'ch' => 6,
            'antig' => 12,
            'detalle' => 55,
            'categoria' => 26,
            'totch' => 8,
            'sep' => 1.5,        // separador
            'fotocopia' => 23.6,
            'obs2' => 16,
            'obs3' => 20,
        ];
        foreach ($anchos as $clave => $ancho) {
            if (isset($c[$clave])) {
                $sheet->getColumnDimension($c[$clave])->setWidth($ancho);
            }
        }

        $sheet->freezePane('B6');

        $writer = new Xlsx($spreadsheet);
        $filename = "LISTA_DOCENTES_Y_MATERIAS_PRELACION_" . str_replace('/', '-', $gestion) . ".xlsx";

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
     * Cada materia devuelve:
     *   - comp:       valor crudo de GRUPOS_COMPARTIDOS.COMP (0 si no hay registro)
     *   - compartida: 1 si el grupo es compartido (COMP > 0), 0 si no.
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
                    $comp = (int) ($m->comp ?? 0);

                    return [
                        'carrera' => $m->carrera,
                        'cod_materia' => $m->cod_materia,
                        'nom_materia' => $m->nom_materia,
                        'grupo' => $m->grupo,
                        'comp' => $comp,
                        'compartida' => $comp > 0 ? 1 : 0,
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