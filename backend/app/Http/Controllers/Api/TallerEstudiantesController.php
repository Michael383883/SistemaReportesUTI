<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\PeriodoAcademicoService;

class TallerEstudiantesController extends Controller
{
    private array $materiasTaller = [
        '1304048', // TALLER DE TITULACION
        '1302057', // TALLER
        '1301054', // TALLER II
        '1301170', // MODALIDADES DE TITULACION
        '1302212', // TALLER DE TITULACION
    ];

    protected PeriodoAcademicoService $periodo;

    public function __construct()
    {
        $this->periodo = new PeriodoAcademicoService(
            null,
            'periodo_academico_semestral'
        );
    }

    public function index(Request $request): JsonResponse
    {
        $anio = $request->query('anio') ?: $this->periodo->anioActual();
        $periodoActual = $request->query('periodo') ?: $this->periodo->periodoActual();

        try {
            $estudiantes = $this->consultaBase($anio, $periodoActual)
                ->orderBy('GRUPOS.PLAN')
                ->orderBy('GRUPOS.MATERIA')
                ->orderBy('ESTUDIANTE')
                ->get();

            return response()->json([
                'success' => true,
                'anio' => $anio,
                'periodo' => $periodoActual,
                'automatico' => !$request->query('anio') && !$request->query('periodo'),
                'total' => $estudiantes->count(),
                'data' => $estudiantes
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al obtener estudiantes de talleres: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ocurrio un error al consultar los estudiantes de talleres.',
            ], 500);
        }
    }

    public function materia(Request $request, string $materia): JsonResponse
    {
        $anio = $request->query('anio') ?: $this->periodo->anioActual();
        $periodoActual = $request->query('periodo') ?: $this->periodo->periodoActual();

        try {
            $estudiantes = $this->consultaBase($anio, $periodoActual, $materia)
                ->orderBy('ESTUDIANTE')
                ->get();

            return response()->json([
                'success' => true,
                'materia' => $materia,
                'anio' => $anio,
                'periodo' => $periodoActual,
                'total' => $estudiantes->count(),
                'data' => $estudiantes
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al obtener estudiantes de la materia ' . $materia . ': ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Ocurrio un error al consultar los estudiantes de la materia.',
            ], 500);
        }
    }

    /**
     * Query base compartida entre index() y materia(), para no repetir joins.
     */
    private function consultaBase($anio, $periodoActual, ?string $materia = null)
    {
        $query = DB::connection('sqlsrv')
            ->table('KARDEX_EXT')
            ->join('GRUPOS', function ($join) {
                $join->on('KARDEX_EXT.ANIO', '=', 'GRUPOS.ANIO')
                    ->on('KARDEX_EXT.PERIODO', '=', 'GRUPOS.PERIODO')
                    ->on('KARDEX_EXT.PLAN', '=', 'GRUPOS.PLAN')
                    ->on('KARDEX_EXT.MATERIA', '=', 'GRUPOS.MATERIA')
                    ->on('KARDEX_EXT.GRUPO', '=', 'GRUPOS.GRUPO');
            })
            ->join('BIOGRAFICOS', 'BIOGRAFICOS.CODIGO', '=', 'KARDEX_EXT.ESTUDIANTE')
            ->leftJoin(
                'BIOGRAFICOS_EXT',
                'BIOGRAFICOS_EXT.ESTUDIANTE',
                '=',
                'BIOGRAFICOS.CODIGO'
            )
            ->join('DOCENTES', 'DOCENTES.CODIGO', '=', 'GRUPOS.DOCENTE')
            ->join('MATERIAS', function ($join) {
                $join->on('GRUPOS.ANIO', '=', 'MATERIAS.ANIO')
                    ->on('GRUPOS.PERIODO', '=', 'MATERIAS.PERIODO')
                    ->on('GRUPOS.PLAN', '=', 'MATERIAS.PLAN')
                    ->on('GRUPOS.MATERIA', '=', 'MATERIAS.CODIGO');
            })
            ->select([
                'GRUPOS.ANIO',
                'GRUPOS.PERIODO',
                'GRUPOS.PLAN',
                'GRUPOS.MATERIA',
                'MATERIAS.NOMBRE as MATERIA_NOMBRE',
                'GRUPOS.GRUPO',
                'BIOGRAFICOS.CODIGO as CODIGO_ESTUDIANTE',
                DB::raw("BIOGRAFICOS.APELLIDOS + ' ' + BIOGRAFICOS.NOMBRES as ESTUDIANTE"),
                'DOCENTES.CODIGO as CODIGO_DOCENTE',
                DB::raw("DOCENTES.APELLIDOS + ' ' + DOCENTES.NOMBRES as DOCENTE"),
                DB::raw("ISNULL(NULLIF(BIOGRAFICOS_EXT.DIR_CELULAR,''), 'Sin teléfono') as CELULAR"),
                DB::raw("ISNULL(NULLIF(BIOGRAFICOS_EXT.DIR_E_MAIL,''), 'Sin correo') as CORREO"),
                'KARDEX_EXT.NOTA_FINAL'
            ])
            ->where('KARDEX_EXT.ANIO', $anio)
            ->where('KARDEX_EXT.PERIODO', $periodoActual)
            ->whereNull('KARDEX_EXT.CANCELADO')
            ->whereIn('KARDEX_EXT.TIPO_EXAMEN', ['N', 'E']);

        if ($materia) {
            $query->where('KARDEX_EXT.MATERIA', $materia);
        } else {
            $query->whereIn('KARDEX_EXT.MATERIA', $this->materiasTaller);
        }

        return $query;
    }
}