// composables/useGenerarPDF.js
// Genera un PDF de impresión oficial estilo UMSS – Materias dictadas de un docente
// Dependencias: jspdf  +  jspdf-autotable
//   npm install jspdf jspdf-autotable

import { jsPDF } from 'jspdf'
import autoTable from 'jspdf-autotable'

/**
 * Formatea una gestión para que sea legible en el PDF.
 * Ejemplos: "2021/4 - Invierno", "2024/1"
 */
function formatGestion(g) {
    return g || ''
}

/**
 * Genera y abre/descarga el PDF del reporte docente.
 * @param {Object} reporte  - objeto devuelto por la API (misma forma que reporte.value)
 * @param {Object} [opts]
 * @param {'open'|'save'|'print'} [opts.action='open']  - qué hacer con el PDF
 */
export function generarPDF(reporte, opts = {}) {
    const { action = 'open' } = opts

    const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'letter' })

    // ── Fuentes y constantes ─────────────────────────────────────────────────────
    const PAGE_W = doc.internal.pageSize.getWidth()   // ~279 mm (letter landscape)
    const MARGIN_L = 14
    const MARGIN_R = 14
    const COL_WIDTH = PAGE_W - MARGIN_L - MARGIN_R

    // Colores
    const COLOR_BLACK = [0, 0, 0]
    const COLOR_GRAY_BG = [235, 235, 235]   // cabecera de tabla
    const COLOR_GRAY_LN = [200, 200, 200]   // líneas

    // ── Encabezado institucional ─────────────────────────────────────────────────
    doc.setFont('helvetica', 'bold')
    doc.setFontSize(9)
    doc.setTextColor(...COLOR_BLACK)

    doc.text('UNIVERSIDAD MAYOR DE SAN SIMON', MARGIN_L, 14)
    doc.text('FACULTAD DE CIENCIAS ECONOMICAS', MARGIN_L, 18.5)

    // Título centrado
    doc.setFontSize(13)
    doc.setFont('helvetica', 'bold')
    doc.text('MATERIAS DICTADAS DE UN DOCENTE', PAGE_W / 2, 16.5, { align: 'center' })

    // Línea divisoria
    doc.setDrawColor(...COLOR_GRAY_LN)
    doc.setLineWidth(0.3)
    doc.line(MARGIN_L, 21, PAGE_W - MARGIN_R, 21)

    // Descripción
    doc.setFont('helvetica', 'normal')
    doc.setFontSize(7.5)
    doc.setTextColor(60, 60, 60)
    const descripcion =
        'Datos Históricos pertenecientes a la Facultad de Ciencias Económicas registrados en el SISS ' +
        'a partir de la gestión 2001. El reporte también detalla los grupos compartidos solo para los ' +
        'cursos Intersemestrales de Verano e Invierno.'
    const descLines = doc.splitTextToSize(descripcion, COL_WIDTH)
    doc.text(descLines, MARGIN_L, 26)

    // ── Datos del docente ────────────────────────────────────────────────────────
    let cursorY = 26 + descLines.length * 3.8 + 3

    doc.setFont('helvetica', 'bold')
    doc.setFontSize(9.5)
    doc.setTextColor(...COLOR_BLACK)

    const codigoDoc = reporte.docente?.codigo || ''
    const nombreDoc = reporte.docente?.nombre || ''
    doc.text(`DOCENTE : (${codigoDoc}) - ${nombreDoc}`, MARGIN_L, cursorY)

    cursorY += 5

    // ── Tabla de materias ────────────────────────────────────────────────────────
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

    autoTable(doc, {
        startY: cursorY,
        margin: { left: MARGIN_L, right: MARGIN_R },
        head: [columnas.map((c) => c.header)],
        body: filas.map((f) => columnas.map((c) => f[c.dataKey])),

        // ── Estilos generales ──
        styles: {
            font: 'helvetica',
            fontSize: 8,
            cellPadding: { top: 2, bottom: 2, left: 3, right: 3 },
            textColor: COLOR_BLACK,
            lineColor: COLOR_GRAY_LN,
            lineWidth: 0.2,
            overflow: 'linebreak',
            valign: 'top',
        },

        // ── Cabecera ──
        headStyles: {
            fillColor: COLOR_GRAY_BG,
            textColor: COLOR_BLACK,
            fontStyle: 'bold',
            fontSize: 7.5,
            halign: 'center',
        },

        // ── Filas alternadas ──
        alternateRowStyles: {
            fillColor: [248, 248, 248],
        },

        // ── Anchos de columna (mm) ──
        columnStyles: {
            0: { cellWidth: 9, halign: 'center' },   // Nº
            1: { cellWidth: 32 },                       // Gestión
            2: { cellWidth: 14, halign: 'center' },    // Plan
            3: { cellWidth: 55 },                       // Materia
            4: { cellWidth: 24, halign: 'center' },    // Compartido
            5: { cellWidth: 12, halign: 'center' },    // GRP
            6: { cellWidth: 28 },                       // Resolución
            7: { cellWidth: 'auto' },                   // Designación (resto)
        },

        // ── Estilo por celda (compartido en violeta suave) ──
        didParseCell(data) {
            if (data.section === 'body' && data.column.index === 4 && data.cell.raw === 'COMPARTIDO') {
                data.cell.styles.textColor = [90, 60, 180]
                data.cell.styles.fontStyle = 'bold'
            }
        },

        // ── Pie de página con número de página ──
        didDrawPage(data) {
            const pageCount = doc.internal.getNumberOfPages()
            const pageNum = doc.internal.getCurrentPageInfo().pageNumber
            doc.setFont('helvetica', 'normal')
            doc.setFontSize(7)
            doc.setTextColor(120, 120, 120)
            doc.text(
                `Página ${pageNum} de ${pageCount}`,
                PAGE_W / 2,
                doc.internal.pageSize.getHeight() - 6,
                { align: 'center' }
            )
            // Fecha de impresión
            const ahora = new Date().toLocaleString('es-BO', { dateStyle: 'short', timeStyle: 'short' })
            doc.text(`Generado: ${ahora}`, PAGE_W - MARGIN_R, doc.internal.pageSize.getHeight() - 6, { align: 'right' })
        },
    })

    // ── Acción ──────────────────────────────────────────────────────────────────
    const fileName = `reporte_docente_${codigoDoc || 'doc'}.pdf`

    if (action === 'save') {
        doc.save(fileName)
    } else if (action === 'print') {
        // Abrir en nueva pestaña para que el navegador muestre el diálogo de impresión
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
        // 'open' → abrir en nueva pestaña (el navegador ofrece imprimir / descargar)
        const blob = doc.output('blob')
        const url = URL.createObjectURL(blob)
        window.open(url, '_blank')
        // Opcional: revocar después de un rato
        setTimeout(() => URL.revokeObjectURL(url), 60_000)
    }
}