<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReporteDocenteController extends Controller
{
    public function materiasDictadas(Request $request)
    {
        $request->validate([
            'docente' => 'required|numeric',
            'anio' => 'nullable|numeric',
        ]);

        $docente = $request->docente;
        $anio = $request->anio;

        $docenteInfo = DB::connection('pgsql')->selectOne("
            SELECT codigo, nombres, apellidos 
            FROM docentes
            WHERE codigo = ?
        ", [$docente]);

        if (!$docenteInfo) {
            return response()->json([
                'success' => false,
                'message' => 'Docente no encontrado'
            ], 404);
        }

        $filtroAnio = $anio ? "AND g.anio = :anio" : "";

        $materias = DB::connection('pgsql')->select("
            SELECT
                ROW_NUMBER() OVER (
                    ORDER BY
                        g.anio,
                        CASE 
                            WHEN g.periodo = '1' THEN 1
                            WHEN g.periodo = '4' THEN 2
                            WHEN g.periodo = '2' THEN 3
                            WHEN g.periodo = '3' THEN 4
                            ELSE 5
                        END,
                        g.grupo ASC,
                        g.materia,
                        g.plan
                ) AS nro,

                g.anio::TEXT || '/' ||
                CASE 
                    WHEN g.periodo = '3' THEN '3 - Verano'
                    WHEN g.periodo = '4' THEN '4 - Invierno'
                    ELSE g.periodo
                END AS gestion,

                CASE 
                    WHEN p.nombre ILIKE '%ADMINISTRACION%' THEN 'ADM'
                    WHEN p.nombre ILIKE '%COMERCIAL%'      THEN 'COM'
                    WHEN p.nombre ILIKE '%FINANCIERA%'     THEN 'FIN'
                    WHEN p.nombre ILIKE '%ECONOMIA%'       THEN 'ECO'
                    WHEN p.nombre ILIKE '%CONTADURIA%'     THEN 'CON'
                    ELSE LEFT(p.nombre, 3)
                END AS plan,

                g.materia::TEXT || ' ' || COALESCE(m.nombre, 'SIN NOMBRE') AS materia,

                CASE 
                    WHEN g.grupo > '30' AND g.periodo IN ('3','4') THEN 'COMPARTIDO'
                    ELSE ''
                END AS compartido,

                g.grupo     AS grp,
                g.resolucion,
                g.designacion

            FROM grupos g

            INNER JOIN docentes d 
                ON d.codigo = g.docente

            INNER JOIN materias m 
                ON  m.codigo  = g.materia
                AND m.anio    = g.anio
                AND m.periodo = g.periodo
                AND m.plan    = g.plan

            LEFT JOIN planes p
                ON  p.codigo = g.plan
                AND p.anio   = g.anio

            WHERE d.codigo = :docente
              AND g.primario = 'Y'
              AND g.tipo = 'N'
              {$filtroAnio}

            ORDER BY
                g.anio,
                CASE 
                    WHEN g.periodo = '1' THEN 1
                    WHEN g.periodo = '4' THEN 2
                    WHEN g.periodo = '2' THEN 3
                    WHEN g.periodo = '3' THEN 4
                    ELSE 5
                END ASC,
                g.grupo ASC,
                g.materia,
                g.plan
        ", array_filter([
                'docente' => $docente,
                'anio' => $anio,
            ]));

        return response()->json([
            'success' => true,
            'docente' => [
                'codigo' => $docenteInfo->codigo,
                'nombre' => trim($docenteInfo->apellidos . ' ' . $docenteInfo->nombres),
            ],
            'anio_filtro' => $anio ?? 'todos',
            'total' => count($materias),
            'materias' => $materias,
        ]);
    }
}