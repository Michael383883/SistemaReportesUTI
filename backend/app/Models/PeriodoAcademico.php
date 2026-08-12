<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PeriodoAcademico extends Model
{
    protected $table = 'periodos_academicos';

    protected $fillable = ['periodo', 'nombre', 'inicio', 'fin', 'actualizado_por'];

    public const CACHE_KEY = 'periodos_academicos.rangos';

    /**
     * Valores de respaldo: EXACTAMENTE el array que antes estaba
     * hardcodeado en ReporteDocenteController::rangosPeriodos().
     * Se usan si la tabla está vacía o si ocurre cualquier error de BD,
     * para que el reporte de docentes nunca se caiga por esto.
     */
    public const DEFAULTS = [
        '3' => ['nombre' => 'Curso de Verano', 'inicio' => '01-05', 'fin' => '02-20'],
        '1' => ['nombre' => 'Semestre I', 'inicio' => '02-10', 'fin' => '06-30'],
        '4' => ['nombre' => 'Curso de Invierno', 'inicio' => '07-01', 'fin' => '08-15'],
        '2' => ['nombre' => 'Semestre II', 'inicio' => '08-05', 'fin' => '12-20'],
    ];

    protected static function booted(): void
    {
        // Cualquier guardado o borrado invalida la caché automáticamente,
        // así el controlador de reportes ve los cambios sin reiniciar nada.
        static::saved(fn() => Cache::forget(self::CACHE_KEY));
        static::deleted(fn() => Cache::forget(self::CACHE_KEY));
    }

    public function actualizadoPor()
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }

    /**
     * Devuelve los rangos en el MISMO formato que usaba el arreglo
     * hardcodeado original:
     *   ['periodo' => ['inicio' => 'MM-DD', 'fin' => 'MM-DD']]
     *
     * Esto es lo que debe llamar ReporteDocenteController::rangosPeriodos().
     */
    public static function obtenerRangos(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addHours(6), function () {
            try {
                $registros = self::all();

                if ($registros->isEmpty()) {
                    return array_map(
                        fn($r) => ['inicio' => $r['inicio'], 'fin' => $r['fin']],
                        self::DEFAULTS
                    );
                }

                return $registros->mapWithKeys(fn($r) => [
                    $r->periodo => ['inicio' => $r->inicio, 'fin' => $r->fin],
                ])->toArray();
            } catch (\Throwable $e) {
                // Ej: la migración todavía no corrió en este entorno.
                return array_map(
                    fn($r) => ['inicio' => $r['inicio'], 'fin' => $r['fin']],
                    self::DEFAULTS
                );
            }
        });
    }
}
