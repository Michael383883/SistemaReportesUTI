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

    //control de migraciones 
    public function incrementalMigrate(Request $request): JsonResponse
    {
        set_time_limit(600);
        ini_set('memory_limit', '512M');

        $migraciones = $request->input('migraciones');

        if (!$migraciones || !is_array($migraciones)) {
            return response()->json(['success' => false, 'message' => 'Debes enviar un arreglo de migraciones'], 400);
        }

        $resultados = [];

        foreach ($migraciones as $migracion) {
            $origen = $migracion['origen'] ?? null;
            $destino = $migracion['destino'] ?? null;

            if (!$origen || !$destino) {
                $resultados[] = ['tabla' => $origen, 'status' => 'error', 'mensaje' => 'Faltan datos'];
                continue;
            }

            try {
                $stats = $this->syncTable($origen, $destino);
                $resultados[] = array_merge(['tabla' => $origen, 'status' => 'ok'], $stats);
            } catch (\Throwable $e) {
                $resultados[] = ['tabla' => $origen, 'status' => 'error', 'mensaje' => $e->getMessage()];
            }
        }

        return response()->json(['success' => true, 'resultados' => $resultados]);
    }


    //MIGRACION VELOSIDAD A LA MITAD DEL NUEVO
    // private function syncTable(string $origen, string $destino): array
    // {
    //     $batchSize = 500;
    //     $page = 1;
    //     $stats = ['insertados' => 0, 'actualizados' => 0, 'eliminados' => 0];

    //     // Obtener columnas del origen
    //     $columnas = DB::connection('sqlsrv2')->select(
    //         "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? ORDER BY ORDINAL_POSITION",
    //         [$origen]
    //     );
    //     $colNames = array_map(fn($c) => $c->COLUMN_NAME, $columnas);
    //     $colsStr = implode(', ', array_map(fn($c) => '[' . $c . ']', $colNames));

    //     // Snapshot actual en Postgres (row_key => row_hash)
    //     $snapshotActual = DB::connection('pgsql')
    //         ->table('migration_snapshots')
    //         ->where('tabla', $origen)
    //         ->pluck('row_hash', 'row_key')
    //         ->toArray();

    //     // Keys vistas en esta corrida (para detectar eliminados)
    //     $keysVistas = [];

    //     // Paginación con ROW_NUMBER (compatible SQL Server 2008)
    //     $orderCol = '[' . $colNames[0] . ']';

    //     while (true) {
    //         $offset = ($page - 1) * $batchSize;
    //         $limit = $offset + $batchSize;

    //         $sql = "
    //         SELECT {$colsStr}
    //         FROM (
    //             SELECT {$colsStr}, ROW_NUMBER() OVER (ORDER BY {$orderCol}) AS [rn]
    //             FROM [{$origen}]
    //         ) AS [p]
    //         WHERE [rn] > {$offset} AND [rn] <= {$limit}
    //     ";

    //         $rows = DB::connection('sqlsrv2')->select($sql);
    //         if (empty($rows))
    //             break;

    //         $toInsert = [];
    //         $toUpdate = [];
    //         $snapshotUpsert = [];

    //         foreach ($rows as $row) {
    //             $arr = array_change_key_case((array) $row, CASE_LOWER);


    //             $hash = md5(implode('|', array_map('strval', $arr)));
    //             // Usamos hash del contenido completo como row_key (sin PK real)
    //             $key = $hash; // ← ver nota abajo

    //             $keysVistas[$key] = true;

    //             if (!isset($snapshotActual[$key])) {
    //                 // Nunca visto → INSERT
    //                 $toInsert[] = $arr;
    //             }
    //             // Si el key existe y el hash coincide → sin cambios (nada que hacer)
    //             // (con hash-como-key, si el contenido cambió, aparece como fila nueva)

    //             $snapshotUpsert[] = [
    //                 'tabla' => $origen,
    //                 'row_key' => $key,
    //                 'row_hash' => $hash,
    //                 'synced_at' => now(),
    //             ];
    //         }

    //         // Insertar nuevas filas
    //         if (!empty($toInsert)) {
    //             foreach (array_chunk($toInsert, 100) as $chunk) {
    //                 DB::connection('pgsql')->table($destino)->insert($chunk);
    //                 $stats['insertados'] += count($chunk);
    //             }
    //         }

    //         // Actualizar snapshot
    //         foreach (array_chunk($snapshotUpsert, 200) as $chunk) {
    //             DB::connection('pgsql')
    //                 ->table('migration_snapshots')
    //                 ->upsert($chunk, ['tabla', 'row_key'], ['row_hash', 'synced_at']);
    //         }

    //         $page++;
    //         if (count($rows) < $batchSize)
    //             break;
    //     }

    //     // Detectar filas eliminadas en SQL Server
    //     $keysEliminadas = array_diff(array_keys($snapshotActual), array_keys($keysVistas));

    //     if (!empty($keysEliminadas)) {
    //         // Para tablas sin PK no podemos hacer DELETE preciso en destino sin PK
    //         // La estrategia más segura: re-sincronizar la tabla completa si hay eliminados
    //         // O alternativamente: llevar un conteo y alertar
    //         $stats['eliminados'] = count($keysEliminadas);

    //         // Limpiar snapshots de filas que ya no existen
    //         foreach (array_chunk($keysEliminadas, 200) as $chunk) {
    //             DB::connection('pgsql')
    //                 ->table('migration_snapshots')
    //                 ->where('tabla', $origen)
    //                 ->whereIn('row_key', $chunk)
    //                 ->delete();
    //         }

    //         // ⚠️ Sin PK no hay forma de borrar la fila exacta en destino.
    //         // Opciones: (a) full re-sync solo cuando hay eliminados, (b) agregar columna de control en destino
    //         // Por ahora: registrar en el resultado para que el cliente decida
    //         $stats['requiere_resync'] = true;
    //     }

    //     return $stats;
    // }


    //migracion k no falla pero tarda un poco mass

    private function syncTable(string $origen, string $destino): array
    {
        $batchSize = 500;
        $page = 1;
        $stats = ['insertados' => 0, 'actualizados' => 0, 'eliminados' => 0];

        $columnas = DB::connection('sqlsrv2')->select(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? ORDER BY ORDINAL_POSITION",
            [$origen]
        );
        $colNames = array_map(fn($c) => $c->COLUMN_NAME, $columnas);
        $colsStr = implode(', ', array_map(fn($c) => '[' . $c . ']', $colNames));

        // ORDER BY todas las columnas para que el orden sea 100% estable entre corridas
        $orderStr = implode(', ', array_map(fn($c) => '[' . $c . ']', $colNames));

        $snapshotActual = DB::connection('pgsql')
            ->table('migration_snapshots')
            ->where('tabla', $origen)
            ->pluck('row_hash', 'row_key')
            ->toArray();

        \Log::info("[$origen] INICIO — Snapshot previo: " . count($snapshotActual) . " filas");

        $keysVistas = [];
        $rowNumber = 0; // contador global de fila, independiente de la página

        while (true) {
            $offset = ($page - 1) * $batchSize;
            $limit = $offset + $batchSize;

            $sql = "
            SELECT {$colsStr}
            FROM (
                SELECT {$colsStr}, ROW_NUMBER() OVER (ORDER BY {$orderStr}) AS [rn]
                FROM [{$origen}]
            ) AS [p]
            WHERE [rn] > {$offset} AND [rn] <= {$limit}
        ";

            $rows = DB::connection('sqlsrv2')->select($sql);
            if (empty($rows))
                break;

            $toInsert = [];
            $snapshotUpsert = [];

            foreach ($rows as $row) {
                $arr = array_change_key_case((array) $row, CASE_LOWER);

                // Normalizar para hash estable
                $norm = array_map(function ($val) {
                    if (is_null($val))
                        return '';
                    if (is_numeric($val)) {
                        return strpos((string) $val, '.') === false
                            ? (string) (int) $val
                            : rtrim(rtrim((string) (float) $val, '0'), '.');
                    }
                    return trim((string) $val);
                }, $arr);

                $rowNumber++;

                // ✅ row_key = número de fila secuencial (estable si el ORDER BY es estable)
                // Así dos filas idénticas tienen keys diferentes: "1", "2", etc.
                $key = (string) $rowNumber;
                $hash = md5(implode('|', $norm));

                $keysVistas[$key] = true;

                if (!isset($snapshotActual[$key])) {
                    // Fila nueva (posición nunca vista antes)
                    $toInsert[] = $arr;
                } elseif ($snapshotActual[$key] !== $hash) {
                    // Misma posición pero contenido diferente → el dato cambió
                    // Sin PK no podemos hacer UPDATE preciso, lo insertamos como nuevo
                    $toInsert[] = $arr;
                    $stats['actualizados']++;
                }
                // Hash igual → sin cambios

                $snapshotUpsert[] = [
                    'tabla' => $origen,
                    'row_key' => $key,
                    'row_hash' => $hash,
                    'synced_at' => now(),
                ];
            }

            if (!empty($toInsert)) {
                foreach (array_chunk($toInsert, 100) as $chunk) {
                    DB::connection('pgsql')->table($destino)->insert($chunk);
                    $stats['insertados'] += count($chunk);
                }
            }

            foreach (array_chunk($snapshotUpsert, 200) as $chunk) {
                DB::connection('pgsql')
                    ->table('migration_snapshots')
                    ->upsert($chunk, ['tabla', 'row_key'], ['row_hash', 'synced_at']);
            }

            $page++;
            if (count($rows) < $batchSize)
                break;
        }

        \Log::info("[$origen] Leídas: $rowNumber filas — Keys vistas: " . count($keysVistas) . " — Snapshot previo: " . count($snapshotActual));

        // Detectar filas eliminadas (posiciones que ya no existen)
        $keysEliminadas = array_diff(array_keys($snapshotActual), array_keys($keysVistas));

        if (!empty($keysEliminadas)) {
            \Log::info("[$origen] Eliminadas: " . count($keysEliminadas));
            $stats['eliminados'] = count($keysEliminadas);

            foreach (array_chunk($keysEliminadas, 200) as $chunk) {
                DB::connection('pgsql')
                    ->table('migration_snapshots')
                    ->where('tabla', $origen)
                    ->whereIn('row_key', $chunk)
                    ->delete();
            }

            $stats['requiere_resync'] = true;
        }

        \Log::info("[$origen] FIN — insert:{$stats['insertados']} act:{$stats['actualizados']} elim:{$stats['eliminados']}");

        return $stats;
    }
}