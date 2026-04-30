// composables/useGenerarPDF.js
// Genera un PDF oficial estilo UMSS – Materias dictadas de un docente
// Orientación: PORTRAIT (vertical) – Letter 216 × 279 mm
// Dependencias: jspdf  +  jspdf-autotable
//   npm install jspdf jspdf-autotable

import { jsPDF } from 'jspdf'
import autoTable from 'jspdf-autotable'

function formatGestion(g) {
    return g || ''
}

export function generarPDF(reporte, opts = {}) {
    const { action = 'open' } = opts

    // ── PORTRAIT Letter (216 × 279 mm) ──────────────────────────────────────────
    const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'letter' })

    const PAGE_W = doc.internal.pageSize.getWidth()   // ~215.9 mm
    const PAGE_H = doc.internal.pageSize.getHeight()  // ~279.4 mm
    const MARGIN_L = 12
    const MARGIN_R = 12
    const CONTENT_W = PAGE_W - MARGIN_L - MARGIN_R    // ~191.9 mm útiles

    const COLOR_BLACK = [0, 0, 0]
    const COLOR_GRAY_BG = [218, 218, 218]
    const COLOR_GRAY_LN = [170, 170, 170]
    const COLOR_ALT_ROW = [246, 246, 246]

    // ════════════════════════════════════════════════════════════════════════════
    // Encabezado institucional — se repite en cada página
    // ════════════════════════════════════════════════════════════════════════════
    function drawHeader() {
        // ── Institución izquierda ────────────────────────────────────────────────
        doc.setFont('helvetica', 'bold')
        doc.setFontSize(7.5)
        doc.setTextColor(...COLOR_BLACK)
        doc.text('UNIVERSIDAD MAYOR DE SAN SIMON', MARGIN_L, 10)
        doc.text('FACULTAD DE CIENCIAS ECONOMICAS', MARGIN_L, 14)

        // ── Título centrado (mismo bloque vertical) ──────────────────────────────
        doc.setFontSize(11)
        doc.text('MATERIAS DICTADAS DE UN DOCENTE', PAGE_W / 2, 12, { align: 'center' })

        // ── Línea divisoria ──────────────────────────────────────────────────────
        doc.setDrawColor(...COLOR_GRAY_LN)
        doc.setLineWidth(0.3)
        doc.line(MARGIN_L, 17, PAGE_W - MARGIN_R, 17)

        // ── Descripción (6.5 pt) ─────────────────────────────────────────────────
        doc.setFont('helvetica', 'normal')
        doc.setFontSize(6.5)
        doc.setTextColor(40, 40, 40)
        const descripcion =
            'Datos Históricos pertenecientes a la Facultad de Ciencias Económicas registrados en el SISS ' +
            'a partir de la gestión 2001. El reporte también detalla los grupos compartidos solo para los ' +
            'cursos Intersemestrales de Verano e Invierno.'
        const descLines = doc.splitTextToSize(descripcion, CONTENT_W)
        doc.text(descLines, MARGIN_L, 21)

        // ── Nombre del docente ───────────────────────────────────────────────────
        const docenteY = 21 + descLines.length * 3.0 + 3
        doc.setFont('helvetica', 'bold')
        doc.setFontSize(8)
        doc.setTextColor(...COLOR_BLACK)
        const codigoDoc = reporte.docente?.codigo || ''
        const nombreDoc = reporte.docente?.nombre || ''
        doc.text(`DOCENTE : (${codigoDoc}) - ${nombreDoc}`, MARGIN_L, docenteY)

        return docenteY + 3
    }

    const startY = drawHeader()

    // ── Columnas ──────────────────────────────────────────────────────────────────
    // Ancho útil portrait ~191.9 mm
    // Distribución (mm): 6 + 22 + 8 + 52 + 20 + 8 + 22 + auto(~53.9) = ~191.9
    const columnas = [
        { header: 'Nº', dataKey: 'nro' },
        { header: 'GESTIÓN', dataKey: 'gestion' },
        { header: 'PLAN', dataKey: 'plan' },
        { header: 'MATERIA', dataKey: 'materia' },
        { header: 'COMPARTIDO', dataKey: 'compartido' },
        { header: 'GRP', dataKey: 'grp' },
        { header: 'RESOLUCIÓN', dataKey: 'resolucion' },
        { header: 'DESIGNACIÓN', dataKey: 'designacion' },
    ]

    const filas = (reporte.materias || []).map((m) => ({
        nro: m.nro,
        gestion: formatGestion(m.gestion),
        plan: m.plan || '',
        materia: m.materia || '',
        compartido: m.compartido ? 'COMPARTIDO' : '',
        grp: m.grp || '',
        resolucion: m.resolucion || '',
        designacion: m.designacion || '',
    }))

    // ── Tabla ─────────────────────────────────────────────────────────────────────
    autoTable(doc, {
        startY,
        margin: { left: MARGIN_L, right: MARGIN_R },
        tableWidth: CONTENT_W,
        head: [columnas.map((c) => c.header)],
        body: filas.map((f) => columnas.map((c) => f[c.dataKey])),

        styles: {
            font: 'helvetica',
            fontSize: 6.2,                          // ← reducido
            cellPadding: { top: 1.2, bottom: 1.2, left: 1.5, right: 1.5 },
            textColor: COLOR_BLACK,
            lineColor: COLOR_GRAY_LN,
            lineWidth: 0.2,
            overflow: 'linebreak',
            valign: 'middle',
        },

        headStyles: {
            fillColor: COLOR_GRAY_BG,
            textColor: COLOR_BLACK,
            fontStyle: 'bold',
            fontSize: 6.2,                           // ← reducido
            halign: 'center',
            valign: 'middle',
            lineColor: [130, 130, 130],
            lineWidth: 0.3,
        },

        alternateRowStyles: {
            fillColor: COLOR_ALT_ROW,
        },

        columnStyles: {
            0: { cellWidth: 6, halign: 'center' },  // Nº
            1: { cellWidth: 22 },  // Gestión  — "2021/4 - Invierno" 1 línea
            2: { cellWidth: 8, halign: 'center' },  // Plan     — ajusta "ADM/COM/FIN"
            3: { cellWidth: 52 },  // Materia  — texto largo
            4: { cellWidth: 20, halign: 'center' },  // Compartido
            5: { cellWidth: 8, halign: 'center' },  // GRP
            6: { cellWidth: 22 },  // Resolución
            7: { cellWidth: 'auto' },  // Designación (~53.9 mm)
        },

        didParseCell(data) {
            // COMPARTIDO en negrita
            if (
                data.section === 'body' &&
                data.column.index === 4 &&
                data.cell.raw === 'COMPARTIDO'
            ) {
                data.cell.styles.textColor = COLOR_BLACK
                data.cell.styles.fontStyle = 'bold'
                data.cell.styles.fontSize = 5.8
            }
            // Materia y Designación: fuente ligeramente más pequeña para acomodar texto
            if (data.section === 'body' && (data.column.index === 3 || data.column.index === 7)) {
                data.cell.styles.fontSize = 6.0
            }
        },

        didAddPage() {
            drawHeader()
        },

        didDrawPage() {
            const pageCount = doc.internal.getNumberOfPages()
            const pageNum = doc.internal.getCurrentPageInfo().pageNumber
            const footerY = PAGE_H - 5

            // Línea sobre pie
            doc.setDrawColor(...COLOR_GRAY_LN)
            doc.setLineWidth(0.2)
            doc.line(MARGIN_L, footerY - 3.5, PAGE_W - MARGIN_R, footerY - 3.5)

            doc.setFont('helvetica', 'normal')
            doc.setFontSize(6)
            doc.setTextColor(90, 90, 90)

            doc.text('Procesado UTi - Facultad de Ciencias Económicas', MARGIN_L, footerY)
            doc.text(`Página ${pageNum} de ${pageCount}`, PAGE_W / 2, footerY, { align: 'center' })

            const ahora = new Date().toLocaleString('es-BO', {
                dateStyle: 'short',
                timeStyle: 'short',
            })
            doc.text(ahora, PAGE_W - MARGIN_R, footerY, { align: 'right' })
        },
    })

    // ── Acción ────────────────────────────────────────────────────────────────────
    const codigoDoc = reporte.docente?.codigo || 'doc'
    const fileName = `reporte_docente_${codigoDoc}.pdf`

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