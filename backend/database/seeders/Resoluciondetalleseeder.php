<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ResolucionDetalleSeeder extends Seeder
{
    /**
     * IMPORTANTE: este seeder depende de que RESOLUCIONES_PDF ya tenga
     * datos (correr primero ResolucionPdfSeeder). Toma los ID_RESOLUCION
     * existentes y les reparte ~2 detalles a cada una (20 resoluciones x
     * 2 = 40 filas en RESOLUCION_DETALLE), tomando los valores de
     * COD_DOCENTE/COD_PLAN/COD_MATERIA/GRUPO/TIPO de un pool de ejemplo
     * parecido a datos reales.
     */
    public function run(): void
    {
        $idsResolucion = DB::table('RESOLUCIONES_PDF')
            ->orderBy('ID_RESOLUCION')
            ->pluck('ID_RESOLUCION')
            ->toArray();

        if (empty($idsResolucion)) {
            $this->command->warn('No hay resoluciones en RESOLUCIONES_PDF. Corre ResolucionPdfSeeder primero.');
            return;
        }

        // Pool de combinaciones docente/plan/materia/grupo/tipo, inspirado
        // en los datos reales que compartiste.
        $pool = [
            ['docente' => '201000113', 'plan' => '125091', 'materia' => '1301143', 'grupo' => '07', 'tipo' => 'N'],
            ['docente' => '201000113', 'plan' => '126091', 'materia' => '1302171', 'grupo' => '57', 'tipo' => 'N'],
            ['docente' => '201000113', 'plan' => '109401', 'materia' => '1301033', 'grupo' => '01', 'tipo' => 'N'],
            ['docente' => '200400011', 'plan' => '089801', 'materia' => '1302008', 'grupo' => '03', 'tipo' => 'N'],
            ['docente' => '200400011', 'plan' => '126091', 'materia' => '1302008', 'grupo' => '03', 'tipo' => 'N'],
            ['docente' => '200400011', 'plan' => '109401', 'materia' => '1301027', 'grupo' => '20', 'tipo' => 'N'],
            ['docente' => '200400011', 'plan' => '125091', 'materia' => '1301144', 'grupo' => '40', 'tipo' => 'N'],
            ['docente' => '198500123', 'plan' => '125091', 'materia' => '1301143', 'grupo' => '07', 'tipo' => 'N'],
            ['docente' => '198500123', 'plan' => '126091', 'materia' => '1302171', 'grupo' => '57', 'tipo' => 'N'],
            ['docente' => '199200456', 'plan' => '109401', 'materia' => '1301027', 'grupo' => '20', 'tipo' => 'N'],
        ];

        // Combinaciones posibles de TIPO_INGRESO / OBSERVACION, igual
        // que en el ejemplo (a veces ambos NULL, a veces solo uno).
        $extras = [
            ['tipo_ingreso' => null,          'observacion' => null],
            ['tipo_ingreso' => 'COMPARTIDO',  'observacion' => null],
            ['tipo_ingreso' => null,          'observacion' => 'TITULAR'],
            ['tipo_ingreso' => null,          'observacion' => 'TEMPORAL'],
            ['tipo_ingreso' => 'COMPARTIDO',  'observacion' => 'TEMPORAL'],
            ['tipo_ingreso' => null,          'observacion' => 'EXAMEN SUFICIENCIA'],
        ];

        $filas = [];
        $detallesPorResolucion = 2; // 20 resoluciones x 2 = 40 filas

        foreach ($idsResolucion as $idResolucion) {
            for ($j = 0; $j < $detallesPorResolucion; $j++) {
                $base  = $pool[array_rand($pool)];
                $extra = $extras[array_rand($extras)];

                $filas[] = [
                    'ID_RESOLUCION' => $idResolucion,
                    'COD_DOCENTE'   => $base['docente'],
                    'COD_PLAN'      => $base['plan'],
                    'COD_MATERIA'   => $base['materia'],
                    'GRUPO'         => $base['grupo'],
                    'TIPO'          => $base['tipo'],
                    'TIPO_INGRESO'  => $extra['tipo_ingreso'],
                    'OBSERVACION'   => $extra['observacion'],
                ];
            }
        }

        // Insertamos en bloques de 20 para no mandar un INSERT gigante.
        foreach (array_chunk($filas, 20) as $bloque) {
            DB::table('RESOLUCION_DETALLE')->insert($bloque);
        }

        $this->command->info(count($filas) . ' detalles insertados en RESOLUCION_DETALLE.');
    }
}