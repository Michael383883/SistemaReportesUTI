// composables/useExportarListado.js
// Exporta a PDF (estilo UMSS) o Excel el listado de documentos de docentes
// tal como se ve en la tabla (ya filtrado).
// Dependencias: jspdf, jspdf-autotable, xlsx
//   npm install jspdf jspdf-autotable xlsx

import { jsPDF } from 'jspdf'
import autoTable from 'jspdf-autotable'
import * as XLSX from 'xlsx'

const v = (x) => (x === null || x === undefined || x === '' ? '' : String(x).trim())

// Convierte el listado de la tabla en filas planas
export function construirFilas(listado) {
    return (listado || []).map((c, i) => ({
        nro: i + 1,
        docente: v(c.NOMBRE_DOCENTE),
        totalDocs: c._totalClasificaciones > 1 ? c._totalClasificaciones : '',
        documento: v(c.TIPO_DOCUMENTO),
        detalle: v(c.DETALLE_GENERAL),
        categoria: v(c.CATEGORIA),
        nivel: v(c.NIVEL),
        gestion: v(c.GESTION),
        periodo: v(c.PERIODO),
    }))
}

function fechaHoraActual() {
    const now = new Date()
    const p = (n) => String(n).padStart(2, '0')
    const hora = now.toLocaleString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true })
    return `${p(now.getDate())}/${p(now.getMonth() + 1)}/${now.getFullYear()} ${hora}`
}

function nombreBase() {
    const d = new Date()
    const p = (n) => String(n).padStart(2, '0')
    return `listado_documentos_docentes_${d.getFullYear()}${p(d.getMonth() + 1)}${p(d.getDate())}`
}

// ───────────────────────── PDF ─────────────────────────
export function exportarPDF(listado, { action = 'open', filtrosTexto = '' } = {}) {
    const filas = construirFilas(listado)

    const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'letter' })
    const PAGE_W = doc.internal.pageSize.getWidth()
    const PAGE_H = doc.internal.pageSize.getHeight()
    const ML = 12
    const MR = 12
    const CONTENT_W = PAGE_W - ML - MR

    const BLACK = [0, 0, 0]
    const GRAY_BG = [240, 240, 240]
    const LINE = [170, 170, 170]

    const titulo = 'LISTADO DE DOCUMENTOS DE DOCENTES'
    const descripcion =
        'Documentos de docentes registrados en el SISS, correspondientes a la Facultad de Ciencias Económicas.'
    const lineaFiltros = filtrosTexto ? `FILTROS : ${filtrosTexto}` : 'FILTROS : Ninguno (todos los registros)'

    function drawHeader() {
        doc.setFont('helvetica', 'bold')
        doc.setFontSize(5.5)
        doc.setTextColor(...BLACK)
        doc.text('UNIVERSIDAD MAYOR DE SAN SIMÓN', ML, 6)
        doc.text('FACULTAD DE CIENCIAS ECONÓMICAS', ML, 8)

        doc.setFontSize(11)
        doc.text(titulo, PAGE_W / 2, 12, { align: 'center' })

        doc.setDrawColor(...LINE)
        doc.setLineWidth(0.3)
        doc.line(ML, 17, PAGE_W - MR, 17)

        doc.setFont('helvetica', 'normal')
        doc.setFontSize(6.5)
        doc.setTextColor(40, 40, 40)
        const descLines = doc.splitTextToSize(descripcion, CONTENT_W)
        doc.text(descLines, ML, 21)

        const y = 21 + descLines.length * 3.0 + 3
        doc.setFont('helvetica', 'bold')
        doc.setFontSize(7.5)
        doc.setTextColor(...BLACK)
        const filtLines = doc.splitTextToSize(lineaFiltros, CONTENT_W)
        doc.text(filtLines, ML, y)

        return y + filtLines.length * 3.2 + 1
    }

    doc.setProperties({ title: 'Listado de documentos de docentes' })
    const startY = drawHeader()

    const columnas = [
        { header: 'Nº', dataKey: 'nro' },
        { header: 'DOCENTE', dataKey: 'docente' },
        { header: 'DOCUMENTO', dataKey: 'documento' },
        { header: 'CATEGORÍA', dataKey: 'categoria' },
        { header: 'NIVEL', dataKey: 'nivel' },
        { header: 'GESTIÓN', dataKey: 'gestion' },
        { header: 'PERIODO', dataKey: 'periodo' },
    ]

    const body = filas.map((f) => [
        f.nro,
        f.totalDocs ? `${f.docente} (${f.totalDocs})` : f.docente,
        f.detalle ? `${f.documento}\n${f.detalle}` : f.documento,
        f.categoria,
        f.nivel,
        f.gestion,
        f.periodo,
    ])

    autoTable(doc, {
        startY,
        margin: { left: ML, right: MR, bottom: 12 },
        tableWidth: CONTENT_W,
        head: [columnas.map((c) => c.header)],
        body,
        styles: {
            font: 'helvetica',
            fontSize: 6.2,
            cellPadding: { top: 1.2, bottom: 1.2, left: 1.5, right: 1.5 },
            textColor: BLACK,
            lineColor: LINE,
            lineWidth: 0,
            overflow: 'linebreak',
            valign: 'middle',
            fillColor: false,
        },
        headStyles: {
            fillColor: GRAY_BG,
            textColor: BLACK,
            fontStyle: 'bold',
            fontSize: 6.2,
            halign: 'center',
            valign: 'middle',
            lineColor: [130, 130, 130],
            lineWidth: 0.3,
        },
        alternateRowStyles: { fillColor: false },
        columnStyles: {
            0: { cellWidth: 10, halign: 'center' },
            1: { cellWidth: 52 },
            2: { cellWidth: 'auto' },
            3: { cellWidth: 30, halign: 'center' },
            4: { cellWidth: 18, halign: 'center' },
            5: { cellWidth: 16, halign: 'center' },
            6: { cellWidth: 16, halign: 'center' },
        },
        didDrawCell(data) {
            if (data.section !== 'body') return
            if (data.column.index !== data.table.columns.length - 1) return
            const { y, height } = data.cell
            doc.setDrawColor(...LINE)
            doc.setLineWidth(0.2)
            doc.line(ML, y + height, ML + CONTENT_W, y + height)
        },
        didAddPage() {
            drawHeader()
        },
        didDrawPage() {
            const pageNum = doc.internal.getCurrentPageInfo().pageNumber
            const footerY = PAGE_H - 5
            doc.setDrawColor(...LINE)
            doc.setLineWidth(0.2)
            doc.line(ML, footerY - 3.5, PAGE_W - MR, footerY - 3.5)
            doc.setFont('helvetica', 'normal')
            doc.setFontSize(6)
            doc.setTextColor(90, 90, 90)
            doc.text('Procesado UTi - Facultad de Ciencias Económicas', ML, footerY)
            doc.text(`Página ${pageNum} de {totalPages}`, PAGE_W / 2, footerY, { align: 'center' })
            doc.text(fechaHoraActual(), PAGE_W - MR, footerY, { align: 'right' })
        },
    })

    // Total al final
    const yFin = (doc.lastAutoTable?.finalY ?? startY) + 5
    if (yFin < PAGE_H - 15) {
        doc.setFont('helvetica', 'bold')
        doc.setFontSize(7)
        doc.setTextColor(...BLACK)
        doc.text(`Total de registros: ${filas.length}`, ML, yFin)
    }

    if (typeof doc.putTotalPages === 'function') doc.putTotalPages('{totalPages}')

    const fileName = `${nombreBase()}.pdf`

    if (action === 'save') {
        doc.save(fileName)
    } else if (action === 'print') {
        const url = URL.createObjectURL(doc.output('blob'))
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
        const url = URL.createObjectURL(doc.output('blob'))
        window.open(url, '_blank')
        setTimeout(() => URL.revokeObjectURL(url), 60_000)
    }
}

// ───────────────────────── EXCEL ─────────────────────────
export function exportarExcel(listado, { filtrosTexto = '' } = {}) {
    const filas = construirFilas(listado)

    const encabezado = [
        ['UNIVERSIDAD MAYOR DE SAN SIMÓN'],
        ['FACULTAD DE CIENCIAS ECONÓMICAS'],
        ['LISTADO DE DOCUMENTOS DE DOCENTES'],
        [filtrosTexto ? `Filtros: ${filtrosTexto}` : 'Filtros: Ninguno (todos los registros)'],
        [`Generado: ${fechaHoraActual()}`],
        [],
        ['Nº', 'DOCENTE', 'N° CLASIF.', 'DOCUMENTO', 'DETALLE', 'CATEGORÍA', 'NIVEL', 'GESTIÓN', 'PERIODO'],
    ]

    const datos = filas.map((f) => [
        f.nro, f.docente, f.totalDocs, f.documento, f.detalle, f.categoria, f.nivel, f.gestion, f.periodo,
    ])

    const ws = XLSX.utils.aoa_to_sheet([...encabezado, ...datos, [], [`Total de registros: ${filas.length}`]])

    ws['!cols'] = [
        { wch: 5 }, { wch: 38 }, { wch: 11 }, { wch: 28 }, { wch: 34 },
        { wch: 24 }, { wch: 14 }, { wch: 10 }, { wch: 9 },
    ]
    // Títulos combinados a lo ancho
    ws['!merges'] = [0, 1, 2, 3, 4].map((r) => ({ s: { r, c: 0 }, e: { r, c: 8 } }))
    ws['!autofilter'] = { ref: `A7:I${7 + filas.length}` }

    const wb = XLSX.utils.book_new()
    XLSX.utils.book_append_sheet(wb, ws, 'Documentos')
    XLSX.writeFile(wb, `${nombreBase()}.xlsx`)
}