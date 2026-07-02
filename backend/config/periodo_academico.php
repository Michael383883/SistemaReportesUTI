<?php

// Rangos de mes-día (sin año) de cada periodo académico.
// Se evalúan contra la fecha actual del sistema cada año.
// Si dos periodos se solapan (semanas de transición/matriculación),
// gana el que tenga el 'inicio' más reciente respecto a hoy.
return [
    'periodos' => [
        '3' => ['inicio' => '01-05', 'fin' => '02-20', 'nombre' => 'Verano'],
        '1' => ['inicio' => '02-10', 'fin' => '06-30', 'nombre' => 'I/{anio}'],
        '4' => ['inicio' => '07-01', 'fin' => '08-15', 'nombre' => 'Invierno'],
        '2' => ['inicio' => '08-05', 'fin' => '12-20', 'nombre' => 'II/{anio}'],
    ],

    // Periodo/año de respaldo si alguna vez no cae en ningún rango
    // (ej. vacaciones de fin de año, 21-dic a 04-ene)
    'fallback_periodo' => '2',
];