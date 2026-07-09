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
        $query = DB::table('CLASIFICACION_DOCENTE as ccd')
            ->join('CLASIFICACION_DOCUMENTO as cdoc', 'cdoc.ID_DOCUMENTO', '=', 'ccd.ID_DOCUMENTO')
            ->join('DOCENTES as d', 'd.CODIGO', '=', 'ccd.COD_DOCENTE')
            ->select(
                'ccd.ID_CLASIFICACION_DOCENTE',
                'ccd.ID_DOCUMENTO',
                'ccd.COD_DOCENTE',
                DB::raw("LTRIM(RTRIM(d.APELLIDOS + ' ' + d.NOMBRES)) AS NOMBRE_DOCENTE"),
                'cdoc.CATEGORIA',
                'cdoc.NIVEL',
                'cdoc.GESTION',
                'cdoc.PERIODO',
                'cdoc.TIPO_DOCUMENTO',
                'cdoc.DETALLE_GENERAL',
                'cdoc.FOTOCOPIA_TITULAR',
                'cdoc.OBSERVACION',
                'cdoc.OBSERVACION2',
                'cdoc.NOMBRE_ARCHIVO'
            );

        if ($request->filled('gestion')) {
            $query->where('cdoc.GESTION', $request->query('gestion'));
        }
        if ($request->filled('categoria')) {
            $query->where('cdoc.CATEGORIA', $request->query('categoria'));
        }
        if ($request->filled('nivel')) {
            $query->where('cdoc.NIVEL', $request->query('nivel'));
        }
        if ($request->filled('tipo_documento')) {
            $query->where('cdoc.TIPO_DOCUMENTO', $request->query('tipo_documento'));
        }

        $cabeceras = $query->orderBy('NOMBRE_DOCENTE')->get();
        $idsClasifDocente = $cabeceras->pluck('ID_CLASIFICACION_DOCENTE');
        $idsDocumento = $cabeceras->pluck('ID_DOCUMENTO')->unique();

        // Materias: agrupadas por docente (ID_CLASIFICACION_DOCENTE)
        $materias = DB::table('CLASIFICACION_MATERIA')
            ->whereIn('ID_CLASIFICACION_DOCENTE', $idsClasifDocente)
            ->orderBy('ORDEN')
            ->get()
            ->groupBy('ID_CLASIFICACION_DOCENTE');

        // Referencias: son del documento completo, se comparten entre los docentes de ese documento
        $referencias = DB::table('CLASIFICACION_REFERENCIA')
            ->whereIn('ID_DOCUMENTO', $idsDocumento)
            ->get()
            ->groupBy('ID_DOCUMENTO');

        $conDetalle = $cabeceras->map(function ($c) use ($materias, $referencias) {
            $c->materias = $materias->get($c->ID_CLASIFICACION_DOCENTE, collect())->values();
            $c->referencias = $referencias->get($c->ID_DOCUMENTO, collect())->values();
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

        $clasificaciones = DB::table('CLASIFICACION_DOCENTE as ccd')
            ->join('CLASIFICACION_DOCUMENTO as cdoc', 'cdoc.ID_DOCUMENTO', '=', 'ccd.ID_DOCUMENTO')
            ->where('ccd.COD_DOCENTE', $cod_docente)
            ->select('ccd.ID_CLASIFICACION_DOCENTE', 'ccd.ID_DOCUMENTO', 'cdoc.*')
            ->orderBy('cdoc.GESTION')
            ->get();

        $idsClasifDocente = $clasificaciones->pluck('ID_CLASIFICACION_DOCENTE');
        $idsDocumento = $clasificaciones->pluck('ID_DOCUMENTO')->unique();

        $materias = DB::table('CLASIFICACION_MATERIA')
            ->whereIn('ID_CLASIFICACION_DOCENTE', $idsClasifDocente)
            ->orderBy('ORDEN')
            ->get()
            ->groupBy('ID_CLASIFICACION_DOCENTE');

        $referencias = DB::table('CLASIFICACION_REFERENCIA')
            ->whereIn('ID_DOCUMENTO', $idsDocumento)
            ->get()
            ->groupBy('ID_DOCUMENTO');

        $timeline = $clasificaciones->map(function ($c) use ($materias, $referencias) {
            $c->materias = $materias->get($c->ID_CLASIFICACION_DOCENTE, collect())->values();
            $c->referencias = $referencias->get($c->ID_DOCUMENTO, collect())->values();
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
            ->join('CLASIFICACION_DOCUMENTO as cdoc', 'cdoc.ID_DOCUMENTO', '=', 'cr.ID_DOCUMENTO')
            ->join('CLASIFICACION_DOCENTE as ccd', 'ccd.ID_DOCUMENTO', '=', 'cdoc.ID_DOCUMENTO')
            ->join('DOCENTES as d', 'd.CODIGO', '=', 'ccd.COD_DOCENTE')
            ->where('cr.NRO_REFERENCIA', $nro)
            ->select(
                'ccd.ID_CLASIFICACION_DOCENTE',
                'ccd.COD_DOCENTE',
                DB::raw("LTRIM(RTRIM(d.APELLIDOS + ' ' + d.NOMBRES)) AS NOMBRE_DOCENTE"),
                'cdoc.CATEGORIA',
                'cdoc.NIVEL',
                'cdoc.TIPO_DOCUMENTO',
                'cdoc.GESTION'
            )
            ->orderBy('NOMBRE_DOCENTE')
            ->get();

        return response()->json($docentes);
    }
}