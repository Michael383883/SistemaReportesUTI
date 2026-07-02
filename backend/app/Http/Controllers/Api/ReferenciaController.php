<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReferenciaController extends Controller
{
    /**
     * GET /api/referencias
     * Obtiene listado de referencias (resoluciones) para seleccionar
     */
    public function index(Request $request)
    {
        try {
            $query = DB::table('RESOLUCIONES_PDF')
                ->select(
                    'ID_RESOLUCION as id',
                    'NRO_RESOLUCION as nro_referencia',
                    'DESCRIPCION as descripcion',
                    'ANIO as anio',
                    'FECHA_SUBIDA as fecha_subida'
                )
                ->whereNotNull('NRO_RESOLUCION')
                ->where('NRO_RESOLUCION', '!=', '');

            // Búsqueda por número de referencia
            if ($request->filled('search')) {
                $search = $request->query('search');
                $query->where(function ($q) use ($search) {
                    $q->where('NRO_RESOLUCION', 'LIKE', "%{$search}%")
                      ->orWhere('DESCRIPCION', 'LIKE', "%{$search}%");
                });
            }

            // Filtro por año
            if ($request->filled('anio')) {
                $query->where('ANIO', $request->query('anio'));
            }

            $referencias = $query->orderBy('NRO_RESOLUCION')->get();

            return response()->json($referencias);

        } catch (\Exception $e) {
            Log::error('Error en ReferenciaController@index', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'params' => $request->all()
            ]);

            return response()->json([
                'error' => 'Error al buscar referencias',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/referencias/anios
     * Obtiene los años disponibles en las referencias
     */
    public function anios()
    {
        try {
            $anios = DB::table('RESOLUCIONES_PDF')
                ->select('ANIO')
                ->distinct()
                ->whereNotNull('ANIO')
                ->orderBy('ANIO', 'DESC')
                ->pluck('ANIO');

            return response()->json($anios);

        } catch (\Exception $e) {
            Log::error('Error en ReferenciaController@anios', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return response()->json([
                'error' => 'Error al obtener los años',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}