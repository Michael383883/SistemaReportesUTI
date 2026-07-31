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
     * (SIN CAMBIOS - se mantiene para uso futuro)
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
     * (SIN CAMBIOS - se mantiene para uso futuro)
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

    /**
     * GET /api/materias/docente
     * NUEVO: Busca materias, pero SOLO las que el docente indicado
     * efectivamente dictó (según GRUPOS), no todas las materias del sistema.
     *
     * Parámetros:
     *  - docente   (requerido) código del docente
     *  - search    (opcional) filtra por nombre, código o sigla
     *  - anio      (opcional) restringe a materias dictadas en ese año
     *  - periodo   (opcional) restringe a materias dictadas en ese periodo
     *
     * Si no se manda anio/periodo, devuelve el listado histórico
     * (distinct) de materias que ese docente dictó alguna vez.
     */
    public function porDocente(Request $request)
    {
        $request->validate([
            'docente' => 'required|numeric',
            'anio' => 'nullable|numeric',
            'periodo' => 'nullable|string|in:1,2,3,4',
            'search' => 'nullable|string|max:60',
        ]);

        try {
            $docente = $request->query('docente');
            $anio = $request->query('anio');
            $periodo = $request->query('periodo');
            $search = $request->query('search');

            $query = DB::connection('sqlsrv')->table('GRUPOS')
                ->join('MATERIAS', function ($join) {
                    $join->on('MATERIAS.CODIGO', '=', 'GRUPOS.MATERIA')
                        ->on('MATERIAS.ANIO', '=', 'GRUPOS.ANIO')
                        ->on('MATERIAS.PERIODO', '=', 'GRUPOS.PERIODO')
                        ->on('MATERIAS.PLAN', '=', 'GRUPOS.PLAN');
                })
                ->leftJoin('PLANES', function ($join) {
                    $join->on('PLANES.CODIGO', '=', 'GRUPOS.PLAN')
                        ->on('PLANES.ANIO', '=', 'GRUPOS.ANIO');
                })
                ->where('GRUPOS.DOCENTE', $docente)
                ->where('GRUPOS.PRIMARIO', 'Y')
                ->where('GRUPOS.TIPO', 'N')
                ->select(
                    'MATERIAS.CODIGO as codigo',
                    'MATERIAS.NOMBRE as nombre',
                    'MATERIAS.SIGLA as sigla',
                    'MATERIAS.PERIODO as periodo',
                    'MATERIAS.ANIO as anio',
                    'MATERIAS.PLAN as cod_plan',
                    'PLANES.NOMBRE as nombre_plan',
                    'GRUPOS.GRUPO as grupo'   // ← NUEVO: número de grupo
                );
            // Nota: se quita distinct() global porque ahora el GRUPO forma
            // parte del resultado y puede repetir la materia con distinto
            // grupo (lo cual es correcto, cada fila = materia+grupo real).

            if ($anio) {
                $query->where('GRUPOS.ANIO', $anio);
            }

            if ($periodo) {
                $query->where('GRUPOS.PERIODO', $periodo);
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('MATERIAS.NOMBRE', 'LIKE', "%{$search}%")
                        ->orWhere('MATERIAS.CODIGO', 'LIKE', "%{$search}%")
                        ->orWhere('MATERIAS.SIGLA', 'LIKE', "%{$search}%");
                });
            }

            $materias = $query
                ->orderBy('MATERIAS.NOMBRE')
                ->orderBy('GRUPOS.GRUPO')
                ->get();

            return response()->json($materias);

        } catch (\Exception $e) {
            Log::error('Error en MateriaController@porDocente', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'params' => $request->all()
            ]);

            return response()->json([
                'error' => 'Error al buscar materias del docente',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    
}