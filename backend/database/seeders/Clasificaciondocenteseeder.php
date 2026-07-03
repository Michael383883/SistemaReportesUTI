<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClasificacionDocenteSeeder extends Seeder
{
    /**
     * Ejecuta el seeder.
     *
     * - Titulares: 201000113, 200400011, 201000110, 198200009
     * - Temporales: 198200010, 198200011, 198200012, 198200014, 198200015, 198200016
     *
     * Los códigos se reparten en forma circular dentro de cada categoría.
     */
    public function run(): void
    {
        // Definir los códigos de docente por categoría
        $codTitulares = ['201000113', '200400011', '201000110', '198200009'];
        $codTemporales = ['198200010', '198200011', '198200012', '198200014', '198200015', '198200016'];

        // Verificar que todos existan en la tabla DOCENTES
        $existentes = DB::table('DOCENTES')
            ->whereIn('CODIGO', array_merge($codTitulares, $codTemporales))
            ->pluck('CODIGO')
            ->map(fn($c) => (string) $c)
            ->toArray();

        $faltantes = array_diff(array_merge($codTitulares, $codTemporales), $existentes);
        if (!empty($faltantes)) {
            $this->command->warn('Los siguientes códigos de DOCENTES no existen y serán omitidos: ' . implode(', ', $faltantes));
        }

        // Filtrar solo los que existen en la BD
        $codTitulares = array_intersect($codTitulares, $existentes);
        $codTemporales = array_intersect($codTemporales, $existentes);

        if (empty($codTitulares) && empty($codTemporales)) {
            $this->command->error('No hay códigos de docente válidos. Ejecuta primero los seeders de DOCENTES.');
            return;
        }

        // ------------------------------------------------------------
        // Registros de ejemplo (TITULARES) - 14 registros
        // ------------------------------------------------------------
        $titulares = [
            [
                'nivel' => 'PRIMER NIVEL',
                'gestion' => '1997',
                'periodo' => 'MARZO 1997',
                'detalle_general' => 'CERTIFICADO DE TITULARIDAD',
                'observacion' => 'TITULAR EN CONTABILIDAD DE COOPERATIVAS Y SEGUROS, MARZO 1997',
                'materias' => [['nombre' => 'CONTABILIDAD DE COOPERATIVAS Y SEGUROS', 'cod_materia' => '1301120', 'cod_plan' => '109401']],
            ],
            [
                'nivel' => 'SEGUNDO NIVEL',
                'gestion' => '2003',
                'periodo' => 'MAYO 2003',
                'detalle_general' => 'TÍTULO DE TITULARIDAD 05/03',
                'observacion' => 'TITULAR EN LA MATERIA METODOS Y TECNICAS DE INVESTIGACION - DE LA CARRERA CIENCIA POLITICA, MAYO 2003',
                'materias' => [['nombre' => 'METODOS Y TECNICAS DE INVESTIGACION', 'cod_materia' => '1302050', 'cod_plan' => '126091']],
            ],
            [
                'nivel' => 'SEGUNDO NIVEL',
                'gestion' => '2003',
                'periodo' => 'MAYO 2003',
                'detalle_general' => 'TÍTULO DE TITULARIDAD 05/03',
                'observacion' => 'TITULAR EN LA MATERIA METODOLOGIA Y DISEÑO DE LA INVESTIGACION - DE LA CARRERA CIENCIA POLITICA, MAYO 2003',
                'materias' => [['nombre' => 'METODOLOGIA Y DISEÑO DE LA INVESTIGACION', 'cod_materia' => '1302051', 'cod_plan' => '126091']],
            ],
            [
                'nivel' => 'SEGUNDO NIVEL',
                'gestion' => '2003',
                'periodo' => 'MAYO 2003',
                'detalle_general' => 'TÍTULO DE TITULARIDAD 05/03',
                'observacion' => 'TITULAR EN LA MATERIA ECONOMIA POLITICA INTERNACIONAL - DE LA CARRERA CIENCIA POLITICA, MAYO 2003',
                'materias' => [['nombre' => 'ECONOMIA POLITICA INTERNACIONAL', 'cod_materia' => '1302052', 'cod_plan' => '126091']],
            ],
            [
                'nivel' => 'PRIMER NIVEL',
                'gestion' => '1997',
                'periodo' => 'MARZO 1997',
                'detalle_general' => 'CERTIFICADO DE TITULARIDAD',
                'observacion' => 'TITULAR EN LA MATERIA TECNICAS DE ESTUDIO E INVESTIGACION - FACULTAD DE CIENCIAS ECONOMICAS Y SOCIOLOGIA, MARZO 1997',
                'materias' => [['nombre' => 'TECNICAS DE ESTUDIO E INVESTIGACION', 'cod_materia' => '1301050', 'cod_plan' => '109401']],
            ],
            [
                'nivel' => 'TERCER NIVEL',
                'gestion' => '2011',
                'periodo' => '29/07/2011',
                'detalle_general' => 'RESOLUCIÓN DEL CONSEJO UNIVERSITARIO (R.C.U.)',
                'observacion' => 'TITULAR (ESTADISTICA) DE LA FACULTAD DE CIENCIAS ECONOMICAS POR R.C.U. 43/11 DEL 29/07/2011',
                'materias' => [['nombre' => 'ESTADISTICA', 'cod_materia' => '1301060', 'cod_plan' => '109401']],
                'referencias' => [['nro' => 'R.C.U. Nº 43/11']],
            ],
            [
                'nivel' => 'TERCER NIVEL',
                'gestion' => '2011',
                'periodo' => '29/07/2011',
                'detalle_general' => 'RESOLUCIÓN DEL CONSEJO UNIVERSITARIO (R.C.U.)',
                'observacion' => 'TITULAR (AUDITORIA OPERATIVA) DE LA FACULTAD DE CIENCIAS ECONOMICAS POR R.C.U. 43/11 DEL 29/07/2011 (AUDITORIA OPERATIVA es equivalente a AUDITORIA DE GESTION por cambio de nombre de asignatura segun acuerdo tecnico 2/2007 del 21 de diciembre del 2007 del departamento de desarrollo curricular dependiente de la DPA)',
                'materias' => [['nombre' => 'AUDITORIA OPERATIVA (AUDITORIA DE GESTION)', 'cod_materia' => '1301061', 'cod_plan' => '109401']],
                'referencias' => [['nro' => 'R.C.U. Nº 43/11']],
            ],
            [
                'nivel' => 'TERCER NIVEL',
                'gestion' => '2011',
                'periodo' => '29/07/2011',
                'detalle_general' => 'RESOLUCIÓN DEL CONSEJO UNIVERSITARIO (R.C.U.)',
                'observacion' => 'TITULAR (INFORMATICA) DE LA FACULTAD DE CIENCIAS ECONOMICAS POR R.C.U. 43/11 DEL 29/07/2011',
                'materias' => [['nombre' => 'INFORMATICA', 'cod_materia' => '1301062', 'cod_plan' => '109401']],
                'referencias' => [['nro' => 'R.C.U. Nº 43/11']],
            ],
            [
                'nivel' => 'TERCER NIVEL',
                'gestion' => '2011',
                'periodo' => '29/07/2011',
                'detalle_general' => 'RESOLUCIÓN DEL CONSEJO UNIVERSITARIO (R.C.U.)',
                'observacion' => 'TITULAR (MICROECONOMIA) EN LA FACULTAD DE CIENCIAS ECONOMICAS POR R.C.U. 43/11 DE 29/07/2011',
                'materias' => [['nombre' => 'MICROECONOMIA', 'cod_materia' => '1301063', 'cod_plan' => '109401']],
                'referencias' => [['nro' => 'R.C.U. Nº 43/11']],
            ],
            [
                'nivel' => 'TERCER NIVEL',
                'gestion' => '2011',
                'periodo' => '29/07/2011',
                'detalle_general' => 'RESOLUCIÓN DEL CONSEJO UNIVERSITARIO (R.C.U.)',
                'observacion' => 'TITULAR (COMERCIO INTERNACIONAL) EN LA FACULTAD DE CIENCIAS ECONOMICAS POR R.C.U. 43/11 DE 29/07/2011',
                'materias' => [['nombre' => 'COMERCIO INTERNACIONAL', 'cod_materia' => '1301064', 'cod_plan' => '109401']],
                'referencias' => [['nro' => 'R.C.U. Nº 43/11']],
            ],
            [
                'nivel' => 'TERCER NIVEL',
                'gestion' => '2011',
                'periodo' => '29/07/2011',
                'detalle_general' => 'RESOLUCIÓN DEL CONSEJO UNIVERSITARIO (R.C.U.)',
                'observacion' => 'TITULAR DE CONTABILIDAD BANCARIA R.C.U.43/11 DEL 29/07/2011',
                'materias' => [['nombre' => 'CONTABILIDAD BANCARIA', 'cod_materia' => '1301065', 'cod_plan' => '109401']],
                'referencias' => [['nro' => 'R.C.U. Nº 43/11']],
            ],
            [
                'nivel' => 'PRIMER NIVEL',
                'gestion' => '1998',
                'periodo' => 'FEBRERO 1998',
                'detalle_general' => 'CERTIFICADO DE TITULARIDAD',
                'observacion' => 'TITULAR EN LAS MATERIAS DE ORGANIZACIÓN Y METODOS, INGENIERIA DE SOFTWARE EN LA FACULTAD DE CIENCIAS Y TECNOLOGIA, FEBRERO 1998',
                'materias' => [
                    ['nombre' => 'ORGANIZACION Y METODOS', 'cod_materia' => '1303010', 'cod_plan' => '125091'],
                    ['nombre' => 'INGENIERIA DE SOFTWARE', 'cod_materia' => '1303011', 'cod_plan' => '125091'],
                ],
            ],
            [
                'nivel' => 'PRIMER NIVEL',
                'gestion' => '2007',
                'periodo' => 'FEBRERO Y MARZO 2007',
                'detalle_general' => 'CERTIFICADO DE TITULARIDAD',
                'observacion' => 'TITULAR EN LAS MATERIAS: METODOLOGIAS DE INVESTIGACION I, PLANIFICACION Y PROYECTOS SOCIALES - FACULTAD DE HUMANIDADES, FEBRERO Y MARZO 2007',
                'materias' => [
                    ['nombre' => 'METODOLOGIAS DE INVESTIGACION I', 'cod_materia' => '1304020', 'cod_plan' => '126091'],
                    ['nombre' => 'PLANIFICACION Y PROYECTOS SOCIALES', 'cod_materia' => '1304021', 'cod_plan' => '126091'],
                ],
                'observacion2' => "Aprobó Ex. Suficiencia en METODOS DE INVESTIGACION Año 1998, Nota 64,00 - RCF Nº 18/98\nAprobó Ex. Suficiencia en METODOS DE INVESTIGACION I Año 1999 - RCF Nº 15/99",
                'referencias' => [['nro' => 'RCF Nº 18/98'], ['nro' => 'RCF Nº 15/99']],
            ],
            [
                'nivel' => 'SEGUNDO NIVEL',
                'gestion' => '1977',
                'periodo' => 'AÑO 1977',
                'detalle_general' => 'CERTIFICADO DE TITULARIDAD',
                'observacion' => 'TITULAR DE LA MATERIA AÑO 1977',
                'materias' => [['nombre' => 'CONTABILIDAD GENERAL', 'cod_materia' => '1301001', 'cod_plan' => '089801']],
            ],
            [
                'nivel' => 'SEGUNDO NIVEL',
                'gestion' => '1991',
                'periodo' => 'AGOSTO 1991',
                'detalle_general' => 'CERTIFICADO DE TITULARIDAD',
                'observacion' => 'TITULAR DE LA MATERIA AGOSTO 1991',
                'materias' => [['nombre' => 'ECONOMIA POLITICA II', 'cod_materia' => '1301002', 'cod_plan' => '089801']],
            ],
        ];

        // ------------------------------------------------------------
        // Registros de ejemplo (TEMPORALES) - 26 registros
        // ------------------------------------------------------------
        $temporales = [
            [
                'nivel' => 'PRIMER NIVEL',
                'gestion' => '2010',
                'periodo' => '2/2010',
                'detalle_general' => 'CUADRO DE EXAMEN 2/2010',
                'observacion' => 'Aprobó Ex. Suficiencia Año 2010 - RCF Nº 39/10',
                'materias' => [['nombre' => 'CONTABILIDAD DE COSTOS', 'cod_materia' => '1301030', 'cod_plan' => '109401', 'nota' => null]],
                'referencias' => [['nro' => 'RCF Nº 39/10']],
            ],
            [
                'nivel' => 'PRIMER NIVEL',
                'gestion' => '1998',
                'periodo' => '1/1998',
                'detalle_general' => 'CERTIFICADO DE MATERIA',
                'observacion' => "Aprobó Ex. Suficiencia Año 1998, Nota 65,00 - RCF Nº 18/98\nAprobó Ex. Suficiencia Año 1999, Nota 74,20 - RCF Nº 15/99",
                'materias' => [['nombre' => 'FINANZAS PUBLICAS', 'cod_materia' => '1301031', 'cod_plan' => '109401', 'nota' => 74]],
                'referencias' => [['nro' => 'RCF Nº 18/98'], ['nro' => 'RCF Nº 15/99']],
            ],
            [
                'nivel' => 'PRIMER NIVEL',
                'gestion' => '2010',
                'periodo' => '2/2010',
                'detalle_general' => 'CUADRO DE EXAMEN 2/2010',
                'observacion' => "Aprobó Ex. Suficiencia Año 2010, Nota 61,02 - RCF Nº 37/10\nAprobó Ex. Suficiencia Año 2010, Nota 64,14 - RCF Nº 37/10",
                'materias' => [['nombre' => 'DERECHO TRIBUTARIO', 'cod_materia' => '1301032', 'cod_plan' => '109401', 'nota' => 64]],
                'referencias' => [['nro' => 'RCF Nº 37/10']],
            ],
            [
                'nivel' => 'PRIMER NIVEL',
                'gestion' => '2016',
                'periodo' => '2/2016',
                'detalle_general' => 'CUADRO DE EXAMEN 2/2016',
                'observacion' => 'Aprobó Ex. Suficiencia Año 2016, Nota 77,98 - RCF Nº 153/16',
                'materias' => [['nombre' => 'AUDITORIA FINANCIERA', 'cod_materia' => '1301066', 'cod_plan' => '109401', 'nota' => 78]],
                'referencias' => [['nro' => 'RCF Nº 153/16']],
            ],
            [
                'nivel' => 'PRIMER NIVEL',
                'gestion' => '2010',
                'periodo' => '1/2010',
                'detalle_general' => 'CUADRO DE EXAMEN 1/2010',
                'observacion' => "Aprobó Ex. Suficiencia Año 2010, Nota 65,58 - RCF Nº 44/10\nAprobó Ex. Suficiencia Año 2011 - RCF Nº 22/11\nAprobó Ex. Suficiencia Año 2012, Nota 64,00 - RCF Nº 30/12",
                'materias' => [['nombre' => 'MERCADOTECNIA', 'cod_materia' => '1301067', 'cod_plan' => '109401', 'nota' => 64]],
                'referencias' => [['nro' => 'RCF Nº 44/10'], ['nro' => 'RCF Nº 22/11'], ['nro' => 'RCF Nº 30/12']],
            ],
            [
                'nivel' => 'PRIMER NIVEL',
                'gestion' => '2016',
                'periodo' => '2/2016',
                'detalle_general' => 'CUADRO DE EXAMEN 2/2016',
                'observacion' => 'Aprobó Ex. Suficiencia Año 2016, Nota 60,80 - RCF Nº 153/16',
                'materias' => [['nombre' => 'ADMINISTRACION DE EMPRESAS', 'cod_materia' => '1301068', 'cod_plan' => '109401', 'nota' => 61]],
                'referencias' => [['nro' => 'RCF Nº 153/16']],
            ],
            [
                'nivel' => 'SEGUNDO NIVEL',
                'gestion' => '1999',
                'periodo' => '1/1999',
                'detalle_general' => 'CERTIFICADO DE MATERIA',
                'observacion' => 'Aprobó Ex. Suficiencia en MACROECONOMIA Año 1999, Nota 65,50 (Certificado) y según lista Resultados Examen de Suficiencia - RCF Nº 16/99',
                'materias' => [['nombre' => 'MACROECONOMIA', 'cod_materia' => '1301069', 'cod_plan' => '109401', 'nota' => 66]],
                'referencias' => [['nro' => 'RCF Nº 16/99']],
                'observacion2' => 'TITULAR EN LA MATERIA DE ECONOMIA INDUSTRIAL DE LA FACULTAD DE CIENCIAS Y TECNOLOGIA GESTION 2003, CERTIFICADO POR LA FACULTAD DE CIENCIAS Y TECNOLOGIA Y DIRECCION DE PLANIFICACION ACADEMICA - DPA, OCUPANDO EL TERCER LUGAR',
            ],
            [
                'nivel' => 'SEGUNDO NIVEL',
                'gestion' => '2016',
                'periodo' => '2/2016',
                'detalle_general' => 'CUADRO DE EXAMEN 2/2016',
                'observacion' => 'Aprobó Ex. Suficiencia Año 2016, Nota 64,32 - RCF Nº 152/16',
                'materias' => [['nombre' => 'FINANZAS CORPORATIVAS', 'cod_materia' => '1301070', 'cod_plan' => '109401', 'nota' => 64]],
                'referencias' => [['nro' => 'RCF Nº 152/16']],
            ],
            [
                'nivel' => 'SEGUNDO NIVEL',
                'gestion' => '1998',
                'periodo' => '1/1998',
                'detalle_general' => 'CERTIFICADO DE MATERIA',
                'observacion' => 'Aprobó Ex. Suficiencia Año 1998, Nota 70,00 - RCF Nº 18/98',
                'materias' => [['nombre' => 'COSTOS INDUSTRIALES', 'cod_materia' => '1301071', 'cod_plan' => '109401', 'nota' => 70]],
                'referencias' => [['nro' => 'RCF Nº 18/98']],
            ],
            [
                'nivel' => 'SEGUNDO NIVEL',
                'gestion' => '2013',
                'periodo' => '2/2013',
                'detalle_general' => 'CUADRO DE EXAMEN 2/2013',
                'observacion' => "Aprobó Ex. Suficiencia Año 2013, Nota 85,30 - RCF Nº 75/13\nAprobó Ex. Suficiencia Año 2012, Nota 61,90 - RCF Nº 56/12\nAprobó Ex. Suficiencia Año 2016, Nota 70,18 - RCF Nº 160/16",
                'materias' => [['nombre' => 'PRESUPUESTOS', 'cod_materia' => '1301072', 'cod_plan' => '109401', 'nota' => 85]],
                'referencias' => [['nro' => 'RCF Nº 75/13'], ['nro' => 'RCF Nº 56/12'], ['nro' => 'RCF Nº 160/16']],
            ],
            [
                'nivel' => 'SEGUNDO NIVEL',
                'gestion' => '1997',
                'periodo' => '1/1997',
                'detalle_general' => 'CERTIFICADO DE MATERIA',
                'observacion' => 'Aprobó Ex. Suficiencia Año 1997 para la Carrera de Economía en la Materia METODOS DE INVESTIGACION con Nota 72,12 - Certificado por el Departamento de Coordinación Académica de la Dirección de Planificación Académica. Actualmente la materia se llama EPISTEMOLOGIA DE LA ECONOMIA pero anteriormente se llamaba METODOS DE INVESTIGACION.',
                'materias' => [['nombre' => 'EPISTEMOLOGIA DE LA ECONOMIA (ANTES METODOS DE INVESTIGACION)', 'cod_materia' => '1301073', 'cod_plan' => '109401', 'nota' => 72]],
            ],
            [
                'nivel' => 'SEGUNDO NIVEL',
                'gestion' => '1991',
                'periodo' => '1/1991',
                'detalle_general' => 'CERTIFICADO DE MATERIA',
                'observacion' => 'Aprobó Ex. Suficiencia Año 1991 para la Carrera de Economía en la Materia ECONOMIA POLITICA II con Nota 70,01 - Certificado por el Director Académico de la Facultad de Ciencias Económicas Julio Cesar Camacho, mayo 2010.',
                'materias' => [['nombre' => 'ECONOMIA POLITICA II', 'cod_materia' => '1301002', 'cod_plan' => '089801', 'nota' => 70]],
            ],
            [
                'nivel' => 'PRIMER NIVEL',
                'gestion' => '2010',
                'periodo' => '2/2010',
                'detalle_general' => 'CUADRO DE EXAMEN 2/2010',
                'observacion' => "Aprobó Ex. Suficiencia Año 2010, Nota 61,935 - RCF Nº 115/10\nAprobó Ex. Suficiencia Año 2010 - RCF Nº 121/10 (En esta RCF se designa)",
                'materias' => [['nombre' => 'ESTADISTICA APLICADA', 'cod_materia' => '1301074', 'cod_plan' => '109401', 'nota' => 62]],
                'referencias' => [['nro' => 'RCF Nº 115/10'], ['nro' => 'RCF Nº 121/10']],
            ],
            [
                'nivel' => 'PRIMER NIVEL',
                'gestion' => '2016',
                'periodo' => '2/2016',
                'detalle_general' => 'CUADRO DE EXAMEN 2/2016',
                'observacion' => "Aprobó Ex. Suficiencia Año 2010, Nota 67,29 - RCF Nº 12/10\nAprobó Ex. Suficiencia Año 2010, Nota 71,28 - RCF Nº 119/10\nAprobó Ex. Suficiencia Año 2012, Nota 60,88 - RCF Nº 28/12\nAprobó Ex. Suficiencia Año 2016, Nota 75,97 - RCF Nº 153/16",
                'materias' => [['nombre' => 'CONTABILIDAD GUBERNAMENTAL', 'cod_materia' => '1301075', 'cod_plan' => '109401', 'nota' => 76]],
                'referencias' => [['nro' => 'RCF Nº 12/10'], ['nro' => 'RCF Nº 119/10'], ['nro' => 'RCF Nº 28/12'], ['nro' => 'RCF Nº 153/16']],
            ],
            [
                'nivel' => 'SEGUNDO NIVEL',
                'gestion' => '2012',
                'periodo' => '2/2012',
                'detalle_general' => 'CUADRO DE EXAMEN 2/2012',
                'observacion' => "Aprobó Ex. Suficiencia Año 2012, Nota 63,50 - RCF Nº 56/12\nAprobó Ex. Suficiencia Año 2012, Nota 65,01 - RCF Nº 64/12\nAprobó Ex. Suficiencia Año 1998, Nota 70,00 - RCF Nº 18/98\nAprobó Ex. de Suficiencia Año 1999, Nota 75,00 - RCF Nº 15/99",
                'materias' => [['nombre' => 'DERECHO LABORAL', 'cod_materia' => '1301076', 'cod_plan' => '109401', 'nota' => 65]],
                'referencias' => [['nro' => 'RCF Nº 56/12'], ['nro' => 'RCF Nº 64/12'], ['nro' => 'RCF Nº 18/98'], ['nro' => 'RCF Nº 15/99']],
            ],
            [
                'nivel' => 'PRIMER NIVEL',
                'gestion' => '2015',
                'periodo' => '2/2015',
                'detalle_general' => 'CUADRO DE EXAMEN 2/2015',
                'observacion' => 'Aprobó Ex. Suficiencia Año 2015, Nota 63,10 - RCF Nº 43/15',
                'materias' => [['nombre' => 'ADMINISTRACION FINANCIERA', 'cod_materia' => '1301077', 'cod_plan' => '109401', 'nota' => 63]],
                'referencias' => [['nro' => 'RCF Nº 43/15']],
            ],
            [
                'nivel' => 'PRIMER NIVEL',
                'gestion' => '2010',
                'periodo' => '2/2010',
                'detalle_general' => 'CUADRO DE EXAMEN 2/2010',
                'observacion' => "Aprobó Ex. Suficiencia en ADMINISTRACION FINANCIERA Año 1998, Nota 81,00 - RCF Nº 18/98\nAprobó Ex. Suficiencia en ANALISIS E INTERPRETACION E.F. Año 1998, Nota 70,60 - RCF Nº 18/98",
                'materias' => [
                    ['nombre' => 'ADMINISTRACION FINANCIERA', 'cod_materia' => '1301077', 'cod_plan' => '109401', 'nota' => 81],
                    ['nombre' => 'ANALISIS E INTERPRETACION DE ESTADOS FINANCIEROS', 'cod_materia' => '1301078', 'cod_plan' => '109401', 'nota' => 71],
                ],
                'referencias' => [['nro' => 'RCF Nº 18/98']],
            ],
            [
                'nivel' => 'PRIMER NIVEL',
                'gestion' => '2005',
                'periodo' => '1/2005',
                'detalle_general' => 'RESOLUCIÓN DEL CONSEJO FACULTATIVO (CONCURSO DE MÉRITOS)',
                'observacion' => 'Ganador de concurso de méritos Año 2005 - RCF 022/05',
                'materias' => [['nombre' => 'DERECHO CIVIL', 'cod_materia' => '1301079', 'cod_plan' => '109401', 'nota' => null]],
                'referencias' => [['nro' => 'RCF Nº 022/05']],
            ],
            [
                'nivel' => 'PRIMER NIVEL',
                'gestion' => '1998',
                'periodo' => '1/1998',
                'detalle_general' => 'CERTIFICADO DE MATERIA',
                'observacion' => "TITULAR (Carrera de Derecho)\nAprobó Ex. Suficiencia Año 1998, Nota 61,00 - RCF Nº 18/98",
                'materias' => [['nombre' => 'INTRODUCCION AL DERECHO', 'cod_materia' => '1301080', 'cod_plan' => '109401', 'nota' => 61]],
                'referencias' => [['nro' => 'RCF Nº 18/98']],
            ],
            [
                'nivel' => 'PRIMER NIVEL',
                'gestion' => '1999',
                'periodo' => '1/1999',
                'detalle_general' => 'CERTIFICADO DE MATERIA',
                'observacion' => 'Aprobó examen de suficiencia el Año 1999, Nota 67,00 - RCF Nº 16/99',
                'materias' => [['nombre' => 'DERECHO PROCESAL', 'cod_materia' => '1301081', 'cod_plan' => '109401', 'nota' => 67]],
                'referencias' => [['nro' => 'RCF Nº 16/99']],
            ],
            [
                'nivel' => 'PRIMER NIVEL',
                'gestion' => '2012',
                'periodo' => '2/2012',
                'detalle_general' => 'CUADRO DE EXAMEN 2/2012',
                'observacion' => 'Aprobó Ex. Suficiencia Año 2012, Nota 61,47 - RCF Nº 26/12',
                'materias' => [['nombre' => 'INGENIERIA DE SOFTWARE', 'cod_materia' => '1303011', 'cod_plan' => '125091', 'nota' => 61]],
                'referencias' => [['nro' => 'RCF Nº 26/12']],
            ],
            [
                'nivel' => 'PRIMER NIVEL',
                'gestion' => '2010',
                'periodo' => '1/2010',
                'detalle_general' => 'CUADRO DE EXAMEN 1/2010',
                'observacion' => 'Aprobó Ex. Suficiencia Año 2010, Nota 63,65 - RCF Nº 115/10',
                'materias' => [['nombre' => 'BASES DE DATOS', 'cod_materia' => '1303012', 'cod_plan' => '125091', 'nota' => 64]],
                'referencias' => [['nro' => 'RCF Nº 115/10']],
            ],
            [
                'nivel' => 'PRIMER NIVEL',
                'gestion' => '2008',
                'periodo' => '1/2008',
                'detalle_general' => 'RESOLUCIÓN DEL CONSEJO FACULTATIVO (CONCURSO DE MÉRITOS)',
                'observacion' => 'Ganadora de concurso de méritos Año 2008, RCF Nº 030/08',
                'materias' => [['nombre' => 'REDES Y COMUNICACIONES', 'cod_materia' => '1303013', 'cod_plan' => '125091', 'nota' => null]],
                'referencias' => [['nro' => 'RCF Nº 030/08']],
            ],
            [
                'nivel' => 'PRIMER NIVEL',
                'gestion' => '1999',
                'periodo' => '1/1999',
                'detalle_general' => 'CERTIFICADO DE MATERIA',
                'observacion' => 'Aprobó Ex. Suficiencia en ESTADISTICA I Año 1999, Nota 80,00 (Cuadro de calificación) - RCF Nº 16/99',
                'materias' => [['nombre' => 'ESTADISTICA I', 'cod_materia' => '1301074', 'cod_plan' => '109401', 'nota' => 80]],
                'referencias' => [['nro' => 'RCF Nº 16/99']],
            ],
            [
                'nivel' => 'SEGUNDO NIVEL',
                'gestion' => '2016',
                'periodo' => '2/2016',
                'detalle_general' => 'CUADRO DE EXAMEN 2/2016',
                'observacion' => "Aprobó Ex. Suficiencia Año 2013, Nota 60,18 - RCF Nº 43/13\nAprobó Ex. Suficiencia Año 2016, Nota 77,27 - RCF Nº 154/16\nAprobó Ex. Suficiencia Año 2016, Nota 76,45 - RCF Nº 154/16",
                'materias' => [['nombre' => 'CONTABILIDAD SUPERIOR', 'cod_materia' => '1301082', 'cod_plan' => '109401', 'nota' => 77]],
                'referencias' => [['nro' => 'RCF Nº 43/13'], ['nro' => 'RCF Nº 154/16']],
            ],
        ];

        // ------------------------------------------------------------
        // Combinar y procesar
        // ------------------------------------------------------------
        $registros = array_merge(
            array_map(fn($r) => $r + ['categoria' => 'Docentes Titulares', 'fotocopia_titular' => true], $titulares),
            array_map(fn($r) => $r + ['categoria' => 'Docentes Temporales', 'fotocopia_titular' => false], $temporales)
        );

        $insertados = 0;
        $omitidos = 0;
        $contTit = 0;
        $contTemp = 0;
        $totalTit = count($codTitulares);
        $totalTemp = count($codTemporales);

        foreach ($registros as $i => $r) {
            // Validar categoría
            if (empty($r['categoria']) || !in_array($r['categoria'], ['Docentes Titulares', 'Docentes Temporales'], true)) {
                $this->command->warn("Registro #{$i} omitido: categoría inválida.");
                $omitidos++;
                continue;
            }

            // Asignar código según categoría (circular)
            if ($r['categoria'] === 'Docentes Titulares') {
                if ($totalTit === 0) {
                    $this->command->warn("Registro #{$i} omitido: no hay códigos de titular disponibles.");
                    $omitidos++;
                    continue;
                }
                $codDocente = $codTitulares[$contTit % $totalTit];
                $contTit++;
            } else {
                if ($totalTemp === 0) {
                    $this->command->warn("Registro #{$i} omitido: no hay códigos de temporal disponibles.");
                    $omitidos++;
                    continue;
                }
                $codDocente = $codTemporales[$contTemp % $totalTemp];
                $contTemp++;
            }

            // Truncar textos largos (ajusta el límite según tu columna)
            $detalleGeneral = substr($r['detalle_general'] ?? '', 0, 255);
            $observacion = substr($r['observacion'] ?? '', 0, 255);
            $observacion2 = isset($r['observacion2']) ? substr($r['observacion2'], 0, 255) : null;

            $idClasificacion = DB::table('CLASIFICACION_DOCENTE')->insertGetId([
                'COD_DOCENTE' => $codDocente,
                'CATEGORIA' => $r['categoria'],
                'NIVEL' => $r['nivel'],
                'GESTION' => (string) $r['gestion'],
                'PERIODO' => $r['periodo'] ?? null,
                'DETALLE_GENERAL' => $detalleGeneral,
                'FOTOCOPIA_TITULAR' => $r['fotocopia_titular'],
                'RUTA_ARCHIVO' => null,
                'NOMBRE_ARCHIVO' => null,
                'OBSERVACION' => $observacion,
                'OBSERVACION2' => $observacion2,
                'FECHA_REGISTRO' => DB::raw('GETDATE()'), // Usa la fecha del servidor
            ], 'ID_CLASIFICACION');

            // Insertar materias
            foreach (($r['materias'] ?? []) as $orden => $m) {
                DB::table('CLASIFICACION_MATERIA')->insert([
                    'ID_CLASIFICACION' => $idClasificacion,
                    'COD_MATERIA' => $m['cod_materia'] ?? null,
                    'NOMBRE_MATERIA' => substr($m['nombre'] ?? '', 0, 255),
                    'COD_PLAN' => $m['cod_plan'] ?? null,
                    'NOTA' => $m['nota'] ?? null,
                    'DETALLE' => isset($m['detalle']) ? substr($m['detalle'], 0, 255) : null,
                    'ORDEN' => $orden,
                ]);
            }

            // Insertar referencias
            foreach (($r['referencias'] ?? []) as $ref) {
                DB::table('CLASIFICACION_REFERENCIA')->insert([
                    'ID_CLASIFICACION' => $idClasificacion,
                    'NRO_REFERENCIA' => substr($ref['nro'] ?? '', 0, 100),
                    'ID_RESOLUCION' => $ref['id_resolucion'] ?? null,
                ]);
            }

            $insertados++;
        }

        $this->command->info("{$insertados} clasificaciones docentes insertadas ({$omitidos} omitidas)");
        $this->command->info("Titulares usados: {$contTit} (rotando entre " . count($codTitulares) . " códigos)");
        $this->command->info("Temporales usados: {$contTemp} (rotando entre " . count($codTemporales) . " códigos)");
    }
}