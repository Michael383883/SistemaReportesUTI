<?php

namespace App\Http\Controllers\Api;

use App\Models\ResolucionPdf;
use App\Models\ResolucionDetalle;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\DashboardAdminController;

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

            DashboardAdminController::limpiarCacheDashboard();

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
//   1) Limpia en GRUPOS lo que aplicarEnGrupos() había escrito.
//   2) Borra los docentes/materias asignados en RESOLUCION_DETALLE.
//   3) Borra el registro en RESOLUCIONES_PDF.
//   4) Borra el archivo PDF físico.
//
// Si la resolución tiene documentos de clasificación docente enlazados
// (CLASIFICACION_REFERENCIA), el borrado se bloquea con un mensaje claro
// en vez de dejar pasar el error crudo de SQL Server.
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

            // 1) Limpiar GRUPOS lo que aplicarEnGrupos() había escrito para
            //    esta resolución. Va antes de borrar RESOLUCION_DETALLE porque
            //    el JOIN necesita que esas filas todavía existan.
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
            //    (acá es donde puede saltar el conflicto de FK con
            //    CLASIFICACION_REFERENCIA, capturado más abajo)
            DB::table('RESOLUCIONES_PDF')
                ->where('ID_RESOLUCION', $id)
                ->delete();

            DB::commit();
            DashboardAdminController::limpiarCacheDashboard();

            // 4) Borrar el archivo físico. Si esto falla no revertimos el
            //    borrado en BD, solo lo dejamos registrado para limpieza manual.
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

        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();

            // errorInfo[0] = SQLSTATE (ej: "23000")
            // errorInfo[1] = código nativo del driver (ej: 547 = FK violation en SQL Server)
            // errorInfo[2] = mensaje nativo completo
            $sqlState = $e->errorInfo[0] ?? null;
            $codigoNativo = $e->errorInfo[1] ?? null;
            $mensajeNativo = $e->errorInfo[2] ?? $e->getMessage();

            \Log::error('QueryException al borrar ResolucionPdf', [
                'id' => $id,
                'getCode' => $e->getCode(),
                'sqlState' => $sqlState,
                'codigoNativo' => $codigoNativo,
                'mensajeNativo' => $mensajeNativo,
            ]);

            $esViolacionFk =
                $sqlState === '23000'
                || $e->getCode() === '23000'
                || (string) $codigoNativo === '547'
                || str_contains($mensajeNativo, 'REFERENCE')
                || str_contains($mensajeNativo, 'FOREIGN KEY')
                || str_contains($mensajeNativo, 'conflicto con la restricci');

            if (!$esViolacionFk) {
                return response()->json([
                    'ok' => false,
                    'error' => 'Ocurrió un error de base de datos al eliminar la resolución.',
                ], 500);
            }

            // Identificar qué tabla es la que bloquea, a partir del nombre
            // de la constraint que trae el mensaje nativo
            $tablaConflicto = null;
            if (preg_match('/"([a-zA-Z0-9_]+)_id_resolucion_foreign"/', $mensajeNativo, $m)) {
                $tablaConflicto = $m[1];
            } elseif (preg_match('/table "dbo\.([A-Z_]+)"/i', $mensajeNativo, $m)) {
                $tablaConflicto = $m[1];
            }

            $registros = collect();

            if ($tablaConflicto && strtoupper($tablaConflicto) === 'CLASIFICACION_REFERENCIA') {
                // CLASIFICACION_REFERENCIA -> CLASIFICACION_DOCUMENTO (info del doc)
                //                          -> CLASIFICACION_DOCENTE -> DOCENTES
                $registros = DB::table('CLASIFICACION_REFERENCIA as cr')
                    ->join('CLASIFICACION_DOCUMENTO as cd', 'cd.ID_DOCUMENTO', '=', 'cr.ID_DOCUMENTO')
                    ->leftJoin('CLASIFICACION_DOCENTE as cdoc', 'cdoc.ID_DOCUMENTO', '=', 'cd.ID_DOCUMENTO')
                    ->leftJoin('DOCENTES as d', 'd.CODIGO', '=', 'cdoc.COD_DOCENTE')
                    ->where('cr.ID_RESOLUCION', $id)
                    ->select(
                        'cr.ID_REF',
                        'cr.NRO_REFERENCIA',
                        'cd.ID_DOCUMENTO',
                        'cd.TIPO_DOCUMENTO',
                        'cd.NOMBRE_ARCHIVO',
                        'cd.CATEGORIA',
                        'cd.GESTION',
                        DB::raw("LTRIM(RTRIM(d.NOMBRES + ' ' + ISNULL(d.APELLIDOS, ''))) AS NOMBRE_DOCENTE")
                    )
                    ->distinct()
                    ->get();
            }

            $cantidad = $registros->count();

            if ($cantidad > 0) {
                $nombresDocentes = $registros->pluck('NOMBRE_DOCENTE')->filter()->unique()->values();
                $tiposDocumento = $registros->pluck('TIPO_DOCUMENTO')->filter()->unique()->values();

                $detalle = "Tiene {$cantidad} documento(s) de clasificación docente enlazado(s) a esta resolución";

                if ($tiposDocumento->isNotEmpty()) {
                    $detalle .= " (" . $tiposDocumento->take(3)->implode(', ') . ")";
                }

                if ($nombresDocentes->isNotEmpty()) {
                    $detalle .= ". Docente(s): " . $nombresDocentes->take(5)->implode(', ');
                    if ($nombresDocentes->count() > 5) {
                        $detalle .= " y otro(s) " . ($nombresDocentes->count() - 5);
                    }
                }

                $detalle .= ". Desvincula o elimina esos documentos primero.";
            } else {
                $detalle = $tablaConflicto
                    ? "Existen registros relacionados en \"{$tablaConflicto}\" que deben eliminarse primero."
                    : 'Existen registros relacionados en otra tabla que deben eliminarse primero.';
            }

            return response()->json([
                'ok' => false,
                'tipo' => 'fk_violation',
                'tabla' => $tablaConflicto,
                'registros_bloqueando' => $registros,
                'error' => "No se puede eliminar la resolución: {$detalle}",
            ], 409);

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

            // ── Guardamos los valores VIEJOS antes de pisarlos ──────────────
            // GRUPOS.RESOLUCION y GRUPOS.DESIGNACION son copias denormalizadas
            // que aplicarEnGrupos() escribió en su momento. Si acá solo
            // actualizamos RESOLUCIONES_PDF, esas copias en GRUPOS quedan
            // desactualizadas (se ve el cambio en el listado pero no en las
            // materias/docentes ya enlazados). Por eso necesitamos el valor
            // anterior para poder ubicar esas filas y sincronizarlas.
            $nroAnterior = $resolucion->NRO_RESOLUCION;
            $descripcionAnterior = $resolucion->DESCRIPCION;

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

            // ── NUEVO: sincronizar GRUPOS que ya tienen esta resolución aplicada ──
            // Buscamos por el NRO_RESOLUCION anterior (no por ID_RESOLUCION,
            // porque GRUPOS no guarda ese ID, solo el texto que copió
            // aplicarEnGrupos). Si el número o la descripción cambiaron,
            // propagamos el cambio a todas las filas de GRUPOS que
            // coincidan con el valor viejo.
            $gruposSincronizados = 0;

            $cambioNumero = $nroAnterior !== $request->nro_resolucion;
            $cambioDescripcion = $descripcionAnterior !== $request->descripcion;

            if ($nroAnterior && ($cambioNumero || $cambioDescripcion)) {
                $gruposSincronizados = DB::update("
                    UPDATE GRUPOS
                    SET
                        RESOLUCION  = ?,
                        DESIGNACION = ?
                    WHERE RESOLUCION COLLATE Modern_Spanish_CI_AS = ? COLLATE Modern_Spanish_CI_AS
                ", [
                    $request->nro_resolucion,
                    $request->descripcion,
                    $nroAnterior,
                ]);
            }

            DB::commit();
            DashboardAdminController::limpiarCacheDashboard();

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
                'grupos_sincronizados' => $gruposSincronizados, // ← útil para mostrar feedback en el front
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

    // POST /clasificaciones/{idDocumento}/generar-resolucion
//
// Puente para el flujo de "Asignar a otros docentes": crea (o reutiliza)
// una fila en RESOLUCIONES_PDF tomando SOLO 4 datos de
// CLASIFICACION_DOCUMENTO (TIPO_DOCUMENTO, DETALLE_GENERAL, GESTION,
// PERIODO) y copiando RUTA_ARCHIVO/NOMBRE_ARCHIVO — no se vuelve a subir
// el PDF. A partir de acá, todo lo que se asigne (docente+materia+
// tipo_ingreso) se guarda en RESOLUCION_DETALLE, igual que el flujo
// original de "Resolución", para que el TIPO_INGRESO sea el que elige
// el usuario por cada materia y no el de CLASIFICACION_DOCUMENTO.CATEGORIA.
public function storeDesdeClasificacion(Request $request, $idDocumento)
{
    $documento = DB::table('CLASIFICACION_DOCUMENTO')
        ->where('ID_DOCUMENTO', $idDocumento)
        ->first();

    if (!$documento) {
        return response()->json(['ok' => false, 'error' => 'Documento no encontrado'], 404);
    }

    // Reutiliza si ya se generó antes para este mismo documento (evita
    // duplicar filas en RESOLUCIONES_PDF cada vez que se asigna a un
    // docente más sobre el mismo documento).
    $existente = DB::table('RESOLUCIONES_PDF')
        ->where('NRO_RESOLUCION', $documento->TIPO_DOCUMENTO)
        ->where('ANIO', (int) $documento->GESTION)
        ->where('PERIODO', $documento->PERIODO)
        ->first();

    if ($existente) {
        return response()->json([
            'ok' => true,
            'id_resolucion' => $existente->ID_RESOLUCION,
            'reutilizada' => true,
        ]);
    }

    $idResolucion = DB::table('RESOLUCIONES_PDF')->insertGetId([
        'NRO_RESOLUCION' => $documento->TIPO_DOCUMENTO,
        'DESCRIPCION'    => $documento->DETALLE_GENERAL,
        'ANIO'           => (int) $documento->GESTION,
        'PERIODO'        => $documento->PERIODO,
        'RUTA_ARCHIVO'   => $documento->RUTA_ARCHIVO,
        'NOMBRE_ARCHIVO' => $documento->NOMBRE_ARCHIVO,
        'TAMANIO_KB'     => null,
        'SUBIDO_POR'     => $request->input('subido_por'),
        'FECHA_SUBIDA'   => now(),
    ], 'ID_RESOLUCION');

    return response()->json([
        'ok' => true,
        'id_resolucion' => $idResolucion,
        'reutilizada' => false,
    ], 201);
}
}