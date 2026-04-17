<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
class DatabaseController extends Controller
{
    /**
     * GET /api/database/status
     */
    public function status(): JsonResponse
    {
        return response()->json([
            'postgres' => $this->checkConnection('pgsql'),
            'sqlserver' => $this->checkConnection('sqlsrv2'),
        ]);
    }

    /**
     * POST /api/database/migrate
     */
    public function migrate(Request $request): JsonResponse
    {
        $migraciones = $request->input('migraciones');

        if (!$migraciones || !is_array($migraciones)) {
            return response()->json([
                'success' => false,
                'message' => 'Debes enviar un arreglo de migraciones'
            ], 400);
        }

        $resultados = [];

        foreach ($migraciones as $migracion) {

            $origen = $migracion['origen'] ?? null;
            $destino = $migracion['destino'] ?? null;

            if (!$origen || !$destino) {
                $resultados[] = [
                    'tabla' => $origen,
                    'status' => 'error',
                    'mensaje' => 'Faltan datos (origen/destino)'
                ];
                continue;
            }

            try {
                $rows = DB::connection('sqlsrv2')
                    ->table($origen)
                    ->get();

                foreach ($rows as $row) {
                    DB::connection('pgsql')
                        ->table($destino)
                        ->updateOrInsert(
                            ['codigo' => $row->CODIGO ?? null], // clave base
                            [
                                'ci' => $row->CI ?? null,
                                'nombres' => $row->NOMBRES ?? null,
                                'apellidos' => $row->APELLIDOS ?? null,
                                'fecha_nac' => $row->FECHA_NAC ?? null,
                                'sexo' => $row->SEXO ?? null,
                                'titulo' => $row->TITULO ?? null,
                                'fecha_nombramiento' => $row->FECHA_NOMBRAMIENTO ?? null,
                            ]
                        );
                }

                $resultados[] = [
                    'tabla' => $origen,
                    'status' => 'ok',
                    'filas' => count($rows)
                ];

            } catch (\Throwable $e) {
                $resultados[] = [
                    'tabla' => $origen,
                    'status' => 'error',
                    'mensaje' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'success' => true,
            'resultados' => $resultados
        ]);
    }
    // ─── Helper ─────────────────────────────────────────────

    private function checkConnection(string $connection): array
    {
        try {
            DB::connection($connection)->getPdo();
            $connected = true;
        } catch (\Throwable) {
            $connected = false;
        }

        return [
            'connected' => $connected,
            'host' => config("database.connections.$connection.host"),
            'database' => config("database.connections.$connection.database"),
        ];
    }
}