<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DocenteTitulosController extends Controller
{
    /**
     * Prefijos de materias que cuentan para considerar a un docente "activo".
     * Si cambian en el futuro, se modifican solo aquí.
     */
    private const PREFIJOS_MATERIAS = ['1301', '1302', '1304'];

    /**
     * Los códigos de docente <= a este valor se ignoran (códigos especiales).
     */
    private const DOCENTE_MINIMO = 100;

    /**
     * Códigos de docentes activos en un año/periodo.
     * Mismo criterio que la consulta de verificación:
     *  - GRUPOS + MATERIAS + DOCENTES
     *  - PRIMARIO = 'Y'
     *  - DOCENTE > 100
     *  - MATERIA que empiece con 1301, 1302 o 1304
     *
     * Reutilizable desde otros controladores (por ejemplo, el Excel).
     */
    public static function codigosDocentesActivos(int $anio, string $periodo): array
    {
        return DB::table('GRUPOS as g')
            ->join('MATERIAS as m', function ($j) {
                $j->on('m.CODIGO', '=', 'g.MATERIA')
                    ->on('m.ANIO', '=', 'g.ANIO')
                    ->on('m.PERIODO', '=', 'g.PERIODO')
                    ->on('m.PLAN', '=', 'g.PLAN');
            })
            ->join('DOCENTES as d', 'd.CODIGO', '=', 'g.DOCENTE')
            ->where('g.ANIO', $anio)
            ->where('g.PERIODO', $periodo)
            ->where('g.PRIMARIO', 'Y')
            ->where('g.DOCENTE', '>', self::DOCENTE_MINIMO)
            ->where(function ($q) {
                foreach (self::PREFIJOS_MATERIAS as $prefijo) {
                    $q->orWhere('g.MATERIA', 'like', $prefijo . '%');
                }
            })
            ->distinct()
            ->pluck('g.DOCENTE')
            ->map(fn($c) => (string) $c)
            ->unique()
            ->values()
            ->all();
    }

    // GET /api/docentes/{codigo}/titulos
    public function titulos($codigo)
    {
        $docente = DB::table('DOCENTES')
            ->where('CODIGO', $codigo)
            ->select('CODIGO', 'APELLIDOS', 'NOMBRES', 'CI', 'FECHA_NAC', 'SEXO', 'TITULO', 'FECHA_NOMBRAMIENTO')
            ->first();

        if (!$docente) {
            return response()->json(['ok' => false, 'message' => 'Docente no encontrado'], 404);
        }

        $filas = DB::table('CLASIFICACION_DOCENTE as ccd')
            ->join('CLASIFICACION_TITULO as t', 't.ID_CLASIFICACION_DOCENTE', '=', 'ccd.ID_CLASIFICACION_DOCENTE')
            ->join('CLASIFICACION_DOCUMENTO as cdoc', 'cdoc.ID_DOCUMENTO', '=', 't.ID_DOCUMENTO')
            ->where('ccd.COD_DOCENTE', $codigo)
            ->select(
                't.ID_DOCUMENTO',
                't.ID_CLASIFICACION_DOCENTE',
                't.TIPO_TITULO',
                't.NOMBRE_TITULO',
                't.UNIVERSIDAD',
                't.PAIS',
                't.FECHA_TITULO',
                't.NUMERO',
                'cdoc.CATEGORIA',
                'cdoc.TIPO_DOCUMENTO',
                'cdoc.NOMBRE_ARCHIVO',
                'cdoc.RUTA_ARCHIVO'
            )
            ->orderBy('t.FECHA_TITULO')
            ->get();

        $titulos = $filas->map(function ($f) use ($docente) {
            $documentos = [];
            if (!empty($f->RUTA_ARCHIVO)) {
                $documentos[] = [
                    'id' => $f->ID_DOCUMENTO,
                    'nombre' => $f->TIPO_DOCUMENTO ?: $f->CATEGORIA,
                    'nombre_archivo' => $f->NOMBRE_ARCHIVO,
                ];
            }

            return [
                'id' => $f->ID_DOCUMENTO . '-' . $f->ID_CLASIFICACION_DOCENTE,
                'tipo_titulo' => $f->TIPO_TITULO,
                'titulo' => $docente->TITULO ?? '',
                'nombre_titulo' => $f->NOMBRE_TITULO,
                'universidad' => $f->UNIVERSIDAD,
                'pais' => $f->PAIS,
                'fecha_titulo' => $f->FECHA_TITULO,
                'numero' => $f->NUMERO,
                'documentos' => $documentos,
            ];
        })->values();

        return response()->json([
            'docente' => [
                'codigo' => $docente->CODIGO,
                'apellidos' => trim($docente->APELLIDOS),
                'nombres' => trim($docente->NOMBRES),
                'ci' => $docente->CI ?? '',
                'fecha_nac' => $docente->FECHA_NAC ?? '',
                'sexo' => $docente->SEXO ?? '',
                'fecha_nombramiento' => $docente->FECHA_NOMBRAMIENTO ?? '',
            ],
            'titulos' => $titulos,
        ]);
    }

    // GET /api/docentes/periodo-titulos?anio=2026&periodo=2&tipo_titulo=
    // Docentes activos en ese periodo (según GRUPOS/MATERIAS) + sus títulos registrados.
    public function porPeriodo(Request $request)
    {
        $request->validate([
            'anio' => 'required|numeric',
            'periodo' => 'required|string|max:10',
            'tipo_titulo' => 'nullable|string|max:60',
        ]);

        $anio = (int) $request->query('anio');
        $periodo = (string) $request->query('periodo');
        $tipo = trim((string) $request->query('tipo_titulo', ''));

        // 1) Docentes activos en el periodo (criterio unificado)
        $codigos = self::codigosDocentesActivos($anio, $periodo);

        $docentes = [];
        $titulosPorDocente = [];

        // SQL Server admite ~2100 parámetros: se consulta por bloques
        foreach (array_chunk($codigos, 1000) as $bloque) {
            $ds = DB::table('DOCENTES')
                ->whereIn('CODIGO', $bloque)
                ->select('CODIGO', 'APELLIDOS', 'NOMBRES', 'CI', 'FECHA_NAC', 'SEXO', 'TITULO', 'FECHA_NOMBRAMIENTO')
                ->get();
            foreach ($ds as $d) {
                $docentes[] = $d;
            }

            // 2) Sus títulos registrados
            $filas = DB::table('CLASIFICACION_DOCENTE as ccd')
                ->join('CLASIFICACION_TITULO as t', 't.ID_CLASIFICACION_DOCENTE', '=', 'ccd.ID_CLASIFICACION_DOCENTE')
                ->join('CLASIFICACION_DOCUMENTO as cdoc', 'cdoc.ID_DOCUMENTO', '=', 't.ID_DOCUMENTO')
                ->whereIn('ccd.COD_DOCENTE', $bloque)
                ->select(
                    'ccd.COD_DOCENTE',
                    't.ID_DOCUMENTO',
                    't.TIPO_TITULO',
                    't.NOMBRE_TITULO',
                    't.UNIVERSIDAD',
                    't.PAIS',
                    't.FECHA_TITULO',
                    't.NUMERO',
                    'cdoc.NOMBRE_ARCHIVO',
                    'cdoc.RUTA_ARCHIVO'
                )
                ->orderBy('t.FECHA_TITULO')
                ->get();

            foreach ($filas as $f) {
                $titulosPorDocente[(string) $f->COD_DOCENTE][] = [
                    'id_documento' => $f->ID_DOCUMENTO,
                    'tipo_titulo' => $f->TIPO_TITULO,
                    'nombre_titulo' => $f->NOMBRE_TITULO,
                    'universidad' => $f->UNIVERSIDAD,
                    'pais' => $f->PAIS,
                    'fecha_titulo' => $f->FECHA_TITULO,
                    'numero' => $f->NUMERO,
                    'tiene_archivo' => !empty($f->RUTA_ARCHIVO),
                    'nombre_archivo' => $f->NOMBRE_ARCHIVO,
                ];
            }
        }

        // 3) Armar filas (un docente = una fila)
        $lista = [];
        foreach ($docentes as $d) {
            $tits = $titulosPorDocente[(string) $d->CODIGO] ?? [];

            if ($tipo !== '') {
                $tits = array_values(array_filter(
                    $tits,
                    fn($t) => mb_strtoupper(trim((string) $t['tipo_titulo'])) === mb_strtoupper($tipo)
                ));
                if (!$tits) {
                    continue; // con filtro de tipo, solo docentes que lo tengan
                }
            }

            $lista[] = [
                'CODIGO' => $d->CODIGO,
                'APELLIDOS' => trim((string) $d->APELLIDOS),
                'NOMBRES' => trim((string) $d->NOMBRES),
                'CI' => $d->CI ?? '',
                'FECHA_NAC' => $d->FECHA_NAC ?? '',
                'SEXO' => $d->SEXO ?? '',
                'TITULO' => trim((string) ($d->TITULO ?? '')),
                'FECHA_NOMBRAMIENTO' => $d->FECHA_NOMBRAMIENTO ?? '',
                'titulos' => $tits,
            ];
        }

        $lista = collect($lista)
            ->sortBy(fn($r) => mb_strtoupper($r['APELLIDOS'] . ' ' . $r['NOMBRES']))
            ->values();

        return response()->json([
            'total' => $lista->count(),
            'docentes' => $lista,
        ]);
    }
}