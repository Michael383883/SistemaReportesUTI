<?php

namespace App\Services;

use Carbon\Carbon;

class PeriodoAcademicoService
{
    protected Carbon $fecha;

    protected array $rangos;

    protected string $fallback;

    /**
     * @param Carbon|null $fecha
     * @param string $config Nombre del archivo de configuración
     *
     * Ejemplos:
     *  periodo_academico_regular
     *  periodo_academico_semestral
     */
    public function __construct(
        ?Carbon $fecha = null,
        string $config = 'periodo_academico_regular'
    ) {
        $this->fecha = $fecha ?? Carbon::now();

        $this->rangos = config("{$config}.periodos", []);

        $this->fallback = config("{$config}.fallback_periodo", '2');
    }

    /**
     * Año actual
     */
    public function anioActual(): int
    {
        return (int) $this->fecha->format('Y');
    }

    /**
     * Periodo actual
     */
    public function periodoActual(): string
    {
        $hoyMd = $this->fecha->format('m-d');

        $candidatos = [];

        foreach ($this->rangos as $periodo => $rango) {

            if ($this->dentroDeRango($hoyMd, $rango['inicio'], $rango['fin'])) {
                $candidatos[$periodo] = $rango;
            }

        }

        if (empty($candidatos)) {
            return $this->fallback;
        }

        if (count($candidatos) === 1) {
            return array_key_first($candidatos);
        }

        /**
         * Si existen rangos solapados,
         * se queda con el que inició más recientemente.
         */
        uasort(
            $candidatos,
            fn ($a, $b) => strcmp($b['inicio'], $a['inicio'])
        );

        return array_key_first($candidatos);
    }

    /**
     * Nombre del periodo
     */
    public function nombrePeriodoActual(): string
    {
        $periodo = $this->periodoActual();

        $nombre = $this->rangos[$periodo]['nombre']
            ?? "Periodo {$periodo}";

        return str_replace(
            '{anio}',
            $this->anioActual(),
            $nombre
        );
    }

    /**
     * Obtiene el rango de un periodo
     */
    public function rangoDe(string $periodo): ?array
    {
        return $this->rangos[$periodo] ?? null;
    }

    /**
     * Todos los periodos
     */
    public function todos(): array
    {
        return $this->rangos;
    }

    /**
     * Verifica si la fecha está dentro del rango.
     * Soporta rangos que cruzan el año.
     */
    protected function dentroDeRango(
        string $hoyMd,
        string $inicio,
        string $fin
    ): bool {

        if ($inicio <= $fin) {

            return $hoyMd >= $inicio
                && $hoyMd <= $fin;

        }

        return $hoyMd >= $inicio
            || $hoyMd <= $fin;
    }
}