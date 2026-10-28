<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PeriodoAcademico extends Model
{
    protected $table = 'periodos_academicos';

    protected $fillable = ['periodo', 'nombre', 'inicio', 'fin', 'actualizado_por'];

    protected $casts = [
        'bloqueado' => 'boolean',
        'bloqueado_anio' => 'integer',
        'bloqueado_en' => 'datetime',
    ];

    public const CACHE_KEY = 'periodos_academicos.rangos';

    public const DEFAULTS = [
        '3' => ['nombre' => 'Curso de Verano', 'inicio' => '01-05', 'fin' => '02-20'],
        '1' => ['nombre' => 'Semestre I', 'inicio' => '02-10', 'fin' => '06-30'],
        '4' => ['nombre' => 'Curso de Invierno', 'inicio' => '07-01', 'fin' => '08-15'],
        '2' => ['nombre' => 'Semestre II', 'inicio' => '08-05', 'fin' => '12-20'],
    ];

    protected static function booted(): void
    {
        static::saved(fn() => Cache::forget(self::CACHE_KEY));
        static::deleted(fn() => Cache::forget(self::CACHE_KEY));
    }

    public function actualizadoPor()
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }

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
                return array_map(
                    fn($r) => ['inicio' => $r['inicio'], 'fin' => $r['fin']],
                    self::DEFAULTS
                );
            }
        });
    }

    /**
     * Devuelve la lista de combinaciones "anio-periodo" bloqueadas
     * manualmente por el admin. Solo afecta la gestión específica
     * (bloqueado_anio), no todo el histórico con ese periodo.
     */
    public static function bloqueosActivos(): array
    {
        return self::query()
            ->where('bloqueado', true)
            ->whereNotNull('bloqueado_anio')
            ->get()
            ->map(fn($p) => "{$p->bloqueado_anio}-{$p->periodo}")
            ->values()
            ->toArray();
    }

    public function bloqueadoPor()
    {
        return $this->belongsTo(\App\Models\User::class, 'bloqueado_por');
    }
}