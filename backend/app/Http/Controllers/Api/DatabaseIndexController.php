<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class DatabaseIndexController extends Controller
{
    /**
     * Definición de los índices a crear.
     * key   => nombre del índice
     * value => [tabla, columnas, columnas include]
     */
    private array $indexes = [
        'IX_KARDEX_EXT_Reportes' => [
            'table' => 'dbo.KARDEX_EXT',
            'columns' => 'ANIO, PERIODO, [PLAN], MATERIA, GRUPO',
            'include' => 'TIPO_EXAMEN, CANCELADO, ESTUDIANTE',
        ],
        'IX_MATERIAS_Reportes' => [
            'table' => 'dbo.MATERIAS',
            'columns' => 'ANIO, PERIODO, [PLAN], CODIGO',
            'include' => 'NIVEL, NOMBRE',
        ],
        'IX_GRUPOS_Reportes' => [
            'table' => 'dbo.GRUPOS',
            'columns' => 'ANIO, PERIODO, MATERIA, GRUPO, DOCENTE',
            'include' => '[PLAN], TIPO, PRIMARIO',
        ],
        'IX_GRUPOS_COMPARTIDOS_Reportes' => [
            'table' => 'dbo.GRUPOS_COMPARTIDOS',
            'columns' => '[PLAN], MATERIA, GRUPO, PRIMARIO',
            'include' => 'COMP, COMPARTIDO, ORDEN',
        ],
        'IX_HORARIOS2_Reportes' => [
            'table' => 'dbo.HORARIOS2',
            'columns' => 'ANIO, PERIODO, TIPO, DOCENTE, MATERIA, GRUPO',
            'include' => 'DIA, HORA, AMBIENTE',
        ],
        'IX_BIOGRAFICOS_CODIGO' => [
            'table' => 'dbo.BIOGRAFICOS',
            'columns' => 'CODIGO',
            'include' => 'APELLIDOS, NOMBRES',
        ],
        'IX_BIOGRAFICOS_EXT_ESTUDIANTE' => [
            'table' => 'dbo.BIOGRAFICOS_EXT',
            'columns' => 'ESTUDIANTE',
            'include' => 'DIR_CELULAR, DIR_E_MAIL',
        ],
    ];

    /**
     * Crea los índices que falten. Los que ya existen se omiten (no da error).
     */
    public function crearIndices(): JsonResponse
    {
        $resultados = [];

        foreach ($this->indexes as $nombreIndice => $def) {
            [$tabla, $tableName] = $this->parseTableName($def['table']);

            try {
                $existe = DB::selectOne(
                    "SELECT 1 AS existe
                     FROM sys.indexes i
                     JOIN sys.tables t ON i.object_id = t.object_id
                     JOIN sys.schemas s ON t.schema_id = s.schema_id
                     WHERE i.name = ? AND t.name = ? AND s.name = ?",
                    [$nombreIndice, $tableName, $tabla]
                );

                if ($existe) {
                    $resultados[] = [
                        'indice' => $nombreIndice,
                        'tabla' => $def['table'],
                        'estado' => 'omitido',
                        'mensaje' => 'El índice ya existe.',
                    ];
                    continue;
                }

                $sql = sprintf(
                    "CREATE NONCLUSTERED INDEX %s ON %s (%s) INCLUDE (%s)",
                    $nombreIndice,
                    $def['table'],
                    $def['columns'],
                    $def['include']
                );

                DB::statement($sql);

                $resultados[] = [
                    'indice' => $nombreIndice,
                    'tabla' => $def['table'],
                    'estado' => 'creado',
                    'mensaje' => 'Índice creado correctamente.',
                ];
            } catch (Throwable $e) {
                Log::error("Error creando índice {$nombreIndice}: " . $e->getMessage());

                $resultados[] = [
                    'indice' => $nombreIndice,
                    'tabla' => $def['table'],
                    'estado' => 'error',
                    'mensaje' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'resultados' => $resultados,
        ]);
    }

    /**
     * Verifica cuáles índices existen y cuáles faltan, sin crear nada.
     */
    public function verificarIndices(): JsonResponse
    {
        $resultados = [];

        foreach ($this->indexes as $nombreIndice => $def) {
            [$tabla, $tableName] = $this->parseTableName($def['table']);

            $existe = DB::selectOne(
                "SELECT 1 AS existe
                 FROM sys.indexes i
                 JOIN sys.tables t ON i.object_id = t.object_id
                 JOIN sys.schemas s ON t.schema_id = s.schema_id
                 WHERE i.name = ? AND t.name = ? AND s.name = ?",
                [$nombreIndice, $tableName, $tabla]
            );

            $resultados[] = [
                'indice' => $nombreIndice,
                'tabla' => $def['table'],
                'existe' => (bool) $existe,
            ];
        }

        return response()->json([
            'success' => true,
            'resultados' => $resultados,
        ]);
    }

    /**
     * Separa 'dbo.KARDEX_EXT' en ['dbo', 'KARDEX_EXT']
     */
    private function parseTableName(string $fullTable): array
    {
        $parts = explode('.', $fullTable);
        return count($parts) === 2 ? $parts : ['dbo', $parts[0]];
    }
}