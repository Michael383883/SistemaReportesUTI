<?php

namespace App\Http\Controllers\Api;

use App\Models\Docente;
use App\Models\DocenteTelefono;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class SecretariaController extends Controller
{
    /**
     * Obtener todos los docentes con sus datos básicos
     * GET /api/secretaria/docentes
     */
    public function getDocentes(Request $request)
    {
        try {
            // Construir query base
            $query = Docente::query()
                ->leftJoin('docentes_telefono', 'docentes.codigo', '=', 'docentes_telefono.docente')
                ->select(
                    'docentes.codigo as docente',
                    'docentes.apellidos',
                    'docentes.nombres',
                    'docentes.ci',
                    'docentes.fecha_nac',
                    'docentes.titulo as grado_academico',
                    'docentes_telefono.unidad',
                    'docentes_telefono.fijo_1',
                    'docentes_telefono.fijo_2',
                    'docentes_telefono.celular_1',
                    'docentes_telefono.celular_2',
                    'docentes_telefono.email',
                    'docentes_telefono.email_institucional',
                    'docentes_telefono.email_gsuite'
                );

            // Aplicar filtros si existen
            if ($request->filled('busqueda')) {
                $busqueda = $request->busqueda;
                $query->where(function ($q) use ($busqueda) {
                    $q->where('docentes.apellidos', 'ILIKE', "%{$busqueda}%")
                        ->orWhere('docentes.nombres', 'ILIKE', "%{$busqueda}%")
                        ->orWhere('docentes.ci', 'ILIKE', "%{$busqueda}%")
                        ->orWhere('docentes_telefono.unidad', 'ILIKE', "%{$busqueda}%");
                });
            }

            if ($request->filled('unidad')) {
                $query->where('docentes_telefono.unidad', $request->unidad);
            }

            $docentes = $query->orderBy('docentes.apellidos')
                ->get()
                ->map(function ($docente) {
                    // Calcular horas totales de carga horaria
                    $horasTotal = $this->calcularHorasTotales($docente->docente);

                    // Formatear el nombre completo
                    $nombreCompleto = trim($docente->apellidos . ' ' . $docente->nombres);

                    return [
                        'docente' => $docente->docente,
                        'nombre_docente' => strtoupper($nombreCompleto),
                        'ci' => $docente->ci,
                        'grado_academico' => $this->formatearGrado($docente->grado_academico),
                        'unidad' => $docente->unidad ?? '',
                        'fijo_1' => $docente->fijo_1 ?? '',
                        'celular_1' => $docente->celular_1 ?? '',
                        'email' => $docente->email ?? $docente->email ?? '',
                        'email_institucional' => $docente->email_institucional ?? $docente->email_institucional ?? '',
                        'email_gsuite' => $docente->email_gsuite ?? $docente->email_gsuite ?? '',
                        'horas_total' => $horasTotal,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $docentes
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener docentes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener detalle de un docente específico
     * GET /api/secretaria/docentes/{codigo}
     */
    public function getDocente($codigo)
    {
        try {
            $docente = Docente::where('codigo', $codigo)
                ->leftJoin('docentes_telefono', 'docentes.codigo', '=', 'docentes_telefono.docente')
                ->select(
                    'docentes.codigo as docente',
                    'docentes.apellidos',
                    'docentes.nombres',
                    'docentes.ci',
                    'docentes.fecha_nac',
                    'docentes.fecha_nombramiento',
                    'docentes.titulo as grado_academico',
                    'docentes.sexo',
                    'docentes_telefono.unidad',
                    'docentes_telefono.fijo_1',
                    'docentes_telefono.fijo_2',
                    'docentes_telefono.celular_1',
                    'docentes_telefono.celular_2',
                    'docentes_telefono.email',
                    'docentes_telefono.email_institucional',
                    'docentes_telefono.email_gsuite',
                    'docentes_telefono.direccion',

                )
                ->first();

            if (!$docente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Docente no encontrado'
                ], 404);
            }

            // Obtener materias/horarios del docente
            $materias = $this->getMateriasDocente($codigo);
            $horasTotal = $this->calcularHorasTotales($codigo);

            $nombreCompleto = trim($docente->apellidos . ' ' . $docente->nombres);

            return response()->json([
                'success' => true,
                'data' => [
                    'docente' => $docente->docente,
                    'nombre_docente' => strtoupper($nombreCompleto),
                    'ci' => $docente->ci,
                    'grado_academico' => $this->formatearGrado($docente->grado_academico),
                    'unidad' => $docente->unidad ?? '',
                    'fijo_1' => $docente->fijo_1 ?? '',
                    'fijo_2' => $docente->fijo_2 ?? '',
                    'celular_1' => $docente->celular_1 ?? '',
                    'celular_2' => $docente->celular_2 ?? '',
                    'email' => $docente->email ?? $docente->email ?? '',
                    'horas_total' => $horasTotal,
                    'materias' => $materias,
                    'fecha_nac' => $docente->fecha_nac,
                    'fecha_nombramiento' => $docente->fecha_nombramiento,
                    'sexo' => $docente->sexo,
                    'direccion' => $docente->direccion ?? '',
                    'email_institucional' => $docente->email_institucional ?? $docente->email_institucional ?? '',
                    'email_gsuite' => $docente->email_gsuite ?? $docente->email_gsuite ?? '',
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener docente: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener materias/horarios de un docente
     * GET /api/secretaria/docentes/{codigo}/horario
     */
    public function getHorarioDocente($codigo)
    {
        try {
            $materias = $this->getMateriasDocente($codigo);
            $horasTotal = $this->calcularHorasTotales($codigo);

            return response()->json([
                'success' => true,
                'data' => [
                    'docente' => $codigo,
                    'horas_total' => $horasTotal,
                    'materias' => $materias
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener horario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Método privado para obtener las materias de un docente
     * Esta es una implementación base - deberás adaptarla según tu estructura de tablas
     */
    private function getMateriasDocente($codigoDocente)
    {
        try {
            // Asumiendo que tienes una tabla de carga horaria o materias por docente
            // Deberás ajustar esto según tu estructura real de base de datos

            // Ejemplo con una posible tabla de carga_docente
            $materias = DB::table('carga_docente')
                ->join('materias', 'carga_docente.materia_id', '=', 'materias.id')
                ->join('grupos', 'carga_docente.grupo_id', '=', 'grupos.id')
                ->join('horarios', 'carga_docente.horario_id', '=', 'horarios.id')
                ->where('carga_docente.docente_codigo', $codigoDocente)
                ->where('carga_docente.gestion_id', $this->getGestionActualId())
                ->select(
                    'materias.nombre',
                    'grupos.nombre as grupo',
                    'carga_docente.horas',
                    'horarios.dia',
                    'horarios.hora_inicio',
                    'horarios.hora_fin',
                    'horarios.aula'
                )
                ->get();

            return $materias;

        } catch (\Exception $e) {
            // Si no existe la tabla, retornar array vacío
            return [];
        }
    }

    /**
     * Calcular horas totales de carga horaria
     */
    private function calcularHorasTotales($codigoDocente)
    {
        try {
            // Usando el endpoint de horario que ya tienes
            $total = DB::table('carga_docente')
                ->where('docente_codigo', $codigoDocente)
                ->where('gestion_id', $this->getGestionActualId())
                ->where('comp', '!=', 1) // No contar las compartidas recibidas
                ->sum('horas');

            return $total ?? 0;

        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Obtener ID de la gestión actual
     */
    private function getGestionActualId()
    {
        try {
            $gestion = DB::table('gestiones')
                ->where('activo', true)
                ->first();

            return $gestion ? $gestion->id : 1;
        } catch (\Exception $e) {
            return 1;
        }
    }

    /**
     * Formatear grado académico
     */
    private function formatearGrado($titulo)
    {
        $titulo = trim($titulo);

        $grados = [
            'DR.' => 'PhD',
            'DR' => 'PhD',
            'PHD' => 'PhD',
            'MSC.' => 'Magister',
            'MSC' => 'Magister',
            'M.SC.' => 'Magister',
            'MGTR.' => 'Magister',
            'LIC.' => 'Licenciado',
            'LIC' => 'Licenciado',
            'ING.' => 'Ingeniero',
            'ING' => 'Ingeniero',
            'ECO.' => 'Licenciado',
            'ECO' => 'Licenciado',
            'ARQ.' => 'Arquitecto',
        ];

        $tituloUpper = strtoupper($titulo);

        if (isset($grados[$tituloUpper])) {
            return $grados[$tituloUpper];
        }

        // Si no coincide con ningún formato predefinido
        return $titulo ?: 'Sin especificar';
    }





    public function getDashboardKPIs()
    {
        try {
            $totalDocentes = Docente::count();

            // Docentes con carga horaria (activos)
            $docentesActivos = DB::table('carga_docente')
                ->distinct('docente_codigo')
                ->where('gestion_id', $this->getGestionActualId())
                ->count();

            // Docentes sin carga
            $docentesSinCarga = $totalDocentes - $docentesActivos;

            // Horas promedio
            $horasPromedio = DB::table('carga_docente')
                ->where('gestion_id', $this->getGestionActualId())
                ->where('comp', '!=', 1)
                ->select(DB::raw('AVG(horas) as promedio'))
                ->first()->promedio ?? 0;

            // Por unidad
            $porUnidad = DB::table('docentes')
                ->leftJoin('docentes_telefono', 'docentes.codigo', '=', 'docentes_telefono.docente')
                ->select('docentes_telefono.unidad', DB::raw('COUNT(*) as cantidad'))
                ->whereNotNull('docentes_telefono.unidad')
                ->where('docentes_telefono.unidad', '!=', '')
                ->groupBy('docentes_telefono.unidad')
                ->get()
                ->map(function ($item) {
                    return [
                        'unidad' => $item->unidad,
                        'cantidad' => $item->cantidad,
                        'horasPromedio' => 0 // Calcular si es necesario
                    ];
                });

            // Por grado
            $porGrado = DB::table('docentes')
                ->select('titulo', DB::raw('COUNT(*) as cantidad'))
                ->whereNotNull('titulo')
                ->where('titulo', '!=', '..')
                ->groupBy('titulo')
                ->get()
                ->map(function ($item) {
                    $grado = $this->formatearGrado($item->titulo);
                    $colores = [
                        'Licenciado' => '#14b8a6',
                        'Magister' => '#3b82f6',
                        'PhD' => '#8b5cf6',
                        'Ingeniero' => '#f59e0b',
                    ];
                    return [
                        'grado' => $grado,
                        'cantidad' => $item->cantidad,
                        'color' => $colores[$grado] ?? '#64748b'
                    ];
                });

            // Carga horaria por rangos
            $cargaHoraria = $this->getDistribucionCargaHoraria();

            // Docentes recientes
            $docentesRecientes = Docente::orderBy('created_at', 'desc')
                ->take(5)
                ->get()
                ->map(function ($docente) {
                    return [
                        'codigo' => $docente->codigo,
                        'nombre' => trim($docente->apellidos . ' ' . $docente->nombres),
                        'grado' => $this->formatearGrado($docente->titulo),
                        'unidad' => $docente->telefono->unidad ?? 'Sin unidad',
                        'horas' => $this->calcularHorasTotales($docente->codigo),
                        'fecha' => $docente->created_at?->format('Y-m-d') ?? date('Y-m-d')
                    ];
                });

            // Alertas
            $alertas = [];
            if ($docentesSinCarga > 0) {
                $alertas[] = [
                    'id' => 1,
                    'tipo' => 'warning',
                    'mensaje' => "$docentesSinCarga docentes sin carga horaria asignada este período",
                    'accion' => '/secretaria/docentes?estado=sin-carga'
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'totalDocentes' => $totalDocentes,
                    'docentesActivos' => $docentesActivos,
                    'docentesSinCarga' => $docentesSinCarga,
                    'horasPromedio' => round($horasPromedio, 1),
                    'porUnidad' => $porUnidad,
                    'porGrado' => $porGrado,
                    'cargaHoraria' => $cargaHoraria,
                    'docentesRecientes' => $docentesRecientes,
                    'alertas' => $alertas
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en dashboard KPIs: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener KPIs: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getDistribucionCargaHoraria()
    {
        try {
            $docentes = Docente::all();
            $distribucion = [
                ['rango' => '0h', 'cantidad' => 0],
                ['rango' => '1-10h', 'cantidad' => 0],
                ['rango' => '11-20h', 'cantidad' => 0],
                ['rango' => '21-30h', 'cantidad' => 0],
                ['rango' => '31-40h', 'cantidad' => 0],
                ['rango' => '40h+', 'cantidad' => 0],
            ];

            foreach ($docentes as $docente) {
                $horas = $this->calcularHorasTotales($docente->codigo);

                if ($horas == 0)
                    $distribucion[0]['cantidad']++;
                elseif ($horas <= 10)
                    $distribucion[1]['cantidad']++;
                elseif ($horas <= 20)
                    $distribucion[2]['cantidad']++;
                elseif ($horas <= 30)
                    $distribucion[3]['cantidad']++;
                elseif ($horas <= 40)
                    $distribucion[4]['cantidad']++;
                else
                    $distribucion[5]['cantidad']++;
            }

            return $distribucion;
        } catch (\Exception $e) {
            return [];
        }
    }
}