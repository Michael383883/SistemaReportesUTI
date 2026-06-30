<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Services\MigracionService;
use Illuminate\Http\Request;
class DatabaseController extends Controller
{
    /**
     * GET /api/database/status
     */
    public function status(): JsonResponse
    {
        return response()->json([
            'sqlserver_2022' => $this->checkConnection('sqlsrv'),
            'sqlserver_2008' => $this->checkConnection('sqlsrv2'),
        ]);
    }
    /**
     * POST /api/database/migrate-all
     */
    public function migrateAll(): JsonResponse
    {
        // Tiempo máximo de ejecución: 5 minutos (300 segundos)
        set_time_limit(300);

        // Límite de memoria: 100 MB
        ini_set('memory_limit', '100M');

        $tables = [
            'BIOGRAFICOS',
            'DOCENTES',
            'DOCENTES_2',
            'DOCENTES_TELEFONO',
            'GRUPOS',
            'GRUPOS_COMPARTIDOS',
            'HORARIOS2',
            'KARDEX_EXT',
            'MATERIAS',
            'NROINSMATGRPNE',
            'PLANES',
            'BIOGRAFICOS_EXT',
        ];

        $resultados = MigracionService::copiarTablas($tables);

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

}