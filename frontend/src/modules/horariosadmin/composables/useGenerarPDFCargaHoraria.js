// composables/useGenerarPDFCargaHoraria.js
import { jsPDF } from 'jspdf'
import autoTable from 'jspdf-autotable'

const C_BLACK = [0, 0, 0]
const C_WHITE = [255, 255, 255]
const C_HEAD_BG = [240, 240, 240]
const C_GRAY_LINE = [140, 140, 140]

// ── Convierte código numérico de plan a abreviación de carrera ───────────────
const PLAN_MAP = {
    '089801': 'CON',
    '109401': 'ADM',
    '125091': 'COM',
    '126091': 'FIN',
    '059801': 'ECO',
}

function getAbreviaturaPlan(plan) {
    if (!plan) return ''
    const s = String(plan).trim()
    if (/^[A-Z]{2,4}$/i.test(s)) return s.toUpperCase()
    if (PLAN_MAP[s]) return PLAN_MAP[s]
    const base = s.slice(0, 6)
    if (PLAN_MAP[base]) return PLAN_MAP[base]
    return s
}

// ── Determina el valor de C según la lógica del componente Vue ──
function getValorC(h) {
    const compTexto = h.COMPARTIDO ?? h.comp ?? ''
    const comp = h.COMP ?? h.C ?? ''

    // Si tiene COMPARTIDO y COMP === '1' → C = 1 (derivada/recibe)
    if (compTexto !== '' && String(comp) === '1') {
        return 1
    }
    // Si tiene COMPARTIDO y COMP === '0' → C = 0 (origen/comparte)
    if (compTexto !== '' && String(comp) === '0') {
        return 0
    }
    // Si no tiene COMPARTIDO → C = 0 (normal)
    if (compTexto === '') {
        return 0
    }
    // Fallback
    return 0
}

// ── Determina si la fila debe mostrar CH en 0 ──
function chMostrada(h) {
    const c = getValorC(h)
    // Si C = 1 (derivada/recibe), mostrar 0
    if (c === 1) return 0
    // Si C = 0 (origen/comparte o normal), mostrar su CH real
    return Number(h.CARGA_HORARIA) || 0
}

export function generarPDFCargaHoraria(docentes = [], { anio, periodo } = {}) {

    const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'letter' })

    const PAGE_W = doc.internal.pageSize.getWidth()
    const PAGE_H = doc.internal.pageSize.getHeight()
    const ML = 8
    const MR = 8
    const CW = PAGE_W - ML - MR

    const d = new Date()
    const fechaActual = `${String(d.getDate()).padStart(2, '0')}/${String(d.getMonth() + 1).padStart(2, '0')}/${d.getFullYear()} ${d.toLocaleString('en-US', { hour: 'numeric', minute: '2-digit', second: '2-digit', hour12: true })}`
    const gestionLabel = `${periodo}/${anio}`
    const HEADER_H = 24

    function drawPageHeader() {
        doc.setFont('helvetica', 'bold')
        doc.setFontSize(6)
        doc.setTextColor(...C_BLACK)
        doc.text('UNIVERSIDAD MAYOR DE SAN SIMÓN', ML, 7)
        doc.text('FACULTAD DE CIENCIAS ECONÓMICAS', ML, 10)

        doc.setFontSize(12.5)
        doc.text('CARGA HORARIA DOCENTES', PAGE_W / 2, 9, { align: 'center' })
        doc.setFontSize(10.5)
        doc.text('FACULTAD DE CIENCIAS ECONÓMICAS', PAGE_W / 2, 13.5, { align: 'center' })

        doc.setFont('helvetica', 'bold')
        doc.setFontSize(10)
        doc.setTextColor(...C_BLACK)
        doc.text(`Gestión Académica ${gestionLabel}`, PAGE_W / 2, 19.5, { align: 'center' })

        doc.setFont('helvetica', 'normal')
        doc.setFontSize(7)
        doc.text(fechaActual, PAGE_W - MR, 19.5, { align: 'right' })
        doc.text('La carga horaria incluye Grupos Compartidos.', ML, 19.5)

        return HEADER_H
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
            doc.text('Procesado UTI - Facultad de Ciencias Económicas', ML, fy)
            doc.text(`Página ${i} de ${total}`, PAGE_W / 2, fy, { align: 'center' })
            doc.text(fechaActual, PAGE_W - MR, fy, { align: 'right' })
        }
    }

    const body = []

    for (let di = 0; di < docentes.length; di++) {
        const docente = docentes[di]
        const horarios = docente.horarios ?? []

        body.push([{
            content: `${docente.docente}  ${(docente.apellidos ?? '').toUpperCase()} ${(docente.nombres ?? '').toUpperCase()}`,
            colSpan: 10,
            styles: {
                fontStyle: 'normal', fontSize: 8.5, halign: 'left',
                fillColor: C_WHITE, lineWidth: 0,
                cellPadding: { top: di === 0 ? 1.5 : 3.5, bottom: 1, left: 1.5, right: 1.5 },
            },
        }])

        for (const h of horarios) {
            const compTexto = h.COMPARTIDO ?? h.comp ?? ''
            const c = getValorC(h)
            const materiaTexto = [h.MATERIA, h.NOMBRE].filter(Boolean).join(' ')

            const planAbrev = getAbreviaturaPlan(h.PLAN ?? '')
            const nivel = h.NIVEL ?? ''
            const planNivel = nivel ? `${planAbrev} - ${nivel}` : planAbrev

            // CH a mostrar: si C=1 mostrar 0, si C=0 mostrar su CH real
            const chValue = chMostrada(h)

            body.push([
                planNivel,
                materiaTexto,
                h.GRUPO ?? '',
                h.DIA ?? '',
                h.AMBIENTE ?? '',
                h.HORARIO ?? '',
                String(chValue),  // CH: 0 si es derivada (C=1), o su valor real
                h.TOTAL_NORMAL ?? '',
                String(c),
                compTexto,
            ])
        }

        // ── CALCULAR TOTALES ──
        // Usamos la misma lógica que el componente Vue:
        // totalChReal = suma de chMostrada() de todas las filas
        // totalInscritos = suma de TOTAL_NORMAL de todas las filas (sin duplicar)
        let totalCH = 0
        let totalInscN = 0
        const gruposVistosInsc = new Set()

        for (const h of horarios) {
            // Sumar CH usando la misma lógica que chMostrada()
            totalCH += chMostrada(h)

            // INSC-N: una sola vez por materia+grupo+nivel
            const clave = `${h.PLAN ?? ''}_${h.GRUPO ?? ''}_${h.MATERIA ?? ''}_${h.NIVEL ?? ''}`
            if (!gruposVistosInsc.has(clave)) {
                gruposVistosInsc.add(clave)
                totalInscN += Number(h.TOTAL_NORMAL) || 0
            }
        }

        body.push([
            { content: '', colSpan: 1, styles: { fillColor: C_WHITE, lineWidth: 0 } },
            { content: '', colSpan: 1, styles: { fillColor: C_WHITE, lineWidth: 0 } },
            { content: '', colSpan: 1, styles: { fillColor: C_WHITE, lineWidth: 0 } },
            { content: '', colSpan: 1, styles: { fillColor: C_WHITE, lineWidth: 0 } },
            { content: '', colSpan: 1, styles: { fillColor: C_WHITE, lineWidth: 0 } },
            { content: 'TOTAL', colSpan: 1, styles: { halign: 'right', fontStyle: 'bold', fontSize: 8, fillColor: C_WHITE, lineWidth: 0 } },
            { content: `${totalCH} Mes(${totalCH * 4})`, colSpan: 1, styles: { halign: 'center', fontStyle: 'bold', fontSize: 8, fillColor: C_WHITE, lineWidth: 0 } },
            { content: String(totalInscN), colSpan: 1, styles: { halign: 'center', fontStyle: 'bold', fontSize: 8, fillColor: C_WHITE, lineWidth: 0 } },
            { content: '', colSpan: 1, styles: { fillColor: C_WHITE, lineWidth: 0 } },
            { content: '', colSpan: 1, styles: { fillColor: C_WHITE, lineWidth: 0 } },
        ])
    }

    drawPageHeader()

    autoTable(doc, {
        startY: HEADER_H,
        margin: { left: ML, right: MR, top: HEADER_H, bottom: 12 },
        tableWidth: CW,
        head: [['PLAN-NVL', 'MATERIA', 'GRP', 'DIA', 'AULA', 'HORA', 'CH', 'INSC-N', 'C', 'COMPARTIDO']],
        body,
        alternateRowStyles: { fillColor: C_WHITE },
        styles: {
            font: 'helvetica', fontSize: 7,
            cellPadding: { top: 0.5, bottom: 0.5, left: 1.5, right: 1.5 },
            textColor: C_BLACK, lineColor: C_GRAY_LINE, lineWidth: 0,
            fillColor: C_WHITE,
            overflow: 'linebreak', valign: 'middle',
        },
        headStyles: {
            fillColor: C_HEAD_BG,
            textColor: C_BLACK,
            fontStyle: 'bold',
            fontSize: 6.5,
            halign: 'center',
            valign: 'middle',
            lineColor: C_GRAY_LINE,
            lineWidth: { top: 0, right: 0, bottom: 0.3, left: 0 },
            cellPadding: { top: 0.8, bottom: 0.8, left: 0.5, right: 0.5 },
        },
        columnStyles: {
            0: { cellWidth: 12, halign: 'left', cellPadding: { top: 0.5, bottom: 0.5, left: 0.5, right: 0.5 } },
            1: { cellWidth: 60, halign: 'left', cellPadding: { top: 0.5, bottom: 0.5, left: 0.5, right: 0.5 } },
            2: { cellWidth: 8, halign: 'center' },
            3: { cellWidth: 8, halign: 'center' },
            4: { cellWidth: 12, halign: 'center' },
            5: { cellWidth: 18, halign: 'center' },
            6: { cellWidth: 15, halign: 'center' },
            7: { cellWidth: 10, halign: 'center' },
            8: { cellWidth: 7, halign: 'center' },
            9: { cellWidth: 'auto', halign: 'left' },
        },
        didParseCell(data) {
            if (data.section === 'head') {
                if (data.column.index === 0) {
                    data.cell.styles.fontSize = 5.5
                }
                return
            }

            if (data.section !== 'body') return

            const raw = data.row.raw
            const isDataRow = Array.isArray(raw) && raw.length === 10
            if (!isDataRow) return

            const firstCell = raw[0]
            if (typeof firstCell === 'object' && firstCell !== null && 'colSpan' in firstCell) return

            const col = data.column.index

            if (col <= 2) {
                data.cell.styles.lineWidth = { top: 0, right: 0, bottom: 0, left: 0 }
            } else {
                data.cell.styles.lineWidth = { top: 0, right: 0, bottom: 0.2, left: 0 }
            }

            if (col === 0) {
                data.cell.styles.fontStyle = 'normal'
                data.cell.styles.fontSize = 6.5
            }

            if (col === 9 && data.cell.raw) {
                data.cell.styles.textColor = C_BLACK
                data.cell.styles.fontStyle = 'normal'
                data.cell.styles.fontSize = 6.5
            }
        },
        didDrawCell(data) {
            if (data.section === 'head' && data.column.index === 7) {
                doc.setFont('helvetica', 'bold')
                doc.setFontSize(6.5)
                const label = 'INSC-N'
                const textW = doc.getTextWidth(label)
                const cx = data.cell.x + data.cell.width / 2
                const lineY = data.cell.y + data.cell.height / 2 + 1.2
                doc.setDrawColor(...C_BLACK)
                doc.setLineWidth(0.2)
                doc.line(cx - textW / 2, lineY, cx + textW / 2, lineY)
            }
        },
        didDrawPage(data) {
            const pageNumber = doc.internal.getCurrentPageInfo().pageNumber
            if (pageNumber > 1) {
                drawPageHeader()
                data.settings.margin.top = HEADER_H
            }
        },
    })

    drawFooters()

    const blob = doc.output('blob')
    const url = URL.createObjectURL(blob)
    return { url, filename: `CargaHoraria_${anio}_${periodo}.pdf` }
}