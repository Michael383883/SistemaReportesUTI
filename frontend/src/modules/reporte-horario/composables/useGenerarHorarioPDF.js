// composables/useGenerarHorarioPDF.js
import { jsPDF } from 'jspdf'
import autoTable from 'jspdf-autotable'

export function generarHorarioPDF(horario, opts = {}) {
    const { action = 'open' } = opts

    const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'letter' })

    const PAGE_W = doc.internal.pageSize.getWidth()
    const PAGE_H = doc.internal.pageSize.getHeight()
    const ML = 12
    const MR = 12
    const CW = PAGE_W - ML - MR

    const BLACK = [0, 0, 0]
    const GRAY_HDR = [218, 218, 218]
    const GRAY_LN = [150, 150, 150]
    const GRAY_TEXT = [90, 90, 90]

    const gestion = horario.gestion || ''
    const docentes = horario.docentes || []

    function drawHeader() {
        const T = 8

        doc.setFont('helvetica', 'bold')
        doc.setFontSize(7.5)
        doc.setTextColor(...BLACK)
        doc.text('UNIVERSIDAD MAYOR DE SAN SIMON', ML, T)
        doc.text('FACULTAD DE CIENCIAS ECONOMICAS', ML, T + 4)

        doc.setFontSize(12)
        doc.text('CARGA HORARIA DOCENTES', PAGE_W / 2, T, { align: 'center' })
        doc.setFontSize(11)
        doc.text('FACULTAD DE CIENCIAS ECONOMICAS', PAGE_W / 2, T + 5, { align: 'center' })

        const ahora = new Date().toLocaleString('es-BO', { dateStyle: 'short', timeStyle: 'short' })
        doc.setFont('helvetica', 'normal')
        doc.setFontSize(6.5)
        doc.setTextColor(...GRAY_TEXT)
        doc.text(ahora, PAGE_W - MR, T, { align: 'right' })

        doc.setFont('helvetica', 'normal')
        doc.setFontSize(6.5)
        doc.setTextColor(...BLACK)
        doc.text('La carga horaria incluye Grupos Compartidos.', ML, T + 10)

        doc.setFont('helvetica', 'bold')
        doc.setFontSize(9)
        doc.text(`Gestión Académica ${gestion}`, PAGE_W / 2, T + 10, { align: 'center' })

        doc.setDrawColor(...GRAY_LN)
        doc.setLineWidth(0.3)
        doc.line(ML, T + 13, PAGE_W - MR, T + 13)

        return T + 16
    }

    const startY = drawHeader()

    const COLS = [
        { header: 'DOCENTE', dataKey: 'docente' },
        { header: 'PLAN', dataKey: 'plan' },
        { header: 'MATERIA', dataKey: 'materia' },
        { header: 'GRP', dataKey: 'grp' },
        { header: 'CH', dataKey: 'ch' },
        { header: 'COMPARTIDO', dataKey: 'compartido' },
    ]

    const body = []

    docentes.forEach((d) => {
        body.push({
            __tipo: 'docente',
            docente: `${d.codigo}   ${d.nombre}`,
            plan: '', materia: '', grp: '', ch: '', compartido: '',
        })

            ; (d.materias || []).forEach((m) => {
                body.push({
                    __tipo: 'materia',
                    docente: '',
                    plan: m.plan || '',
                    materia: m.materia || '',
                    grp: m.grp ?? '',
                    ch: m.ch ?? '',
                    compartido: m.compartido || '',
                })
            })

        body.push({
            __tipo: 'total',
            docente: '', plan: '',
            materia: 'TOTAL',
            grp: '',
            ch: d.total_ch ?? '',
            compartido: '',
        })
    })

    autoTable(doc, {
        startY,
        margin: { left: ML, right: MR },
        tableWidth: CW,

        head: [COLS.map(c => c.header)],
        body: body.map(r => COLS.map(c => r[c.dataKey])),

        // ── Estilo base: sin bordes en body ──────────────────────────────
        styles: {
            font: 'helvetica',
            fontSize: 7,
            cellPadding: { top: 0.9, bottom: 0.9, left: 2.0, right: 2.0 },
            textColor: BLACK,
            lineWidth: 0,           // sin bordes en todas las celdas body
            lineColor: [255, 255, 255],
            fillColor: [255, 255, 255],
            overflow: 'linebreak',
            valign: 'middle',
            minCellHeight: 0,
        },

        // ── Cabecera: solo ella tiene fondo y bordes ──────────────────────
        headStyles: {
            fillColor: GRAY_HDR,
            textColor: BLACK,
            fontStyle: 'bold',
            fontSize: 7.5,
            halign: 'center',
            valign: 'middle',
            cellPadding: { top: 1.8, bottom: 1.8, left: 2.0, right: 2.0 },
            lineColor: [130, 130, 130],
            lineWidth: 0.3,
        },

        alternateRowStyles: { fillColor: [255, 255, 255], lineWidth: 0 },

        columnStyles: {
            0: { cellWidth: 38 },          // DOCENTE
            1: { cellWidth: 16, halign: 'center' },  // PLAN
            2: { cellWidth: 'auto' },      // MATERIA — toma el resto (~55 mm)
            3: { cellWidth: 12, halign: 'center' },  // GRP
            4: { cellWidth: 10, halign: 'center' },  // CH
            5: { cellWidth: 50 },          // COMPARTIDO
        },

        didParseCell(data) {
            if (data.section !== 'body') return
            const row = body[data.row.index]
            if (!row) return

            // Asegurar sin bordes en todas las celdas body
            data.cell.styles.lineWidth = 0
            data.cell.styles.fillColor = [255, 255, 255]

            if (row.__tipo === 'docente') {
                data.cell.styles.fontStyle = 'bold'
                data.cell.styles.fontSize = 7.5
                data.cell.styles.cellPadding = { top: 3.5, bottom: 0.5, left: 2.0, right: 2.0 }
            }

            if (row.__tipo === 'materia') {
                data.cell.styles.fontStyle = 'normal'
                data.cell.styles.fontSize = 7
                data.cell.styles.cellPadding = { top: 0.8, bottom: 0.8, left: 2.0, right: 2.0 }
                // Columna COMPARTIDO en gris
                if (data.column.index === 5 && data.cell.raw) {
                    data.cell.styles.textColor = [90, 90, 90]
                    data.cell.styles.fontSize = 6.5
                }
            }

            if (row.__tipo === 'total') {
                data.cell.styles.fontStyle = 'bold'
                data.cell.styles.fontSize = 7.5
                data.cell.styles.cellPadding = { top: 0.5, bottom: 3.5, left: 2.0, right: 2.0 }
                if (data.column.index === 2) data.cell.styles.halign = 'right'
                if (data.column.index === 4) data.cell.styles.halign = 'center'
            }
        },

        didDrawCell(data) {
            if (data.section !== 'body') return
            const row = body[data.row.index]
            if (!row) return

            // Solo dibujar línea separadora debajo del bloque de cada docente (fila total)
            if (row.__tipo === 'total' && data.column.index === COLS.length - 1) {
                doc.setDrawColor(...GRAY_LN)
                doc.setLineWidth(0.25)
                doc.line(
                    ML,
                    data.cell.y + data.cell.height,
                    PAGE_W - MR,
                    data.cell.y + data.cell.height
                )
            }
        },

        didAddPage() {
            drawHeader()
        },

        didDrawPage() {
            const pageCount = doc.internal.getNumberOfPages()
            const pageNum = doc.internal.getCurrentPageInfo().pageNumber
            const FY = PAGE_H - 5

            doc.setDrawColor(...GRAY_LN)
            doc.setLineWidth(0.2)
            doc.line(ML, FY - 3.5, PAGE_W - MR, FY - 3.5)

            doc.setFont('helvetica', 'normal')
            doc.setFontSize(6)
            doc.setTextColor(...GRAY_TEXT)
            doc.text('Procesado UTi – Facultad de Ciencias Económicas', ML, FY)

            doc.setFont('helvetica', 'bold')
            doc.setTextColor(...BLACK)
            doc.text(`Página ${pageNum} de ${pageCount}`, PAGE_W / 2, FY, { align: 'center' })

            doc.setFont('helvetica', 'normal')
            doc.setTextColor(...GRAY_TEXT)
            const ahora = new Date().toLocaleString('es-BO', { dateStyle: 'short', timeStyle: 'short' })
            doc.text(ahora, PAGE_W - MR, FY, { align: 'right' })
        },
    })

    const fileName = `carga_horaria_${gestion.replace(/\s+/g, '_').replace(/\//g, '-')}.pdf`

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