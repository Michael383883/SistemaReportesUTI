// composables/useReporteInscritosLista.js
// Genera el PDF académico de LISTA COMPLETA de inscritos por docente
// (formato institucional UMSS), con jsPDF + jspdf-autotable.
// No incluye alumnos de examen de mesa.

import { ref } from 'vue'
import { jsPDF } from 'jspdf'
import autoTable from 'jspdf-autotable'

// ── Paleta institucional ─────────────────────────────────────────────────
const C_BLACK = [0, 0, 0]
const C_WHITE = [255, 255, 255]
const C_HEAD_BG = [240, 240, 240]
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
    doc.text('UNIVERSIDAD MAYOR DE SAN SIMÓN', ML, 8)
    doc.text('FACULTAD DE CIENCIAS ECONÓMICAS', ML, 10)

    doc.setFontSize(12.5)
    doc.text(titulo, PAGE_W / 2, 9, { align: 'center' })
    doc.setFontSize(10.5)
    doc.text('FACULTAD DE CIENCIAS ECONÓMICAS', PAGE_W / 2, 13.5, { align: 'center' })

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
        doc.text('Procesado UTI - Facultad de Ciencias Económicas', ML, fy)
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
 * - 'imprimir': imprime mediante un iframe oculto.
 * - 'descargar': dispara la descarga directa.
 */
function finalizarSalida(doc, filename, modo, ventanaPreabierta) {
    if (modo === 'imprimir') {
        const blob = doc.output('blob')
        const url = URL.createObjectURL(blob)

        // Si había una ventana pre-abierta (por el gesto de clic), la cerramos:
        // no la necesitamos para imprimir, usamos un iframe oculto en su lugar.
        if (ventanaPreabierta && !ventanaPreabierta.closed) {
            ventanaPreabierta.close()
        }

        const iframe = document.createElement('iframe')
        iframe.style.position = 'fixed'
        iframe.style.right = '0'
        iframe.style.bottom = '0'
        iframe.style.width = '0'
        iframe.style.height = '0'
        iframe.style.border = '0'
        iframe.src = url

        iframe.onload = () => {
            try {
                iframe.contentWindow.focus()
                iframe.contentWindow.print()
            } catch (e) {
                console.error('No se pudo imprimir automáticamente', e)
                window.open(url, '_blank')
            }
        }

        document.body.appendChild(iframe)

        setTimeout(() => {
            document.body.removeChild(iframe)
            URL.revokeObjectURL(url)
        }, 60_000)

        return
    }

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
// LISTA COMPLETA
//   docente
//   materia · carrera · plan — n inscritos
//   N° | CÓDIGO | NOMBRE DEL ESTUDIANTE
//   estudiantes...
//
// El docente se escribe una vez; cada materia es su propia tabla, con la
// fila de materia + la cabecera de columnas como encabezado (se repiten si
// la lista de esa materia continúa en la página siguiente).
// ────────────────────────────────────────────────────────────────────────────
function generarListaCompleta(data, anio, periodo, modo = 'descargar', ventanaPreabierta = null, opciones = {}) {
    const doc = crearDocumento()
    const PAGE_W = doc.internal.pageSize.getWidth()
    const PAGE_H = doc.internal.pageSize.getHeight()
    const ML = 8
    const MR = 8
    const CW = PAGE_W - ML - MR
    const MARGIN_BOTTOM = 12
    const fechaActual = fechaFormateada()
    const TITULO = opciones.titulo ?? 'LISTA DE INSCRITOS POR DOCENTE'
    const ETIQUETA_TOTAL = opciones.etiquetaTotal ?? 'TOTAL INSCRITOS DEL DOCENTE'
    const NOTA = 'No incluye alumnos de examen de mesa.'
    const HEADER_H = drawPageHeader(doc, { titulo: TITULO, anio, periodo, fechaActual, notaSuperior: NOTA })

    // Páginas que ya tienen el encabezado institucional dibujado
    const paginasConEncabezado = new Set([1])

    // Si no cabe `necesario` mm en la página actual, salta de página
    function asegurarEspacio(y, necesario) {
        if (y + necesario <= PAGE_H - MARGIN_BOTTOM) return y
        doc.addPage()
        const pagina = doc.internal.getCurrentPageInfo().pageNumber
        if (!paginasConEncabezado.has(pagina)) {
            drawPageHeader(doc, { titulo: TITULO, anio, periodo, fechaActual, notaSuperior: NOTA })
            paginasConEncabezado.add(pagina)
        }
        return HEADER_H
    }

    let y = HEADER_H

    data.forEach((docente, dIdx) => {
        // ── Línea del docente ────────────────────────────────────────────────
        // Necesita sitio para: docente + materia + cabecera + 1 estudiante
        y = asegurarEspacio(y + (dIdx === 0 ? 1.5 : 5), 26)

        doc.setFont('helvetica', 'bold')
        doc.setFontSize(8.5)
        doc.setTextColor(...C_BLACK)
        const textoDocente = `${dIdx + 1}.  COD. ${docente.cod_docente}   ${(docente.apellidos ?? '').toUpperCase()}, ${(docente.nombres ?? '').toUpperCase()}`
        doc.text(textoDocente, ML + 1.5, y + 3)
        y += 5.5

        // Aplanamos carreras → materias conservando carrera y plan
        const materias = []
        docente.carreras.forEach(carrera => {
            carrera.materias.forEach(materia => materias.push({ carrera, materia }))
        })

        materias.forEach(({ carrera, materia }, mIdx) => {
            const esUltima = mIdx === materias.length - 1
            const body = []

            // ── Estudiantes regulares ────────────────────────────────────────
            materia.inscritos.forEach((est, idx) => {
                body.push([
                    { content: String(idx + 1) },
                    { content: est.codigo },
                    { content: est.nombre },
                ])
            })

            // ── Abandono (si existen) ─────────────────────────────────────────
            if (materia.subtotal_abandonos) {
                body.push([{
                    content: `Abandono — ${materia.subtotal_abandonos} estudiantes`,
                    colSpan: 3,
                    styles: {
                        fontStyle: 'bolditalic', fontSize: 6.8, halign: 'left',
                        fillColor: C_WHITE, textColor: C_MUTED, lineWidth: 0,
                        cellPadding: { top: 1, bottom: 0.8, left: 6, right: 1.5 },
                    },
                }])

                materia.inscritos_abandonos.forEach((est, idx) => {
                    body.push([
                        { content: String(idx + 1) },
                        { content: est.codigo },
                        { content: est.nombre },
                    ])
                })
            }

            // ── Total del docente (al final de su última materia) ─────────────
            if (esUltima) {
                const totalTexto = docente.total_abandonos
                    ? `${ETIQUETA_TOTAL}: ${docente.total_inscritos}  (+ ${docente.total_abandonos} abandono)`
                    : `${ETIQUETA_TOTAL}: ${docente.total_inscritos}`

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
            }

            // ── Cabecera: fila de materia + fila de columnas ──────────────────
            const head = [
                [{
                    content: `${materia.nom_materia}  (Gr. ${materia.grupo})  ·  ${carrera.carrera}  ·  Plan ${carrera.plan}  —  ${materia.subtotal} inscritos`,
                    colSpan: 3,
                    styles: {
                        fontStyle: 'bold', fontSize: 7.5, halign: 'left',
                        fillColor: C_GROUP_BG, textColor: C_MUTED,
                        lineWidth: 0,
                        cellPadding: { top: 1.2, bottom: 1.2, left: 3, right: 1.5 },
                    },
                }],
                ['N°', 'CÓDIGO', 'NOMBRE DEL ESTUDIANTE'],
            ]

            // Materia + cabecera + al menos una fila de estudiante
            y = asegurarEspacio(y + (mIdx === 0 ? 0 : 1.5), 16)

            autoTable(doc, {
                startY: y,
                margin: { left: ML, right: MR, top: HEADER_H, bottom: MARGIN_BOTTOM },
                tableWidth: CW,
                head,
                body,
                showHead: 'everyPage',
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
                didParseCell(cellData) {
                    if (cellData.section === 'head') {
                        // Fila 0 = materia (estilos propios); fila 1 = columnas
                        if (cellData.row.index === 1 && cellData.column.index === 0) {
                            cellData.cell.styles.halign = 'center'
                        }
                        return
                    }
                    if (cellData.section !== 'body') return

                    const raw = cellData.row.raw
                    const primera = Array.isArray(raw) ? raw[0] : null
                    const esGrupo = primera && typeof primera === 'object' && 'colSpan' in primera
                    if (esGrupo) return

                    cellData.cell.styles.lineWidth = { top: 0, right: 0, bottom: 0.15, left: 0 }
                    if (cellData.column.index === 0) {
                        cellData.cell.styles.textColor = C_MUTED
                        cellData.cell.styles.fontSize = 6.8
                    }
                    if (cellData.column.index === 1) {
                        cellData.cell.styles.font = 'courier'
                        cellData.cell.styles.textColor = C_MUTED
                        cellData.cell.styles.fontSize = 6.8
                    }
                },
                didDrawPage() {
                    const pagina = doc.internal.getCurrentPageInfo().pageNumber
                    if (!paginasConEncabezado.has(pagina)) {
                        drawPageHeader(doc, { titulo: TITULO, anio, periodo, fechaActual, notaSuperior: NOTA })
                        paginasConEncabezado.add(pagina)
                    }
                },
            })

            y = doc.lastAutoTable.finalY
        })
    })

    drawFooters(doc, fechaActual)

    const filename = opciones.filename ?? `Lista_Inscritos_${anio}_${periodo}.pdf`
    finalizarSalida(doc, filename, modo, ventanaPreabierta)
}

// ────────────────────────────────────────────────────────────────────────────
// Composable público — SOLO lista completa
// ────────────────────────────────────────────────────────────────────────────
export function useReporteInscritosLista() {
    const generandoLista = ref(false)

    async function exportarListaCompleta(data, anio, periodo, modo = 'descargar', ventanaPreabierta = null, opciones = {}) {
        if (!data?.length) {
            if (ventanaPreabierta && !ventanaPreabierta.closed) ventanaPreabierta.close()
            return
        }
        generandoLista.value = true
        try {
            generarListaCompleta(data, anio, periodo, modo, ventanaPreabierta, opciones)
        } finally {
            generandoLista.value = false
        }
    }

    return {
        generandoLista,
        exportarListaCompleta,
    }
}