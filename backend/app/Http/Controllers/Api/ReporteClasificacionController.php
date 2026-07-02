<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReporteClasificacionController extends Controller
{
    // GET /reportes/clasificacion
    public function listado(Request $request)
    {
        $query = DB::table('CLASIFICACION_DOCENTE as cd')
            ->join('DOCENTES as d', 'd.CODIGO', '=', 'cd.COD_DOCENTE')
            ->select(
                'cd.ID_CLASIFICACION',
                'cd.COD_DOCENTE',
                DB::raw("LTRIM(RTRIM(d.APELLIDOS + ' ' + d.NOMBRES)) AS NOMBRE_DOCENTE"),
                'cd.CATEGORIA',
                'cd.NIVEL',
                'cd.GESTION',
                'cd.PERIODO',
                'cd.DETALLE_GENERAL',
                'cd.FOTOCOPIA_TITULAR',
                'cd.OBSERVACION',
                'cd.OBSERVACION2',
                'cd.NOMBRE_ARCHIVO'
            );

        if ($request->filled('gestion')) {
            $query->where('cd.GESTION', $request->query('gestion'));
        }
        if ($request->filled('categoria')) {
            $query->where('cd.CATEGORIA', $request->query('categoria'));
        }
        if ($request->filled('nivel')) {
            $query->where('cd.NIVEL', $request->query('nivel'));
        }

        $cabeceras = $query->orderBy('NOMBRE_DOCENTE')->get();
        $ids = $cabeceras->pluck('ID_CLASIFICACION');

        $materias = DB::table('CLASIFICACION_MATERIA')
            ->whereIn('ID_CLASIFICACION', $ids)
            ->orderBy('ORDEN')
            ->get()
            ->groupBy('ID_CLASIFICACION');

        $referencias = DB::table('CLASIFICACION_REFERENCIA')
            ->whereIn('ID_CLASIFICACION', $ids)
            ->get()
            ->groupBy('ID_CLASIFICACION');

        $conDetalle = $cabeceras->map(function ($c) use ($materias, $referencias) {
            $c->materias = $materias->get($c->ID_CLASIFICACION, collect())->values();
            $c->referencias = $referencias->get($c->ID_CLASIFICACION, collect())->values();
            return $c;
        });

        $agrupadoPorNivel = $conDetalle->groupBy('CATEGORIA');

        return response()->json($agrupadoPorNivel);
    }

    // GET /reportes/clasificacion/docente/{cod_docente}
    public function porDocente($cod_docente)
    {
        if (!ctype_digit((string) $cod_docente)) {
            return response()->json([
                'ok' => false,
                'error' => 'Código de docente inválido',
            ], 400);
        }

        $docente = DB::table('DOCENTES')->where('CODIGO', $cod_docente)->first();

        if (!$docente) {
            return response()->json(['ok' => false, 'error' => 'Docente no encontrado'], 404);
        }

        $clasificaciones = DB::table('CLASIFICACION_DOCENTE')
            ->where('COD_DOCENTE', $cod_docente)
            ->orderBy('GESTION')
            ->get();

        $ids = $clasificaciones->pluck('ID_CLASIFICACION');

        $materias = DB::table('CLASIFICACION_MATERIA')
            ->whereIn('ID_CLASIFICACION', $ids)
            ->orderBy('ORDEN')
            ->get()
            ->groupBy('ID_CLASIFICACION');

        $referencias = DB::table('CLASIFICACION_REFERENCIA')
            ->whereIn('ID_CLASIFICACION', $ids)
            ->get()
            ->groupBy('ID_CLASIFICACION');

        $timeline = $clasificaciones->map(function ($c) use ($materias, $referencias) {
            $c->materias = $materias->get($c->ID_CLASIFICACION, collect())->values();
            $c->referencias = $referencias->get($c->ID_CLASIFICACION, collect())->values();
            return $c;
        });

        return response()->json([
            'docente' => $docente,
            'clasificaciones' => $timeline,
        ]);
    }

    // GET /reportes/clasificacion/por-referencia
    public function porReferencia(Request $request)
    {
        $nro = $request->query('nro');

        if (!$nro) {
            return response()->json(['ok' => false, 'error' => 'Falta parámetro nro'], 400);
        }

        $docentes = DB::table('CLASIFICACION_REFERENCIA as cr')
            ->join('CLASIFICACION_DOCENTE as cd', 'cd.ID_CLASIFICACION', '=', 'cr.ID_CLASIFICACION')
            ->join('DOCENTES as d', 'd.CODIGO', '=', 'cd.COD_DOCENTE')
            ->where('cr.NRO_REFERENCIA', $nro)
            ->select(
                'cd.ID_CLASIFICACION',
                'cd.COD_DOCENTE',
                DB::raw("LTRIM(RTRIM(d.APELLIDOS + ' ' + d.NOMBRES)) AS NOMBRE_DOCENTE"),
                'cd.CATEGORIA',
                'cd.NIVEL',
                'cd.GESTION'
            )
            ->orderBy('NOMBRE_DOCENTE')
            ->get();

        return response()->json($docentes);
    }
}