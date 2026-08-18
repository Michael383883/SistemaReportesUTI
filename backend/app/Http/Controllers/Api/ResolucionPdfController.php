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

    // DELETE /resoluciones/{id}
    //
    // Borra la resolución completa:
    //   1) Todos los docentes/materias asignados en RESOLUCION_DETALLE
    //      para esa resolución.
    //   2) El registro en RESOLUCIONES_PDF.
    //   3) El archivo PDF físico en storage.
    // Los pasos 1 y 2 van en una transacción de BD: si algo falla, no
    // queda nada a medio borrar. El archivo físico se borra después,
    // ya que el storage no participa de la transacción de la BD.
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $resolucion = DB::table('RESOLUCIONES_PDF')
                ->where('ID_RESOLUCION', $id)
                ->first();

            if (!$resolucion) {
                DB::rollBack();

                return response()->json([
                    'ok' => false,
                    'error' => 'Resolución no encontrada'
                ], 404);
            }

            // 1) Limpiar en GRUPOS lo que aplicarEnGrupos() había escrito
            //    para esta resolución (RESOLUCION, DESIGNACION, TIPO_INGRESO).
            //    OJO: esto va ANTES de borrar RESOLUCION_DETALLE, porque el
            //    JOIN necesita que esas filas todavía existan para poder
            //    identificar qué filas de GRUPOS corresponden a esta
            //    resolución. No se borran filas de GRUPOS, solo se limpian
            //    esos 3 campos (el grupo/materia sigue existiendo).
            $gruposLimpiados = DB::update("
                UPDATE g
                SET
                    g.RESOLUCION   = NULL,
                    g.DESIGNACION  = NULL,
                    g.TIPO_INGRESO = NULL
                FROM GRUPOS g
                JOIN RESOLUCION_DETALLE dr
                    ON  g.DOCENTE                          = dr.COD_DOCENTE
                    AND g.[PLAN]  COLLATE Modern_Spanish_CI_AS = dr.COD_PLAN   COLLATE Modern_Spanish_CI_AS
                    AND g.MATERIA COLLATE Modern_Spanish_CI_AS = dr.COD_MATERIA COLLATE Modern_Spanish_CI_AS
                    AND g.[GRUPO] COLLATE Modern_Spanish_CI_AS = dr.[GRUPO]     COLLATE Modern_Spanish_CI_AS
                    AND g.[TIPO]  COLLATE Modern_Spanish_CI_AS = dr.[TIPO]      COLLATE Modern_Spanish_CI_AS
                JOIN RESOLUCIONES_PDF rp
                    ON rp.ID_RESOLUCION = dr.ID_RESOLUCION
                WHERE
                    rp.ID_RESOLUCION = ?
                    AND g.ANIO    = rp.ANIO
                    AND g.PERIODO COLLATE Modern_Spanish_CI_AS = CAST(rp.PERIODO AS NVARCHAR(10)) COLLATE Modern_Spanish_CI_AS
            ", [$id]);

            // 2) Borrar docentes/materias asignados a esta resolución
            $detallesBorrados = DB::table('RESOLUCION_DETALLE')
                ->where('ID_RESOLUCION', $id)
                ->delete();

            // 3) Borrar el registro de la resolución
            DB::table('RESOLUCIONES_PDF')
                ->where('ID_RESOLUCION', $id)
                ->delete();

            DB::commit();

            // 3) Borrar el archivo físico. Si esto falla no revertimos
            //    el borrado en BD, solo lo dejamos registrado en el log
            //    para limpieza manual posterior.
            try {
                if ($resolucion->RUTA_ARCHIVO && \Storage::disk('public')->exists($resolucion->RUTA_ARCHIVO)) {
                    \Storage::disk('public')->delete($resolucion->RUTA_ARCHIVO);
                }
            } catch (\Throwable $eArchivo) {
                \Log::warning('No se pudo borrar el archivo físico de la resolución', [
                    'id' => $id,
                    'ruta' => $resolucion->RUTA_ARCHIVO,
                    'error' => $eArchivo->getMessage(),
                ]);
            }

            return response()->json([
                'ok' => true,
                'mensaje' => 'Resolución, archivo, docentes asignados y datos en GRUPOS eliminados correctamente',
                'detalles_eliminados' => $detallesBorrados,
                'grupos_limpiados' => $gruposLimpiados,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            $mensajeSeguro = preg_replace(
                '/[\x00-\x08\x0B\x0C\x0E-\x1F\x80-\xFF]/',
                '?',
                $e->getMessage()
            );

            \Log::error('Error al borrar ResolucionPdf', [
                'id' => $id,
                'mensaje' => $mensajeSeguro,
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);

            return response()->json([
                'ok' => false,
                'error' => $mensajeSeguro
            ], 500);
        }
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

    // PUT/POST /resoluciones/{id}   (multipart, por eso se recibe como POST con _method=PUT)
    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $resolucion = DB::table('RESOLUCIONES_PDF')
                ->where('ID_RESOLUCION', $id)
                ->first();

            if (!$resolucion) {
                DB::rollBack();
                return response()->json([
                    'ok' => false,
                    'error' => 'Resolución no encontrada'
                ], 404);
            }

            $request->validate([
                'nro_resolucion' => 'required|string|max:50',
                'descripcion' => 'nullable|string|max:200',
                'anio' => 'required|integer',
                'periodo' => 'required|string|max:2',
                'archivo_pdf' => 'nullable|file|mimes:pdf|max:20480', // opcional al editar
            ]);

            $datosActualizar = [
                'NRO_RESOLUCION' => $request->nro_resolucion,
                'DESCRIPCION' => $request->descripcion,
                'ANIO' => (int) $request->anio,
                'PERIODO' => $request->periodo,
            ];

            $rutaAnterior = $resolucion->RUTA_ARCHIVO;
            $huboArchivoNuevo = $request->hasFile('archivo_pdf');

            // Si mandaron un PDF nuevo, lo guardamos y reemplazamos los datos del archivo
            if ($huboArchivoNuevo) {
                $archivo = $request->file('archivo_pdf');

                if (!$archivo->isValid()) {
                    DB::rollBack();
                    return response()->json([
                        'ok' => false,
                        'error' => 'Archivo inválido'
                    ], 400);
                }

                $carpeta = 'resoluciones/' . $request->anio;
                $nombreArchivo = preg_replace('/[^A-Za-z0-9\-_\.]/', '-', $archivo->getClientOriginalName());
                $rutaArchivo = $archivo->storeAs($carpeta, $nombreArchivo, 'public');

                $datosActualizar['RUTA_ARCHIVO'] = $rutaArchivo;
                $datosActualizar['NOMBRE_ARCHIVO'] = $archivo->getClientOriginalName();
                $datosActualizar['TAMANIO_KB'] = round($archivo->getSize() / 1024);
            }

            DB::table('RESOLUCIONES_PDF')
                ->where('ID_RESOLUCION', $id)
                ->update($datosActualizar);

            DB::commit();

            // Recién después del commit borramos el PDF viejo (si se reemplazó)
            if ($huboArchivoNuevo && $rutaAnterior && \Storage::disk('public')->exists($rutaAnterior)) {
                try {
                    \Storage::disk('public')->delete($rutaAnterior);
                } catch (\Throwable $eArchivo) {
                    \Log::warning('No se pudo borrar el PDF anterior al editar', [
                        'id' => $id,
                        'ruta' => $rutaAnterior,
                        'error' => $eArchivo->getMessage(),
                    ]);
                }
            }

            return response()->json([
                'ok' => true,
                'mensaje' => 'Resolución actualizada correctamente',
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'ok' => false,
                'tipo' => 'validacion',
                'errores' => $e->errors()
            ], 422);

        } catch (\Throwable $e) {
            DB::rollBack();

            $mensajeSeguro = preg_replace(
                '/[\x00-\x08\x0B\x0C\x0E-\x1F\x80-\xFF]/',
                '?',
                $e->getMessage()
            );

            \Log::error('Error al actualizar ResolucionPdf', [
                'id' => $id,
                'mensaje' => $mensajeSeguro,
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);

            return response()->json([
                'ok' => false,
                'error' => $mensajeSeguro
            ], 500);
        }
    }
}