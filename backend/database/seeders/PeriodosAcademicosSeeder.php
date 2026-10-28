<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PeriodosAcademicosSeeder extends Seeder
{
    /**
     * Valores por defecto = los mismos que estaban hardcodeados en
     * ReporteDocenteController::rangosPeriodos().
     *
     * NOTA: si este insert también falla con SQLSTATE[22007] (bug de
     * conversión nvarchar->datetime en ODBC Driver 17), es un problema
     * del driver/servidor, no de la migración. Pendiente: DBCC FREEPROCCACHE
     * o actualizar a ODBC Driver 18.
     */
    public function run(): void
    {
        $now = now();

        $filas = [
            ['periodo' => '3', 'nombre' => 'Curso de Verano', 'inicio' => '01-05', 'fin' => '02-20'],
            ['periodo' => '1', 'nombre' => 'Semestre I', 'inicio' => '02-10', 'fin' => '06-30'],
            ['periodo' => '4', 'nombre' => 'Curso de Invierno', 'inicio' => '07-01', 'fin' => '08-15'],
            ['periodo' => '2', 'nombre' => 'Semestre II', 'inicio' => '08-05', 'fin' => '12-20'],
        ];

        foreach ($filas as $fila) {
            DB::table('periodos_academicos')->insert([
                'periodo' => $fila['periodo'],
                'nombre' => $fila['nombre'],
                'inicio' => $fila['inicio'],
                'fin' => $fila['fin'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}


