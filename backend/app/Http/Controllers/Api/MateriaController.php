<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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


    /**
     * GET /api/materias/buscar?q=texto
     * Una fila por materia + plan + grupo, con la abreviatura del plan.
     */
    public function buscar(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2|max:60',
        ]);

        try {
            $palabras = preg_split('/\s+/', trim($request->query('q')), -1, PREG_SPLIT_NO_EMPTY);

            $condiciones = '';
            $bindings = [];
            foreach ($palabras as $i => $p) {
                $condiciones .= " AND (MATERIAS.NOMBRE LIKE :n{$i} OR GRUPOS.MATERIA LIKE :c{$i})";
                $bindings["n{$i}"] = "%{$p}%";
                $bindings["c{$i}"] = "%{$p}%";
            }

            $sql = <<<SQL
        SELECT TOP 40
            GRUPOS.MATERIA            AS codigo,
            MAX(MATERIAS.NOMBRE)      AS nombre,
            GRUPOS.[PLAN]             AS cod_plan,
            MAX([PLANES].NOMBRE)      AS nombre_plan,
            GRUPOS.GRUPO              AS grupo,
            MIN(GRUPOS.ANIO)          AS anio_desde,
            MAX(GRUPOS.ANIO)          AS anio_hasta
        FROM GRUPOS
        INNER JOIN MATERIAS
            ON MATERIAS.CODIGO  = GRUPOS.MATERIA
            AND MATERIAS.ANIO    = GRUPOS.ANIO
            AND MATERIAS.PERIODO = GRUPOS.PERIODO
            AND MATERIAS.[PLAN]  = GRUPOS.[PLAN]
        LEFT JOIN [PLANES]
            ON [PLANES].CODIGO = GRUPOS.[PLAN]
            AND [PLANES].ANIO  = GRUPOS.ANIO
        WHERE GRUPOS.PRIMARIO = 'Y'
          AND GRUPOS.TIPO = 'N'
          AND GRUPOS.PERIODO NOT IN ('3','4')
          AND MATERIAS.NOMBRE IS NOT NULL
          {$condiciones}
        GROUP BY GRUPOS.MATERIA, GRUPOS.[PLAN], GRUPOS.GRUPO
        ORDER BY MAX(MATERIAS.NOMBRE), GRUPOS.MATERIA, GRUPOS.[PLAN], GRUPOS.GRUPO
        SQL;

            $filas = DB::connection('sqlsrv')->select($sql, $bindings);

            $resultado = array_map(fn($f) => [
                'codigo' => trim((string) $f->codigo),
                'nombre' => trim((string) $f->nombre),
                'cod_plan' => trim((string) $f->cod_plan),
                'nombre_plan' => trim((string) $f->nombre_plan),
                'plan' => $this->abreviarPlan($f->nombre_plan),
                'grupo' => trim((string) $f->grupo),
                'anio_desde' => (int) $f->anio_desde,
                'anio_hasta' => (int) $f->anio_hasta,
            ], $filas);

            return response()->json($resultado);

        } catch (\Exception $e) {
            Log::error('Error en MateriaController@buscar', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'params' => $request->all(),
            ]);

            return response()->json([
                'error' => 'Error al buscar materias',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Misma regla que plan_abrev en ReporteDocenteController.
     */
    private function abreviarPlan(?string $nombrePlan): string
    {
        $n = Str::upper(Str::ascii(trim((string) $nombrePlan)));

        return match (true) {
            $n === '' => '—',
            str_contains($n, 'ADMINISTRACION') => 'ADM',
            str_contains($n, 'COMERCIAL') => 'COM',
            str_contains($n, 'FINANCIERA') => 'FIN',
            str_contains($n, 'ECONOMIA') => 'ECO',
            str_contains($n, 'CONTADURIA') => 'CON',
            default => mb_substr($n, 0, 3),
        };
    }

}