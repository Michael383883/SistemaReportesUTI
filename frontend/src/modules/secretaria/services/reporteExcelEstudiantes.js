/**
 * reporteExcelEstudiantes.js
 * Exportación (CSV) de Estudiantes Inscritos — pensado para volúmenes
 * grandes (60k+ registros, ej. 65,343 alumnos en talleres).
 *
 * ─────────────────────────────────────────────────────────────────────
 * POR QUÉ ESTE ARCHIVO EXISTE APARTE:
 *
 * El endpoint `getInscritosCompleto` trae TODO de una sola vez. Con
 * 65k+ filas eso significa: una respuesta HTTP enorme, 65k objetos JS
 * armados de golpe, y si además se intenta "Ver" en una tabla HTML,
 * 65k filas de DOM — el navegador se congela.
 *
 * Este servicio evita ambos problemas:
 *
 *  1. "Ver" (vista previa) NUNCA carga el listado completo. Pide al
 *     backend solo LIMITE_PREVIEW filas (usando los filtros que el
 *     usuario ya tenga puestos). Si hay más, se avisa en vez de
 *     intentar pintarlas todas.
 *
 *  2. "Descargar" trae los datos en LOTES paginados (TAMANO_LOTE a la
 *     vez) usando el endpoint paginado que ya existe (`getInscritos`
 *     con page/perPage). Cada lote se convierte directo a líneas CSV
 *     (no se acumulan los objetos completos), y se informa el
 *     progreso via callback para poder mostrar una barra en la UI.
 *
 * NOTA IMPORTANTE (nuevo formato agrupado):
 * El backend ahora devuelve `data` como una lista de GRUPOS
 * (materia + nivel + grupo), cada uno con su `docente` y su lista de
 * `estudiantes`. La paginación (page/perPage) sigue operando sobre
 * FILAS de estudiante puro, así que cada página trae exactamente
 * `perPage` estudiantes repartidos en N grupos — solo que ya vienen
 * organizados. Por eso, antes de armar el CSV o la vista previa, hay
 * que "aplanar" los grupos de vuelta a filas por estudiante.
 * ─────────────────────────────────────────────────────────────────────
 * SOLUCIÓN IDEAL A FUTURO (requiere backend):
 *
 * Lo más eficiente de verdad es que el backend exponga un endpoint de
 * exportación (ej. GET /api/estudiantes-inscritos/export?formato=csv)
 * que genere el archivo y lo devuelva como stream con
 * `Content-Disposition: attachment`. Así el navegador nunca transporta
 * 65k filas como JSON — solo descarga el archivo ya armado del lado
 * del servidor (más rápido, usa menos memoria en el cliente, y no
 * depende de mantener la pestaña abierta mientras se arma el CSV).
 *
 * Si ese endpoint llega a existir, el cambio se hace solo acá adentro
 * (por ejemplo reemplazando `descargarCsvCompleto` por un simple
 * `window.location.href = urlDeExportacion`), sin tocar el componente
 * Vue que consume este servicio.
 * ─────────────────────────────────────────────────────────────────────
 */

import estudiantesInscritosService from './estudiantesInscritosService.js'

// Filas máximas que se muestran en "Ver" (vista previa en pestaña nueva)
export const LIMITE_PREVIEW = 500

// Filas por página al traer el listado completo para descargar
export const TAMANO_LOTE = 5000

// A partir de este total, se avisa al usuario que la descarga puede tardar
export const UMBRAL_ADVERTENCIA = 20000

const ENCABEZADOS = [
    'Carrera',
    'Nivel',
    'Materia',
    'Nombre Materia',
    'Grupo',
    'Cod Docente',
    'Docente',
    'Codigo Estudiante',
    'Estudiante',
]

/**
 * Convierte la lista de grupos (con estudiantes anidados) en una
 * lista plana de filas, una por estudiante, con los datos del grupo
 * y del docente repetidos en cada fila.
 */
function aplanarGrupos(grupos) {
    const filas = []

    for (const g of grupos) {
        const filaBase = {
            siglaPlan: g.siglaPlan,
            nivel: g.nivel,
            materia: g.materia,
            nombreMateria: g.nombreMateria,
            grupo: g.grupo,
            codDocente: g.docente?.codDocente ?? '',
            docente: g.docente?.docente ?? 'Sin docente asignado',
        }

        for (const est of g.estudiantes) {
            filas.push({
                ...filaBase,
                codEstudiante: est.codEstudiante,
                estudiante: est.estudiante,
            })
        }
    }

    return filas
}

function filaCsv(e) {
    const valores = [
        e.siglaPlan,
        e.nivel,
        e.materia,
        e.nombreMateria,
        e.grupo,
        e.codDocente,
        e.docente,
        e.codEstudiante,
        e.estudiante,
    ]
    return valores.map((v) => `"${String(v ?? '').replace(/"/g, '""')}"`).join(',')
}

function nombreArchivo(filtros) {
    return `estudiantes_inscritos_${filtros.anio}_${filtros.periodo}.csv`
}

/**
 * Trae solo una muestra acotada (LIMITE_PREVIEW filas de estudiante)
 * para la vista previa, y aplana los grupos recibidos a filas planas.
 * Devuelve también `total` y `truncado` para que la UI pueda avisar
 * "mostrando 500 de 65,343 — filtra por nivel/carrera para ver un
 * listado más chico".
 */
export async function obtenerVistaPrevia(filtros) {
    const resp = await estudiantesInscritosService.getInscritos({
        anio: filtros.anio,
        periodo: filtros.periodo,
        plan: filtros.plan || null,
        nivel: filtros.nivel || null,
        page: 1,
        perPage: LIMITE_PREVIEW,
    })

    const filas = aplanarGrupos(resp.data)

    return {
        filas,
        total: resp.total,
        truncado: resp.total > filas.length,
        limite: LIMITE_PREVIEW,
    }
}

/**
 * Descarga el CSV completo, trayendo los datos en lotes de
 * TAMANO_LOTE filas de estudiante (en vez de un solo pedido gigante).
 * Cada lote de grupos se aplana antes de convertirlo a líneas CSV.
 *
 * @param {Object} filtros
 * @param {(cargados: number, total: number) => void} onProgreso
 *        callback invocado después de cada lote, útil para mostrar
 *        una barra de progreso o un "cargando 20,000 / 65,343...".
 * @returns {Promise<number>} el total de filas exportadas
 */
export async function descargarCsvCompleto(filtros, onProgreso = () => { }) {
    let pagina = 1
    let total = Infinity
    let cargados = 0
    const lineas = [ENCABEZADOS.join(',')]

    while ((pagina - 1) * TAMANO_LOTE < total) {
        const resp = await estudiantesInscritosService.getInscritos({
            anio: filtros.anio,
            periodo: filtros.periodo,
            plan: filtros.plan || null,
            nivel: filtros.nivel || null,
            page: pagina,
            perPage: TAMANO_LOTE,
        })

        total = resp.total

        const filas = aplanarGrupos(resp.data)
        filas.forEach((e) => lineas.push(filaCsv(e)))
        cargados += filas.length

        onProgreso(Math.min(cargados, total), total)

        // Si el backend ya no devolvió filas, no hay mas paginas
        if (filas.length === 0) break

        pagina += 1
    }

    const blob = new Blob([lineas.join('\n')], { type: 'text/csv;charset=utf-8;' })
    const url = URL.createObjectURL(blob)
    const enlace = document.createElement('a')
    enlace.href = url
    enlace.download = nombreArchivo(filtros)
    enlace.click()
    URL.revokeObjectURL(url)

    return total
}