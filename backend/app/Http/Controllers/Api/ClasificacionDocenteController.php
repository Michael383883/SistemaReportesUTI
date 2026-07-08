<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ClasificacionDocenteController extends Controller
{
    // GET /clasificaciones
    // GET /clasificaciones
    public function index(Request $request)
    {
        $query = DB::table('CLASIFICACION_DOCENTE as cd')
            ->join('DOCENTES as d', 'd.CODIGO', '=', 'cd.COD_DOCENTE')
            ->select(
                'cd.ID_CLASIFICACION',
                'cd.COD_DOCENTE',
                DB::raw("LTRIM(RTRIM(d.APELLIDOS + ' ' + d.NOMBRES)) AS NOMBRE_DOCENTE"),
                'cd.CATEGORIA',
                'cd.NIVEL',
                'cd.TIPO_DOCUMENTO',
                'cd.GESTION',
                'cd.PERIODO',
                'cd.FOTOCOPIA_TITULAR',
                'cd.NOMBRE_ARCHIVO',
                'cd.FECHA_REGISTRO'
            );

        if ($request->filled('categoria')) {
            $query->where('cd.CATEGORIA', $request->query('categoria'));
        }
        if ($request->filled('nivel')) {
            $query->where('cd.NIVEL', $request->query('nivel'));
        }
        if ($request->filled('gestion')) {
            $query->where('cd.GESTION', $request->query('gestion'));
        }
        if ($request->filled('cod_docente')) {
            $query->where('cd.COD_DOCENTE', $request->query('cod_docente'));
        }
        if ($request->filled('tipo_documento')) {
            $query->where('cd.TIPO_DOCUMENTO', $request->query('tipo_documento'));
        }

        $listado = $query->orderBy('NOMBRE_DOCENTE')->get();

        return response()->json($listado);
    }

    // GET /clasificaciones/{id}
    public function show($id)
    {
        $cabecera = DB::table('CLASIFICACION_DOCENTE as cd')
            ->join('DOCENTES as d', 'd.CODIGO', '=', 'cd.COD_DOCENTE')
            ->where('cd.ID_CLASIFICACION', $id)
            ->select(
                'cd.*',
                DB::raw("LTRIM(RTRIM(d.APELLIDOS + ' ' + d.NOMBRES)) AS NOMBRE_DOCENTE")
            )
            ->first();

        if (!$cabecera) {
            return response()->json(['ok' => false, 'error' => 'Clasificación no encontrada'], 404);
        }

        $cabecera->materias = DB::table('CLASIFICACION_MATERIA')
            ->where('ID_CLASIFICACION', $id)
            ->orderBy('ORDEN')
            ->get();

        $cabecera->referencias = DB::table('CLASIFICACION_REFERENCIA')
            ->where('ID_CLASIFICACION', $id)
            ->get();

        return response()->json($cabecera);
    }

    // POST /clasificaciones
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $request->validate([
                'cod_docente' => 'required|integer',
                'categoria' => 'required|string|in:Docentes Titulares,Docentes Temporales,Examen de suficiencia,Acefala,Sin Examen de suficiencia',
                'nivel' => 'nullable|string|in:Primer nivel,Segundo nivel,Tercer nivel',
                'tipo_documento' => 'nullable|string|max: 40', // <-- nuevo
                'gestion' => 'nullable|string|max:10',
                'periodo' => 'nullable|string|max:30',
                'detalle_general' => 'nullable|string',
                'observacion' => 'nullable|string|max:300',
                'observacion2' => 'nullable|string|max:300',
                'archivo_pdf' => 'nullable|file|mimes:pdf|max:20480',
            ]);

            $rutaArchivo = null;
            $nombreArchivo = null;
            $fotocopia = false;

            if ($request->hasFile('archivo_pdf')) {
                $archivo = $request->file('archivo_pdf');

                if (!$archivo->isValid()) {
                    return response()->json(['ok' => false, 'error' => 'Archivo inválido'], 400);
                }

                $carpeta = 'clasificacion_docente/' . ($request->gestion ?: 'sin_gestion');
                $nombreLimpio = preg_replace('/[^A-Za-z0-9\-_\.]/', '-', $archivo->getClientOriginalName());
                $rutaArchivo = $archivo->storeAs($carpeta, $nombreLimpio, 'public');
                $nombreArchivo = $archivo->getClientOriginalName();
                $fotocopia = true;
            }

            $idClasificacion = DB::table('CLASIFICACION_DOCENTE')->insertGetId([
                'COD_DOCENTE' => $request->cod_docente,
                'CATEGORIA' => $request->categoria,
                'NIVEL' => $request->nivel ?: null,           // ← Guarda el texto "PRIMER NIVEL", "SEGUNDO NIVEL", "TERCER NIVEL"
                'GESTION' => $request->gestion,
                'PERIODO' => $request->periodo,
                'TIPO_DOCUMENTO' => $request->tipo_documento,
                'DETALLE_GENERAL' => $request->detalle_general,
                'FOTOCOPIA_TITULAR' => $fotocopia,
                'RUTA_ARCHIVO' => $rutaArchivo,
                'NOMBRE_ARCHIVO' => $nombreArchivo,
                'OBSERVACION' => $request->observacion,
                'OBSERVACION2' => $request->observacion2,
                'FECHA_REGISTRO' => now(),
            ], 'ID_CLASIFICACION');

            // ------- materias -------
            $materiasInsertadas = 0;

            if ($request->filled('materias')) {
                $materias = json_decode($request->materias, true);

                if (!is_array($materias)) {
                    throw new \Exception('materias inválidas: el JSON no es un array');
                }

                foreach ($materias as $i => $m) {
                    DB::table('CLASIFICACION_MATERIA')->insert([
                        'ID_CLASIFICACION' => $idClasificacion,
                        'COD_MATERIA' => $m['cod_materia'] ?? null,
                        'NOMBRE_MATERIA' => $m['nombre_materia'],
                        'COD_PLAN' => $m['cod_plan'] ?? null,
                        'NOTA' => $m['nota'] ?? null,
                        'DETALLE' => $m['detalle'] ?? null,
                        'ORDEN' => $i,
                    ]);
                    $materiasInsertadas++;
                }
            }

            // ------- referencias -------
            $referenciasInsertadas = 0;

            if ($request->filled('referencias')) {
                $referencias = json_decode($request->referencias, true);

                if (!is_array($referencias)) {
                    throw new \Exception('referencias inválidas: el JSON no es un array');
                }

                foreach ($referencias as $r) {
                    DB::table('CLASIFICACION_REFERENCIA')->insert([
                        'ID_CLASIFICACION' => $idClasificacion,
                        'NRO_REFERENCIA' => $r['nro_referencia'],
                        'ID_RESOLUCION' => $r['id_resolucion'] ?? null,
                    ]);
                    $referenciasInsertadas++;
                }
            }

            DB::commit();

            return response()->json([
                'ok' => true,
                'mensaje' => 'Clasificación registrada correctamente',
                'id_clasificacion' => $idClasificacion,
                'materias_insertadas' => $materiasInsertadas,
                'referencias_insertadas' => $referenciasInsertadas,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();

            return response()->json([
                'ok' => false,
                'tipo' => 'validacion',
                'errores' => $e->errors(),
            ], 422);

        } catch (\Throwable $e) {
            DB::rollBack();

            $mensajeSeguro = preg_replace(
                '/[\x00-\x08\x0B\x0C\x0E-\x1F\x80-\xFF]/',
                '?',
                $e->getMessage()
            );

            \Log::error('Error ClasificacionDocente', [
                'mensaje' => $mensajeSeguro,
                'linea' => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            return response()->json(['ok' => false, 'error' => $mensajeSeguro], 500);
        }
    }

    // GET /clasificaciones/{id}/pdf
    public function descargar(Request $request, $id)
    {
        $cab = DB::table('CLASIFICACION_DOCENTE')
            ->select('ID_CLASIFICACION', 'NOMBRE_ARCHIVO', 'RUTA_ARCHIVO')
            ->where('ID_CLASIFICACION', $id)
            ->first();

        if (!$cab) {
            return response()->json(['ok' => false, 'error' => 'Clasificación no encontrada'], 404);
        }

        if (!$cab->RUTA_ARCHIVO || !Storage::disk('public')->exists($cab->RUTA_ARCHIVO)) {
            return response()->json(['ok' => false, 'error' => 'Archivo no encontrado en storage'], 404);
        }

        $contenido = Storage::disk('public')->get($cab->RUTA_ARCHIVO);
        $disposicion = $request->query('modo') === 'descargar' ? 'attachment' : 'inline';

        return response($contenido)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', $disposicion . '; filename="' . $cab->NOMBRE_ARCHIVO . '"');
    }

    // DELETE /clasificaciones/{id}
    public function destroy($id)
    {
        $cab = DB::table('CLASIFICACION_DOCENTE')->where('ID_CLASIFICACION', $id)->first();

        if (!$cab) {
            return response()->json(['ok' => false, 'error' => 'Clasificación no encontrada'], 404);
        }

        DB::table('CLASIFICACION_DOCENTE')->where('ID_CLASIFICACION', $id)->delete();

        try {
            if ($cab->RUTA_ARCHIVO && Storage::disk('public')->exists($cab->RUTA_ARCHIVO)) {
                Storage::disk('public')->delete($cab->RUTA_ARCHIVO);
            }
        } catch (\Throwable $eArchivo) {
            \Log::warning('No se pudo borrar el archivo físico de la clasificación', [
                'id' => $id,
                'ruta' => $cab->RUTA_ARCHIVO,
                'error' => $eArchivo->getMessage(),
            ]);
        }

        return response()->json(['ok' => true, 'mensaje' => 'Clasificación eliminada correctamente']);
    }
}