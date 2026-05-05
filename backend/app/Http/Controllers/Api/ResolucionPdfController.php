<?php

namespace App\Http\Controllers\Api;

use App\Models\ResolucionPdf;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResolucionPdfController extends Controller
{
    // GET /resoluciones
    public function index()
    {
        $resoluciones = ResolucionPdf::with('detalles')
            ->select('id_resolucion', 'nro_resolucion', 'descripcion', 'anio', 'periodo', 'nombre_archivo', 'tamanio_kb', 'fecha_subida', 'subido_por')
            ->orderBy('fecha_subida', 'desc')
            ->get();

        return response()->json($resoluciones);
    }

    // GET /resoluciones/{id}
    public function show($id)
    {
        $resolucion = ResolucionPdf::with('detalles')
            ->select('id_resolucion', 'nro_resolucion', 'descripcion', 'anio', 'periodo', 'nombre_archivo', 'tamanio_kb', 'fecha_subida', 'subido_por')
            ->findOrFail($id);

        return response()->json($resolucion);
    }

    // POST /resoluciones
    public function store(Request $request)
    {
        try {

            // ===============================
            // 1. VALIDAR CAMPOS
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
            // 2. VERIFICAR QUE LLEGÓ ARCHIVO
            // ===============================
            if (!$request->hasFile('archivo_pdf')) {
                return response()->json([
                    'ok' => false,
                    'error' => 'No se recibió archivo_pdf'
                ], 400);
            }

            $archivo = $request->file('archivo_pdf');

            // ===============================
            // 3. VERIFICAR ARCHIVO VÁLIDO
            // ===============================
            if (!$archivo->isValid()) {
                return response()->json([
                    'ok' => false,
                    'error' => 'Archivo inválido'
                ], 400);
            }

            // ===============================
            // 4. LEER CONTENIDO PDF
            // ===============================
            $contenido = file_get_contents($archivo->getRealPath());

            if ($contenido === false) {
                return response()->json([
                    'ok' => false,
                    'error' => 'No se pudo leer el archivo PDF'
                ], 500);
            }

            // ===============================
            // 5. CONVERTIR A HEX PARA BYTEA
            // ===============================
            $hex = bin2hex($contenido);

            // ===============================
            // 6. INSERTAR EN POSTGRESQL
            // ===============================
            $id = DB::table('resoluciones_pdf')->insertGetId([
                'nro_resolucion' => $request->nro_resolucion,
                'descripcion' => $request->descripcion,
                'anio' => (int) $request->anio,
                'periodo' => $request->periodo,

                // BYTEA PostgreSQL
                'archivo_pdf' => DB::raw("decode('$hex','hex')"),

                'nombre_archivo' => $archivo->getClientOriginalName(),
                'tamanio_kb' => round($archivo->getSize() / 1024),
                'subido_por' => $request->subido_por,
                'fecha_subida' => now(),

            ], 'id_resolucion');

            // ===============================
            // 7. RESPUESTA EXITOSA
            // ===============================
            return response()->json([
                'ok' => true,
                'mensaje' => 'PDF guardado correctamente',
                'id_resolucion' => $id,
                'archivo' => $archivo->getClientOriginalName(),
                'peso_kb' => round($archivo->getSize() / 1024)
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {

            return response()->json([
                'ok' => false,
                'tipo' => 'validacion',
                'errores' => $e->errors()
            ], 422);

        } catch (\Illuminate\Database\QueryException $e) {

            \Log::error('Error SQL ResolucionPdf', [
                'sql_error' => $e->getMessage()
            ]);

            return response()->json([
                'ok' => false,
                'tipo' => 'database',
                'error' => 'Error al guardar en PostgreSQL'
            ], 500);

        } catch (\Throwable $e) {

            \Log::error('Error General ResolucionPdf', [
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);

            return response()->json([
                'ok' => false,
                'tipo' => 'general',
                'error' => 'Error interno del servidor'
            ], 500);
        }
    }

    public function descargar($id)
    {
        $pdf = ResolucionPdf::findOrFail($id);

        $contenido = $pdf->archivo_pdf;

        // Si viene como resource desde PostgreSQL
        if (is_resource($contenido)) {
            $contenido = stream_get_contents($contenido);
        }

        return response($contenido)
            ->header('Content-Type', 'application/pdf')
            ->header(
                'Content-Disposition',
                'inline; filename="' . $pdf->nombre_archivo . '"'
            );
    }

    // GET /resoluciones/por-numero?nro=RR+N+21/2007
    public function porNumero(Request $request)
    {
        $nro = $request->query('nro');
        if (!$nro) {
            return response()->json(['ok' => false, 'error' => 'Falta el parámetro nro'], 400);
        }

        $resolucion = ResolucionPdf::select('id_resolucion', 'nro_resolucion', 'nombre_archivo', 'anio', 'periodo')
            ->where('nro_resolucion', $nro)
            ->first();

        if (!$resolucion) {
            return response()->json(['ok' => false, 'error' => 'No encontrada'], 404);
        }

        return response()->json(['ok' => true, 'id_resolucion' => $resolucion->id_resolucion, 'nombre_archivo' => $resolucion->nombre_archivo]);
    }
}
