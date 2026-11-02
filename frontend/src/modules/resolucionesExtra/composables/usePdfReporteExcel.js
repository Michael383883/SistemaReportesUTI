// composables/usePdfReporteExcel.js
// Genera un PDF con las mismas filas que se ven en la vista previa del Excel.
// Requiere: npm i jspdf jspdf-autotable
import { jsPDF } from 'jspdf'
import autoTable from 'jspdf-autotable'

const COLOR_SIN_DOCUMENTO = [255, 237, 213] // naranja suave
const COLOR_COMPARTIDA = [252, 231, 243]    // rosa suave

/**
 * @param {Array}  filas  reporteExcel.previewMostrado.value
 * @param {Object} opts   { titulo, version, gestion, anio, periodo, soloActivos, totalDocentes }
 * @returns {{ url: string, filename: string }}
 */
export function generarPDFReporteExcel(filas, opts = {}) {
    const {
        titulo = 'Reporte Excel',
        version = '',
        gestion = '',
        anio = '',
        periodo = '',
        soloActivos = false,
        totalDocentes = 0,
    } = opts

    const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' })
    const pageW = doc.internal.pageSize.getWidth()
    const pageH = doc.internal.pageSize.getHeight()

    // ── Encabezado ──
    doc.setFont('helvetica', 'bold')
    doc.setFontSize(14)
    doc.text(version ? `${titulo} – ${version}` : titulo, 10, 12)

    doc.setFont('helvetica', 'normal')
    doc.setFontSize(8.5)
    doc.setTextColor(100)
    const partes = []
    if (gestion) partes.push(`Gestión: ${gestion}`)
    if (soloActivos) partes.push(`Activos en ${anio} / periodo ${periodo}`)
    partes.push(`Docentes: ${totalDocentes}`)
    partes.push(`Filas: ${filas.length}`)
    doc.text(partes.join('   |   '), 10, 17.5)
    doc.setTextColor(0)

    // ── Cuerpo (respeta los rowspan de la vista) ──
    const body = filas.map((item) => {
        const fondo = item.SIN_DOCUMENTO
            ? COLOR_SIN_DOCUMENTO
            : item.COMPARTIDA
                ? COLOR_COMPARTIDA
                : null
        const base = fondo ? { fillColor: fondo } : {}
        const row = []

        if (item.INICIO_GRUPO) {
            row.push({
                content: String(item.N ?? ''),
                rowSpan: item.FILAS_GRUPO || 1,
                styles: { ...base, halign: 'center', valign: 'middle', fontStyle: 'bold' },
            })
            row.push({
                content: item.NOMBRE_DOCENTE || '',
                rowSpan: item.FILAS_GRUPO || 1,
                styles: { ...base, valign: 'middle', fontStyle: 'bold' },
            })
        }

        if (item.INICIO_MATERIA !== false) {
            row.push({
                content: item.NOMBRE_MATERIA || '',
                rowSpan: item.FILAS_MATERIA || 1,
                styles: { ...base, valign: 'middle', fontStyle: item.NEGRITA ? 'bold' : 'normal' },
            })
            row.push({
                content: String(item.CH || '—'),
                rowSpan: item.FILAS_MATERIA || 1,
                styles: { ...base, halign: 'center', valign: 'middle' },
            })
        }

        row.push({ content: item.DETALLE || '—', styles: base })
        row.push({ content: item.CATEGORIA || '—', styles: base })

        if (item.INICIO_GRUPO) {
            const total = item.TOTAL_CH ? String(item.TOTAL_CH) : '—'
            const mes = item.TOTAL_CH_MES ? `\nMes ${item.TOTAL_CH_MES}` : ''
            row.push({
                content: total + mes,
                rowSpan: item.FILAS_GRUPO || 1,
                styles: { ...base, halign: 'center', valign: 'middle', fontStyle: 'bold' },
            })
        }

        row.push({ content: item.NIVEL || '—', styles: base })
        row.push({ content: item.FOTOCOPIA_TITULAR || '—', styles: base })
        row.push({ content: item.OBS2 || '—', styles: base })
        row.push({ content: item.OBS3 || '—', styles: base })
        return row
    })

    autoTable(doc, {
        startY: 21,
        head: [[
            'Nº', 'Docente', 'Materia', 'CH', 'Detalle', 'Categoría',
            'T. CH', 'Nivel', 'Fotocopia', 'Obs 2', 'Obs 3',
        ]],
        body,
        theme: 'grid',
        margin: { left: 10, right: 10, top: 12, bottom: 12 },
        styles: {
            fontSize: 7,
            cellPadding: 1.4,
            lineColor: [209, 213, 219],
            lineWidth: 0.1,
            overflow: 'linebreak',
            textColor: 30,
        },
        headStyles: {
            fillColor: [30, 41, 59],
            textColor: 255,
            fontStyle: 'bold',
            halign: 'center',
        },
        columnStyles: {
            0: { cellWidth: 8 },
            1: { cellWidth: 42 },
            2: { cellWidth: 52 },
            3: { cellWidth: 10 },
            4: { cellWidth: 32 },
            5: { cellWidth: 28 },
            6: { cellWidth: 16 },
            7: { cellWidth: 18 },
            8: { cellWidth: 20 },
            9: { cellWidth: 25 },
            10: { cellWidth: 'auto' },
        },
        didDrawPage: () => {
            const n = doc.internal.getNumberOfPages()
            doc.setFontSize(7.5)
            doc.setTextColor(120)
            doc.text(`Página ${doc.internal.getCurrentPageInfo().pageNumber} de ${n}`, pageW - 10, pageH - 5, { align: 'right' })
            doc.setTextColor(0)
        },
    })

    // Corrige el "de N" en todas las páginas al terminar
    const total = doc.internal.getNumberOfPages()
    for (let i = 1; i <= total; i++) {
        doc.setPage(i)
        doc.setFillColor(255, 255, 255)
        doc.rect(pageW - 45, pageH - 9, 40, 6, 'F')
        doc.setFontSize(7.5)
        doc.setTextColor(120)
        doc.text(`Página ${i} de ${total}`, pageW - 10, pageH - 5, { align: 'right' })
    }

    const blob = doc.output('blob')
    const url = URL.createObjectURL(blob)
    const limpio = (version || titulo).replace(/[^\w\-]+/g, '_')
    return { url, filename: `${limpio}.pdf` }
}