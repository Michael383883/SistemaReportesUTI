// composables/usePdfReporteExcel.js
// Genera un PDF con el mismo diseño que el Excel (mismas columnas, encabezado
// gris, bordes finos y línea gruesa al terminar cada docente), usando las
// mismas filas que se ven en la vista previa.
// Requiere: npm i jspdf jspdf-autotable
import { jsPDF } from 'jspdf'
import autoTable from 'jspdf-autotable'

const GRIS_ENCABEZADO = [217, 217, 217] // D9D9D9, igual que el Excel
const NEGRO = [0, 0, 0]
const BLANCO = [255, 255, 255]

// Columna separadora vacía (equivale a la columna J del Excel)
const COL_SEPARADOR = 7

/**
 * @param {Array}  filas  reporteExcel.previewMostrado.value
 * @param {Object} opts   { titulo, version, ... }
 * @returns {{ url: string, filename: string }}
 */
export function generarPDFReporteExcel(filas, opts = {}) {
    const {
        titulo = 'Reporte Excel',
        version = '',
    } = opts

    const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' })
    const pageW = doc.internal.pageSize.getWidth()
    const pageH = doc.internal.pageSize.getHeight()

    const margenX = 10

    // Ancho de las columnas de la tabla principal (Nº ... T. CH), sin las de la derecha
    const anchos = [6, 44, 45, 7, 64, 30, 9, 2, 27, 19, 23]
    const anchoTablaPrincipal = anchos.slice(0, 7).reduce((a, b) => a + b, 0)

    // ── Encabezado (igual que el Excel) ──
    doc.setTextColor(0)
    doc.setFont('helvetica', 'normal')
    doc.setFontSize(9)
    doc.text('Universidad Mayor de San Simón', margenX, 10)
    doc.text('Facultad de Ciencias Económicas', margenX, 14.5)

    doc.setFont('helvetica', 'bold')
    doc.setFontSize(13)
    doc.text(
        'LISTA DE DOCENTES Y LAS MATERIAS QUE IMPARTEN - PRELACIÓN',
        margenX + anchoTablaPrincipal / 2,
        21,
        { align: 'center' }
    )

    doc.setFont('helvetica', 'normal')
    doc.setFontSize(8)
    const nota = doc.splitTextToSize(
        'Nota: Este es un documento preliminar aún no aprobado por la comisión revisora, el mismo puede ser modificado de acuerdo a la solicitud debidamente documentada con Resolución.',
        anchoTablaPrincipal
    )
    doc.text(nota, margenX, 26.5)

    const inicioTabla = 26.5 + nota.length * 3.6 + 2

    // ── Cuerpo (respeta los rowspan de la vista) ──
    const separador = () => ({
        content: '',
        styles: { fillColor: BLANCO, lineWidth: 0 },
    })

    const body = filas.map((item) => {
        const row = []

        if (item.INICIO_GRUPO) {
            row.push({
                content: String(item.N ?? ''),
                rowSpan: item.FILAS_GRUPO || 1,
                styles: { halign: 'center', valign: 'middle' },
            })
            row.push({
                content: item.NOMBRE_DOCENTE || '',
                rowSpan: item.FILAS_GRUPO || 1,
                styles: { halign: 'left', valign: 'middle' },
            })
        }

        if (item.INICIO_MATERIA !== false) {
            row.push({
                content: item.NOMBRE_MATERIA || '',
                rowSpan: item.FILAS_MATERIA || 1,
                styles: { halign: 'left', valign: 'middle' },
            })
            row.push({
                content: String(item.CH ?? ''),
                rowSpan: item.FILAS_MATERIA || 1,
                styles: { halign: 'center', valign: 'middle' },
            })
        }

        row.push({ content: item.DETALLE || '' })
        row.push({ content: item.CATEGORIA || '' })

        if (item.INICIO_GRUPO) {
            const total = item.TOTAL_CH ? String(item.TOTAL_CH) : ''
            const mes = item.TOTAL_CH && item.TOTAL_CH_MES ? `\nMes ${item.TOTAL_CH_MES}` : ''
            row.push({
                content: total + mes,
                rowSpan: item.FILAS_GRUPO || 1,
                styles: { halign: 'center', valign: 'middle', fontStyle: 'bold' },
            })
        }

        row.push(separador())
        row.push({ content: item.FOTOCOPIA_TITULAR || '' })
        row.push({ content: item.OBS2 || '' })
        row.push({ content: item.OBS3 || '' })
        return row
    })

    const estiloCabecera = { halign: 'center', valign: 'middle' }

    autoTable(doc, {
        startY: inicioTabla,
        head: [[
            { content: 'Nº', styles: estiloCabecera },
            { content: 'NOMBRE DOCENTE', styles: estiloCabecera },
            { content: 'NOMBRE MATERIA', styles: estiloCabecera },
            { content: 'CH', styles: estiloCabecera },
            { content: 'DETALLE', styles: estiloCabecera },
            { content: 'CATEGORIA', styles: estiloCabecera },
            { content: 'T. CH', styles: estiloCabecera },
            { content: '', styles: { fillColor: BLANCO, lineWidth: 0 } },
            { content: 'FOTOCOPIA TITULAR', styles: estiloCabecera },
            { content: 'OBS 2', styles: estiloCabecera },
            { content: 'OBS3', styles: estiloCabecera },
        ]],
        body,
        theme: 'grid',
        margin: { left: margenX, right: margenX, top: 12, bottom: 12 },
        styles: {
            font: 'helvetica',
            fontSize: 7,
            cellPadding: 1.4,
            lineColor: NEGRO,
            lineWidth: 0.1,
            overflow: 'linebreak',
            textColor: NEGRO,
            fillColor: BLANCO,
            valign: 'middle',
        },
        headStyles: {
            fillColor: GRIS_ENCABEZADO,
            textColor: NEGRO,
            fontStyle: 'bold',
            halign: 'center',
        },
        columnStyles: Object.fromEntries(
            anchos.map((w, i) => [i, { cellWidth: w }])
        ),
        // Línea gruesa al final de cada docente (como el borde medio del Excel)
        didDrawCell: (data) => {
            if (data.section !== 'body' || data.column.index === COL_SEPARADOR) return
            const ultimaFila = data.row.index + (data.cell.rowSpan || 1) - 1
            if (!filas[ultimaFila]?.FIN_GRUPO) return

            const { x, y, width, height } = data.cell
            doc.setDrawColor(0)
            doc.setLineWidth(0.5)
            doc.line(x, y + height, x + width, y + height)
            doc.setLineWidth(0.1)
        },
    })

    // Numeración de páginas
    const total = doc.internal.getNumberOfPages()
    for (let i = 1; i <= total; i++) {
        doc.setPage(i)
        doc.setFont('helvetica', 'normal')
        doc.setFontSize(7.5)
        doc.setTextColor(120)
        doc.text(`Página ${i} de ${total}`, pageW - margenX, pageH - 5, { align: 'right' })
    }
    doc.setTextColor(0)

    const blob = doc.output('blob')
    const url = URL.createObjectURL(blob)
    const limpio = (version || titulo).replace(/[^\w\-]+/g, '_')
    return { url, filename: `${limpio}.pdf` }
}