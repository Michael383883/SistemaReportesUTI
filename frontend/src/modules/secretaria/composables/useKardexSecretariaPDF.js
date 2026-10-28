// composables/useKardexSecretariaPDF.js
// PDF del Kardex para secretaria: mismo estilo oficial UMSS (Letter vertical),
// pero SIN las columnas Resolución ni Designación.
// Dependencias: jspdf + jspdf-autotable
//   npm install jspdf jspdf-autotable

import { jsPDF } from 'jspdf'
import autoTable from 'jspdf-autotable'

function norm(v) {
    if (v === null || v === undefined) return ''
    return String(v).trim()
}

// ── Mapa de códigos de plan (igual que el PDF original) ─────────────────────
const PLAN_MAP = {
    '089801': 'CCP',
    '109401': 'ADM',
    '125091': 'COM',
    '126091': 'FIN',
    '059801': 'ECO',
}

// Si el código de MATERIA empieza con "240" se muestra "DESC"
function getPlanAbbrev(plan, materiaCodigo) {
    if (norm(materiaCodigo).startsWith('240')) return 'DESC'
    const codigo = norm(plan)
    return PLAN_MAP[codigo] || codigo
}

/**
 * @param {Object} reporte  { docente: { codigo, nombre }, materias: [...] }
 * @param {Object} opts     { action: 'print' | 'open' | 'save', nombreArchivo }
 *                          (sinResolucionDesignacion se acepta por compatibilidad:
 *                           este PDF nunca incluye esas columnas)
 */
export function generarPDF(reporte, opts = {}) {
    const { action = 'print', nombreArchivo = '' } = opts

    const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'letter' })

    const PAGE_W = doc.internal.pageSize.getWidth()
    const PAGE_H = doc.internal.pageSize.getHeight()
    const MARGIN_L = 12
    const MARGIN_R = 12
    const CONTENT_W = PAGE_W - MARGIN_L - MARGIN_R

    const COLOR_BLACK = [0, 0, 0]
    const COLOR_GRAY_BG = [240, 240, 240]
    const COLOR_ROW_LINE = [170, 170, 170]

    const tituloPdf = 'MATERIAS DICTADAS DE UN DOCENTE'
    const descripcionPdf =
        'Datos Históricos pertenecientes a la Facultad de Ciencias Económicas registrados en el SISS ' +
        'a partir de la gestión 2001. El reporte también detalla los grupos compartidos solo para los ' +
        'cursos Intersemestrales de Verano e Invierno.'
    const lineaDocente = `DOCENTE : (${reporte.docente?.codigo || ''}) - ${reporte.docente?.nombre || ''}`

    // ── Encabezado institucional (se repite en cada página) ─────────────────
    function drawHeader() {
        doc.setFont('helvetica', 'bold')
        doc.setFontSize(5.5)
        doc.setTextColor(...COLOR_BLACK)
        doc.text('UNIVERSIDAD MAYOR DE SAN SIMÓN', MARGIN_L, 6)
        doc.text('FACULTAD DE CIENCIAS ECONÓMICAS', MARGIN_L, 8)

        doc.setFontSize(11)
        doc.text(tituloPdf, PAGE_W / 2, 12, { align: 'center' })

        doc.setDrawColor(...COLOR_ROW_LINE)
        doc.setLineWidth(0.3)
        doc.line(MARGIN_L, 17, PAGE_W - MARGIN_R, 17)

        doc.setFont('helvetica', 'normal')
        doc.setFontSize(6.5)
        doc.setTextColor(40, 40, 40)
        const descLines = doc.splitTextToSize(descripcionPdf, CONTENT_W)
        doc.text(descLines, MARGIN_L, 21)

        const sujetoY = 21 + descLines.length * 3.0 + 3
        doc.setFont('helvetica', 'bold')
        doc.setFontSize(8)
        doc.setTextColor(...COLOR_BLACK)
        doc.text(lineaDocente, MARGIN_L, sujetoY)

        return sujetoY + 3
    }

    // ── Pie de página ───────────────────────────────────────────────────────
    function drawFooter() {
        const pageNum = doc.internal.getCurrentPageInfo().pageNumber
        const footerY = PAGE_H - 5

        doc.setDrawColor(...COLOR_ROW_LINE)
        doc.setLineWidth(0.2)
        doc.line(MARGIN_L, footerY - 3.5, PAGE_W - MARGIN_R, footerY - 3.5)

        doc.setFont('helvetica', 'normal')
        doc.setFontSize(6)
        doc.setTextColor(90, 90, 90)

        doc.text('Procesado UTi - Facultad de Ciencias Económicas', MARGIN_L, footerY)
        doc.text(`Página ${pageNum} de {totalPages}`, PAGE_W / 2, footerY, { align: 'center' })

        const now = new Date()
        const ahora =
            `${String(now.getDate()).padStart(2, '0')}/${String(now.getMonth() + 1).padStart(2, '0')}/${now.getFullYear()} ` +
            now.toLocaleString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true })
        doc.text(ahora, PAGE_W - MARGIN_R, footerY, { align: 'right' })
    }

    doc.setProperties({ title: 'Kardex de docente' })

    const startY = drawHeader()

    // ── Columnas (sin Resolución ni Designación) ────────────────────────────
    const columnas = [
        { header: 'Nº', key: 'nro', width: 10, halign: 'center' },
        { header: 'GESTIÓN', key: 'gestion', width: 30 },
        { header: 'PLAN', key: 'plan', width: 16, halign: 'center' },
        { header: 'MATERIA', key: 'materia', width: 'auto' },
        { header: 'COMPARTIDO', key: 'compartido', width: 26, halign: 'center' },
        { header: 'GRP', key: 'grp', width: 12, halign: 'center' },
    ]

    const filas = (reporte.materias || []).map((m) => ({
        nro: m.nro,
        gestion: m.gestion || '',
        plan: getPlanAbbrev(m.plan, m.materia_codigo),
        materia: m.materia || '',
        compartido: norm(m.compartido) === 'COMPARTIDO' ? 'COMPARTIDO' : '',
        grp: m.grp || '',
    }))

    const columnStyles = {}
    columnas.forEach((c, i) => {
        columnStyles[i] = { cellWidth: c.width, ...(c.halign ? { halign: c.halign } : {}) }
    })

    autoTable(doc, {
        startY,
        margin: { left: MARGIN_L, right: MARGIN_R },
        tableWidth: CONTENT_W,
        head: [columnas.map((c) => c.header)],
        body: filas.map((f) => columnas.map((c) => f[c.key])),

        styles: {
            font: 'helvetica',
            fontSize: 6.2,
            cellPadding: { top: 1.2, bottom: 1.2, left: 1.5, right: 1.5 },
            textColor: COLOR_BLACK,
            lineColor: COLOR_ROW_LINE,
            lineWidth: 0,
            overflow: 'linebreak',
            valign: 'middle',
            fillColor: false,
        },

        headStyles: {
            fillColor: COLOR_GRAY_BG,
            textColor: COLOR_BLACK,
            fontStyle: 'bold',
            fontSize: 6.2,
            halign: 'center',
            valign: 'middle',
            lineColor: [130, 130, 130],
            lineWidth: 0.3,
        },

        alternateRowStyles: { fillColor: false },

        columnStyles,

        // Solo líneas horizontales entre filas
        didDrawCell(data) {
            if (data.section !== 'body') return
            if (data.column.index !== data.table.columns.length - 1) return

            const { y, height } = data.cell
            doc.setDrawColor(...COLOR_ROW_LINE)
            doc.setLineWidth(0.2)
            doc.line(MARGIN_L, y + height, MARGIN_L + CONTENT_W, y + height)
        },

        didAddPage() {
            drawHeader()
        },

        didDrawPage() {
            drawFooter()
        },
    })

    if (typeof doc.putTotalPages === 'function') {
        doc.putTotalPages('{totalPages}')
    }

    // ── Acción ──────────────────────────────────────────────────────────────
    const fileName = nombreArchivo || `kardex_docente_${reporte.docente?.codigo || 'doc'}.pdf`

    if (action === 'save') {
        doc.save(fileName)
    } else if (action === 'print') {
        const blob = doc.output('blob')
        const url = URL.createObjectURL(blob)
        const iframe = document.createElement('iframe')
        iframe.style.display = 'none'
        iframe.src = url
        document.body.appendChild(iframe)
        iframe.onload = () => {
            iframe.contentWindow.focus()
            iframe.contentWindow.print()
            setTimeout(() => {
                document.body.removeChild(iframe)
                URL.revokeObjectURL(url)
            }, 2000)
        }
    } else {
        const blob = doc.output('blob')
        const url = URL.createObjectURL(blob)
        window.open(url, '_blank')
        setTimeout(() => URL.revokeObjectURL(url), 60_000)
    }
}