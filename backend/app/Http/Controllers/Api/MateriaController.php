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
     * Incluye el nombre del plan (JOIN con PLANES)
     */
    public function index(Request $request)
    {
        try {
            $query = DB::table('MATERIAS as m')
                ->leftJoin('PLANES as p', function ($join) {
                    $join->on('m.PLAN', '=', 'p.CODIGO')
                        ->on('m.ANIO', '=', 'p.ANIO');
                })
                ->select(
                    'm.CODIGO as codigo',
                    'm.NOMBRE as nombre',
                    'm.SIGLA as sigla',
                    'm.PERIODO as periodo',
                    'm.ANIO as anio',
                    'm.PLAN as cod_plan',
                    'p.NOMBRE as nombre_plan'
                )
                ->whereNotNull('m.NOMBRE')
                ->where('m.NOMBRE', '!=', 'NULL');

            if ($request->filled('anio')) {
                $query->where('m.ANIO', $request->query('anio'));
            } else {
                return response()->json([]);
            }

            if ($request->filled('periodo')) {
                $query->where('m.PERIODO', $request->query('periodo'));
            }

            if ($request->filled('search')) {
                $search = $request->query('search');
                $query->where(function ($q) use ($search) {
                    $q->where('m.NOMBRE', 'LIKE', "%{$search}%")
                        ->orWhere('m.CODIGO', 'LIKE', "%{$search}%")
                        ->orWhere('m.SIGLA', 'LIKE', "%{$search}%");
                });
            }

            $materias = $query->orderBy('m.NOMBRE')->get();

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