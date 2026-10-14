<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoriaClasificacionController extends Controller
{
    // Tablas/columnas donde vive el "uso real" de cada tipo (DOCUMENTO y
    // TITULO tienen una sola fuente; KARDEX tiene dos, ver abajo).
    private const TABLAS_USO = [
        'DOCUMENTO' => ['tabla' => 'CLASIFICACION_DOCUMENTO', 'columna' => 'CATEGORIA'],
        'TITULO' => ['tabla' => 'CLASIFICACION_TITULO', 'columna' => 'TIPO_TITULO'],
    ];

    // KARDEX: el tipo_ingreso se guarda tanto en GRUPO como en
    // DETALLE_RESOLUCION (ajustar tabla/columna si en tu esquema son otros).
    private const TABLAS_USO_KARDEX = [
        ['tabla' => 'GRUPOS', 'columna' => 'TIPO_INGRESO'],
        ['tabla' => 'RESOLUCION_DETALLE', 'columna' => 'TIPO_INGRESO'],
    ];

    // Nombre legible de cada tabla, para mostrarlo en el front cuando una
    // categoría está en uso ("Grupos (materias): 12").
    private const ETIQUETAS_TABLAS = [
        'CLASIFICACION_DOCUMENTO' => 'Documentos de clasificación',
        'CLASIFICACION_TITULO' => 'Títulos académicos registrados',
        'GRUPOS' => 'Grupos (materias)',
        'RESOLUCION_DETALLE' => 'Detalles de resolución',
    ];

    private function normalizarTipoRuta(string $tipo): string
    {
        // La ruta usa minúscula ('documento' / 'titulo' / 'kardex'); internamente
        // se guarda en mayúscula.
        $tipo = strtoupper($tipo);
        abort_unless(in_array($tipo, ['DOCUMENTO', 'TITULO', 'KARDEX']), 422, 'Tipo inválido');
        return $tipo;
    }

    /**
     * Fuentes (tabla + columna) donde puede estar usada una categoría del tipo dado.
     */
    private function fuentesUso(string $tipo): array
    {
        if ($tipo === 'KARDEX') {
            return self::TABLAS_USO_KARDEX;
        }

        return isset(self::TABLAS_USO[$tipo]) ? [self::TABLAS_USO[$tipo]] : [];
    }

    /**
     * Cuenta en qué tablas está usada una categoría.
     * Devuelve: ['en_uso' => bool, 'total' => int, 'detalle' => [['tabla','etiqueta','cantidad'], ...]]
     * Solo incluye en 'detalle' las tablas donde realmente hay registros.
     */
    private function calcularUso(string $tipo, string $nombre): array
    {
        $nombreUpper = mb_strtoupper(trim($nombre));
        $detalle = [];

        foreach ($this->fuentesUso($tipo) as $fuente) {
            $cantidad = DB::table($fuente['tabla'])
                ->whereRaw('UPPER(' . $fuente['columna'] . ') = ?', [$nombreUpper])
                ->count();

            if ($cantidad > 0) {
                $detalle[] = [
                    'tabla' => $fuente['tabla'],
                    'etiqueta' => self::ETIQUETAS_TABLAS[$fuente['tabla']] ?? $fuente['tabla'],
                    'cantidad' => $cantidad,
                ];
            }
        }

        return [
            'en_uso' => count($detalle) > 0,
            'total' => (int) collect($detalle)->sum('cantidad'),
            'detalle' => $detalle,
        ];
    }

    public function index(string $tipo)
    {
        $tipo = $this->normalizarTipoRuta($tipo);

        $nombresDeUso = collect();

        if ($tipo === 'KARDEX') {
            foreach (self::TABLAS_USO_KARDEX as $fuente) {
                $nombresDeUso = $nombresDeUso->merge(
                    DB::table($fuente['tabla'])
                        ->select($fuente['columna'] . ' as NOMBRE')
                        ->whereNotNull($fuente['columna'])
                        ->distinct()
                        ->pluck('NOMBRE')
                );
            }
        } else {
            $fuente = self::TABLAS_USO[$tipo];
            $nombresDeUso = $nombresDeUso->merge(
                DB::table($fuente['tabla'])
                    ->select($fuente['columna'] . ' as NOMBRE')
                    ->whereNotNull($fuente['columna'])
                    ->distinct()
                    ->pluck('NOMBRE')
            );
        }

        $delCatalogo = DB::table('CATEGORIAS_CLASIFICACION')
            ->where('TIPO', $tipo)
            ->pluck('NOMBRE');

        // Unión de "lo que ya está en uso" + "lo que está en el catálogo".
        // Así, aunque el catálogo esté vacío la primera vez (antes de correr
        // la migración con los seeds), si ya hay datos usados en GRUPO /
        // DETALLE_RESOLUCION igual aparecen. Y viceversa: si el catálogo
        // tiene una categoría que todavía nadie usó, también aparece.
        $nombres = $nombresDeUso
            ->merge($delCatalogo)
            ->map(fn($n) => trim($n))
            ->filter()
            ->unique(fn($n) => mb_strtoupper($n))
            ->sort()
            ->values();

        return response()->json($nombres);
    }

    /**
     * GET /api/categorias-clasificacion/{tipo}/uso?nombre=X
     *
     * Informa si la categoría está en uso y en qué tablas, para que el front
     * decida si muestra el botón "Eliminar" o la lista de dependencias.
     */
    public function uso(Request $request, string $tipo)
    {
        $tipo = $this->normalizarTipoRuta($tipo);

        $request->validate(['nombre' => 'required|string']);

        return response()->json($this->calcularUso($tipo, (string) $request->query('nombre')));
    }

    public function store(Request $request, string $tipo)
    {
        $tipo = $this->normalizarTipoRuta($tipo);

        $request->validate(['nombre' => 'required|string|max:60']);

        $nombre = in_array($tipo, ['TITULO', 'KARDEX'])
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
        $nuevo = in_array($tipo, ['TITULO', 'KARDEX'])
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
            } elseif ($tipo === 'TITULO') {
                DB::table('CLASIFICACION_TITULO')
                    ->where('TIPO_TITULO', $anterior)
                    ->update(['TIPO_TITULO' => $nuevo]);
            } elseif ($tipo === 'KARDEX') {
                foreach (self::TABLAS_USO_KARDEX as $fuente) {
                    DB::table($fuente['tabla'])
                        ->where($fuente['columna'], $anterior)
                        ->update([$fuente['columna'] => $nuevo]);
                }
            }
        });

        return response()->json(['nombre' => $nuevo]);
    }

    public function destroy(Request $request, string $tipo)
    {
        $tipo = $this->normalizarTipoRuta($tipo);

        $request->validate([
            'nombre' => 'required|string',
        ]);

        $nombre = trim($request->nombre);
        $nombreUpper = mb_strtoupper($nombre);

        // ── Verificar si está en uso real ────────────────────────────────
        // Aunque el front ya consulta /uso antes de mostrar el botón, acá se
        // vuelve a validar: nadie debe poder borrar una categoría en uso
        // llamando directo a la API. Se responde 409 (conflicto) con el mismo
        // detalle que devuelve /uso para que el front pueda mostrarlo.
        $uso = $this->calcularUso($tipo, $nombre);

        if ($uso['en_uso']) {
            return response()->json([
                'error' => 'No se puede eliminar: la categoría está en uso. Cambiala o quitala de esos registros primero.',
                'en_uso' => true,
                'total' => $uso['total'],
                'detalle' => $uso['detalle'],
            ], 409);
        }

        // ── Ya no está en uso en ningún lado → segura para borrar del catálogo ──
        $eliminado = DB::table('CATEGORIAS_CLASIFICACION')
            ->where('TIPO', $tipo)
            ->whereRaw('UPPER(NOMBRE) = ?', [$nombreUpper])
            ->delete();

        if (!$eliminado) {
            return response()->json(['error' => 'No se encontró esa categoría en el catálogo'], 404);
        }

        return response()->json(['ok' => true, 'nombre' => $nombre]);
    }
}