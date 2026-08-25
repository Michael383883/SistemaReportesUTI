<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoriaClasificacionController extends Controller
{
    private function normalizarTipoRuta(string $tipo): string
    {
        // La ruta usa 'documento' / 'titulo' en minúscula; internamente
        // se guarda en mayúscula ('DOCUMENTO' / 'TITULO').
        $tipo = strtoupper($tipo);
        abort_unless(in_array($tipo, ['DOCUMENTO', 'TITULO']), 422, 'Tipo inválido');
        return $tipo;
    }

    public function index(string $tipo)
    {
        $tipo = $this->normalizarTipoRuta($tipo);

        if ($tipo === 'DOCUMENTO') {
            $deUso = DB::table('CLASIFICACION_DOCUMENTO')
                ->select('CATEGORIA as NOMBRE')
                ->whereNotNull('CATEGORIA')
                ->distinct();
        } else {
            $deUso = DB::table('CLASIFICACION_TITULO')
                ->select('TIPO_TITULO as NOMBRE')
                ->whereNotNull('TIPO_TITULO')
                ->distinct();
        }

        $delCatalogo = DB::table('CATEGORIAS_CLASIFICACION')
            ->select('NOMBRE')
            ->where('TIPO', $tipo);

        $nombres = $deUso
            ->unionAll($delCatalogo)
            ->get()
            ->pluck('NOMBRE')
            ->map(fn($n) => trim($n))
            ->filter()
            ->unique(fn($n) => mb_strtoupper($n))
            ->sort()
            ->values();

        return response()->json($nombres);
    }

    public function store(Request $request, string $tipo)
    {
        $tipo = $this->normalizarTipoRuta($tipo);

        $request->validate(['nombre' => 'required|string|max:60']);

        $nombre = $tipo === 'TITULO'
            ? mb_strtoupper(trim($request->nombre))
            : trim($request->nombre);

        $existe = DB::table('CATEGORIAS_CLASIFICACION')
            ->where('TIPO', $tipo)
            ->whereRaw('UPPER(NOMBRE) = ?', [mb_strtoupper($nombre)])
            ->exists();

        if ($existe) {
            return response()->json(['error' => 'Ya existe con ese nombre'], 422);
        }

        DB::table('CATEGORIAS_CLASIFICACION')->insert([
            'NOMBRE' => $nombre,
            'TIPO' => $tipo,
        ]);

        return response()->json(['nombre' => $nombre], 201);
    }

    public function update(Request $request, string $tipo)
    {
        $tipo = $this->normalizarTipoRuta($tipo);

        $request->validate([
            'anterior' => 'required|string',
            'nuevo' => 'required|string|max:60',
        ]);

        $anterior = $request->anterior;
        $nuevo = $tipo === 'TITULO'
            ? mb_strtoupper(trim($request->nuevo))
            : trim($request->nuevo);

        $existeOtro = DB::table('CATEGORIAS_CLASIFICACION')
            ->where('TIPO', $tipo)
            ->whereRaw('UPPER(NOMBRE) = ?', [mb_strtoupper($nuevo)])
            ->whereRaw('UPPER(NOMBRE) <> ?', [mb_strtoupper($anterior)])
            ->exists();

        if ($existeOtro) {
            return response()->json(['error' => 'Ya existe otra categoría con ese nombre'], 422);
        }

        DB::transaction(function () use ($tipo, $anterior, $nuevo) {
            DB::table('CATEGORIAS_CLASIFICACION')
                ->where('TIPO', $tipo)
                ->whereRaw('UPPER(NOMBRE) = ?', [mb_strtoupper($anterior)])
                ->update(['NOMBRE' => $nuevo]);

            if ($tipo === 'DOCUMENTO') {
                DB::table('CLASIFICACION_DOCUMENTO')
                    ->where('CATEGORIA', $anterior)
                    ->update(['CATEGORIA' => $nuevo]);
            } else {
                DB::table('CLASIFICACION_TITULO')
                    ->where('TIPO_TITULO', $anterior)
                    ->update(['TIPO_TITULO' => $nuevo]);
            }
        });

        return response()->json(['nombre' => $nuevo]);
    }
}