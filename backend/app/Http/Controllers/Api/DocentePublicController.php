<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DocentePublicController extends Controller
{
    /**
     * Ficha pública del docente por CI.
     * GET /api/docentes/by-ci/{ci}
     *
     * Devuelve todo DOCENTES menos FECHA_NOMBRAMIENTO.
     */
    public function showByCi($ci)
    {
        $ci = trim((string) $ci);

        if ($ci === '') {
            return response()->json([
                'success' => false,
                'message' => 'CI requerido',
            ], 422);
        }

        $filas = DB::table('DOCENTES')
            ->where('CI', $ci)
            ->select('CODIGO', 'CI', 'NOMBRES', 'APELLIDOS', 'FECHA_NAC', 'SEXO', 'TITULO')
            ->orderBy('CODIGO')
            ->get()
            ->map(fn($f) => [
                'codigo' => $f->CODIGO,
                'ci' => $f->CI,
                'nombres' => trim((string) $f->NOMBRES),
                'apellidos' => trim((string) ($f->APELLIDOS ?? '')),
                'fecha_nac' => $f->FECHA_NAC,
                'sexo' => $f->SEXO,
                'titulo' => $f->TITULO,
            ])
            ->values();

        if ($filas->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Docente no encontrado',
            ], 404);
        }

        // CI debería ser único, pero si hay duplicados se devuelve la lista
        // para que el consumidor (SCIA) desambigüe por codigo.
        if ($filas->count() === 1) {
            return response()->json([
                'success' => true,
                'data' => $filas->first(),
            ]);
        }

        return response()->json([
            'success' => true,
            'total' => $filas->count(),
            'data' => $filas,
        ]);
    }

    /**
     * Docentes asignados a una materia en un periodo/gestión/carrera.
     * GET /api/docentes/por-materia?gestion=2026&periodo=1&carrera=109401&materia=130101
     *
     * Los 4 parámetros son obligatorios. carrera = código PLAN,
     * materia = código exacto. Solo asignaciones reales
     * (GRUPOS.PRIMARIO = 'Y' AND GRUPOS.TIPO = 'N').
     */
    public function porMateria(Request $request)
    {
        $data = $request->validate([
            'gestion' => 'required|numeric',
            'periodo' => 'required|string|in:1,2,3,4',
            'carrera' => 'required|string|max:10',
            'materia' => 'required|string|max:10',
        ]);

        $filas = DB::table('GRUPOS as g')
            ->join('DOCENTES as d', 'd.CODIGO', '=', 'g.DOCENTE')
            ->join('MATERIAS as m', function ($j) {
                $j->on('m.CODIGO', '=', 'g.MATERIA')
                    ->on('m.ANIO', '=', 'g.ANIO')
                    ->on('m.PERIODO', '=', 'g.PERIODO')
                    ->on('m.PLAN', '=', 'g.PLAN');
            })
            ->leftJoin('PLANES as p', function ($j) {
                $j->on('p.CODIGO', '=', 'g.PLAN')
                    ->on('p.ANIO', '=', 'g.ANIO');
            })
            ->where('g.ANIO', (int) $data['gestion'])
            ->where('g.PERIODO', (string) $data['periodo'])
            ->where('g.PLAN', trim((string) $data['carrera']))
            ->where('g.MATERIA', trim((string) $data['materia']))
            ->where('g.PRIMARIO', 'Y')
            ->where('g.TIPO', 'N')
            ->select(
                'd.CODIGO',
                'd.CI',
                'd.NOMBRES',
                'd.APELLIDOS',
                'd.FECHA_NAC',
                'd.SEXO',
                'd.TITULO',
                'g.GRUPO',
                'g.PLAN',
                'm.NOMBRE as NOMBRE_MATERIA',
                'p.NOMBRE as NOMBRE_PLAN'
            )
            ->orderBy('d.APELLIDOS')
            ->orderBy('d.NOMBRES')
            ->orderBy('g.GRUPO')
            ->get()
            ->map(fn($f) => [
                'codigo' => $f->CODIGO,
                'ci' => $f->CI,
                'nombres' => trim((string) $f->NOMBRES),
                'apellidos' => trim((string) ($f->APELLIDOS ?? '')),
                'fecha_nac' => $f->FECHA_NAC,
                'sexo' => $f->SEXO,
                'titulo' => $f->TITULO,
                'grupo' => trim((string) ($f->GRUPO ?? '')),
                'plan' => trim((string) ($f->PLAN ?? '')),
                'nombre_plan' => trim((string) ($f->NOMBRE_PLAN ?? '')),
                'materia' => trim((string) $data['materia']),
                'nombre_materia' => trim((string) ($f->NOMBRE_MATERIA ?? '')),
                'gestion' => (int) $data['gestion'],
                'periodo' => (string) $data['periodo'],
            ])
            ->values();

        return response()->json([
            'success' => true,
            'total' => $filas->count(),
            'filtros' => [
                'gestion' => (int) $data['gestion'],
                'periodo' => (string) $data['periodo'],
                'carrera' => trim((string) $data['carrera']),
                'materia' => trim((string) $data['materia']),
            ],
            'data' => $filas,
        ]);
    }
}
