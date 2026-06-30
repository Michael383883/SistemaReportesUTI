<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Exception;

class MigracionService
{

    /**
     * Tablas permitidas
     */
    private static array $allowedTables = [

        'BIOGRAFICOS',
        'DOCENTES',
        'DOCENTES_2',
        'DOCENTES_TELEFONO',
        'GRUPOS',
        'GRUPOS_COMPARTIDOS',
        'HORARIOS',
        'HORARIOS2',
        'KARDEX_EXT',
        'MATERIAS',
        'NROINSMATGRPNE',
        'PLANES',
        'BIOGRAFICOS_EXT'

    ];

    /**
     * Crear tablas desde SQL Server 2008
     */
    public static function copiarTablas(array $tables)
    {
        $resultados = [];

        foreach ($tables as $table) {

            try {

                $table = strtoupper(trim($table));

                /**
                 * Seguridad anti SQL Injection
                 */
                if (!in_array($table, self::$allowedTables)) {

                    $resultados[] = [
                        'tabla' => $table,
                        'success' => false,
                        'message' => 'Tabla no permitida'
                    ];

                    continue;
                }

                /**
                 * Verificar si ya existe
                 */
                $exists = DB::select("
                    SELECT COUNT(*) as total
                    FROM INFORMATION_SCHEMA.TABLES
                    WHERE TABLE_NAME = ?
                ", [$table]);

                if ($exists[0]->total > 0) {

                    $resultados[] = [
                        'tabla' => $table,
                        'success' => false,
                        'message' => 'La tabla ya existe'
                    ];

                    continue;
                }

                /**
                 * Crear tabla + copiar datos
                 */
                $sql = "

                    SELECT *
                    INTO dbo.[$table]
                    FROM SQL2008.prueba.dbo.[$table]

                ";

                DB::statement($sql);

                $resultados[] = [
                    'tabla' => $table,
                    'success' => true,
                    'message' => 'Tabla copiada correctamente'
                ];

            } catch (Exception $e) {

                $resultados[] = [
                    'tabla' => $table,
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
        }

        return $resultados;
    }



    public static function syncDocentes()
    {
        try {

            $resultado = DB::select("
                EXEC SP_SYNC_DOCENTES
            ");

            return [

                'success' => true,
                'data' => $resultado

            ];

        } catch (Exception $e) {

            return [

                'success' => false,
                'message' => $e->getMessage()

            ];
        }
    }

    public static function syncTable(
        string $tabla,
        string $pk
    ) {
        return DB::select(
            "EXEC dbo.SP_SYNC_TABLA ?, ?",
            [$tabla, $pk]
        );
    }
}