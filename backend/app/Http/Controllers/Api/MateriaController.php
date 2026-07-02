<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MateriaController extends Controller
{
    /**
     * GET /api/materias
     * Busca materias por gestión (ANIO) y/o periodo (PERIODO)
     */
    public function index(Request $request)
    {
        try {
            $query = DB::table('MATERIAS')
                ->select(
                    'CODIGO as codigo',
                    'NOMBRE as nombre',
                    'SIGLA as sigla',
                    'PERIODO as periodo',
                    'ANIO as anio',
                    'PLAN as plan'
                )
                ->whereNotNull('NOMBRE')
                ->where('NOMBRE', '!=', 'NULL');

            // Filtro por gestión (ANIO) - OBLIGATORIO
            if ($request->filled('anio')) {
                $query->where('ANIO', $request->query('anio'));
            } else {
                // Si no hay año, devolver vacío
                return response()->json([]);
            }

            // Filtro por periodo (PERIODO) - OPCIONAL
            if ($request->filled('periodo')) {
                $query->where('PERIODO', $request->query('periodo'));
            }

            // Búsqueda por texto - OPCIONAL
            if ($request->filled('search')) {
                $search = $request->query('search');
                $query->where(function ($q) use ($search) {
                    $q->where('NOMBRE', 'LIKE', "%{$search}%")
                      ->orWhere('CODIGO', 'LIKE', "%{$search}%")
                      ->orWhere('SIGLA', 'LIKE', "%{$search}%");
                });
            }

            $materias = $query->orderBy('NOMBRE')->get();

            return response()->json($materias);

        } catch (\Exception $e) {
            Log::error('Error en MateriaController@index', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'params' => $request->all()
            ]);

            return response()->json([
                'error' => 'Error al buscar materias',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/materias/periodos
     * Obtiene los años disponibles
     */
    public function periodos()
    {
        try {
            $periodos = DB::table('MATERIAS')
                ->select('ANIO')
                ->distinct()
                ->whereNotNull('ANIO')
                ->orderBy('ANIO', 'DESC')
                ->pluck('ANIO');

            return response()->json($periodos);

        } catch (\Exception $e) {
            Log::error('Error en MateriaController@periodos', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return response()->json([
                'error' => 'Error al obtener los periodos',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}