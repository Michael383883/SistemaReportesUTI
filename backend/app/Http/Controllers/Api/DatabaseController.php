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
    // public function migrate(Request $request): JsonResponse
    // {
    //     $migraciones = $request->input('migraciones');

    //     if (!$migraciones || !is_array($migraciones)) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Debes enviar un arreglo de migraciones'
    //         ], 400);
    //     }

    //     $resultados = [];

    //     foreach ($migraciones as $migracion) {

    //         $origen = $migracion['origen'] ?? null;
    //         $destino = $migracion['destino'] ?? null;

    //         if (!$origen || !$destino) {
    //             $resultados[] = [
    //                 'tabla' => $origen,
    //                 'status' => 'error',
    //                 'mensaje' => 'Faltan datos (origen/destino)'
    //             ];
    //             continue;
    //         }

    //         try {
    //             $rows = DB::connection('sqlsrv2')
    //                 ->table($origen)
    //                 ->get();

    //             foreach ($rows as $row) {
    //                 DB::connection('pgsql')
    //                     ->table($destino)
    //                     ->updateOrInsert(
    //                         ['codigo' => $row->CODIGO ?? null], // clave base
    //                         [
    //                             'ci' => $row->CI ?? null,
    //                             'nombres' => $row->NOMBRES ?? null,
    //                             'apellidos' => $row->APELLIDOS ?? null,
    //                             'fecha_nac' => $row->FECHA_NAC ?? null,
    //                             'sexo' => $row->SEXO ?? null,
    //                             'titulo' => $row->TITULO ?? null,
    //                             'fecha_nombramiento' => $row->FECHA_NOMBRAMIENTO ?? null,
    //                         ]
    //                     );
    //             }

    //             $resultados[] = [
    //                 'tabla' => $origen,
    //                 'status' => 'ok',
    //                 'filas' => count($rows)
    //             ];

    //         } catch (\Throwable $e) {
    //             $resultados[] = [
    //                 'tabla' => $origen,
    //                 'status' => 'error',
    //                 'mensaje' => $e->getMessage()
    //             ];
    //         }
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'resultados' => $resultados
    //     ]);
    // }
//romper memoria
    // public function migrate(Request $request): JsonResponse
    // {
    //     $migraciones = $request->input('migraciones');

    //     if (!$migraciones || !is_array($migraciones)) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Debes enviar un arreglo de migraciones'
    //         ], 400);
    //     }

    //     $resultados = [];

    //     foreach ($migraciones as $migracion) {

    //         $origen = $migracion['origen'] ?? null;
    //         $destino = $migracion['destino'] ?? null;

    //         if (!$origen || !$destino) {
    //             $resultados[] = [
    //                 'tabla' => $origen,
    //                 'status' => 'error',
    //                 'mensaje' => 'Faltan datos (origen/destino)'
    //             ];
    //             continue;
    //         }

    //         try {
    //             $rows = DB::connection('sqlsrv2')
    //                 ->table($origen)
    //                 ->get();

    //             $filasProcesadas = 0;

    //             foreach ($rows as $row) {

    //                 // 🔥 Convertir a array y pasar TODO a minúsculas
    //                 $data = array_change_key_case((array) $row, CASE_LOWER);

    //                 DB::connection('pgsql')
    //                     ->table($destino)
    //                     ->insert($data);

    //                 $filasProcesadas++;
    //             }

    //             $resultados[] = [
    //                 'tabla' => $origen,
    //                 'status' => 'ok',
    //                 'filas' => $filasProcesadas
    //             ];

    //         } catch (\Throwable $e) {
    //             $resultados[] = [
    //                 'tabla' => $origen,
    //                 'status' => 'error',
    //                 'mensaje' => $e->getMessage()
    //             ];
    //         }
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'resultados' => $resultados
    //     ]);
    // }

    //grupos com

    // public function migrate(Request $request): JsonResponse
    // {
    //     // 🔥 TIEMPO MÁXIMO: 90 segundos
    //     set_time_limit(90);
    //     ini_set('max_execution_time', 90);
    //     ini_set('memory_limit', '512M');

    //     // 🔥 quitar logs de queries (mejora rendimiento)
    //     DB::connection('pgsql')->disableQueryLog();
    //     DB::connection('sqlsrv2')->disableQueryLog();

    //     $migraciones = $request->input('migraciones');

    //     if (!$migraciones || !is_array($migraciones)) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Debes enviar un arreglo de migraciones'
    //         ], 400);
    //     }

    //     $resultados = [];

    //     // 🔥 PK MANUALES
    //     $pkManual = [
    //         'GRUPOS' => ['ANIO', 'PERIODO', 'MATERIA', 'GRUPO'],
    //         'MATERIAS' => ['ANIO', 'PLAN', 'CODIGO'],
    //         'PLANES' => ['PLAN']
    //     ];

    //     foreach ($migraciones as $migracion) {

    //         $origen = $migracion['origen'] ?? null;
    //         $destino = $migracion['destino'] ?? null;

    //         if (!$origen || !$destino) {
    //             $resultados[] = [
    //                 'tabla' => $origen,
    //                 'status' => 'error',
    //                 'mensaje' => 'Faltan datos (origen/destino)'
    //             ];
    //             continue;
    //         }

    //         try {

    //             // 🔍 detectar PK desde SQL Server
    //             $pk = DB::connection('sqlsrv2')->select("
    //             SELECT c.COLUMN_NAME
    //             FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc
    //             JOIN INFORMATION_SCHEMA.CONSTRAINT_COLUMN_USAGE c
    //                 ON c.CONSTRAINT_NAME = tc.CONSTRAINT_NAME
    //             WHERE tc.TABLE_NAME = ?
    //             AND tc.CONSTRAINT_TYPE = 'PRIMARY KEY'
    //         ", [$origen]);

    //             if (!empty($pk)) {
    //                 $pkColumns = array_map(fn($c) => strtolower($c->COLUMN_NAME), $pk);
    //             } else {
    //                 if (!isset($pkManual[$origen])) {
    //                     throw new \Exception("No se pudo determinar PK para $origen");
    //                 }
    //                 $pkColumns = array_map('strtolower', $pkManual[$origen]);
    //             }

    //             $batchSize = 2000; // 🔥 MÁS RÁPIDO
    //             $lastValues = null;
    //             $filasProcesadas = 0;

    //             while (true) {

    //                 $query = DB::connection('sqlsrv2')->table($origen);

    //                 foreach ($pkColumns as $col) {
    //                     $query->orderBy(strtoupper($col));
    //                 }

    //                 // 🔥 paginación manual (SQL Server 2008)
    //                 if ($lastValues) {
    //                     $query->where(function ($q) use ($pkColumns, $lastValues) {
    //                         foreach ($pkColumns as $i => $col) {
    //                             $q->orWhere(function ($sub) use ($pkColumns, $lastValues, $i, $col) {
    //                                 for ($j = 0; $j < $i; $j++) {
    //                                     $sub->where(strtoupper($pkColumns[$j]), '=', $lastValues[$pkColumns[$j]]);
    //                                 }
    //                                 $sub->where(strtoupper($col), '>', $lastValues[$col]);
    //                             });
    //                         }
    //                     });
    //                 }

    //                 $rows = $query->take($batchSize)->get();

    //                 if ($rows->isEmpty())
    //                     break;

    //                 $data = [];

    //                 foreach ($rows as $row) {
    //                     $data[] = array_change_key_case((array) $row, CASE_LOWER);
    //                 }

    //                 // 🔥 eliminar duplicados
    //                 $uniqueData = [];

    //                 foreach ($data as $item) {
    //                     $key = implode('|', array_map(fn($k) => trim((string) ($item[$k] ?? '')), $pkColumns));
    //                     $uniqueData[$key] = $item;
    //                 }

    //                 $data = array_values($uniqueData);

    //                 if (empty($data))
    //                     break;

    //                 $updateColumns = array_diff(array_keys($data[0]), $pkColumns);

    //                 DB::connection('pgsql')
    //                     ->table($destino)
    //                     ->upsert($data, $pkColumns, $updateColumns);

    //                 $filasProcesadas += count($data);

    //                 // 🔥 guardar último registro
    //                 $lastRow = end($data);
    //                 $lastValues = [];

    //                 foreach ($pkColumns as $col) {
    //                     $lastValues[$col] = $lastRow[$col];
    //                 }
    //             }

    //             $resultados[] = [
    //                 'tabla' => $origen,
    //                 'status' => 'ok',
    //                 'filas' => $filasProcesadas
    //             ];

    //         } catch (\Throwable $e) {
    //             $resultados[] = [
    //                 'tabla' => $origen,
    //                 'status' => 'error',
    //                 'mensaje' => $e->getMessage()
    //             ];
    //         }
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'resultados' => $resultados
    //     ]);
    // }

    public function migrate(Request $request): JsonResponse
    {
        set_time_limit(300);
        ini_set('memory_limit', '512M');

        DB::connection('pgsql')->disableQueryLog();
        DB::connection('sqlsrv2')->disableQueryLog();

        $migraciones = $request->input('migraciones');

        if (!$migraciones || !is_array($migraciones)) {
            return response()->json([
                'success' => false,
                'message' => 'Debes enviar un arreglo de migraciones'
            ], 400);
        }

        $resultados = [];

        $insertDirecto = ['MATERIAS', 'GRUPOS'];

        $pkManual = [
            'GRUPOS' => ['ANIO', 'PERIODO', 'MATERIA', 'GRUPO'],
            'PLANES' => ['PLAN']
        ];

        foreach ($migraciones as $migracion) {

            $origen = $migracion['origen'] ?? null;
            $destino = $migracion['destino'] ?? null;

            if (!$origen || !$destino) {
                $resultados[] = [
                    'tabla' => $origen,
                    'status' => 'error',
                    'mensaje' => 'Faltan datos'
                ];
                continue;
            }

            try {

                $filasProcesadas = 0;

                // =====================================================
                // CASO ESPECIAL: ROW_NUMBER() para SQL Server 2008
                // Usado para GRUPOS y MATERIAS
                // =====================================================
                if (in_array($origen, $insertDirecto)) {

                    $batchSize = 500;
                    $page = 1;

                    $columnas = DB::connection('sqlsrv2')
                        ->select("SELECT COLUMN_NAME 
                              FROM INFORMATION_SCHEMA.COLUMNS 
                              WHERE TABLE_NAME = ? 
                              ORDER BY ORDINAL_POSITION", [$origen]);

                    $cols = array_map(fn($c) => '[' . $c->COLUMN_NAME . ']', $columnas);
                    $colsStr = implode(', ', $cols);

                    $orderCols = array_map(
                        fn($c) => '[' . strtoupper($c) . ']',
                        $pkManual[$origen] ?? [$columnas[0]->COLUMN_NAME]
                    );
                    $orderStr = implode(', ', $orderCols);

                    while (true) {

                        $offset = ($page - 1) * $batchSize;
                        $limit = $offset + $batchSize;

                        $sql = "
                        SELECT {$colsStr}
                        FROM (
                            SELECT {$colsStr},
                                   ROW_NUMBER() OVER (ORDER BY {$orderStr}) AS [rn]
                            FROM [{$origen}]
                        ) AS [paginado]
                        WHERE [rn] > {$offset} AND [rn] <= {$limit}
                    ";

                        $rows = DB::connection('sqlsrv2')->select($sql);

                        if (empty($rows))
                            break;

                        $data = array_map(function ($row) {
                            $item = array_change_key_case((array) $row, CASE_LOWER);
                            unset($item['id']);
                            unset($item['rn']);
                            return $item;
                        }, $rows);


                        // MATERIAS y otras sin PK → insert directo
                        DB::connection('pgsql')
                            ->table($destino)
                            ->insert($data);


                        $filasProcesadas += count($data);
                        $page++;

                        if (count($rows) < $batchSize)
                            break;
                    }

                    $resultados[] = [
                        'tabla' => $origen,
                        'status' => 'ok',
                        'filas' => $filasProcesadas
                    ];

                    continue;
                }

                // =====================================================
                // FLUJO NORMAL: DOCENTES, PLANES y otras tablas
                // =====================================================

                $total = DB::connection('sqlsrv2')->table($origen)->count();

                $pk = DB::connection('sqlsrv2')->select("
                SELECT c.COLUMN_NAME
                FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc
                JOIN INFORMATION_SCHEMA.CONSTRAINT_COLUMN_USAGE c
                    ON c.CONSTRAINT_NAME = tc.CONSTRAINT_NAME
                WHERE tc.TABLE_NAME = ?
                AND tc.CONSTRAINT_TYPE = 'PRIMARY KEY'
            ", [$origen]);

                if (!empty($pk)) {
                    $pkColumns = array_values(
                        array_map(fn($c) => strtolower($c->COLUMN_NAME), $pk)
                    );
                } else {
                    $pkColumns = isset($pkManual[$origen])
                        ? array_values(array_map('strtolower', $pkManual[$origen]))
                        : null;
                }

                // 🟢 CASO 1: Tablas pequeñas o sin PK
                if ($total < 5000 || !$pkColumns) {

                    $rows = DB::connection('sqlsrv2')->table($origen)->get();

                    $data = $rows->map(
                        fn($row) => array_change_key_case((array) $row, CASE_LOWER)
                    )->toArray();

                    foreach (array_chunk($data, 500) as $chunk) {
                        DB::connection('pgsql')->table($destino)->insert($chunk);
                        $filasProcesadas += count($chunk);
                    }

                } else {

                    // 🔴 CASO 2: Tablas grandes con PK — keyset pagination
                    $batchSize = 2000;
                    $lastValues = null;

                    while (true) {

                        $query = DB::connection('sqlsrv2')->table($origen);

                        foreach ($pkColumns as $col) {
                            $query->orderBy(strtoupper($col));
                        }

                        if ($lastValues) {
                            $query->where(function ($q) use ($pkColumns, $lastValues) {

                                $cols = array_values($pkColumns);

                                foreach ($cols as $i => $col) {
                                    $q->orWhere(function ($sub) use ($cols, $lastValues, $i, $col) {
                                        for ($j = 0; $j < $i; $j++) {
                                            $sub->where(
                                                strtoupper($cols[$j]),
                                                '=',
                                                $lastValues[$cols[$j]]
                                            );
                                        }
                                        $sub->where(strtoupper($col), '>', $lastValues[$col]);
                                    });
                                }
                            });
                        }

                        $rows = $query->take($batchSize)->get();

                        if ($rows->isEmpty())
                            break;

                        $data = $rows->map(
                            fn($row) => array_change_key_case((array) $row, CASE_LOWER)
                        )->toArray();

                        // Limpiar duplicados dentro del batch
                        $unique = [];
                        foreach ($data as $item) {
                            $key = implode('|', array_map(
                                fn($k) => trim((string) ($item[$k] ?? '')),
                                $pkColumns
                            ));
                            $unique[$key] = $item;
                        }
                        $data = array_values($unique);

                        if (empty($data))
                            break;

                        $updateColumns = array_diff(array_keys($data[0]), $pkColumns);

                        DB::connection('pgsql')
                            ->table($destino)
                            ->upsert($data, $pkColumns, $updateColumns);

                        $filasProcesadas += count($data);

                        $lastRow = end($data);
                        $lastValues = [];
                        foreach ($pkColumns as $col) {
                            $lastValues[$col] = $lastRow[$col];
                        }
                    }
                }

                $resultados[] = [
                    'tabla' => $origen,
                    'status' => 'ok',
                    'filas' => $filasProcesadas
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