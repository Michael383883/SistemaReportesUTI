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

const ENCABEZADOS = ['Carrera', 'Nivel', 'Materia', 'Grupo', 'Codigo', 'Estudiante']

function filaCsv(e) {
    const valores = [e.siglaPlan, e.nivel, e.nombreMateria, e.grupo, e.codEstudiante, e.estudiante]
    return valores.map((v) => `"${String(v ?? '').replace(/"/g, '""')}"`).join(',')
}

function nombreArchivo(filtros) {
    return `estudiantes_inscritos_${filtros.anio}_${filtros.periodo}.csv`
}

/**
 * Trae solo una muestra acotada (LIMITE_PREVIEW filas) para la vista
 * previa. Devuelve también `total` y `truncado` para que la UI pueda
 * avisar "mostrando 500 de 65,343 — filtra por nivel/carrera para ver
 * un listado más chico".
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

    return {
        filas: resp.data,
        total: resp.total,
        truncado: resp.total > resp.data.length,
        limite: LIMITE_PREVIEW,
    }
}

/**
 * Descarga el CSV completo, trayendo los datos en lotes de
 * TAMANO_LOTE filas (en vez de un solo pedido gigante).
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
        resp.data.forEach((e) => lineas.push(filaCsv(e)))
        onProgreso(Math.min(pagina * TAMANO_LOTE, total), total)

        // Si el backend devolvió menos filas de las pedidas, no hay más páginas
        if (resp.data.length < TAMANO_LOTE) break

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