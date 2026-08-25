<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class DatabaseController extends Controller
{
    private const CONEXION = 'sqlsrv';

    /**
     * Whitelist explícita: SOLO tablas que creamos nosotros vía migración.
     * A propósito no se lee de sys.tables/INFORMATION_SCHEMA para no listar
     * ni de casualidad las tablas legacy del 2008 (DOCENTES, GRUPOS, etc.)
     * que conviven en la misma base.
     */
    private const NUESTRAS_TABLAS = [
        'CLASIFICACION_DOCUMENTO',
        'CLASIFICACION_DOCENTE',
        'CLASIFICACION_MATERIA',
        'CLASIFICACION_REFERENCIA',
        'CLASIFICACION_TITULO',
        'CATEGORIAS_CLASIFICACION',
        'RESOLUCIONES_PDF',
        'RESOLUCION_DETALLE',
        // 'periodos_academicos' vive en la conexión por defecto, no en sqlsrv
    ];

    /** GET /api/database/status */
    /** GET /api/database/status */
    public function status(): JsonResponse
    {
        try {
            DB::connection(self::CONEXION)->getPdo();
            $connected = true;
        } catch (Throwable $e) {
            $connected = false;
        }

        return response()->json([
            'connected' => $connected,
            'host' => config('database.connections.' . self::CONEXION . '.host'),
            'database' => config('database.connections.' . self::CONEXION . '.database'),
        ]);
    }
    /** GET /api/database/tables — solo nuestras tablas, solo nombre + filas */
    public function tables(): JsonResponse
    {
        try {
            $conn = DB::connection(self::CONEXION);
            $resultado = [];

            foreach (self::NUESTRAS_TABLAS as $tabla) {
                // $tabla sale siempre de la constante de arriba, nunca del
                // request, así que no hay riesgo de inyección al interpolarla.
                try {
                    $total = $conn->selectOne("SELECT COUNT(*) AS n FROM [$tabla]")->n;
                    $resultado[] = ['tabla' => $tabla, 'filas' => (int) $total];
                } catch (Throwable $e) {
                    // Si la tabla aún no existe (migración no corrida), la omitimos
                    // en vez de tirar el endpoint completo.
                    continue;
                }
            }

            return response()->json(['success' => true, 'tablas' => $resultado]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error al consultar la base de datos'], 500);
        }
    }
}