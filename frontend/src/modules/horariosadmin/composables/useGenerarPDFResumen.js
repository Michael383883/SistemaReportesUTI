// composables/useGenerarPDFResumen.js
import { jsPDF } from 'jspdf'
import autoTable from 'jspdf-autotable'

const C_BLACK = [0, 0, 0]
const C_WHITE = [255, 255, 255]
const C_HEAD_BG = [211, 211, 211]
const C_GRAY_LINE = [140, 140, 140]
const C_ZERO_TEXT = [150, 150, 150]

// ── Convierte código numérico de plan a abreviación de carrera ───────────────
const PLAN_MAP = {
    '089801': 'CON',
    '109401': 'ADM',
    '125091': 'COM',
    '126091': 'FIN',
    '059801': 'ECO',
}

function planAAbreviacion(plan) {
    if (!plan) return ''
    const s = String(plan).trim()
    if (/^[A-Z]{2,4}$/i.test(s)) return s.toUpperCase()
    if (PLAN_MAP[s]) return PLAN_MAP[s]
    const base = s.slice(0, 6)
    if (PLAN_MAP[base]) return PLAN_MAP[base]
    return s
}

// ── Función para determinar el valor de C ──
function getValorC(mat) {
    const compTexto = mat.COMPARTIDO ?? ''
    const comp = mat.COMP ?? ''
    
    if (compTexto !== '' && String(comp) === '1') return 1
    if (compTexto !== '' && String(comp) === '0') return 0
    if (compTexto === '') return 0
    return 0
}

// ── Función para determinar la CH a mostrar ──
function chMostrada(mat) {
    const c = getValorC(mat)
    if (c === 1) return 0
    return Number(mat.CARGA_HORARIA) || 0
}

/**
 * @param {Array}  docentes  - Lista de docentes con sus materias
 * @param {object} options
 * @param {number|string} options.anio
 * @param {number|string} options.periodo
 * @param {'ver'|'descargar'} [options.modo='descargar']
 */
export function generarPDFResumen(docentes = [], { anio, periodo, modo = 'descargar' } = {}) {

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
        doc.text('UNIVERSIDAD MAYOR DE SAN SIMON', ML, 8)
        doc.text('FACULTAD DE CIENCIAS ECONOMICAS', ML, 10)

        doc.setFontSize(12.5)
        doc.text('CARGA HORARIA DOCENTES', PAGE_W / 2, 9, { align: 'center' })
        doc.setFontSize(10.5)
        doc.text('FACULTAD DE CIENCIAS ECONOMICAS', PAGE_W / 2, 13.5, { align: 'center' })

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
            doc.text('Procesado UTI - Facultad de Ciencias Economicas', ML, fy)
            doc.text(`Página ${i} de ${total}`, PAGE_W / 2, fy, { align: 'center' })
            doc.text(fechaActual, PAGE_W - MR, fy, { align: 'right' })
        }
    }

    const body = []

    for (let di = 0; di < docentes.length; di++) {
        const docente = docentes[di]
        const materias = docente.materias ?? []

        body.push([{
            content: `${docente.docente}  ${(docente.apellidos ?? '').toUpperCase()} ${(docente.nombres ?? '').toUpperCase()}`,
            colSpan: 7,
            styles: {
                fontStyle: 'normal', 
                fontSize: 8.5, 
                halign: 'left',
                fillColor: C_WHITE, 
                lineWidth: 0,
                cellPadding: { top: di === 0 ? 1.5 : 3.5, bottom: 1, left: 1.5, right: 1.5 },
            },
        }])

        for (const mat of materias) {
            const compTexto = mat.COMPARTIDO ?? ''
            const c = getValorC(mat)
            const chValue = chMostrada(mat)
            const materiaTexto = [mat.MATERIA, mat.NOMBRE].filter(Boolean).join(' ')

            let planTexto
            if (mat.CARRERA) {
                planTexto = `${mat.CARRERA} - ${mat.NIVEL ?? ''}`
            } else {
                const abrev = planAAbreviacion(mat.PLAN ?? '')
                planTexto = abrev ? `${abrev} - ${mat.NIVEL ?? ''}` : (mat.NIVEL ?? '')
            }

            body.push([
                planTexto,
                materiaTexto,
                mat.GRUPO ?? '',
                String(chValue),  // 🔥 Usar chMostrada()
                String(mat.TOTAL_NORMAL ?? ''),
                String(c),
                compTexto,
            ])
        }

        // ── 🔥 CALCULAR TOTALES CORRECTAMENTE ──
        let totalCH = 0
        let totalInscN = 0
        const gruposVistosInsc = new Set()

        for (const mat of materias) {
            // CH: sumar usando chMostrada()
            totalCH += chMostrada(mat)

            // INSC-N: una sola vez por materia+grupo+nivel
            const clave = `${mat.CARRERA ?? ''}_${mat.GRUPO ?? ''}_${mat.MATERIA ?? ''}_${mat.NIVEL ?? ''}`
            if (!gruposVistosInsc.has(clave)) {
                gruposVistosInsc.add(clave)
                totalInscN += Number(mat.TOTAL_NORMAL) || 0
            }
        }

        body.push([
            { content: '', colSpan: 1, styles: { fillColor: C_WHITE, lineWidth: 0 } },
            { content: 'TOTAL', colSpan: 2, styles: { halign: 'right', fontStyle: 'bold', fontSize: 8, fillColor: C_WHITE, lineWidth: 0 } },
            { content: String(totalCH), colSpan: 1, styles: { halign: 'center', fontStyle: 'bold', fontSize: 8, fillColor: C_WHITE, lineWidth: 0 } },
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
        head: [['PLAN-NVL', 'MATERIA', 'GRP', 'CH', 'INSC-N', 'C', 'COMPARTIDO']],
        body,
        alternateRowStyles: { fillColor: C_WHITE },
        styles: {
            font: 'helvetica', 
            fontSize: 6.5,  //  Reducido para que quepa mejor
            cellPadding: { top: 0.4, bottom: 0.4, left: 1, right: 1 },
            textColor: C_BLACK, 
            lineColor: C_GRAY_LINE, 
            lineWidth: 0,
            fillColor: C_WHITE,
            overflow: 'linebreak', 
            valign: 'middle',
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
            cellPadding: { top: 0.6, bottom: 0.6, left: 0.5, right: 0.5 },
        },
        columnStyles: {
            0: { cellWidth: 16, halign: 'left', cellPadding: { top: 0.3, bottom: 0.3, left: 0.5, right: 0.3 } },  // 🔥 Achicado
            1: { cellWidth: 60, halign: 'left', cellPadding: { top: 0.3, bottom: 0.3, left: 0.3, right: 0.5 } },  // 🔥 Ajustado
            2: { cellWidth: 10, halign: 'center' },
            3: { cellWidth: 10, halign: 'center' },
            4: { cellWidth: 14, halign: 'center' },
            5: { cellWidth: 8, halign: 'center' },
            6: { cellWidth: 'auto', halign: 'left' },
        },
        didParseCell(data) {
            if (data.section === 'head') {
                if ([2, 3, 4, 5].includes(data.column.index)) {
                    data.cell.styles.halign = 'center'
                }
                if (data.column.index === 0) {
                    data.cell.styles.fontSize = 6.5
                }
                return
            }

            if (data.section !== 'body') return

            const raw = data.row.raw
            const isDataRow = Array.isArray(raw) && raw.length === 7
            if (!isDataRow) return

            const firstCell = raw[0]
            if (typeof firstCell === 'object' && firstCell !== null && 'colSpan' in firstCell) return

            const col = data.column.index

            if (col <= 1) {
                data.cell.styles.lineWidth = { top: 0, right: 0, bottom: 0, left: 0 }
            } else {
                data.cell.styles.lineWidth = { top: 0, right: 0, bottom: 0.2, left: 0 }
            }

            if (col === 0) {
                data.cell.styles.fontStyle = 'normal'
                data.cell.styles.fontSize = 6.5  //  Fuente más pequeña para PLAN
            }

            if (col === 5 && data.cell.raw === '0') {
                data.cell.styles.textColor = C_ZERO_TEXT
            }

            if (col === 6 && data.cell.raw) {
                data.cell.styles.textColor = C_BLACK
                data.cell.styles.fontStyle = 'normal'
                data.cell.styles.fontSize = 6.5
            }
        },
        didDrawCell(data) {
            if (data.section === 'head' && data.column.index === 4) {
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
            if (doc.internal.getCurrentPageInfo().pageNumber > 1) {
                drawPageHeader()
            }
        },
    })

    drawFooters()

    const filename = `ResumenCargaHoraria_${anio}_${periodo}.pdf`

    if (modo === 'ver') {
        const blob = doc.output('blob')
        const url = URL.createObjectURL(blob)
        const ventana = window.open(url, '_blank')
        if (ventana) {
            ventana.addEventListener('load', () => URL.revokeObjectURL(url), { once: true })
        } else {
            setTimeout(() => URL.revokeObjectURL(url), 10_000)
        }
    } else {
        doc.save(filename)
    }
}