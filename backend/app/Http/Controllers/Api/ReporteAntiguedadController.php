<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReporteAntiguedadController extends Controller
{
    // Mismos planes que ReporteKardexMateriaController
    private const PLANES = ['109401', '125091', '126091', '089801', '059801'];

    // Solo estos periodos cuentan para la antigüedad (1 y 2 = semestres)
    private const PERIODOS_CUENTAN = ['1', '2'];

    /**
     * GET /api/reportes/antiguedad-docentes-materia?anio=2026&periodo=2
     *
     * Devuelve, por docente activo en ese anio/periodo, cada materia que dictó
     * y la lista de periodos ("2024-1", "2024-2"...) en que la dictó, hasta
     * el anio/periodo indicado (inclusive).
     */
    public function index(Request $request)
    {
        $request->validate([
            'anio' => 'required|integer|min:2000|max:2100',
            'periodo' => 'required|string|in:1,2,3,4',
        ]);

        $anio = (int) $request->input('anio');
        $periodo = (string) $request->input('periodo');

        $planesSql = implode(',', array_map(fn($p) => "'" . $p . "'", self::PLANES));
        $periodosSql = implode(',', array_map(fn($p) => "'" . $p . "'", self::PERIODOS_CUENTAN));

        $sql = <<<SQL
        SELECT DISTINCT
            LTRIM(RTRIM(CAST(G.DOCENTE AS VARCHAR(30)))) AS cod_docente,
            M.NOMBRE                                     AS nom_materia,
            G.ANIO                                       AS anio,
            G.PERIODO                                    AS periodo
        FROM GRUPOS AS G
        INNER JOIN MATERIAS AS M
            ON  M.CODIGO  = G.MATERIA
            AND M.ANIO    = G.ANIO
            AND M.PERIODO = G.PERIODO
            AND M.[PLAN]  = G.[PLAN]
        WHERE G.PRIMARIO = 'Y'
          AND G.TIPO     = 'N'
          AND G.[PLAN] IN ({$planesSql})
          AND G.PERIODO IN ({$periodosSql})
          AND (G.ANIO < :anio1 OR (G.ANIO = :anio2 AND G.PERIODO <= :periodo1))
          AND G.DOCENTE IS NOT NULL
          AND G.DOCENTE IN (
                SELECT G2.DOCENTE
                FROM GRUPOS AS G2
                WHERE G2.ANIO    = :anio3
                  AND G2.PERIODO = :periodo2
                  AND G2.PRIMARIO = 'Y'
                  AND G2.TIPO     = 'N'
          )
        SQL;

        $rows = DB::connection('sqlsrv')->select($sql, [
            'anio1' => $anio,
            'anio2' => $anio,
            'anio3' => $anio,
            'periodo1' => $periodo,
            'periodo2' => $periodo,
        ]);

        // docente -> materia(normalizada) -> ['nom_materia', 'periodos' => set]
        $mapa = [];
        foreach ($rows as $r) {
            $cod = trim((string) $r->cod_docente);
            $nom = trim((string) $r->nom_materia);
            $key = Str::upper(Str::ascii($nom));
            $per = trim((string) $r->anio) . '-' . trim((string) $r->periodo);

            $mapa[$cod][$key]['nom_materia'] = $nom;
            $mapa[$cod][$key]['periodos'][$per] = true;
        }

        $data = [];
        foreach ($mapa as $cod => $materias) {
            $lista = [];
            foreach ($materias as $m) {
                $lista[] = [
                    'nom_materia' => $m['nom_materia'],
                    'periodos' => array_keys($m['periodos']),
                ];
            }
            $data[] = ['codigo' => (string) $cod, 'materias' => $lista];
        }

        return response()->json(['success' => true, 'data' => $data]);
    }
}