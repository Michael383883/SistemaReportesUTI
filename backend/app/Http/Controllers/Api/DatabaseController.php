<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

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
    public function migrate(): JsonResponse
    {
        // Verificar conexión SQL Server
        $sqlStatus = $this->checkConnection('sqlsrv2');

        if (!$sqlStatus['connected']) {
            return response()->json([
                'success' => false,
                'message' => 'SQL Server no tiene conexión activa.'
            ], 422);
        }

        try {
            // 🔁 EJEMPLO REAL DE MIGRACIÓN
            $rows = DB::connection('sqlsrv2')->table('tu_tabla')->get();

            DB::connection('pgsql')->table('tu_tabla')->insert(
                $rows->map(fn($item) => (array) $item)->toArray()
            );

            return response()->json([
                'success' => true,
                'message' => 'Migración completada exitosamente.'
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error durante la migración: ' . $e->getMessage()
            ], 500);
        }
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