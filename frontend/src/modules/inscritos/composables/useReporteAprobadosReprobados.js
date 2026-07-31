// composables/useReporteAprobadosReprobados.js
// Genera el PDF académico de RESUMEN DE APROBADOS Y REPROBADOS
// (formato institucional UMSS), con jsPDF + jspdf-autotable.
// Matriz Docente x Carrera, con Inscritos/Aprobados/Reprobados por
// carrera y totales generales al final.

import { ref } from 'vue'
import { jsPDF } from 'jspdf'
import autoTable from 'jspdf-autotable'

// ── Paleta institucional (misma que useReporteInscritosTotales.js) ──────
const C_BLACK = [0, 0, 0]
const C_WHITE = [255, 255, 255]
const C_HEAD_BG = [240, 240, 240]
const C_GRAY_LINE = [140, 140, 140]
const C_ZERO_TEXT = [150, 150, 150]
const C_DIVIDER = [140, 140, 140] // gris oscuro para separar grupos de carrera

// ── Helpers de documento ─────────────────────────────────────────────────
function crearDocumento() {
    return new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'letter' })
    // landscape: con 3 columnas (I/A/R) por carrera, portrait queda muy angosto
}

function fechaFormateada() {
    const d = new Date()
    const dia = String(d.getDate()).padStart(2, '0')
    const mes = String(d.getMonth() + 1).padStart(2, '0')
    const anio = d.getFullYear()
    const hora = d.toLocaleString('en-US', {
        hour: 'numeric', minute: '2-digit', second: '2-digit', hour12: true,
    })
    return `${dia}/${mes}/${anio} ${hora}`
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
        doc.text('Procesado SIA-UTI - Facultad de Ciencias Económicas', ML, fy)
        doc.text(`Página ${i} de ${total}`, PAGE_W / 2, fy, { align: 'center' })
        doc.text(fechaActual, PAGE_W - MR, fy, { align: 'right' })
    }
}

/**
 * Devuelve la salida final del PDF según el modo ('ver' o 'descargar').
 * Misma logica que useReporteInscritosTotales.js.
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
// RESUMEN DE APROBADOS/REPROBADOS — matriz Docente x Carrera (I/A/R)
// ────────────────────────────────────────────────────────────────────────────
function generarResumenAprobadosReprobados(data, anio, periodo, modo = 'descargar', ventanaPreabierta = null) {
    const doc = crearDocumento()
    const PAGE_W = doc.internal.pageSize.getWidth()
    const ML = 8
    const MR = 8
    const CW = PAGE_W - ML - MR
    const fechaActual = fechaFormateada()
    const TITULO = 'RESUMEN DE APROBADOS Y REPROBADOS'
    const NOTA = 'No incluye alumnos de mesa'
    const HEADER_H = drawPageHeader(doc, { titulo: TITULO, anio, periodo, fechaActual, notaSuperior: NOTA })

    // ── Carreras dinámicas (según aparición en los datos) ────────────────────
    const carrerasSet = []
    data.forEach(d => d.carreras.forEach(c => {
        if (!carrerasSet.includes(c.carrera)) carrerasSet.push(c.carrera)
    }))
    const carreras = carrerasSet.length ? carrerasSet : ['ADM', 'ECO', 'CCP', 'COM', 'FIN']

    const totalGlobalIns = data.reduce((s, d) => s + (d.total_inscritos ?? 0), 0)
    const totalGlobalApr = data.reduce((s, d) => s + (d.total_aprobados ?? 0), 0)
    const totalGlobalRep = data.reduce((s, d) => s + (d.total_reprobados ?? 0), 0)

    // ── Encabezado de 2 filas: carrera con colSpan 3, subcolumnas I/A/R ──────
    const headMatriz = [
        [
            { content: 'N°', rowSpan: 2 },
            { content: 'CÓDIGO', rowSpan: 2 },
            { content: 'DOCENTE', rowSpan: 2 },
            ...carreras.map(c => ({ content: c, colSpan: 3, styles: { halign: 'center' } })),
            { content: 'TOTAL', colSpan: 3, styles: { halign: 'center' } },
        ],
        [
            ...carreras.flatMap(() => ['Ins.', 'Apr.', 'Rep.']),
            'Ins.', 'Apr.', 'Rep.',
        ],
    ]

    const bodyMatriz = data.map((docente, idx) => {
        const porCarrera = {}
        docente.carreras.forEach(c => { porCarrera[c.carrera] = c })

        const celdasCarreras = carreras.flatMap(c => {
            const info = porCarrera[c]
            const ins = info?.subtotal_inscritos ?? 0
            const apr = info?.subtotal_aprobados ?? 0
            const rep = info?.subtotal_reprobados ?? 0
            return [
                ins > 0 ? String(ins) : '—',
                ins > 0 ? String(apr) : '—',
                ins > 0 ? String(rep) : '—',
            ]
        })

        return [
            String(idx + 1),
            docente.cod_docente,
            `${docente.apellidos}, ${docente.nombres}`,
            ...celdasCarreras,
            String(docente.total_inscritos ?? 0),
            String(docente.total_aprobados ?? 0),
            String(docente.total_reprobados ?? 0),
        ]
    })

    bodyMatriz.push([
        { content: '', styles: { fillColor: C_WHITE, lineWidth: 0 } },
        { content: '', styles: { fillColor: C_WHITE, lineWidth: 0 } },
        { content: 'TOTAL GENERAL', styles: { fontStyle: 'bold', fillColor: C_HEAD_BG, lineWidth: 0 } },
        ...carreras.flatMap(c => {
            const sumIns = data.reduce((s, d) => s + (d.carreras.find(x => x.carrera === c)?.subtotal_inscritos ?? 0), 0)
            const sumApr = data.reduce((s, d) => s + (d.carreras.find(x => x.carrera === c)?.subtotal_aprobados ?? 0), 0)
            const sumRep = data.reduce((s, d) => s + (d.carreras.find(x => x.carrera === c)?.subtotal_reprobados ?? 0), 0)
            const estilo = { fontStyle: 'bold', halign: 'center', fillColor: C_HEAD_BG, lineWidth: 0 }
            return [
                { content: sumIns > 0 ? String(sumIns) : '—', styles: estilo },
                { content: sumIns > 0 ? String(sumApr) : '—', styles: estilo },
                { content: sumIns > 0 ? String(sumRep) : '—', styles: estilo },
            ]
        }),
        { content: String(totalGlobalIns), styles: { fontStyle: 'bold', halign: 'center', fillColor: C_HEAD_BG, lineWidth: 0 } },
        { content: String(totalGlobalApr), styles: { fontStyle: 'bold', halign: 'center', fillColor: C_HEAD_BG, lineWidth: 0 } },
        { content: String(totalGlobalRep), styles: { fontStyle: 'bold', halign: 'center', fillColor: C_HEAD_BG, lineWidth: 0 } },
    ])

    autoTable(doc, {
        startY: HEADER_H,
        margin: { left: ML, right: MR, top: HEADER_H, bottom: 12 },
        tableWidth: CW,
        head: headMatriz,
        body: bodyMatriz,
        alternateRowStyles: { fillColor: C_WHITE },
        styles: {
            font: 'helvetica', fontSize: 7,
            cellPadding: { top: 0.7, bottom: 0.7, left: 1.5, right: 1.5 },
            textColor: C_BLACK, lineColor: C_GRAY_LINE, lineWidth: { top: 0, right: 0, bottom: 0.15, left: 0 },
            fillColor: C_WHITE, halign: 'center', valign: 'middle',
        },
        headStyles: {
            fillColor: C_HEAD_BG, textColor: C_BLACK, fontStyle: 'bold',
            fontSize: 7.2, halign: 'center', valign: 'middle',
            lineColor: C_GRAY_LINE, lineWidth: { top: 0, right: 0, bottom: 0.3, left: 0 },
        },
        columnStyles: {
            0: { cellWidth: 12 },
            1: { cellWidth: 18, font: 'courier' },
            2: { cellWidth: 48, halign: 'left' },
        },
        didParseCell(cellData) {
            if (cellData.section !== 'body') return
            const raw = cellData.row.raw
            const isTotalRow = Array.isArray(raw) && typeof raw[2] === 'object' && raw[2]?.content === 'TOTAL GENERAL'
            if (isTotalRow) return
            if (cellData.column.index >= 3 && cellData.cell.raw === '—') {
                cellData.cell.styles.textColor = C_ZERO_TEXT
            }
        },
        didDrawPage() {
            if (doc.internal.getCurrentPageInfo().pageNumber > 1) {
                drawPageHeader(doc, { titulo: TITULO, anio, periodo, fechaActual, notaSuperior: NOTA })
            }
        },
        didDrawCell(cellData) {
            const col = cellData.column.index
            const raw = cellData.cell.raw

            // Cabecera fila 1: celdas de carrera con colSpan 3 (no dibujar tras "TOTAL", es el borde de la tabla)
            const esGrupoCarreraHeader =
                cellData.section === 'head' &&
                raw && typeof raw === 'object' &&
                raw.colSpan === 3 &&
                raw.content !== 'TOTAL'

            // Última columna de cada tripleta (Insc/Aprob/Reprob) -> divisor tras cada carrera y antes de TOTAL
            const esUltimaDeTripleta = col >= 3 && (col - 3) % 3 === 2

            // Después de la columna DOCENTE, separando nombres de la grilla de datos
            const esColDocente = col === 2

            if (esGrupoCarreraHeader || esUltimaDeTripleta || esColDocente) {
                const { x, y, width, height } = cellData.cell
                doc.setDrawColor(...C_DIVIDER)
                doc.setLineWidth(0.35)
                doc.line(x + width, y, x + width, y + height)
            }
        },
    })

    drawFooters(doc, fechaActual)

    const filename = `Resumen_Aprobados_Reprobados_${anio}_${periodo}.pdf`
    finalizarSalida(doc, filename, modo, ventanaPreabierta)
}

// ────────────────────────────────────────────────────────────────────────────
// Composable público — resumen de aprobados/reprobados
// ────────────────────────────────────────────────────────────────────────────
export function useReporteAprobadosReprobados() {
    const generandoAprobados = ref(false)

    async function exportarAprobadosReprobados(data, anio, periodo, modo = 'descargar', ventanaPreabierta = null) {
        if (!data?.length) {
            if (ventanaPreabierta && !ventanaPreabierta.closed) ventanaPreabierta.close()
            return
        }
        generandoAprobados.value = true
        try {
            generarResumenAprobadosReprobados(data, anio, periodo, modo, ventanaPreabierta)
        } finally {
            generandoAprobados.value = false
        }
    }

    return {
        generandoAprobados,
        exportarAprobadosReprobados,
    }
}