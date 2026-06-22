<?php

namespace App\Http\Controllers\Api;

use App\Models\ResolucionPdf;
use App\Models\ResolucionDetalle;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResolucionPdfController extends Controller
{
    // GET /resoluciones
    public function index()
    {
        $resoluciones = ResolucionPdf::with('detalles')
            ->select(
                'ID_RESOLUCION',
                'NRO_RESOLUCION',
                'DESCRIPCION',
                'ANIO',
                'PERIODO',
                'NOMBRE_ARCHIVO',
                'TAMANIO_KB',
                'FECHA_SUBIDA',
                'SUBIDO_POR'
            )
            ->orderBy('FECHA_SUBIDA', 'desc')
            ->get();

        return response()->json($resoluciones);
    }

    // GET /resoluciones/{id}
    public function show($id)
    {
        $resolucion = ResolucionPdf::with('detalles')
            ->select(
                'ID_RESOLUCION',
                'NRO_RESOLUCION',
                'DESCRIPCION',
                'ANIO',
                'PERIODO',
                'NOMBRE_ARCHIVO',
                'TAMANIO_KB',
                'FECHA_SUBIDA',
                'SUBIDO_POR'
            )
            ->findOrFail($id);

        return response()->json($resolucion);
    }

    // POST /resoluciones
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {

            // ===============================
            // VALIDACIÓN
            // ===============================
            $request->validate([
                'nro_resolucion' => 'required|string|max:50',
                'descripcion' => 'nullable|string|max:200',
                'anio' => 'required|integer',
                'periodo' => 'required|string|max:2',
                'archivo_pdf' => 'required|file|mimes:pdf|max:20480',
                'subido_por' => 'nullable|string|max:100',
            ]);

            // ===============================
            // VERIFICAR ARCHIVO
            // ===============================
            if (!$request->hasFile('archivo_pdf')) {
                return response()->json([
                    'ok' => false,
                    'error' => 'No se recibió archivo_pdf'
                ], 400);
            }

            $archivo = $request->file('archivo_pdf');

            if (!$archivo->isValid()) {
                return response()->json([
                    'ok' => false,
                    'error' => 'Archivo inválido'
                ], 400);
            }

            // ===============================
// GUARDAR PDF EN STORAGE
// ===============================
            $carpeta = 'resoluciones/' . $request->anio;

            // Nombre limpio: RR-001-2026.pdf  (sin espacios ni caracteres raros)
            $nombreArchivo = preg_replace('/[^A-Za-z0-9\-_\.]/', '-', $archivo->getClientOriginalName());

            $rutaArchivo = $archivo->storeAs($carpeta, $nombreArchivo, 'public');
            // $rutaArchivo queda como: "resoluciones/2026/RR-001-2026.pdf"

            // ===============================
// INSERT NORMAL (sin binario)
// ===============================
            DB::table('RESOLUCIONES_PDF')->insert([
                'NRO_RESOLUCION' => $request->nro_resolucion,
                'DESCRIPCION' => $request->descripcion,
                'ANIO' => (int) $request->anio,
                'PERIODO' => $request->periodo,
                'RUTA_ARCHIVO' => $rutaArchivo,          // ← la ruta relativa
                'NOMBRE_ARCHIVO' => $archivo->getClientOriginalName(),
                'TAMANIO_KB' => round($archivo->getSize() / 1024),
                'SUBIDO_POR' => $request->subido_por,
                'FECHA_SUBIDA' => now(),
            ]);

            // ===============================
            // OBTENER ID INSERTADO
            // ===============================
            $idResolucion = DB::selectOne("
                SELECT TOP 1 ID_RESOLUCION
                FROM RESOLUCIONES_PDF
                ORDER BY ID_RESOLUCION DESC
            ")->ID_RESOLUCION;

            // ===============================
            // INSERTAR DETALLES
            // ===============================
            $detallesInsertados = 0;

            if ($request->filled('detalles')) {

                $detalles = json_decode($request->detalles, true);

                if (!is_array($detalles)) {
                    throw new \Exception('Detalles inválidos: el JSON no es un array');
                }

                $dataDetalles = collect($detalles)->map(function ($item) use ($idResolucion) {
                    return [
                        'ID_RESOLUCION' => $idResolucion,
                        'COD_DOCENTE' => $item['cod_docente'],
                        'COD_PLAN' => $item['cod_plan'],
                        'COD_MATERIA' => $item['cod_materia'],
                        'GRUPO' => $item['grupo'] ?? null,
                        'TIPO' => $item['tipo'] ?? null,
                        'OBSERVACION' => $item['observacion'] ?? null,
                    ];
                });

                ResolucionDetalle::insert($dataDetalles->toArray());

                $detallesInsertados = $dataDetalles->count();
            }

            DB::commit();

            // ===============================
            // RESPUESTA OK
            // ===============================
            return response()->json([
                'ok' => true,
                'mensaje' => 'PDF guardado correctamente',
                'id_resolucion' => $idResolucion,
                'archivo' => $archivo->getClientOriginalName(),
                'peso_kb' => round($archivo->getSize() / 1024),
                'url' => asset('storage/' . $rutaArchivo), // ← URL accesible
                'detalles_insertados' => $detallesInsertados
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {

            DB::rollBack();

            return response()->json([
                'ok' => false,
                'tipo' => 'validacion',
                'errores' => $e->errors()
            ], 422);

        } catch (\Throwable $e) {

            DB::rollBack();

            // Limpiar caracteres no-UTF8 para que json() no explote
            $mensajeSeguro = preg_replace(
                '/[\x00-\x08\x0B\x0C\x0E-\x1F\x80-\xFF]/',
                '?',
                $e->getMessage()
            );

            \Log::error('Error ResolucionPdf', [
                'mensaje' => $mensajeSeguro,
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);

            return response()->json([
                'ok' => false,
                'tipo' => 'general',
                'error' => $mensajeSeguro
            ], 500);
        }
    }

    // GET /resoluciones/{id}/pdf            -> abre el PDF en el navegador (ver)
    // GET /resoluciones/{id}/pdf?modo=descargar -> fuerza la descarga del archivo
    // GET /resoluciones/{id}/pdf            -> abre el PDF en el navegador
// GET /resoluciones/{id}/pdf?modo=descargar -> fuerza la descarga
    public function descargar(Request $request, $id)
    {
        $pdf = ResolucionPdf::select(
            'ID_RESOLUCION',
            'NOMBRE_ARCHIVO',
            'RUTA_ARCHIVO'          // ← ya no pedimos ARCHIVO_PDF
        )->findOrFail($id);

        // Verificar que el archivo exista en storage
        if (!\Storage::disk('public')->exists($pdf->RUTA_ARCHIVO)) {
            return response()->json([
                'ok' => false,
                'error' => 'Archivo no encontrado en storage'
            ], 404);
        }

        $contenido = \Storage::disk('public')->get($pdf->RUTA_ARCHIVO);
        $disposicion = $request->query('modo') === 'descargar' ? 'attachment' : 'inline';

        return response($contenido)
            ->header('Content-Type', 'application/pdf')
            ->header(
                'Content-Disposition',
                $disposicion . '; filename="' . $pdf->NOMBRE_ARCHIVO . '"'
            );
    }
    // GET /resoluciones/por-numero?nro=RR-1/2026
    public function porNumero(Request $request)
    {
        $nro = $request->query('nro');

        if (!$nro) {
            return response()->json([
                'ok' => false,
                'error' => 'Falta parámetro nro'
            ], 400);
        }

        $resolucion = ResolucionPdf::select(
            'ID_RESOLUCION',
            'NRO_RESOLUCION',
            'NOMBRE_ARCHIVO',
            'ANIO',
            'PERIODO'
        )
            ->where('NRO_RESOLUCION', $nro)
            ->first();

        if (!$resolucion) {
            return response()->json([
                'ok' => false,
                'error' => 'No encontrada'
            ], 404);
        }

        return response()->json([
            'ok' => true,
            'id_resolucion' => $resolucion->ID_RESOLUCION,
            'nombre_archivo' => $resolucion->NOMBRE_ARCHIVO
        ]);
    }
}