// composables/useReporteInscritosLista.js
// Genera el PDF académico de LISTA COMPLETA de inscritos por docente
// (formato institucional UMSS), con jsPDF + jspdf-autotable.

import { ref } from 'vue'
import { jsPDF } from 'jspdf'
import autoTable from 'jspdf-autotable'

// ── Paleta institucional ─────────────────────────────────────────────────
const C_BLACK = [0, 0, 0]
const C_WHITE = [255, 255, 255]
const C_HEAD_BG = [211, 211, 211]
const C_GROUP_BG = [235, 235, 235]
const C_GRAY_LINE = [140, 140, 140]
const C_MUTED = [90, 90, 90]

// ── Helpers de documento ─────────────────────────────────────────────────
function crearDocumento() {
    return new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'letter' })
}

function fechaFormateada() {
    // Formato: DD/MM/AAAA, hh:mm:ss a. m./p. m.
    return new Date().toLocaleString('en-GB', {
        day: 'numeric', month: 'numeric', year: 'numeric',
        hour: 'numeric', minute: '2-digit', second: '2-digit', hour12: true,
    })
}

function drawPageHeader(doc, { titulo, anio, periodo, fechaActual, notaSuperior }) {
    const PAGE_W = doc.internal.pageSize.getWidth()
    const ML = 8
    const MR = 8
    const HEADER_H = 24

    doc.setFont('helvetica', 'bold')
    doc.setFontSize(6)
    doc.setTextColor(...C_BLACK)
    doc.text('UNIVERSIDAD MAYOR DE SAN SIMON', ML, 8)
    doc.text('FACULTAD DE CIENCIAS ECONOMICAS', ML, 10)

    doc.setFontSize(12.5)
    doc.text(titulo, PAGE_W / 2, 9, { align: 'center' })
    doc.setFontSize(10.5)
    doc.text('FACULTAD DE CIENCIAS ECONOMICAS', PAGE_W / 2, 13.5, { align: 'center' })

    doc.setFont('helvetica', 'bold')
    doc.setFontSize(10)
    doc.setTextColor(...C_BLACK)
    doc.text(`Gestión Académica ${periodo}/${anio}`, PAGE_W / 2, 19.5, { align: 'center' })

    doc.setFont('helvetica', 'normal')
    doc.setFontSize(7)
    doc.text(fechaActual, PAGE_W - MR, 19.5, { align: 'right' })
    doc.text(notaSuperior ?? '', ML, 19.5)

    return HEADER_H
}

function drawFooters(doc, fechaActual) {
    const PAGE_W = doc.internal.pageSize.getWidth()
    const PAGE_H = doc.internal.pageSize.getHeight()
    const ML = 8
    const MR = 8
    const total = doc.internal.getNumberOfPages()

    for (let i = 1; i <= total; i++) {
        doc.setPage(i)
        const fy = PAGE_H - 5
        doc.setDrawColor(...C_GRAY_LINE)
        doc.setLineWidth(0.2)
        doc.line(ML, fy - 2, PAGE_W - MR, fy - 2)
        doc.setFont('helvetica', 'normal')
        doc.setFontSize(6.5)
        doc.setTextColor(80, 80, 80)
        doc.text('Procesado UTI - Facultad de Ciencias Economicas', ML, fy)
        doc.text(`Página ${i} de ${total}`, PAGE_W / 2, fy, { align: 'center' })
        doc.text(fechaActual, PAGE_W - MR, fy, { align: 'right' })
    }
}

/**
 * Devuelve la salida final del PDF según el modo:
 * - 'ver': redirige una ventana YA abierta (pre-abierta en el clic del
 *   usuario) hacia el blob del PDF. Si no hay ventana pre-abierta, intenta
 *   abrir una nueva (puede ser bloqueada por el navegador si no hay gesto
 *   de usuario directo). Si falla, cae a descarga como último recurso.
 * - 'descargar': dispara la descarga directa.
 */
function finalizarSalida(doc, filename, modo, ventanaPreabierta) {
    if (modo === 'ver') {
        const blob = doc.output('blob')
        const url = URL.createObjectURL(blob)

        if (ventanaPreabierta && !ventanaPreabierta.closed) {
            ventanaPreabierta.location.href = url
        } else {
            const ventana = window.open(url, '_blank')
            if (!ventana) {
                // Popup bloqueado y no había ventana pre-abierta: fallback a descarga
                doc.save(filename)
                URL.revokeObjectURL(url)
                return
            }
        }

        setTimeout(() => URL.revokeObjectURL(url), 60_000)
    } else {
        doc.save(filename)
    }
}

// ────────────────────────────────────────────────────────────────────────────
// LISTA COMPLETA — docente → carrera → materia → estudiantes
// ────────────────────────────────────────────────────────────────────────────
function generarListaCompleta(data, anio, periodo, modo = 'descargar', ventanaPreabierta = null) {
    const doc = crearDocumento()
    const PAGE_W = doc.internal.pageSize.getWidth()
    const ML = 8
    const MR = 8
    const CW = PAGE_W - ML - MR
    const fechaActual = fechaFormateada()
    const TITULO = 'LISTA DE INSCRITOS POR DOCENTE'
    const NOTA = 'La lista incluye todos los grupos y carreras asignadas al docente.'
    const HEADER_H = drawPageHeader(doc, { titulo: TITULO, anio, periodo, fechaActual, notaSuperior: NOTA })

    const body = []

    data.forEach((docente, dIdx) => {
        // ── Fila de docente ──────────────────────────────────────────────────
        body.push([{
            content: `${dIdx + 1}.  COD. ${docente.cod_docente}   ${(docente.apellidos ?? '').toUpperCase()}, ${(docente.nombres ?? '').toUpperCase()}`,
            colSpan: 3,
            styles: {
                fontStyle: 'bold', fontSize: 8.5, halign: 'left',
                fillColor: C_WHITE, textColor: C_BLACK, lineWidth: 0,
                cellPadding: { top: dIdx === 0 ? 1.5 : 4, bottom: 1.5, left: 1.5, right: 1.5 },
            },
        }])

        docente.carreras.forEach(carrera => {
            // ── Fila de carrera ────────────────────────────────────────────────
            body.push([{
                content: `${carrera.carrera}  ·  Plan ${carrera.plan}  ·  Subtotal: ${carrera.subtotal} inscritos`,
                colSpan: 3,
                styles: {
                    fontStyle: 'bold', fontSize: 7.5, halign: 'left',
                    fillColor: C_GROUP_BG, textColor: C_MUTED, lineWidth: 0,
                    cellPadding: { top: 1.2, bottom: 1.2, left: 3, right: 1.5 },
                },
            }])

            carrera.materias.forEach(materia => {
                // ── Fila de materia ────────────────────────────────────────────────
                body.push([{
                    content: `${materia.nom_materia}  (Gr. ${materia.grupo})  —  ${materia.subtotal} inscritos`,
                    colSpan: 3,
                    styles: {
                        fontStyle: 'bolditalic', fontSize: 7, halign: 'left',
                        fillColor: C_WHITE, textColor: C_MUTED, lineWidth: 0,
                        cellPadding: { top: 1, bottom: 0.8, left: 5, right: 1.5 },
                    },
                }])

                // ── Estudiantes regulares ────────────────────────────────────────
                materia.inscritos.forEach((est, idx) => {
                    body.push([
                        { content: String(idx + 1) },
                        { content: est.codigo },
                        { content: est.nombre },
                    ])
                })

                // ── Examen de mesa (si existen) ──────────────────────────────────
                if (materia.subtotal_examen_mesa) {
                    body.push([{
                        content: `Examen de mesa — ${materia.subtotal_examen_mesa} estudiantes`,
                        colSpan: 3,
                        styles: {
                            fontStyle: 'bolditalic', fontSize: 6.8, halign: 'left',
                            fillColor: C_WHITE, textColor: C_MUTED, lineWidth: 0,
                            cellPadding: { top: 1, bottom: 0.8, left: 6, right: 1.5 },
                        },
                    }])

                    materia.inscritos_examen_mesa.forEach((est, idx) => {
                        body.push([
                            { content: String(idx + 1) },
                            { content: est.codigo },
                            { content: est.nombre },
                        ])
                    })
                }
            })
        })

        // ── Total del docente ──────────────────────────────────────────────
        const totalTexto = docente.total_examen_mesa
            ? `TOTAL INSCRITOS DEL DOCENTE: ${docente.total_inscritos}  (+ ${docente.total_examen_mesa} examen de mesa)`
            : `TOTAL INSCRITOS DEL DOCENTE: ${docente.total_inscritos}`

        body.push([{
            content: totalTexto,
            colSpan: 3,
            styles: {
                fontStyle: 'bold', fontSize: 7.5, halign: 'right',
                fillColor: C_WHITE, textColor: C_BLACK,
                lineWidth: { top: 0.3, right: 0, bottom: 0, left: 0 },
                lineColor: C_GRAY_LINE,
                cellPadding: { top: 1.5, bottom: 2.5, left: 1.5, right: 1.5 },
            },
        }])
    })

    autoTable(doc, {
        startY: HEADER_H,
        margin: { left: ML, right: MR, top: HEADER_H, bottom: 12 },
        tableWidth: CW,
        head: [['N°', 'CÓDIGO', 'NOMBRE DEL ESTUDIANTE']],
        body,
        alternateRowStyles: { fillColor: C_WHITE },
        styles: {
            font: 'helvetica', fontSize: 7.2,
            cellPadding: { top: 0.7, bottom: 0.7, left: 1.5, right: 1.5 },
            textColor: C_BLACK, lineColor: C_GRAY_LINE, lineWidth: 0,
            fillColor: C_WHITE, overflow: 'linebreak', valign: 'middle',
        },
        headStyles: {
            fillColor: C_HEAD_BG, textColor: C_BLACK, fontStyle: 'bold',
            fontSize: 7.5, halign: 'left', valign: 'middle',
            lineColor: C_GRAY_LINE, lineWidth: { top: 0, right: 0, bottom: 0.3, left: 0 },
        },
        columnStyles: {
            0: { cellWidth: 14, halign: 'center' },
            1: { cellWidth: 28 },
            2: { cellWidth: 'auto' },
        },
        didParseCell(data) {
            if (data.section === 'head') {
                if (data.column.index === 0) data.cell.styles.halign = 'center'
                return
            }
            if (data.section !== 'body') return

            const raw = data.row.raw
            const primera = Array.isArray(raw) ? raw[0] : null
            const esGrupo = primera && typeof primera === 'object' && 'colSpan' in primera
            if (esGrupo) return

            data.cell.styles.lineWidth = { top: 0, right: 0, bottom: 0.15, left: 0 }
            if (data.column.index === 0) {
                data.cell.styles.textColor = C_MUTED
                data.cell.styles.fontSize = 6.8
            }
            if (data.column.index === 1) {
                data.cell.styles.font = 'courier'
                data.cell.styles.textColor = C_MUTED
                data.cell.styles.fontSize = 6.8
            }
        },
        didDrawPage() {
            if (doc.internal.getCurrentPageInfo().pageNumber > 1) {
                drawPageHeader(doc, { titulo: TITULO, anio, periodo, fechaActual, notaSuperior: NOTA })
            }
        },
    })

    drawFooters(doc, fechaActual)

    const filename = `Lista_Inscritos_${anio}_${periodo}.pdf`
    finalizarSalida(doc, filename, modo, ventanaPreabierta)
}

// ────────────────────────────────────────────────────────────────────────────
// Composable público — SOLO lista completa
// ────────────────────────────────────────────────────────────────────────────
export function useReporteInscritosLista() {
    const generandoLista = ref(false)

    async function exportarListaCompleta(data, anio, periodo, modo = 'descargar', ventanaPreabierta = null) {
        if (!data?.length) {
            if (ventanaPreabierta && !ventanaPreabierta.closed) ventanaPreabierta.close()
            return
        }
        generandoLista.value = true
        try {
            generarListaCompleta(data, anio, periodo, modo, ventanaPreabierta)
        } finally {
            generandoLista.value = false
        }
    }

    return {
        generandoLista,
        exportarListaCompleta,
    }
}