// composables/useReporteInscritosTotales.js
// Genera el PDF académico de RESUMEN DE TOTALES de inscritos
// (formato institucional UMSS), con jsPDF + jspdf-autotable.
// Incluye la matriz Docente × Carrera con totales de inscritos,
// más columnas de Aprobados / Reprobados / Total a nivel docente.

import { ref } from 'vue'
import { jsPDF } from 'jspdf'
import autoTable from 'jspdf-autotable'

// ── Paleta institucional ─────────────────────────────────────────────────
const C_BLACK = [0, 0, 0]
const C_WHITE = [255, 255, 255]
const C_HEAD_BG = [240, 240, 240]
const C_GRAY_LINE = [140, 140, 140]
const C_ZERO_TEXT = [150, 150, 150]
const C_DIVIDER = [90, 90, 90] // gris oscuro para separar columnas de carrera

// ── Helpers de documento ─────────────────────────────────────────────────
function crearDocumento() {
    return new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'letter' })
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
 * Devuelve la salida final del PDF según el modo:
 * - 'ver': redirige una ventana YA abierta (pre-abierta en el clic del
 *   usuario) hacia el blob del PDF. Si no hay ventana pre-abierta, intenta
 *   abrir una nueva (puede ser bloqueada por el navegador si no hay gesto
 *   de usuario directo). Si falla, cae a descarga como último recurso.
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
// RESUMEN DE TOTALES — matriz Docente × Carrera + Aprob./Reprob./Total
// ────────────────────────────────────────────────────────────────────────────
function generarResumenTotales(data, anio, periodo, modo = 'descargar', ventanaPreabierta = null) {
    const doc = crearDocumento()
    const PAGE_W = doc.internal.pageSize.getWidth()
    const ML = 8
    const MR = 8
    const CW = PAGE_W - ML - MR
    const fechaActual = fechaFormateada()
    const TITULO = 'INSCRITOS — SOLO TOTALES'
    const NOTA = 'No incluye alumnnos de mesa'
    const HEADER_H = drawPageHeader(doc, { titulo: TITULO, anio, periodo, fechaActual, notaSuperior: NOTA })

    // ── Carreras dinámicas (según aparición en los datos) ────────────────────
    const carrerasSet = []
    data.forEach(d => d.carreras.forEach(c => {
        if (!carrerasSet.includes(c.carrera)) carrerasSet.push(c.carrera)
    }))
    const carreras = carrerasSet.length ? carrerasSet : ['ADM', 'ECO', 'CCP', 'COM', 'FIN']

    // Índice de la última columna de carrera (para las líneas divisorias)
    const COL_FIJAS = 3 // N°, CÓDIGO, DOCENTE
    const ultimaColCarrera = COL_FIJAS + carreras.length - 1

    const totalGlobalIns = data.reduce((s, d) =>
        s + (d.total_inscritos ?? d.carreras.reduce((s2, c) => s2 + (c.subtotal_inscritos ?? c.subtotal ?? 0), 0)), 0)
    const totalGlobalApr = data.reduce((s, d) =>
        s + (d.total_aprobados ?? d.carreras.reduce((s2, c) => s2 + (c.subtotal_aprobados ?? 0), 0)), 0)
    const totalGlobalRep = data.reduce((s, d) =>
        s + (d.total_reprobados ?? d.carreras.reduce((s2, c) => s2 + (c.subtotal_reprobados ?? 0), 0)), 0)
    
    // ── Tabla: matriz Docente × Carrera + APROB./REPROB./TOTAL ───────────────
    const headMatriz = [['N°', 'CÓDIGO', 'DOCENTE', ...carreras, 'APROB.', 'REPROB.', 'TOTAL']]

    const bodyMatriz = data.map((docente, idx) => {
        const totPorCarrera = {}
        docente.carreras.forEach(c => {
            totPorCarrera[c.carrera] = c.subtotal_inscritos ?? c.subtotal ?? 0
        })

        // Totales por docente: igual lógica que useReporteAprobadosReprobados.js,
        // sumando desde carreras (solo el total, sin desglose por carrera)
        const totAprobDocente = docente.carreras.reduce((s, c) => s + (c.subtotal_aprobados ?? 0), 0)
        const totReprobDocente = docente.carreras.reduce((s, c) => s + (c.subtotal_reprobados ?? 0), 0)
        const totInscritosDocente = docente.carreras.reduce((s, c) => s + (c.subtotal_inscritos ?? c.subtotal ?? 0), 0)

        return [
            String(idx + 1),
            docente.cod_docente,
            `${docente.apellidos}, ${docente.nombres}`,
            ...carreras.map(c => {
                const val = totPorCarrera[c] ?? 0
                return val > 0 ? String(val) : '—'
            }),
            String(docente.total_aprobados ?? totAprobDocente),
            String(docente.total_reprobados ?? totReprobDocente),
            String(docente.total_inscritos ?? totInscritosDocente),
        ]
    })

    bodyMatriz.push([
        { content: '', styles: { fillColor: C_WHITE, lineWidth: 0 } },
        { content: '', styles: { fillColor: C_WHITE, lineWidth: 0 } },
        { content: 'TOTAL GENERAL', styles: { fontStyle: 'bold', fillColor: C_HEAD_BG, lineWidth: 0 } },
        ...carreras.map(c => {
            const sum = data.reduce((s, d) => {
                const car = d.carreras.find(x => x.carrera === c)
                return s + (car?.subtotal_inscritos ?? car?.subtotal ?? 0)
            }, 0)
            return { content: sum > 0 ? String(sum) : '—', styles: { fontStyle: 'bold', halign: 'center', fillColor: C_HEAD_BG, lineWidth: 0 } }
        }),
        { content: String(totalGlobalApr), styles: { fontStyle: 'bold', halign: 'center', fillColor: C_HEAD_BG, lineWidth: 0 } },
        { content: String(totalGlobalRep), styles: { fontStyle: 'bold', halign: 'center', fillColor: C_HEAD_BG, lineWidth: 0 } },
        { content: String(totalGlobalIns), styles: { fontStyle: 'bold', halign: 'center', fillColor: C_HEAD_BG, lineWidth: 0 } },
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
            0: { cellWidth: 9 },
            1: { cellWidth: 18, font: 'courier' },
            2: { cellWidth: 55, halign: 'left' },
        },
        didParseCell(data) {
            if (data.section !== 'body') return
            const raw = data.row.raw
            const isTotalRow = Array.isArray(raw) && typeof raw[2] === 'object' && raw[2]?.content === 'TOTAL GENERAL'
            if (isTotalRow) return
            if (data.column.index >= 3 && data.column.index <= ultimaColCarrera && data.cell.raw === '—') {
                data.cell.styles.textColor = C_ZERO_TEXT
            }
        },
        // Líneas divisorias verticales gris oscuro: después de DOCENTE y entre cada carrera
        // (la última, en ultimaColCarrera, separa además el bloque de carreras del de Aprob./Reprob./Total)
        didDrawCell(cellData) {
            const col = cellData.column.index
            const esColDocente = col === 2
            const esColCarrera = col >= COL_FIJAS && col <= ultimaColCarrera

            if (esColDocente || esColCarrera) {
                const { x, y, width, height } = cellData.cell
                doc.setDrawColor(...C_DIVIDER)
                doc.setLineWidth(0.35)
                doc.line(x + width, y, x + width, y + height)
            }
        },
        didDrawPage() {
            if (doc.internal.getCurrentPageInfo().pageNumber > 1) {
                drawPageHeader(doc, { titulo: TITULO, anio, periodo, fechaActual, notaSuperior: NOTA })
            }
        },
    })

    drawFooters(doc, fechaActual)

    const filename = `Resumen_Totales_Inscritos_${anio}_${periodo}.pdf`
    finalizarSalida(doc, filename, modo, ventanaPreabierta)
}

// ────────────────────────────────────────────────────────────────────────────
// Composable público — SOLO resumen de totales
// ────────────────────────────────────────────────────────────────────────────
export function useReporteInscritosTotales() {
    const generandoResumen = ref(false)

    async function exportarResumenTotales(data, anio, periodo, modo = 'descargar', ventanaPreabierta = null) {
        if (!data?.length) {
            if (ventanaPreabierta && !ventanaPreabierta.closed) ventanaPreabierta.close()
            return
        }
        generandoResumen.value = true
        try {
            generarResumenTotales(data, anio, periodo, modo, ventanaPreabierta)
        } finally {
            generandoResumen.value = false
        }
    }

    return {
        generandoResumen,
        exportarResumenTotales,
    }
}