<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
     * (SIN CAMBIOS)
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
                    'GRUPOS.GRUPO as grupo'
                );

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
     * GET /api/materias/buscar?q=texto&grupo=34
     * Una fila por materia + plan + grupo, con la abreviatura del plan.
     */
    public function buscar(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2|max:60',
            'grupo' => 'nullable|string|max:10|alpha_num',
        ]);

        try {
            $q = trim($request->query('q'));
            $grupo = trim((string) $request->query('grupo', ''));

            // Caché 5 min: los datos históricos casi no cambian
            $resultado = Cache::remember(
                'materias_buscar:' . md5(mb_strtolower($q) . '|' . mb_strtolower($grupo)),
                300,
                fn() => $this->consultarMaterias($q, $grupo)
            );

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

    private function consultarMaterias(string $q, string $grupo): array
    {
        $palabras = preg_split('/\s+/', $q, -1, PREG_SPLIT_NO_EMPTY);

        $condiciones = '';
        $bindings = [];
        foreach ($palabras as $i => $p) {
            if (ctype_digit($p)) {
                // Número: código por prefijo (usa índice) o parte del nombre
                $condiciones .= " AND (MATERIAS.CODIGO LIKE :c{$i} OR MATERIAS.NOMBRE LIKE :n{$i})";
                $bindings["c{$i}"] = "{$p}%";
            } else {
                $condiciones .= " AND MATERIAS.NOMBRE LIKE :n{$i}";
            }
            $bindings["n{$i}"] = "%{$p}%";
        }

        // Filtro de grupo en SQL: "3" -> 3, 30, 34...  y "1" también encuentra "01"
        $filtroGrupo = '';
        $orden = '';
        if ($grupo !== '') {
            $g = ltrim($grupo, '0');
            if ($g === '')
                $g = '0';

            $filtroGrupo = " AND (GRUPOS.GRUPO LIKE :g1 OR GRUPOS.GRUPO LIKE :g2)";
            $bindings['g1'] = "{$g}%";
            $bindings['g2'] = "0{$g}%";

            // Grupo exacto primero
            $orden = "CASE WHEN LTRIM(RTRIM(GRUPOS.GRUPO)) IN (:e1, :e2) THEN 0 ELSE 1 END,";
            $bindings['e1'] = $g;
            $bindings['e2'] = "0{$g}";
        }

        $sql = <<<SQL
        SELECT TOP 40
            MATERIAS.CODIGO           AS codigo,
            MAX(MATERIAS.NOMBRE)      AS nombre,
            GRUPOS.[PLAN]             AS cod_plan,
            MAX([PLANES].NOMBRE)      AS nombre_plan,
            GRUPOS.GRUPO              AS grupo,
            MIN(GRUPOS.ANIO)          AS anio_desde,
            MAX(GRUPOS.ANIO)          AS anio_hasta
        FROM MATERIAS
        INNER JOIN GRUPOS
            ON GRUPOS.MATERIA    = MATERIAS.CODIGO
            AND GRUPOS.ANIO      = MATERIAS.ANIO
            AND GRUPOS.PERIODO   = MATERIAS.PERIODO
            AND GRUPOS.[PLAN]    = MATERIAS.[PLAN]
        LEFT JOIN [PLANES]
            ON [PLANES].CODIGO = GRUPOS.[PLAN]
            AND [PLANES].ANIO  = GRUPOS.ANIO
        WHERE GRUPOS.PRIMARIO = 'Y'
          AND GRUPOS.TIPO = 'N'
          AND MATERIAS.PERIODO NOT IN ('3','4')
          AND MATERIAS.NOMBRE IS NOT NULL
          {$condiciones}
          {$filtroGrupo}
        GROUP BY MATERIAS.CODIGO, GRUPOS.[PLAN], GRUPOS.GRUPO
        ORDER BY {$orden} MAX(MATERIAS.NOMBRE), MATERIAS.CODIGO, GRUPOS.[PLAN], GRUPOS.GRUPO
        SQL;

        $filas = DB::connection('sqlsrv')->select($sql, $bindings);

        return array_map(fn($f) => [
            'codigo' => trim((string) $f->codigo),
            'nombre' => trim((string) $f->nombre),
            'cod_plan' => trim((string) $f->cod_plan),
            'nombre_plan' => trim((string) $f->nombre_plan),
            'plan' => $this->abreviarPlan($f->nombre_plan),
            'grupo' => trim((string) $f->grupo),
            'anio_desde' => (int) $f->anio_desde,
            'anio_hasta' => (int) $f->anio_hasta,
        ], $filas);
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