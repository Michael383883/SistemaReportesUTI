<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EstudianteInscritoController extends Controller
{
    /**
     * Lista de estudiantes inscritos por anio/periodo, segun los planes
     * de estudio definidos (CON, ADM, COM, FIN, ECO).
     *
     * Parametros opcionales (query string):
     *   - anio      (default: 2026)
     *   - periodo   (default: 1)
     *   - plan      (filtra por un plan especifico, ej: 089801)
     *   - materia   (filtra por codigo de materia)
     *   - grupo     (filtra por grupo)
     *   - nivel     (filtra por un nivel especifico, ej: A, B, C...)
     *   - page      (default: 1) numero de pagina
     *   - per_page  (default: 100, maximo: 500) registros por pagina
     *
     * Ejemplo: GET /api/estudiantes-inscritos?anio=2026&periodo=1&nivel=A&page=1&per_page=100
     */
    public function index(Request $request)
    {
        $anio    = $request->query('anio', '2026');
        $periodo = $request->query('periodo', '1');
        $plan    = $request->query('plan');
        $materia = $request->query('materia');
        $grupo   = $request->query('grupo');
        $nivel   = $request->query('nivel');

        $page    = max((int) $request->query('page', 1), 1);
        $perPage = (int) $request->query('per_page', 100);
        $perPage = $perPage > 0 ? min($perPage, 500) : 100;
        $offset  = ($page - 1) * $perPage;

        $planesPermitidos = ['089801', '109401', '125091', '126091', '059801'];

        // Niveles validos para la malla (excluye K, O, W que no se usan)
        $nivelesPermitidos = [
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J',
            'L', 'M', 'N', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'X',
        ];

        $selectData = "
            SELECT
                K.ANIO,
                K.PERIODO,
                K.[PLAN],
                M.NIVEL,
                CASE K.[PLAN]
                    WHEN '089801' THEN 'CON'
                    WHEN '109401' THEN 'ADM'
                    WHEN '125091' THEN 'COM'
                    WHEN '126091' THEN 'FIN'
                    WHEN '059801' THEN 'ECO'
                    ELSE K.[PLAN]
                END AS SIGLA_PLAN,
                K.MATERIA,
                M.NOMBRE AS NOMBRE_MATERIA,
                K.GRUPO,
                B.CODIGO AS COD_ESTUDIANTE,
                B.APELLIDOS + ' ' + B.NOMBRES AS ESTUDIANTE
        ";

        // FROM/JOIN/WHERE compartido entre el conteo total y la consulta paginada
        $nivelesPlaceholders = implode(', ', array_map(
            fn ($idx) => ":nivel_permitido_{$idx}",
            array_keys($nivelesPermitidos)
        ));

        $fromWhere = "
            FROM KARDEX_EXT K
            INNER JOIN BIOGRAFICOS B
                ON B.CODIGO = K.ESTUDIANTE
            INNER JOIN MATERIAS M
                ON M.ANIO = K.ANIO
                AND M.PERIODO = K.PERIODO
                AND M.[PLAN] = K.[PLAN]
                AND M.CODIGO = K.MATERIA
            INNER JOIN GRUPOS G
                ON G.ANIO = K.ANIO
                AND G.PERIODO = K.PERIODO
                AND G.[PLAN] = K.[PLAN]
                AND G.MATERIA = K.MATERIA
                AND G.GRUPO = K.GRUPO
            WHERE
                K.ANIO = :anio
                AND K.PERIODO = :periodo
                AND K.CANCELADO IS NULL
                AND K.TIPO_EXAMEN IN ('N','E')
                AND G.PRIMARIO = 'Y'
                AND G.TIPO = 'N'
                AND K.[PLAN] IN ('089801','109401','125091','126091','059801')
                AND M.NIVEL IN ($nivelesPlaceholders)
        ";

        $bindings = [
            'anio'    => $anio,
            'periodo' => $periodo,
        ];

        foreach ($nivelesPermitidos as $idx => $nivelPermitido) {
            $bindings["nivel_permitido_{$idx}"] = $nivelPermitido;
        }

        if ($plan) {
            if (!in_array($plan, $planesPermitidos)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El plan indicado no es valido.',
                ], 422);
            }
            $fromWhere .= " AND K.[PLAN] = :plan ";
            $bindings['plan'] = $plan;
        }

        if ($materia) {
            $fromWhere .= " AND K.MATERIA = :materia ";
            $bindings['materia'] = $materia;
        }

        if ($grupo) {
            $fromWhere .= " AND K.GRUPO = :grupo ";
            $bindings['grupo'] = $grupo;
        }

        if ($nivel) {
            if (!in_array($nivel, $nivelesPermitidos)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El nivel indicado no es valido.',
                ], 422);
            }
            $fromWhere .= " AND M.NIVEL = :nivel_filtro ";
            $bindings['nivel_filtro'] = $nivel;
        }

        $sqlCount = "SELECT COUNT(*) AS TOTAL " . $fromWhere;

        $sqlData = $selectData . $fromWhere . "
            ORDER BY
                K.[PLAN],
                M.NIVEL ASC,
                K.MATERIA,
                K.GRUPO,
                B.APELLIDOS,
                B.NOMBRES
            OFFSET :offset ROWS FETCH NEXT :per_page ROWS ONLY
        ";

        $bindingsData = $bindings + [
            'offset'   => $offset,
            'per_page' => $perPage,
        ];

        try {
            $total      = (int) (DB::select($sqlCount, $bindings)[0]->TOTAL ?? 0);
            $resultados = DB::select($sqlData, $bindingsData);

            return response()->json([
                'success'     => true,
                'page'        => $page,
                'per_page'    => $perPage,
                'total'       => $total,
                'total_pages' => $perPage > 0 ? (int) ceil($total / $perPage) : 0,
                'data'        => $resultados,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al obtener estudiantes inscritos: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ocurrio un error al consultar los estudiantes inscritos.',
            ], 500);
        }
    }
}