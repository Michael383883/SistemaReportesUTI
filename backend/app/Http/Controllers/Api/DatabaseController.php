<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MigracionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DatabaseController extends Controller
{
    public function status(): JsonResponse
    {
        return response()->json([
            'sqlserver_2022' => $this->checkConnection('sqlsrv'),
            'sqlserver_2008' => $this->checkConnection('sqlsrv2'),
        ]);
    }

    /** POST /api/database/migrar-catalogos → todos los catálogos */
    public function migrarCatalogos(): JsonResponse
    {
        set_time_limit(300);

        $tablas = [
            'BIOGRAFICOS',
            'BIOGRAFICOS_EXT',
            'DOCENTES',
            'DOCENTES_2',
            'DOCENTES_TELEFONO',
            'MATERIAS',
            'PLANES',
            'GRUPOS_COMPARTIDOS',
            'NROINSMATGRPNE',
        ];

        return $this->respuesta(MigracionService::sincronizarCatalogos($tablas));
    }

    /** POST /api/database/migrar-catalogo/{tabla} → un solo catálogo */
    public function migrarCatalogo(string $tabla): JsonResponse
    {
        set_time_limit(120);

        return $this->respuesta([MigracionService::sincronizarCatalogo($tabla)]);
    }

    /** POST /api/database/migrar-grupos  { "anio": "2024", "periodo": "1" } */
    public function migrarGrupos(Request $request): JsonResponse
    {
        set_time_limit(300);

        try {
            $validated = $request->validate([
                'anio' => 'required|string',
                'periodo' => 'required|string',
            ]);

            $resultado = MigracionService::sincronizarGrupos($validated['anio'], $validated['periodo']);

            return $this->respuesta([$resultado]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /** POST /api/database/migrar-semestre  { "anio": "2024", "periodo": "1", "tablas": ["HORARIOS2","KARDEX_EXT"] } */
    public function migrarSemestre(Request $request): JsonResponse
    {
        set_time_limit(300);

        try {
            $validated = $request->validate([
                'anio' => 'required|string',
                'periodo' => 'required|string',
                'tablas' => 'array',
            ]);

            $tablasDefault = ['HORARIOS2', 'KARDEX_EXT'];

            $resultados = MigracionService::sincronizarPorSemestre(
                $request->input('tablas', $tablasDefault),
                $validated['anio'],
                $validated['periodo']
            );

            return $this->respuesta($resultados);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function respuesta(array $resultados): JsonResponse
    {
        $exitosas = array_filter($resultados, fn($r) => $r['success']);
        $fallidas = array_filter($resultados, fn($r) => !$r['success']);

        return response()->json([
            'success' => count($fallidas) === 0,
            'resumen' => [
                'total' => count($resultados),
                'exitosas' => count($exitosas),
                'fallidas' => count($fallidas),
            ],
            'detalle' => $resultados,
        ], count($fallidas) === 0 ? 200 : 207);
    }

    private function checkConnection(string $connection): array
    {
        try {
            DB::connection($connection)->getPdo();
            $connected = true;
        } catch (\Throwable $e) {
            $connected = false;
        }

        return [
            'connected' => $connected,
            'host' => config("database.connections.$connection.host"),
            'database' => config("database.connections.$connection.database"),
        ];
    }
    /** POST /api/database/carga-inicial  { "tablas": ["GRUPOS","HORARIOS2","KARDEX_EXT"] } (opcional) */
    public function cargaInicial(Request $request): JsonResponse
    {
        set_time_limit(0); // histórico completo puede tardar más de 5 min, sin límite
        ini_set('memory_limit', '256M');

        try {
            $tablasDefault = ['GRUPOS', 'HORARIOS2', 'KARDEX_EXT'];
            $tablas = $request->input('tablas', $tablasDefault);

            $resultados = MigracionService::cargaInicial($tablas);

            return $this->respuesta($resultados);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}