<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PeriodoAcademico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PeriodoAcademicoController extends Controller
{
    private const ORDEN_ACADEMICO = ['1' => 1, '4' => 2, '2' => 3, '3' => 4];
    private const PATRON_MES_DIA = '/^(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/';

    /**
     * Lista los 4 periodos académicos en su orden académico real
     * (1 -> Invierno -> 2 -> Verano), junto con quién los modificó por última vez.
     */
    public function index()
    {
        $periodos = PeriodoAcademico::with('actualizadoPor:id,name')
            ->get()
            ->sortBy(fn($p) => self::ORDEN_ACADEMICO[$p->periodo] ?? 99)
            ->values();

        return response()->json([
            'success' => true,
            'periodos' => $periodos,
        ]);
    }

    /**
     * Actualiza los 4 periodos en un solo request (un único formulario,
     * un único botón "Guardar" en el frontend).
     *
     * Espera: { periodos: [{ id, inicio: 'MM-DD', fin: 'MM-DD', nombre? }, ...] }
     */
    public function actualizarMasivo(Request $request)
    {
        $data = $request->validate([
            'periodos' => 'required|array|min:1|max:4',
            'periodos.*.id' => 'required|integer|exists:periodos_academicos,id',
            'periodos.*.inicio' => ['required', 'regex:' . self::PATRON_MES_DIA],
            'periodos.*.fin' => ['required', 'regex:' . self::PATRON_MES_DIA],
            'periodos.*.nombre' => 'nullable|string|max:40',
        ]);

        // Validación de negocio: dentro de cada periodo, inicio debe ser
        // anterior a fin (comparación de strings 'MM-DD' funciona porque
        // están rellenados con ceros a la izquierda).
        foreach ($data['periodos'] as $fila) {
            if ($fila['inicio'] >= $fila['fin']) {
                return response()->json([
                    'success' => false,
                    'message' => "Periodo id {$fila['id']}: la fecha de inicio ({$fila['inicio']}) debe ser anterior a la fecha de fin ({$fila['fin']}).",
                ], 422);
            }
        }

        DB::transaction(function () use ($data, $request) {
            foreach ($data['periodos'] as $fila) {
                $periodo = PeriodoAcademico::findOrFail($fila['id']);
                $periodo->inicio = $fila['inicio'];
                $periodo->fin = $fila['fin'];
                if (!empty($fila['nombre'])) {
                    $periodo->nombre = $fila['nombre'];
                }
                $periodo->actualizado_por = $request->user()?->id;
                $periodo->save(); // dispara el evento 'saved' -> limpia la caché
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Rangos de periodos académicos actualizados correctamente.',
            'periodos' => PeriodoAcademico::with('actualizadoPor:id,name')->get(),
        ]);
    }

    /**
     * Restaura los 4 periodos a los valores originales (los que estaban
     * hardcodeados en ReporteDocenteController antes de este cambio).
     */
    public function restaurarValoresPredeterminados(Request $request)
    {
        foreach (PeriodoAcademico::DEFAULTS as $periodo => $rango) {
            PeriodoAcademico::updateOrCreate(
                ['periodo' => $periodo],
                [
                    'nombre' => $rango['nombre'],
                    'inicio' => $rango['inicio'],
                    'fin' => $rango['fin'],
                    'actualizado_por' => $request->user()?->id,
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Valores restaurados a los predeterminados.',
            'periodos' => PeriodoAcademico::with('actualizadoPor:id,name')->get(),
        ]);
    }
}
