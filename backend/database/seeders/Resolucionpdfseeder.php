<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ResolucionPdfSeeder extends Seeder
{
    /**
     * Genera 20 resoluciones de ejemplo (solo el registro/metadatos,
     * NO sube ni requiere archivos PDF reales en storage). El campo
     * NOMBRE_ARCHIVO/RUTA_ARCHIVO queda "simulado" solo para que el
     * frontend tenga algo que mostrar; si intentas descargar el PDF
     * con el endpoint /pdf va a dar 404 porque el archivo físico no
     * existe, a menos que subas manualmente PDFs de prueba a esas rutas.
     *
     * PERIODO solo admite los valores 1, 2, 3 o 4.
     */
    private const PERIODOS_VALIDOS = [1, 2, 3, 4];

    public function run(): void
    {
        $usuarios = ['admin', 'jefatura.academica', 'rrhh.docente', 'secretaria.general'];

        $resoluciones = [
            ['nro' => 'RR Nº 266/2024', 'anio' => 2024, 'periodo' => 1, 'desc' => 'Del 26 de febrero al 19 de abril de 2024. El semestre 1/2024 inició el 26 de febrero y terminó el 09 de julio del 2024.'],
            ['nro' => 'RR Nº 267/2024', 'anio' => 2024, 'periodo' => 1, 'desc' => 'Del 22 de abril al 09 de julio de 2024. Continuación de designaciones docentes semestre 1/2024.'],
            ['nro' => 'RR Nº 301/2024', 'anio' => 2024, 'periodo' => 2, 'desc' => 'Del 05 de agosto al 20 de diciembre de 2024. El semestre 2/2024 inició el 05 de agosto y terminó el 20 de diciembre del 2024.'],
            ['nro' => 'RR Nº 312/2024', 'anio' => 2024, 'periodo' => 2, 'desc' => 'Designación de docentes para carga horaria complementaria, semestre 2/2024.'],
            ['nro' => 'RR Nº 045/2023', 'anio' => 2023, 'periodo' => 1, 'desc' => 'Del 27 de febrero al 07 de julio de 2023. El semestre 1/2023 inició el 27 de febrero y terminó el 07 de julio del 2023.'],
            ['nro' => 'RR Nº 118/2023', 'anio' => 2023, 'periodo' => 2, 'desc' => 'Del 07 de agosto al 15 de diciembre de 2023. El semestre 2/2023 inició el 07 de agosto y terminó el 15 de diciembre del 2023.'],
            ['nro' => 'RR Nº 019/2025', 'anio' => 2025, 'periodo' => 1, 'desc' => 'Del 24 de febrero al 04 de julio de 2025. El semestre 1/2025 inició el 24 de febrero y terminó el 04 de julio del 2025.'],
            ['nro' => 'RR Nº 088/2025', 'anio' => 2025, 'periodo' => 2, 'desc' => 'Del 04 de agosto al 19 de diciembre de 2025. El semestre 2/2025 inició el 04 de agosto y terminó el 19 de diciembre del 2025.'],
            ['nro' => 'RR Nº 270/2024', 'anio' => 2024, 'periodo' => 1, 'desc' => 'Ratificación de designaciones docentes correspondientes al semestre 1/2024.'],
            ['nro' => 'RR Nº 289/2024', 'anio' => 2024, 'periodo' => 1, 'desc' => 'Designación de docentes interinos para cubrir vacancias del semestre 1/2024.'],
            ['nro' => 'RR Nº 330/2024', 'anio' => 2024, 'periodo' => 2, 'desc' => 'Designación de tribunales de examen de suficiencia, semestre 2/2024.'],
            ['nro' => 'RR Nº 341/2024', 'anio' => 2024, 'periodo' => 2, 'desc' => 'Ampliación de carga horaria para docentes titulares, semestre 2/2024.'],
            ['nro' => 'RR Nº 052/2023', 'anio' => 2023, 'periodo' => 1, 'desc' => 'Designación de docentes suplentes para el semestre 1/2023.'],
            ['nro' => 'RR Nº 130/2023', 'anio' => 2023, 'periodo' => 2, 'desc' => 'Designación de docentes temporales para el semestre 2/2023.'],
            ['nro' => 'RR Nº 025/2025', 'anio' => 2025, 'periodo' => 1, 'desc' => 'Reasignación de grupos y materias, semestre 1/2025.'],
            ['nro' => 'RR Nº 095/2025', 'anio' => 2025, 'periodo' => 2, 'desc' => 'Designación de docentes con carga compartida, semestre 2/2025.'],
            ['nro' => 'RR Nº 275/2024', 'anio' => 2024, 'periodo' => 1, 'desc' => 'Designación de docentes para exámenes de suficiencia, semestre 1/2024.'],
            ['nro' => 'RR Nº 350/2024', 'anio' => 2024, 'periodo' => 2, 'desc' => 'Cierre de gestión académica 2024, ratificación general de designaciones.'],
            ['nro' => 'RR Nº 060/2023', 'anio' => 2023, 'periodo' => 1, 'desc' => 'Designación de docentes titulares nuevos, semestre 1/2023.'],
            ['nro' => 'RR Nº 140/2023', 'anio' => 2023, 'periodo' => 2, 'desc' => 'Cierre de gestión académica 2023, ratificación general de designaciones.'],
        ];

        foreach ($resoluciones as $i => $r) {
            if (!in_array($r['periodo'], self::PERIODOS_VALIDOS, true)) {
                throw new InvalidArgumentException(
                    "PERIODO inválido ({$r['periodo']}) en resolución {$r['nro']}. Solo se permiten los valores: " . implode(', ', self::PERIODOS_VALIDOS)
                );
            }

            $numeroLimpio = preg_replace('/[^0-9]/', '', explode('/', $r['nro'])[0]);
            $nombreArchivo = 'RR-' . $numeroLimpio . '-' . $r['anio'] . '.pdf';

            DB::table('RESOLUCIONES_PDF')->insert([
                'NRO_RESOLUCION' => $r['nro'],
                'DESCRIPCION'    => $r['desc'],
                'ANIO'           => $r['anio'],
                'PERIODO'        => $r['periodo'],
                'RUTA_ARCHIVO'   => 'resoluciones/' . $r['anio'] . '/' . $nombreArchivo,
                'NOMBRE_ARCHIVO' => $nombreArchivo,
                'TAMANIO_KB'     => rand(120, 850),
                'SUBIDO_POR'     => $usuarios[$i % count($usuarios)],
                'FECHA_SUBIDA'   => now()->subDays(rand(5, 500)),
            ]);
        }
    }
}