// composables/useGenerarPDFCargaHoraria.js
import { jsPDF } from 'jspdf'
import autoTable from 'jspdf-autotable'

const C_BLACK = [0, 0, 0]
const C_WHITE = [255, 255, 255]
const C_GRAY_HEAD = [218, 218, 218]
const C_GRAY_LINE = [160, 160, 160]
const C_ALT_ROW = [242, 242, 242]
const C_TOTAL_BG = [230, 230, 230]
const C_COMP_TEXT = [180, 50, 0]
const C_ZERO_TEXT = [150, 150, 150]

export function generarPDFCargaHoraria(docentes = [], { anio, periodo } = {}) {

    const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'letter' })

    const PAGE_W = doc.internal.pageSize.getWidth()
    const PAGE_H = doc.internal.pageSize.getHeight()
    const ML = 10
    const MR = 10
    const CW = PAGE_W - ML - MR

    const fechaActual = new Date().toLocaleString('en-US', {
        month: 'numeric', day: 'numeric', year: 'numeric',
        hour: 'numeric', minute: '2-digit', second: '2-digit', hour12: true,
    })

    const gestionLabel = `${periodo}/${anio}`
    let Y = 0

    function drawPageHeader() {
        doc.setFont('helvetica', 'bold')
        doc.setFontSize(7.5)
        doc.setTextColor(...C_BLACK)
        doc.text('UNIVERSIDAD MAYOR DE SAN SIMON', ML, 9)
        doc.text('FACULTAD DE CIENCIAS ECONOMICAS', ML, 13)

        doc.setFontSize(11)
        doc.text('CARGA HORARIA DOCENTES', PAGE_W / 2, 9, { align: 'center' })
        doc.setFontSize(9)
        doc.text('FACULTAD DE CIENCIAS ECONOMICAS', PAGE_W / 2, 13, { align: 'center' })

        doc.setDrawColor(...C_GRAY_LINE)
        doc.setLineWidth(0.4)
        doc.line(ML, 15, PAGE_W - MR, 15)

        doc.setFont('helvetica', 'bold')
        doc.setFontSize(9)
        doc.setTextColor(...C_BLACK)
        doc.text(`Gestión Académica ${gestionLabel}`, PAGE_W / 2, 20, { align: 'center' })

        doc.setFont('helvetica', 'normal')
        doc.setFontSize(7)
        doc.text(fechaActual, PAGE_W - MR, 20, { align: 'right' })

        doc.setFontSize(6.5)
        doc.text('La carga horaria incluye Grupos Compartidos.', ML, 20)

        doc.setDrawColor(...C_GRAY_LINE)
        doc.setLineWidth(0.3)
        doc.line(ML, 22, PAGE_W - MR, 22)

        return 25
    }

    function drawFooters() {
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

    Y = drawPageHeader()

    for (let di = 0; di < docentes.length; di++) {
        const docente = docentes[di]

        if (Y > PAGE_H - 35) {
            doc.addPage()
            Y = drawPageHeader()
        }

        doc.setFont('helvetica', 'bold')
        doc.setFontSize(8.5)
        doc.setTextColor(...C_BLACK)
        doc.text(
            `${docente.docente}  ${(docente.apellidos ?? '').toUpperCase()} ${(docente.nombres ?? '').toUpperCase()}`,
            ML, Y + 4
        )
        Y += 8

        const rows = []
        for (const h of (docente.horarios ?? [])) {
            const planTexto = h.PLAN ?? ''
            const compTexto = h.COMPARTIDO ?? h.comp ?? ''
            const cFlag = h.C ?? (compTexto.toLowerCase().startsWith('comparte de') ? 1 : 0)
            rows.push([
                planTexto,
                `${h.MATERIA ?? ''}\n${h.NOMBRE ?? ''}`,
                h.GRUPO ?? '',
                h.DIA ?? '',
                h.AMBIENTE ?? '',
                h.HORARIO ?? '',
                h.CARGA_HORARIA ?? '',
                h.TOTAL_NORMAL ?? '',
                String(cFlag),
                compTexto,
            ])
        }

        autoTable(doc, {
            startY: Y,
            margin: { left: ML, right: MR, bottom: 12 },
            tableWidth: CW,
            head: [['DOCENTE / PLAN', 'MATERIA', 'GRP', 'DIA', 'AULA', 'HORA', 'CH', 'INSC-N', 'C', 'COMPARTIDO']],
            body: rows,
            foot: [[
                { content: 'TOTAL', colSpan: 6, styles: { halign: 'right', fontStyle: 'bold', fillColor: C_TOTAL_BG, textColor: C_BLACK, fontSize: 7.5 } },
                { content: String(docente.total_ch ?? ''), colSpan: 4, styles: { halign: 'center', fontStyle: 'bold', fillColor: C_TOTAL_BG, textColor: C_BLACK, fontSize: 8 } },
            ]],
            styles: {
                font: 'helvetica', fontSize: 7,
                cellPadding: { top: 1.0, bottom: 1.0, left: 1.5, right: 1.5 },
                textColor: C_BLACK, lineColor: C_GRAY_LINE, lineWidth: 0.2,
                overflow: 'linebreak', valign: 'middle',
            },
            headStyles: {
                fillColor: C_GRAY_HEAD, textColor: C_BLACK, fontStyle: 'bold',
                fontSize: 7, halign: 'center', valign: 'middle',
                lineColor: [120, 120, 120], lineWidth: 0.3,
            },
            alternateRowStyles: { fillColor: C_ALT_ROW },
            footStyles: { fillColor: C_TOTAL_BG, lineColor: C_GRAY_LINE, lineWidth: 0.3 },
            columnStyles: {
                0: { cellWidth: 22 },
                1: { cellWidth: 52 },
                2: { cellWidth: 8, halign: 'center' },
                3: { cellWidth: 8, halign: 'center' },
                4: { cellWidth: 20 },
                5: { cellWidth: 24 },
                6: { cellWidth: 8, halign: 'center' },
                7: { cellWidth: 12, halign: 'center' },
                8: { cellWidth: 8, halign: 'center' },
                9: { cellWidth: 'auto' },
            },
            didParseCell(data) {
                if (data.section !== 'body') return
                const row = rows[data.row.index]
                if (!row) return
                if (data.column.index === 8 && data.cell.raw === '0') {
                    data.cell.styles.textColor = C_ZERO_TEXT
                }
                if (data.column.index === 9 && data.cell.raw) {
                    data.cell.styles.textColor = C_COMP_TEXT
                    data.cell.styles.fontStyle = 'bold'
                    data.cell.styles.fontSize = 6.5
                }
            },
            didAddPage(data) {
                Y = drawPageHeader()
                data.settings.margin.top = Y
            },
        })

        Y = doc.lastAutoTable.finalY + 6
    }

    drawFooters()

    // ── Retorna blob URL para vista previa en vez de descargar ────────────────
    const blob = doc.output('blob')
    const url = URL.createObjectURL(blob)
    return { url, filename: `CargaHoraria_${anio}_${periodo}.pdf` }
}