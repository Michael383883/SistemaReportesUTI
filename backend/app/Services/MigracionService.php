<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Exception;

class MigracionService
{
    private const CONEXION_2022 = 'sqlsrv';
    private const LINKED_SERVER = 'SQL2008';
    private const DB_2008 = 'prueba';

    /** Catálogos: reemplazo completo, sin PK, sin MERGE */
    private static array $catalogos = [
        'BIOGRAFICOS',
        'BIOGRAFICOS_EXT',
        'DOCENTES_2',
        'DOCENTES_TELEFONO',
        'MATERIAS',
        'PLANES',
        'GRUPOS_COMPARTIDOS',
        'NROINSMATGRPNE',
    ];

    /** Tablas de semestre SIN merge: se borra y reinserta solo ese ANIO+PERIODO */
    private static array $tablasSemestreReplace = ['HORARIOS2', 'KARDEX_EXT'];

    /** ⚠️ PK sin confirmar aún — correr diagnóstico antes de usar en producción */
    private const GRUPOS_PK = ['ANIO', 'PERIODO', 'PLAN', 'MATERIA', 'GRUPO'];

    /** PK de DOCENTES — confirmar que CODIGO es único antes de usar en producción */
    private const DOCENTES_PK = ['CODIGO'];

    /** Tablas permitidas para carga inicial (histórico completo, una sola vez) */
    private static array $tablasCargaInicial = [
        'GRUPOS' => true,
        'HORARIOS2' => false,
        'KARDEX_EXT' => true,
    ];

    /** Máximo de filas de detalle que devolvemos por respuesta (evita payloads gigantes) */
    private const MAX_DETALLE = 100;

    public static function cargaInicial(array $tablas): array
    {
        $resultados = [];
        foreach ($tablas as $tabla) {
            $tabla = strtoupper(trim($tabla));

            if (!array_key_exists($tabla, self::$tablasCargaInicial)) {
                $resultados[] = self::error($tabla, 'No es una tabla permitida para carga inicial');
                continue;
            }

            $resultados[] = self::cargaInicialTabla($tabla, self::$tablasCargaInicial[$tabla]);
        }
        return $resultados;
    }

    private static function cargaInicialTabla(string $tabla, bool $deduplicar): array
    {
        try {
            $linked = self::LINKED_SERVER . '.' . self::DB_2008 . '.dbo.[' . $tabla . ']';
            $conn = DB::connection(self::CONEXION_2022);

            if (self::tablaExisteEn2022($tabla)) {
                return [
                    'tabla' => $tabla,
                    'success' => false,
                    'message' => 'La tabla ya existe en 2022 — la carga inicial ya se realizó. Usa el endpoint por semestre para seguir actualizando.',
                ];
            }

            $columnas = self::obtenerColumnasDesde2008($tabla);
            if (empty($columnas)) {
                return self::error($tabla, 'No se pudieron obtener columnas vía linked server');
            }

            $colsList = implode(', ', array_map(fn($c) => "[$c]", $columnas));

            if ($deduplicar) {
                $sql = "
                ;WITH Origen AS (
                    SELECT *,
                        ROW_NUMBER() OVER (PARTITION BY $colsList ORDER BY (SELECT NULL)) AS RN
                    FROM $linked
                )
                SELECT $colsList
                INTO dbo.[$tabla]
                FROM Origen
                WHERE RN = 1
            ";
            } else {
                // Copia exacta, duplicados incluidos
                $sql = "SELECT * INTO dbo.[$tabla] FROM $linked";
            }

            $conn->statement($sql);

            $total = $conn->selectOne("SELECT COUNT(*) AS total FROM dbo.[$tabla]")->total;

            return [
                'tabla' => $tabla,
                'success' => true,
                'message' => "Carga inicial completa: {$total} filas" . ($deduplicar ? ' (deduplicadas)' : ' (tal cual, sin deduplicar)'),
                'cambios' => [
                    'insertados' => (int) $total,
                    'nota' => 'Carga inicial: toda la tabla es insert (no hay "antes" que comparar).',
                ],
            ];
        } catch (Exception $e) {
            return self::error($tabla, $e->getMessage());
        }
    }

    /**
     * Igual que obtenerColumnas() pero la separo porque aquí SÍ necesitamos
     * la lista antes de que la tabla exista en 2022 (la carga inicial la crea).
     */
    private static function obtenerColumnasDesde2008(string $tabla): array
    {
        $linkedInfo = self::LINKED_SERVER . '.' . self::DB_2008 . '.INFORMATION_SCHEMA.COLUMNS';
        $rows = DB::connection(self::CONEXION_2022)->select("
        SELECT COLUMN_NAME FROM $linkedInfo WHERE TABLE_NAME = ? ORDER BY ORDINAL_POSITION
    ", [$tabla]);
        return array_map(fn($r) => $r->COLUMN_NAME, $rows);
    }

    /* ==================== CATÁLOGOS ==================== */

    /** Migra UN solo catálogo por nombre */
    public static function sincronizarCatalogo(string $tabla): array
    {
        $tabla = strtoupper(trim($tabla));

        if (!in_array($tabla, self::$catalogos)) {
            return self::error($tabla, 'No es una tabla catálogo permitida');
        }

        return self::syncCatalogoCompleto($tabla);
    }

    /** Migra TODOS los catálogos */
    public static function sincronizarCatalogos(array $tablas): array
    {
        $resultados = [];
        foreach ($tablas as $tabla) {
            $resultados[] = self::sincronizarCatalogo($tabla);
        }
        return $resultados;
    }

    private static function syncCatalogoCompleto(string $tabla): array
    {
        try {
            $linked = self::LINKED_SERVER . '.' . self::DB_2008 . '.dbo.[' . $tabla . ']';
            $conn = DB::connection(self::CONEXION_2022);

            if (!self::tablaExisteEn2022($tabla)) {
                $conn->statement("SELECT * INTO dbo.[$tabla] FROM $linked WHERE 1 = 0");
            }

            // Conteo antes de tocar nada, para poder comparar
            $filasAntes = $conn->selectOne("SELECT COUNT(*) AS n FROM dbo.[$tabla]")->n;
            $filasDespues = 0;

            $conn->transaction(function () use ($conn, $tabla, $linked, &$filasDespues) {
                $conn->statement("TRUNCATE TABLE dbo.[$tabla]");

                // SET NOCOUNT ON evita que el "(N rows affected)" del INSERT aparezca
                // como un resultset vacío antes del SELECT @@ROWCOUNT (error IMSSP:
                // "active result contains no fields" en pdo_sqlsrv).
                $filasDespues = $conn->selectOne(
                    "SET NOCOUNT ON; INSERT INTO dbo.[$tabla] SELECT * FROM $linked; SELECT @@ROWCOUNT AS n;"
                )->n;
            });

            return [
                'tabla' => $tabla,
                'success' => true,
                'message' => 'Reemplazada completa (TRUNCATE + INSERT)',
                'cambios' => [
                    'filas_antes' => (int) $filasAntes,
                    'filas_despues' => (int) $filasDespues,
                    'diferencia' => (int) $filasDespues - (int) $filasAntes,
                    'nota' => 'Es un espejo completo, no hay detalle fila por fila de qué cambió.',
                ],
            ];
        } catch (Exception $e) {
            return self::error($tabla, $e->getMessage());
        }
    }

    /* ==================== GRUPOS (MERGE con DELETE) ==================== */

    public static function sincronizarGrupos(string $anio, string $periodo): array
    {
        try {
            $tabla = 'GRUPOS';
            $pk = self::GRUPOS_PK;
            $linked = self::LINKED_SERVER . '.' . self::DB_2008 . '.dbo.[' . $tabla . ']';

            if (!self::tablaExisteEn2022($tabla)) {
                DB::connection(self::CONEXION_2022)->statement("
                SELECT * INTO dbo.[$tabla] FROM $linked WHERE 1 = 0
            ");
            }

            $columnas = self::obtenerColumnas($tabla);
            if (empty($columnas)) {
                return self::error($tabla, 'No se pudieron obtener columnas vía linked server');
            }

            $colsInsert = implode(', ', array_map(fn($c) => "[$c]", $columnas));
            $colsSrcInsert = implode(', ', array_map(fn($c) => "S.[$c]", $columnas));
            $setUpdate = implode(', ', array_map(
                fn($c) => "T.[$c] = S.[$c]",
                array_filter($columnas, fn($c) => !in_array($c, $pk))
            ));

            $partitionCols = implode(', ', array_map(fn($c) => "[$c]", $pk));
            $onClause = implode(' AND ', array_map(fn($c) => "T.[$c] = S.[$c]", $pk));

            // Columnas de la PK para reportar QUÉ registro cambió (casteadas a NVARCHAR
            // para no pelear con tipos distintos al meterlas en la tabla variable).
            $outputCols = implode(",\n                ", array_map(
                fn($c) => "CAST(ISNULL(inserted.[$c], deleted.[$c]) AS NVARCHAR(100)) AS [$c]",
                $pk
            ));
            $tableVarCols = implode(', ', array_map(fn($c) => "[$c] NVARCHAR(100)", $pk));

            // Todo en un solo batch: las variables de tabla no sobreviven entre
            // llamadas separadas a DB::statement(), así que DECLARE + MERGE + SELECT
            // final tienen que ir juntos y se leen con un único ->select().
            $sql = "
                SET NOCOUNT ON;
                DECLARE @Cambios TABLE (accion NVARCHAR(10), $tableVarCols);

                ;WITH Origen AS (
                    SELECT *,
                        ROW_NUMBER() OVER (PARTITION BY $partitionCols ORDER BY ANIO) AS RN
                    FROM $linked
                    WHERE [ANIO] = ? AND [PERIODO] = ?
                )
                MERGE dbo.[$tabla] AS T
                USING (SELECT * FROM Origen WHERE RN = 1) AS S
                ON $onClause
                WHEN MATCHED THEN
                    UPDATE SET $setUpdate
                WHEN NOT MATCHED BY TARGET THEN
                    INSERT ($colsInsert) VALUES ($colsSrcInsert)
                WHEN NOT MATCHED BY SOURCE AND T.[ANIO] = ? AND T.[PERIODO] = ? THEN
                    DELETE
                OUTPUT
                    \$action,
                    $outputCols
                INTO @Cambios;

                SELECT * FROM @Cambios ORDER BY accion;
            ";

            $filas = DB::connection(self::CONEXION_2022)->select($sql, [$anio, $periodo, $anio, $periodo]);

            $cambios = self::resumirCambios($filas, $pk);

            return [
                'tabla' => $tabla,
                'success' => true,
                'message' => "GRUPOS sincronizada (INSERT/UPDATE/DELETE) para {$anio}-{$periodo}",
                'cambios' => $cambios,
            ];
        } catch (Exception $e) {
            return self::error('GRUPOS', $e->getMessage());
        }
    }

    /* ==================== DOCENTES (MERGE con DELETE) ==================== */

    /** Sincroniza DOCENTES vía MERGE (solo INSERT/UPDATE, sin DELETE por las FK de tablas hijas como CLASIFICACION_DOCENTE) */
    /** Sincroniza DOCENTES vía MERGE completo (INSERT + UPDATE + DELETE).
     *  El 2008 manda: si un docente ya no existe ahí, se borra también en 2022.
     *  Antes de borrar al padre, se eliminan sus hijos huérfanos en
     *  CLASIFICACION_DOCENTE (FK_CLASIFICACION_DOCENTE_DOCENTE) para no romper
     *  la integridad referencial. */
    public static function sincronizarDocentesMerge(): array
    {
        try {
            $tabla = 'DOCENTES';
            $pk = self::DOCENTES_PK;
            $linked = self::LINKED_SERVER . '.' . self::DB_2008 . '.dbo.[' . $tabla . ']';
            $conn = DB::connection(self::CONEXION_2022);

            if (!self::tablaExisteEn2022($tabla)) {
                $conn->statement("SELECT * INTO dbo.[$tabla] FROM $linked WHERE 1 = 0");
            }

            $columnas = self::obtenerColumnas($tabla);
            if (empty($columnas)) {
                return self::error($tabla, 'No se pudieron obtener columnas vía linked server');
            }

            $colsInsert = implode(', ', array_map(fn($c) => "[$c]", $columnas));
            $colsSrcInsert = implode(', ', array_map(fn($c) => "S.[$c]", $columnas));
            $setUpdate = implode(', ', array_map(
                fn($c) => "T.[$c] = S.[$c]",
                array_filter($columnas, fn($c) => !in_array($c, $pk))
            ));

            $partitionCols = implode(', ', array_map(fn($c) => "[$c]", $pk));
            $onClause = implode(' AND ', array_map(fn($c) => "T.[$c] = S.[$c]", $pk));

            $outputCols = implode(",\n                ", array_map(
                fn($c) => "CAST(ISNULL(inserted.[$c], deleted.[$c]) AS NVARCHAR(100)) AS [$c]",
                $pk
            ));
            $tableVarCols = implode(', ', array_map(fn($c) => "[$c] NVARCHAR(100)", $pk));

            $huerfanosEliminados = 0;
            $filas = [];

            $conn->transaction(function () use ($conn, $tabla, $linked, $partitionCols, $colsInsert, $colsSrcInsert, $setUpdate, $onClause, $outputCols, $tableVarCols, &$huerfanosEliminados, &$filas) {
                // 1) Borrar primero los hijos de CLASIFICACION_DOCENTE cuyo docente
                //    ya no existe en el origen (2008). Si no hacemos esto antes,
                //    el DELETE del MERGE de abajo revienta por la FK.
                $huerfanosEliminados = $conn->selectOne("
                SET NOCOUNT ON;
                DELETE CD
                FROM dbo.CLASIFICACION_DOCENTE AS CD
                WHERE NOT EXISTS (
                    SELECT 1 FROM $linked AS S WHERE S.[CODIGO] = CD.[COD_DOCENTE]
                );
                SELECT @@ROWCOUNT AS n;
            ")->n;

                // 2) MERGE completo: ahora sí con DELETE, el 2008 manda.
                $sql = "
                SET NOCOUNT ON;
                DECLARE @Cambios TABLE (accion NVARCHAR(10), $tableVarCols);

                ;WITH Origen AS (
                    SELECT *,
                        ROW_NUMBER() OVER (PARTITION BY $partitionCols ORDER BY (SELECT NULL)) AS RN
                    FROM $linked
                )
                MERGE dbo.[$tabla] AS T
                USING (SELECT * FROM Origen WHERE RN = 1) AS S
                ON $onClause
                WHEN MATCHED THEN
                    UPDATE SET $setUpdate
                WHEN NOT MATCHED BY TARGET THEN
                    INSERT ($colsInsert) VALUES ($colsSrcInsert)
                WHEN NOT MATCHED BY SOURCE THEN
                    DELETE
                OUTPUT
                    \$action,
                    $outputCols
                INTO @Cambios;

                SELECT * FROM @Cambios ORDER BY accion;
            ";

                $filas = $conn->select($sql);
            });

            $cambios = self::resumirCambios($filas, $pk);
            $cambios['huerfanos_eliminados'] = (int) $huerfanosEliminados;
            if ($huerfanosEliminados > 0) {
                $cambios['nota_huerfanos'] = "Se eliminaron {$huerfanosEliminados} filas de CLASIFICACION_DOCENTE porque su docente ya no existe en el 2008.";
            }

            return [
                'tabla' => $tabla,
                'success' => true,
                'message' => 'DOCENTES sincronizada vía MERGE (INSERT + UPDATE + DELETE) — espejo exacto del 2008',
                'cambios' => $cambios,
            ];
        } catch (Exception $e) {
            return self::error('DOCENTES', $e->getMessage());
        }
    }

    /**
     * Convierte las filas del OUTPUT del MERGE en { insertados, actualizados,
     * eliminados, detalle[] }. El detalle se acota a MAX_DETALLE filas para
     * no reventar el payload en semestres con muchos cambios.
     */
    private static function resumirCambios(array $filas, array $columnasDetalle): array
    {
        $cambios = ['insertados' => 0, 'actualizados' => 0, 'eliminados' => 0, 'detalle' => []];
        $map = ['INSERT' => 'insertados', 'UPDATE' => 'actualizados', 'DELETE' => 'eliminados'];

        foreach ($filas as $fila) {
            $accion = $fila->accion ?? null;
            if (isset($map[$accion])) {
                $cambios[$map[$accion]]++;
            }

            if (count($cambios['detalle']) < self::MAX_DETALLE) {
                $item = ['accion' => $accion];
                foreach ($columnasDetalle as $col) {
                    $item[strtolower($col)] = $fila->$col ?? null;
                }
                $cambios['detalle'][] = $item;
            }
        }

        $total = count($filas);
        if ($total > self::MAX_DETALLE) {
            $cambios['nota'] = 'Se muestran los primeros ' . self::MAX_DETALLE . " cambios de {$total} en total.";
        }

        return $cambios;
    }

    /* ==================== HORARIOS2 / KARDEX_EXT (DELETE + INSERT scoped) ==================== */

    public static function sincronizarPorSemestre(array $tablas, string $anio, string $periodo): array
    {
        $resultados = [];
        foreach ($tablas as $tabla) {
            $tabla = strtoupper(trim($tabla));

            if (!in_array($tabla, self::$tablasSemestreReplace)) {
                $resultados[] = self::error($tabla, 'No es una tabla permitida para este endpoint');
                continue;
            }

            $resultados[] = self::syncSemestreReplace($tabla, $anio, $periodo);
        }
        return $resultados;
    }

    private static function syncSemestreReplace(string $tabla, string $anio, string $periodo): array
    {
        try {
            $linked = self::LINKED_SERVER . '.' . self::DB_2008 . '.dbo.[' . $tabla . ']';
            $conn = DB::connection(self::CONEXION_2022);

            if (!self::tablaExisteEn2022($tabla)) {
                $conn->statement("SELECT * INTO dbo.[$tabla] FROM $linked WHERE 1 = 0");
            }

            $eliminados = 0;
            $insertados = 0;

            $conn->transaction(function () use ($conn, $tabla, $linked, $anio, $periodo, &$eliminados, &$insertados) {
                // SET NOCOUNT ON evita que el "(N rows affected)" aparezca como un
                // resultset vacío antes del SELECT @@ROWCOUNT (error IMSSP en pdo_sqlsrv).
                $eliminados = $conn->selectOne(
                    "SET NOCOUNT ON; DELETE FROM dbo.[$tabla] WHERE [ANIO] = ? AND [PERIODO] = ?; SELECT @@ROWCOUNT AS n;",
                    [$anio, $periodo]
                )->n;

                $insertados = $conn->selectOne(
                    "SET NOCOUNT ON; INSERT INTO dbo.[$tabla] SELECT * FROM $linked WHERE [ANIO] = ? AND [PERIODO] = ?; SELECT @@ROWCOUNT AS n;",
                    [$anio, $periodo]
                )->n;
            });

            return [
                'tabla' => $tabla,
                'success' => true,
                'message' => "Reemplazada para {$anio}-{$periodo} (DELETE + INSERT)",
                'cambios' => [
                    'eliminados' => (int) $eliminados,
                    'insertados' => (int) $insertados,
                    'nota' => 'Esta tabla se reemplaza completa para el periodo (no tiene PK confiable), por eso no aplica "actualizados" fila por fila.',
                ],
            ];
        } catch (Exception $e) {
            return self::error($tabla, $e->getMessage());
        }
    }

    /* ==================== Helpers ==================== */

    private static function error(string $tabla, string $msg): array
    {
        return ['tabla' => $tabla, 'success' => false, 'message' => $msg];
    }

    private static function tablaExisteEn2022(string $tabla): bool
    {
        $res = DB::connection(self::CONEXION_2022)->select("
            SELECT COUNT(*) as total FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = ?
        ", [$tabla]);
        return $res[0]->total > 0;
    }

    private static function obtenerColumnas(string $tabla): array
    {
        $linkedInfo = self::LINKED_SERVER . '.' . self::DB_2008 . '.INFORMATION_SCHEMA.COLUMNS';
        $rows = DB::connection(self::CONEXION_2022)->select("
            SELECT COLUMN_NAME FROM $linkedInfo WHERE TABLE_NAME = ? ORDER BY ORDINAL_POSITION
        ", [$tabla]);
        return array_map(fn($r) => $r->COLUMN_NAME, $rows);
    }

    public static function syncDocentes()
    {
        try {
            return ['success' => true, 'data' => DB::select("EXEC SP_SYNC_DOCENTES")];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public static function syncTable(string $tabla, string $pk)
    {
        return DB::select("EXEC dbo.SP_SYNC_TABLA ?, ?", [$tabla, $pk]);
    }
}