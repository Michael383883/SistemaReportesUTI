// composables/useReporteInscritos.js
// Genera PDFs directamente en el navegador con jsPDF (sin backend).
// Instalación: npm install jspdf

import { ref } from 'vue'

// ── Paleta de colores por carrera ────────────────────────────────────────────
const COLORES = {
    ADM: { r: 219, g: 234, b: 254 }, // blue-100
    ECO: { r: 209, g: 250, b: 229 }, // emerald-100
    CCP: { r: 237, g: 233, b: 254 }, // purple-100
    COM: { r: 255, g: 237, b: 213 }, // orange-100
    FIN: { r: 254, g: 249, b: 195 }, // yellow-100
    NN: { r: 241, g: 245, b: 249 }, // slate-100
}

const COLOR_HEADER = { r: 30, g: 41, b: 59 } // slate-800
const COLOR_SUBHEAD = { r: 51, g: 65, b: 85 } // slate-700
const COLOR_ROW_ODD = { r: 248, g: 250, b: 252 } // slate-50
const COLOR_ROW_EVEN = { r: 255, g: 255, b: 255 } // white
const COLOR_TEXT = { r: 30, g: 41, b: 59 }
const COLOR_MUTED = { r: 100, g: 116, b: 139 }
const COLOR_LINE = { r: 226, g: 232, b: 240 } // slate-200

// ── Helpers ──────────────────────────────────────────────────────────────────
function rgb(doc, color) {
    return [color.r, color.g, color.b]
}

function setFill(doc, color) {
    doc.setFillColor(color.r, color.g, color.b)
}

function setTextColor(doc, color) {
    doc.setTextColor(color.r, color.g, color.b)
}

function setDrawColor(doc, color) {
    doc.setDrawColor(color.r, color.g, color.b)
}

/**
 * Dibuja el encabezado institucional en cada página.
 * @returns {number} Y después del header
 */
function dibujarHeader(doc, anio, periodo, titulo, pageW) {
    // Banda superior oscura
    setFill(doc, COLOR_HEADER)
    doc.rect(0, 0, pageW, 22, 'F')

    // Título del reporte
    doc.setFont('helvetica', 'bold')
    doc.setFontSize(13)
    setTextColor(doc, { r: 255, g: 255, b: 255 })
    doc.text(titulo, 14, 14)

    // Gestión / período (derecha)
    doc.setFontSize(9)
    doc.setFont('helvetica', 'normal')
    doc.text(`Gestión ${anio} · Período ${periodo}`, pageW - 14, 14, { align: 'right' })

    // Fecha de generación
    setFill(doc, { r: 51, g: 65, b: 85 })
    doc.rect(0, 22, pageW, 8, 'F')
    doc.setFontSize(7.5)
    setTextColor(doc, { r: 148, g: 163, b: 184 })
    const fecha = new Date().toLocaleDateString('es-BO', {
        day: '2-digit', month: 'long', year: 'numeric',
    })
    doc.text(`Generado el ${fecha}`, 14, 27.5)

    return 36 // Y inicial del contenido
}

/**
 * Pie de página con número.
 */
function dibujarFooter(doc, pageNum, totalPages, pageW, pageH) {
    setFill(doc, COLOR_HEADER)
    doc.rect(0, pageH - 10, pageW, 10, 'F')
    doc.setFontSize(7)
    doc.setFont('helvetica', 'normal')
    setTextColor(doc, { r: 148, g: 163, b: 184 })
    doc.text(`Página ${pageNum} de ${totalPages}`, pageW / 2, pageH - 3.5, { align: 'center' })
}

/**
 * Verifica si cabe más contenido; si no, agrega página y redibuja header.
 * @returns {number} nuevo Y
 */
function checkPage(doc, y, needed, anio, periodo, titulo, pageW, pageH) {
    if (y + needed > pageH - 16) {
        doc.addPage()
        return dibujarHeader(doc, anio, periodo, titulo, pageW)
    }
    return y
}

// ────────────────────────────────────────────────────────────────────────────
// REPORTE 1 — Lista completa (docente → carrera → materia → estudiantes)
// ────────────────────────────────────────────────────────────────────────────
async function generarListaCompleta(data, anio, periodo) {
    const { jsPDF } = await import('jspdf')
    const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' })
    const pageW = doc.internal.pageSize.getWidth()
    const pageH = doc.internal.pageSize.getHeight()
    const TITULO = 'Lista de Inscritos por Docente'

    let y = dibujarHeader(doc, anio, periodo, TITULO, pageW)

    data.forEach((docente, dIdx) => {
        // ── Bloque docente ──────────────────────────────────────────────────
        y = checkPage(doc, y, 18, anio, periodo, TITULO, pageW, pageH)

        // Banda nombre docente
        setFill(doc, COLOR_SUBHEAD)
        doc.rect(14, y, pageW - 28, 10, 'F')
        doc.setFont('helvetica', 'bold')
        doc.setFontSize(10)
        setTextColor(doc, { r: 255, g: 255, b: 255 })
        doc.text(
            `${dIdx + 1}.  ${docente.apellidos}, ${docente.nombres}`,
            18, y + 6.8
        )
        doc.setFontSize(8)
        doc.text(`Cód. ${docente.cod_docente}`, pageW - 16, y + 6.8, { align: 'right' })
        y += 12

        docente.carreras.forEach(carrera => {
            // ── Bloque carrera ────────────────────────────────────────────────
            y = checkPage(doc, y, 10, anio, periodo, TITULO, pageW, pageH)

            const col = COLORES[carrera.carrera] ?? COLORES.NN
            setFill(doc, col)
            doc.rect(14, y, pageW - 28, 7, 'F')
            doc.setFont('helvetica', 'bold')
            doc.setFontSize(8.5)
            setTextColor(doc, COLOR_TEXT)
            doc.text(
                `${carrera.carrera}  ·  Plan ${carrera.plan}  ·  Total: ${carrera.subtotal} inscritos`,
                18, y + 5
            )
            y += 9

            carrera.materias.forEach(materia => {
                // ── Encabezado materia ──────────────────────────────────────────
                y = checkPage(doc, y, 10, anio, periodo, TITULO, pageW, pageH)

                setFill(doc, { r: 241, g: 245, b: 249 })
                doc.rect(18, y, pageW - 36, 6.5, 'F')
                doc.setFont('helvetica', 'bolditalic')
                doc.setFontSize(7.5)
                setTextColor(doc, COLOR_MUTED)
                doc.text(
                    `  ${materia.nom_materia}  (Gr. ${materia.grupo})  —  ${materia.subtotal} inscritos`,
                    20, y + 4.5
                )
                y += 8

                // ── Tabla de estudiantes ────────────────────────────────────────
                materia.inscritos.forEach((est, idx) => {
                    y = checkPage(doc, y, 6, anio, periodo, TITULO, pageW, pageH)

                    const bg = idx % 2 === 0 ? COLOR_ROW_ODD : COLOR_ROW_EVEN
                    setFill(doc, bg)
                    doc.rect(18, y, pageW - 36, 5.5, 'F')

                    doc.setFont('helvetica', 'normal')
                    doc.setFontSize(7)
                    setTextColor(doc, COLOR_MUTED)
                    doc.text(`${idx + 1}`, 22, y + 3.8)

                    setTextColor(doc, { r: 100, g: 116, b: 139 })
                    doc.setFont('courier', 'normal')
                    doc.text(`${est.codigo}`, 28, y + 3.8)

                    doc.setFont('helvetica', 'normal')
                    setTextColor(doc, COLOR_TEXT)
                    doc.text(`${est.nombre}`, 55, y + 3.8)
                    y += 5.5
                })

                y += 3 // espacio entre materias
            })

            y += 4 // espacio entre carreras
        })

        // ── Total del docente ─────────────────────────────────────────────
        y = checkPage(doc, y, 8, anio, periodo, TITULO, pageW, pageH)
        setDrawColor(doc, COLOR_LINE)
        doc.setLineWidth(0.3)
        doc.line(14, y, pageW - 14, y)
        doc.setFont('helvetica', 'bold')
        doc.setFontSize(8)
        setTextColor(doc, COLOR_MUTED)
        doc.text(
            `Total inscritos del docente: ${docente.total_inscritos}`,
            pageW - 16, y + 5, { align: 'right' }
        )
        y += 10
    })

    // Pies de página
    const totalPages = doc.internal.getNumberOfPages()
    for (let p = 1; p <= totalPages; p++) {
        doc.setPage(p)
        dibujarFooter(doc, p, totalPages, pageW, pageH)
    }

    doc.save(`inscritos_lista_completa_${anio}_${periodo}.pdf`)
}

// ────────────────────────────────────────────────────────────────────────────
// REPORTE 2 — Solo totales y cantidades (sin lista de estudiantes)
// ────────────────────────────────────────────────────────────────────────────
async function generarResumenTotales(data, anio, periodo) {
    const { jsPDF } = await import('jspdf')
    const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' })
    const pageW = doc.internal.pageSize.getWidth()
    const pageH = doc.internal.pageSize.getHeight()
    const TITULO = 'Resumen de Inscritos — Solo Totales'

    let y = dibujarHeader(doc, anio, periodo, TITULO, pageW)

    // ── Tabla global resumen ─────────────────────────────────────────────────
    const totalGlobal = data.reduce((s, d) => s + d.total_inscritos, 0)

    // Encabezado de tabla
    y = checkPage(doc, y, 10, anio, periodo, TITULO, pageW, pageH)
    setFill(doc, COLOR_HEADER)
    doc.rect(14, y, pageW - 28, 8, 'F')
    doc.setFont('helvetica', 'bold')
    doc.setFontSize(8)
    setTextColor(doc, { r: 255, g: 255, b: 255 })
    doc.text('N°', 16, y + 5.5)
    doc.text('Código', 22, y + 5.5)
    doc.text('Docente', 42, y + 5.5)
    doc.text('ADM', pageW - 72, y + 5.5, { align: 'center' })
    doc.text('ECO', pageW - 60, y + 5.5, { align: 'center' })
    doc.text('CCP', pageW - 48, y + 5.5, { align: 'center' })
    doc.text('COM', pageW - 36, y + 5.5, { align: 'center' })
    doc.text('FIN', pageW - 24, y + 5.5, { align: 'center' })
    doc.text('TOTAL', pageW - 14, y + 5.5, { align: 'right' })
    y += 9

    // Filas por docente
    data.forEach((docente, idx) => {
        y = checkPage(doc, y, 7, anio, periodo, TITULO, pageW, pageH)

        const bg = idx % 2 === 0 ? COLOR_ROW_ODD : COLOR_ROW_EVEN
        setFill(doc, bg)
        doc.rect(14, y, pageW - 28, 6.5, 'F')

        // Construir mapa carrera → subtotal
        const totPorCarrera = {}
        docente.carreras.forEach(c => { totPorCarrera[c.carrera] = c.subtotal })

        doc.setFont('helvetica', 'normal')
        doc.setFontSize(7.5)
        setTextColor(doc, COLOR_MUTED)
        doc.text(`${idx + 1}`, 16, y + 4.5)

        doc.setFont('courier', 'normal')
        doc.text(`${docente.cod_docente}`, 22, y + 4.5)

        doc.setFont('helvetica', 'normal')
        setTextColor(doc, COLOR_TEXT)
        // Truncar nombre si es muy largo
        const nombre = `${docente.apellidos}, ${docente.nombres}`.substring(0, 32)
        doc.text(nombre, 42, y + 4.5)

        // Totales por carrera
        const carreras = ['ADM', 'ECO', 'CCP', 'COM', 'FIN']
        const xCols = [pageW - 72, pageW - 60, pageW - 48, pageW - 36, pageW - 24]
        carreras.forEach((c, i) => {
            const val = totPorCarrera[c] ?? 0
            if (val > 0) {
                const col = COLORES[c] ?? COLORES.NN
                setFill(doc, col)
                doc.roundedRect(xCols[i] - 5, y + 0.8, 10, 5, 1, 1, 'F')
                setTextColor(doc, COLOR_TEXT)
                doc.setFont('helvetica', 'bold')
                doc.text(`${val}`, xCols[i], y + 4.5, { align: 'center' })
            } else {
                setTextColor(doc, COLOR_LINE)
                doc.setFont('helvetica', 'normal')
                doc.text('—', xCols[i], y + 4.5, { align: 'center' })
            }
        })

        // Total docente
        doc.setFont('helvetica', 'bold')
        doc.setFontSize(8)
        setTextColor(doc, { r: 29, g: 78, b: 216 }) // blue-700
        doc.text(`${docente.total_inscritos}`, pageW - 14, y + 4.5, { align: 'right' })

        y += 6.5
    })

    // ── Fila de totales globales ─────────────────────────────────────────────
    y = checkPage(doc, y, 10, anio, periodo, TITULO, pageW, pageH)
    setFill(doc, { r: 30, g: 58, b: 138 }) // blue-900
    doc.rect(14, y, pageW - 28, 8, 'F')
    doc.setFont('helvetica', 'bold')
    doc.setFontSize(8.5)
    setTextColor(doc, { r: 255, g: 255, b: 255 })
    doc.text('TOTAL GENERAL', 42, y + 5.5)

    const carreras = ['ADM', 'ECO', 'CCP', 'COM', 'FIN']
    const xCols = [pageW - 72, pageW - 60, pageW - 48, pageW - 36, pageW - 24]
    carreras.forEach((c, i) => {
        const sum = data.reduce((s, d) => {
            const car = d.carreras.find(x => x.carrera === c)
            return s + (car?.subtotal ?? 0)
        }, 0)
        doc.text(sum > 0 ? `${sum}` : '—', xCols[i], y + 5.5, { align: 'center' })
    })

    doc.text(`${totalGlobal}`, pageW - 14, y + 5.5, { align: 'right' })
    y += 12

    // ── Sección detalle por carrera y materia (sin nombres) ──────────────────
    y = checkPage(doc, y, 14, anio, periodo, TITULO, pageW, pageH)
    setFill(doc, COLOR_SUBHEAD)
    doc.rect(14, y, pageW - 28, 8, 'F')
    doc.setFont('helvetica', 'bold')
    doc.setFontSize(9)
    setTextColor(doc, { r: 255, g: 255, b: 255 })
    doc.text('Detalle por Docente → Carrera → Materia', 18, y + 5.5)
    y += 11

    data.forEach((docente, dIdx) => {
        y = checkPage(doc, y, 12, anio, periodo, TITULO, pageW, pageH)

        // Banda docente
        setFill(doc, { r: 51, g: 65, b: 85 })
        doc.rect(14, y, pageW - 28, 8, 'F')
        doc.setFont('helvetica', 'bold')
        doc.setFontSize(8.5)
        setTextColor(doc, { r: 255, g: 255, b: 255 })
        doc.text(
            `${dIdx + 1}.  ${docente.apellidos}, ${docente.nombres}  (Cód. ${docente.cod_docente})`,
            18, y + 5.5
        )
        doc.text(`${docente.total_inscritos} inscritos`, pageW - 16, y + 5.5, { align: 'right' })
        y += 10

        docente.carreras.forEach(carrera => {
            y = checkPage(doc, y, 8, anio, periodo, TITULO, pageW, pageH)

            const col = COLORES[carrera.carrera] ?? COLORES.NN
            setFill(doc, col)
            doc.rect(18, y, pageW - 36, 6, 'F')
            doc.setFont('helvetica', 'bold')
            doc.setFontSize(8)
            setTextColor(doc, COLOR_TEXT)
            doc.text(
                `${carrera.carrera}  ·  Plan ${carrera.plan}`,
                22, y + 4.2
            )
            doc.text(`${carrera.subtotal}`, pageW - 18, y + 4.2, { align: 'right' })
            y += 7

            // Tabla de materias (sin estudiantes)
            carrera.materias.forEach((mat, mIdx) => {
                y = checkPage(doc, y, 6, anio, periodo, TITULO, pageW, pageH)

                const bg = mIdx % 2 === 0 ? COLOR_ROW_ODD : COLOR_ROW_EVEN
                setFill(doc, bg)
                doc.rect(22, y, pageW - 44, 5.5, 'F')

                doc.setFont('helvetica', 'normal')
                doc.setFontSize(7.5)
                setTextColor(doc, COLOR_MUTED)
                doc.text(`${mat.cod_materia}`, 25, y + 3.8)

                setTextColor(doc, COLOR_TEXT)
                // Truncar nombre materia
                const nomMat = mat.nom_materia.substring(0, 40)
                doc.text(nomMat, 45, y + 3.8)

                doc.setFont('helvetica', 'normal')
                setTextColor(doc, COLOR_MUTED)
                doc.text(`Gr. ${mat.grupo}`, pageW - 36, y + 3.8)

                doc.setFont('helvetica', 'bold')
                setTextColor(doc, COLOR_TEXT)
                doc.text(`${mat.subtotal}`, pageW - 18, y + 3.8, { align: 'right' })

                y += 5.5
            })

            y += 3
        })

        y += 5
    })

    // Pies de página
    const totalPages = doc.internal.getNumberOfPages()
    for (let p = 1; p <= totalPages; p++) {
        doc.setPage(p)
        dibujarFooter(doc, p, totalPages, pageW, pageH)
    }

    doc.save(`inscritos_resumen_totales_${anio}_${periodo}.pdf`)
}

// ────────────────────────────────────────────────────────────────────────────
// Composable público
// ────────────────────────────────────────────────────────────────────────────
export function useReporteInscritos() {
    const generandoLista = ref(false)
    const generandoResumen = ref(false)

    async function exportarListaCompleta(data, anio, periodo) {
        if (!data?.length) return
        generandoLista.value = true
        try {
            await generarListaCompleta(data, anio, periodo)
        } finally {
            generandoLista.value = false
        }
    }

    async function exportarResumenTotales(data, anio, periodo) {
        if (!data?.length) return
        generandoResumen.value = true
        try {
            await generarResumenTotales(data, anio, periodo)
        } finally {
            generandoResumen.value = false
        }
    }

    return {
        generandoLista,
        generandoResumen,
        exportarListaCompleta,
        exportarResumenTotales,
    }
}