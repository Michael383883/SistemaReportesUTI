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
            'sqlserver_2022' => $this->checkConnection('sqlsrv'),
            'sqlserver_2008' => $this->checkConnection('sqlsrv2'),
        ]);
    }

    public function migrate(Request $request): JsonResponse
    {
        try {
            $docentes = DB::connection('sqlsrv2')->table('DOCENTES')->get();

            if ($docentes->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron registros.',
                    'total' => 0,
                ], 404);
            }

            $insertados = 0;
            $omitidos = 0;
            $errores = [];

            foreach ($docentes as $docente) {
                try {
                    $codigo = (int) $docente->CODIGO;

                    $existe = DB::connection('sqlsrv')
                        ->table('DOCENTES')
                        ->whereRaw('CODIGO = ?', [$codigo])
                        ->exists();

                    if ($existe) {
                        $omitidos++;
                        continue;
                    }

                    DB::connection('sqlsrv')->table('DOCENTES')->insert([
                        'CODIGO' => $codigo,
                        'CI' => $this->limpiarString($docente->CI, 'SIN_CI'),
                        'NOMBRES' => $docente->NOMBRES,
                        'APELLIDOS' => $docente->APELLIDOS,
                        'FECHA_NAC' => $this->limpiarFecha($docente->FECHA_NAC),
                        'SEXO' => $this->limpiarString($docente->SEXO, 'X'),
                        'TITULO' => $docente->TITULO,
                        'FECHA_NOMBRAMIENTO' => $this->limpiarFecha($docente->FECHA_NOMBRAMIENTO),
                    ]);

                    $insertados++;

                } catch (\Exception $e) {
                    $errores[] = [
                        'CODIGO' => $docente->CODIGO,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Migración completada.',
                'total' => $docentes->count(),
                'insertados' => $insertados,
                'omitidos' => $omitidos,
                'errores' => $errores,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error general: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ─────────────────────────────────────────────
// Convierte cualquier tipo de fecha a string
// que SQL Server DATETIME acepta, o NULL
// ─────────────────────────────────────────────
    private function limpiarFecha($fecha): ?string
    {
        if (is_null($fecha))
            return null;

        try {
            // Viene como objeto DateTime del driver de SQL Server
            if ($fecha instanceof \DateTime) {
                return $fecha->format('Y-m-d H:i:s');
            }

            // Viene como string
            $str = trim((string) $fecha);
            if ($str === '' || $str === '?' || $str === '1900-01-01 00:00:00') {
                return null;
            }

            // Forzar conversión con Carbon
            $carbon = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $str)
                ?? \Carbon\Carbon::parse($str);

            // Validar rango de DATETIME de SQL Server (1753 - 9999)
            if ($carbon->year < 1753 || $carbon->year > 9999) {
                return null;
            }

            return $carbon->format('Y-m-d H:i:s');

        } catch (\Exception $e) {
            return null;
        }
    }

    // ─────────────────────────────────────────────
// Limpia strings vacíos o nulos con un default
// ─────────────────────────────────────────────
    private function limpiarString($valor, string $default): string
    {
        if (is_null($valor))
            return $default;
        $str = trim((string) $valor);
        if ($str === '' || $str === '?')
            return $default;
        return $str;
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
    // public function migrate(Request $request): JsonResponse

    // {
    //     set_time_limit(300);
    //     ini_set('memory_limit', '512M');

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

    //     $insertDirecto = ['MATERIAS', 'GRUPOS'];

    //     $pkManual = [
    //         'GRUPOS' => ['ANIO', 'PERIODO', 'MATERIA', 'GRUPO'],
    //         'PLANES' => ['PLAN']
    //     ];

    //     foreach ($migraciones as $migracion) {

    //         $origen = $migracion['origen'] ?? null;
    //         $destino = $migracion['destino'] ?? null;

    //         if (!$origen || !$destino) {
    //             $resultados[] = [
    //                 'tabla' => $origen,
    //                 'status' => 'error',
    //                 'mensaje' => 'Faltan datos'
    //             ];
    //             continue;
    //         }

    //         try {

    //             $filasProcesadas = 0;

    //             // =====================================================
    //             // CASO ESPECIAL: ROW_NUMBER() para SQL Server 2008
    //             // Usado para GRUPOS y MATERIAS
    //             // =====================================================
    //             if (in_array($origen, $insertDirecto)) {

    //                 $batchSize = 500;
    //                 $page = 1;

    //                 $columnas = DB::connection('sqlsrv2')
    //                     ->select("SELECT COLUMN_NAME 
    //                           FROM INFORMATION_SCHEMA.COLUMNS 
    //                           WHERE TABLE_NAME = ? 
    //                           ORDER BY ORDINAL_POSITION", [$origen]);

    //                 $cols = array_map(fn($c) => '[' . $c->COLUMN_NAME . ']', $columnas);
    //                 $colsStr = implode(', ', $cols);

    //                 $orderCols = array_map(
    //                     fn($c) => '[' . strtoupper($c) . ']',
    //                     $pkManual[$origen] ?? [$columnas[0]->COLUMN_NAME]
    //                 );
    //                 $orderStr = implode(', ', $orderCols);

    //                 while (true) {

    //                     $offset = ($page - 1) * $batchSize;
    //                     $limit = $offset + $batchSize;

    //                     $sql = "
    //                     SELECT {$colsStr}
    //                     FROM (
    //                         SELECT {$colsStr},
    //                                ROW_NUMBER() OVER (ORDER BY {$orderStr}) AS [rn]
    //                         FROM [{$origen}]
    //                     ) AS [paginado]
    //                     WHERE [rn] > {$offset} AND [rn] <= {$limit}
    //                 ";

    //                     $rows = DB::connection('sqlsrv2')->select($sql);

    //                     if (empty($rows))
    //                         break;

    //                     $data = array_map(function ($row) {
    //                         $item = array_change_key_case((array) $row, CASE_LOWER);
    //                         unset($item['id']);
    //                         unset($item['rn']);
    //                         return $item;
    //                     }, $rows);


    //                     // MATERIAS y otras sin PK → insert directo
    //                     DB::connection('pgsql')
    //                         ->table($destino)
    //                         ->insert($data);


    //                     $filasProcesadas += count($data);
    //                     $page++;

    //                     if (count($rows) < $batchSize)
    //                         break;
    //                 }

    //                 $resultados[] = [
    //                     'tabla' => $origen,
    //                     'status' => 'ok',
    //                     'filas' => $filasProcesadas
    //                 ];

    //                 continue;
    //             }

    //             // =====================================================
    //             // FLUJO NORMAL: DOCENTES, PLANES y otras tablas
    //             // =====================================================

    //             $total = DB::connection('sqlsrv2')->table($origen)->count();

    //             $pk = DB::connection('sqlsrv2')->select("
    //             SELECT c.COLUMN_NAME
    //             FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc
    //             JOIN INFORMATION_SCHEMA.CONSTRAINT_COLUMN_USAGE c
    //                 ON c.CONSTRAINT_NAME = tc.CONSTRAINT_NAME
    //             WHERE tc.TABLE_NAME = ?
    //             AND tc.CONSTRAINT_TYPE = 'PRIMARY KEY'
    //         ", [$origen]);

    //             if (!empty($pk)) {
    //                 $pkColumns = array_values(
    //                     array_map(fn($c) => strtolower($c->COLUMN_NAME), $pk)
    //                 );
    //             } else {
    //                 $pkColumns = isset($pkManual[$origen])
    //                     ? array_values(array_map('strtolower', $pkManual[$origen]))
    //                     : null;
    //             }

    //             //  CASO 1: Tablas pequeñas o sin PK
    //             if ($total < 5000 || !$pkColumns) {

    //                 $rows = DB::connection('sqlsrv2')->table($origen)->get();

    //                 $data = $rows->map(
    //                     fn($row) => array_change_key_case((array) $row, CASE_LOWER)
    //                 )->toArray();

    //                 foreach (array_chunk($data, 500) as $chunk) {
    //                     DB::connection('pgsql')->table($destino)->insert($chunk);
    //                     $filasProcesadas += count($chunk);
    //                 }

    //             } else {

    //                 //  CASO 2: Tablas grandes con PK — keyset pagination
    //                 $batchSize = 2000;
    //                 $lastValues = null;

    //                 while (true) {

    //                     $query = DB::connection('sqlsrv2')->table($origen);

    //                     foreach ($pkColumns as $col) {
    //                         $query->orderBy(strtoupper($col));
    //                     }

    //                     if ($lastValues) {
    //                         $query->where(function ($q) use ($pkColumns, $lastValues) {

    //                             $cols = array_values($pkColumns);

    //                             foreach ($cols as $i => $col) {
    //                                 $q->orWhere(function ($sub) use ($cols, $lastValues, $i, $col) {
    //                                     for ($j = 0; $j < $i; $j++) {
    //                                         $sub->where(
    //                                             strtoupper($cols[$j]),
    //                                             '=',
    //                                             $lastValues[$cols[$j]]
    //                                         );
    //                                     }
    //                                     $sub->where(strtoupper($col), '>', $lastValues[$col]);
    //                                 });
    //                             }
    //                         });
    //                     }

    //                     $rows = $query->take($batchSize)->get();

    //                     if ($rows->isEmpty())
    //                         break;

    //                     $data = $rows->map(
    //                         fn($row) => array_change_key_case((array) $row, CASE_LOWER)
    //                     )->toArray();

    //                     // Limpiar duplicados dentro del batch
    //                     $unique = [];
    //                     foreach ($data as $item) {
    //                         $key = implode('|', array_map(
    //                             fn($k) => trim((string) ($item[$k] ?? '')),
    //                             $pkColumns
    //                         ));
    //                         $unique[$key] = $item;
    //                     }
    //                     $data = array_values($unique);

    //                     if (empty($data))
    //                         break;

    //                     $updateColumns = array_diff(array_keys($data[0]), $pkColumns);

    //                     DB::connection('pgsql')
    //                         ->table($destino)
    //                         ->upsert($data, $pkColumns, $updateColumns);

    //                     $filasProcesadas += count($data);

    //                     $lastRow = end($data);
    //                     $lastValues = [];
    //                     foreach ($pkColumns as $col) {
    //                         $lastValues[$col] = $lastRow[$col];
    //                     }
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

    // // ─── Helper ─────────────────────────────────────────────

    // private function checkConnection(string $connection): array
    // {
    //     try {
    //         DB::connection($connection)->getPdo();
    //         $connected = true;
    //     } catch (\Throwable) {
    //         $connected = false;
    //     }

    //     return [
    //         'connected' => $connected,
    //         'host' => config("database.connections.$connection.host"),
    //         'database' => config("database.connections.$connection.database"),
    //     ];
    // }

    // //control de migraciones 
    // public function incrementalMigrate(Request $request): JsonResponse
    // {
    //     set_time_limit(0);
    //     ini_set('memory_limit', '512M');

    //     $migraciones = $request->input('migraciones');

    //     if (!$migraciones || !is_array($migraciones)) {
    //         return response()->json(['success' => false, 'message' => 'Debes enviar un arreglo de migraciones'], 400);
    //     }

    //     $resultados = [];

    //     foreach ($migraciones as $migracion) {
    //         $origen = $migracion['origen'] ?? null;
    //         $destino = $migracion['destino'] ?? null;

    //         if (!$origen || !$destino) {
    //             $resultados[] = ['tabla' => $origen, 'status' => 'error', 'mensaje' => 'Faltan datos'];
    //             continue;
    //         }

    //         try {
    //             $stats = $this->syncTable($origen, $destino);
    //             $resultados[] = array_merge(['tabla' => $origen, 'status' => 'ok'], $stats);
    //         } catch (\Throwable $e) {
    //             $resultados[] = ['tabla' => $origen, 'status' => 'error', 'mensaje' => $e->getMessage()];
    //         }
    //     }

    //     return response()->json(['success' => true, 'resultados' => $resultados]);
    // }


    // //MIGRACION VELOSIDAD A LA MITAD DEL NUEVO

    // //EL DE ARIBA ES ANTES DE HORARIOSSSSS2
    // private function syncTable(string $origen, string $destino): array
    // {
    //     $batchSize = 500;
    //     $page = 1;

    //     $stats = [
    //         'insertados' => 0,
    //         'actualizados' => 0,
    //         'eliminados' => 0
    //     ];

    //     // =====================================================
    //     // OBTENER COLUMNAS
    //     // =====================================================

    //     $columnas = DB::connection('sqlsrv2')->select(
    //         "
    //     SELECT COLUMN_NAME
    //     FROM INFORMATION_SCHEMA.COLUMNS
    //     WHERE TABLE_NAME = ?
    //     ORDER BY ORDINAL_POSITION
    //     ",
    //         [$origen]
    //     );

    //     $colNames = array_map(
    //         fn($c) => trim($c->COLUMN_NAME),
    //         $columnas
    //     );

    //     // Validar columnas
    //     if (empty($colNames)) {
    //         throw new \Exception("La tabla {$origen} no tiene columnas válidas");
    //     }

    //     // =====================================================
    //     // COLUMNAS SQL
    //     // =====================================================

    //     $colsStr = implode(
    //         ', ',
    //         array_map(fn($c) => '[' . $c . ']', $colNames)
    //     );

    //     // =====================================================
    //     // ORDER BY MANUAL PARA TABLAS SIN PK
    //     // =====================================================

    //     $orderManual = [

    //         // HORARIOS2
    //         'HORARIOS2' => '[ANIO], [PERIODO], [HORA]',

    //         // puedes agregar más tablas aquí
    //         // 'OTRA_TABLA' => '[CAMPO1], [CAMPO2]'
    //     ];

    //     $orderStr = $orderManual[$origen]
    //         ?? implode(
    //             ', ',
    //             array_map(fn($c) => '[' . $c . ']', $colNames)
    //         );

    //     // Validar ORDER BY
    //     if (empty(trim($orderStr))) {
    //         throw new \Exception("ORDER BY vacío para {$origen}");
    //     }

    //     // =====================================================
    //     // SNAPSHOT ACTUAL
    //     // =====================================================

    //     $snapshotActual = DB::connection('pgsql')
    //         ->table('migration_snapshots')
    //         ->where('tabla', $origen)
    //         ->pluck('row_hash', 'row_key')
    //         ->toArray();

    //     \Log::info("[$origen] INICIO — Snapshot previo: " . count($snapshotActual));

    //     $keysVistas = [];
    //     $rowNumber = 0;

    //     // =====================================================
    //     // PAGINACIÓN SQL SERVER 2008
    //     // =====================================================

    //     while (true) {

    //         $offset = ($page - 1) * $batchSize;
    //         $limit = $offset + $batchSize;

    //         $sql = "
    //         SELECT {$colsStr}
    //         FROM (
    //             SELECT
    //                 {$colsStr},
    //                 ROW_NUMBER() OVER (ORDER BY {$orderStr}) AS [rn]
    //             FROM [{$origen}]
    //         ) AS [p]
    //         WHERE [rn] > {$offset}
    //         AND [rn] <= {$limit}
    //     ";

    //         $rows = DB::connection('sqlsrv2')->select($sql);

    //         if (empty($rows)) {
    //             break;
    //         }

    //         $toInsert = [];
    //         $snapshotUpsert = [];

    //         foreach ($rows as $row) {

    //             $arr = array_change_key_case(
    //                 (array) $row,
    //                 CASE_LOWER
    //             );

    //             // quitar rn
    //             unset($arr['rn']);

    //             // =====================================================
    //             // NORMALIZAR HASH
    //             // =====================================================

    //             $norm = array_map(function ($val) {

    //                 if (is_null($val)) {
    //                     return '';
    //                 }

    //                 if (is_numeric($val)) {

    //                     return strpos((string) $val, '.') === false
    //                         ? (string) (int) $val
    //                         : rtrim(
    //                             rtrim((string) (float) $val, '0'),
    //                             '.'
    //                         );
    //                 }

    //                 return trim((string) $val);

    //             }, $arr);

    //             $rowNumber++;

    //             // =====================================================
    //             // KEY ESTABLE
    //             // =====================================================

    //             $key = (string) $rowNumber;

    //             // HASH
    //             $hash = md5(
    //                 implode('|', $norm)
    //             );

    //             $keysVistas[$key] = true;

    //             // =====================================================
    //             // INSERT / UPDATE DETECTION
    //             // =====================================================

    //             if (!isset($snapshotActual[$key])) {

    //                 // fila nueva
    //                 $toInsert[] = $arr;

    //             } elseif ($snapshotActual[$key] !== $hash) {

    //                 // fila modificada
    //                 $toInsert[] = $arr;

    //                 $stats['actualizados']++;
    //             }

    //             // =====================================================
    //             // SNAPSHOT UPSERT
    //             // =====================================================

    //             $snapshotUpsert[] = [
    //                 'tabla' => $origen,
    //                 'row_key' => $key,
    //                 'row_hash' => $hash,
    //                 'synced_at' => now(),
    //             ];
    //         }

    //         // =====================================================
    //         // INSERTAR EN POSTGRES
    //         // =====================================================

    //         if (!empty($toInsert)) {

    //             foreach (array_chunk($toInsert, 100) as $chunk) {

    //                 DB::connection('pgsql')
    //                     ->table($destino)
    //                     ->insert($chunk);

    //                 $stats['insertados'] += count($chunk);
    //             }
    //         }

    //         // =====================================================
    //         // ACTUALIZAR SNAPSHOT
    //         // =====================================================

    //         foreach (array_chunk($snapshotUpsert, 200) as $chunk) {

    //             DB::connection('pgsql')
    //                 ->table('migration_snapshots')
    //                 ->upsert(
    //                     $chunk,
    //                     ['tabla', 'row_key'],
    //                     ['row_hash', 'synced_at']
    //                 );
    //         }

    //         $page++;

    //         if (count($rows) < $batchSize) {
    //             break;
    //         }
    //     }

    //     // =====================================================
    //     // DETECTAR ELIMINADOS
    //     // =====================================================

    //     $keysEliminadas = array_diff(
    //         array_keys($snapshotActual),
    //         array_keys($keysVistas)
    //     );

    //     if (!empty($keysEliminadas)) {

    //         $stats['eliminados'] = count($keysEliminadas);

    //         \Log::info("[$origen] Eliminadas: " . count($keysEliminadas));

    //         foreach (array_chunk($keysEliminadas, 200) as $chunk) {

    //             DB::connection('pgsql')
    //                 ->table('migration_snapshots')
    //                 ->where('tabla', $origen)
    //                 ->whereIn('row_key', $chunk)
    //                 ->delete();
    //         }

    //         $stats['requiere_resync'] = true;
    //     }

    //     // =====================================================
    //     // LOG FINAL
    //     // =====================================================

    //     \Log::info(
    //         "[$origen] FIN — insert:{$stats['insertados']} "
    //         . "act:{$stats['actualizados']} "
    //         . "elim:{$stats['eliminados']}"
    //     );

    //     return $stats;
    // }


    // /**
    //  * POST /api/database/migraciontot
    //  *
    //  * Recibe { origen: "TABLA_SQLSERVER", destino: "tabla_postgres" }
    //  * Crea la tabla en Postgres si no existe, y migra todos los datos.
    //  */
    // public function migraciontot(Request $request): JsonResponse
    // {
    //     set_time_limit(300);
    //     ini_set('memory_limit', '512M');

    //     $origen = $request->input('origen');
    //     $destino = $request->input('destino');

    //     if (!$origen || !$destino) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Debes enviar origen y destino',
    //         ], 400);
    //     }

    //     try {
    //         // =====================================================
    //         // 1. LEER COLUMNAS DEL ORIGEN (SQL Server)
    //         // =====================================================
    //         $columnas = DB::connection('sqlsrv2')->select("
    //         SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH,
    //                NUMERIC_PRECISION, NUMERIC_SCALE, IS_NULLABLE
    //         FROM INFORMATION_SCHEMA.COLUMNS
    //         WHERE TABLE_NAME = ?
    //         ORDER BY ORDINAL_POSITION
    //     ", [$origen]);

    //         if (empty($columnas)) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => "La tabla '{$origen}' no existe o no tiene columnas",
    //             ], 404);
    //         }

    //         // =====================================================
    //         // 2. MAPEAR TIPOS SQL Server → PostgreSQL
    //         // =====================================================
    //         $mapTipos = [
    //             'int' => 'INTEGER',
    //             'bigint' => 'BIGINT',
    //             'smallint' => 'SMALLINT',
    //             'tinyint' => 'SMALLINT',
    //             'bit' => 'BOOLEAN',
    //             'float' => 'DOUBLE PRECISION',
    //             'real' => 'REAL',
    //             'decimal' => 'NUMERIC',
    //             'numeric' => 'NUMERIC',
    //             'money' => 'NUMERIC(19,4)',
    //             'smallmoney' => 'NUMERIC(10,4)',
    //             'char' => 'CHAR',
    //             'nchar' => 'CHAR',
    //             'varchar' => 'VARCHAR',
    //             'nvarchar' => 'VARCHAR',
    //             'text' => 'TEXT',
    //             'ntext' => 'TEXT',
    //             'date' => 'DATE',
    //             'datetime' => 'TIMESTAMP',
    //             'datetime2' => 'TIMESTAMP',
    //             'smalldatetime' => 'TIMESTAMP',
    //             'time' => 'TIME',
    //             'uniqueidentifier' => 'UUID',
    //             'binary' => 'BYTEA',
    //             'varbinary' => 'BYTEA',
    //             'image' => 'BYTEA',
    //             'xml' => 'TEXT',
    //         ];

    //         // =====================================================
    //         // 3. CONSTRUIR DDL Y CREAR TABLA EN POSTGRES
    //         // =====================================================
    //         $colDefs = [];

    //         foreach ($columnas as $col) {
    //             $sqlType = strtolower($col->DATA_TYPE);
    //             $pgType = $mapTipos[$sqlType] ?? 'TEXT';
    //             $nullable = strtoupper($col->IS_NULLABLE) === 'YES' ? '' : ' NOT NULL';
    //             $colName = strtolower($col->COLUMN_NAME);

    //             // Agregar precisión/longitud donde aplica
    //             if (in_array($sqlType, ['varchar', 'nvarchar', 'char', 'nchar'])) {
    //                 $len = $col->CHARACTER_MAXIMUM_LENGTH;
    //                 $pgType .= ($len && $len > 0) ? "({$len})" : '';
    //             } elseif (in_array($sqlType, ['decimal', 'numeric'])) {
    //                 if ($col->NUMERIC_PRECISION) {
    //                     $pgType .= "({$col->NUMERIC_PRECISION},{$col->NUMERIC_SCALE})";
    //                 }
    //             }

    //             $colDefs[] = "    \"{$colName}\" {$pgType}{$nullable}";
    //         }

    //         $ddl = "CREATE TABLE IF NOT EXISTS \"{$destino}\" (\n"
    //             . implode(",\n", $colDefs) . "\n)";

    //         DB::connection('pgsql')->statement($ddl);

    //         // =====================================================
    //         // 4. MIGRAR DATOS CON PAGINACIÓN ROW_NUMBER()
    //         //    (compatible SQL Server 2008)
    //         // =====================================================
    //         $colNames = array_map(fn($c) => $c->COLUMN_NAME, $columnas);
    //         $colsStr = implode(', ', array_map(fn($c) => '[' . $c . ']', $colNames));
    //         $orderStr = '[' . $colNames[0] . ']';   // primer campo como orden base

    //         $batchSize = 500;
    //         $page = 1;
    //         $filasProcesadas = 0;

    //         while (true) {
    //             $offset = ($page - 1) * $batchSize;
    //             $limit = $offset + $batchSize;

    //             $sql = "
    //             SELECT {$colsStr}
    //             FROM (
    //                 SELECT {$colsStr},
    //                        ROW_NUMBER() OVER (ORDER BY {$orderStr}) AS [rn]
    //                 FROM [{$origen}]
    //             ) AS [paginado]
    //             WHERE [rn] > {$offset} AND [rn] <= {$limit}
    //         ";

    //             $rows = DB::connection('sqlsrv2')->select($sql);

    //             if (empty($rows))
    //                 break;

    //             $data = array_map(function ($row) {
    //                 return array_change_key_case((array) $row, CASE_LOWER);
    //             }, $rows);

    //             foreach (array_chunk($data, 100) as $chunk) {
    //                 DB::connection('pgsql')->table($destino)->insert($chunk);
    //                 $filasProcesadas += count($chunk);
    //             }

    //             $page++;

    //             if (count($rows) < $batchSize)
    //                 break;
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'tabla' => $destino,
    //             'columnas' => count($columnas),
    //             'filas' => $filasProcesadas,
    //             'mensaje' => "Tabla '{$destino}' creada y migrada correctamente",
    //         ]);

    //     } catch (\Throwable $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

}