// composables/useGenerarPDFCargaHoraria.js
import { jsPDF } from 'jspdf'
import autoTable from 'jspdf-autotable'

const C_BLACK = [0, 0, 0]
const C_WHITE = [255, 255, 255]
const C_HEAD_BG = [211, 211, 211]
const C_GRAY_LINE = [140, 140, 140]
const C_ZERO_TEXT = [150, 150, 150]

// ── Convierte código numérico de plan a abreviación de carrera ───────────────
// Solo la sigla, sin turno/nivel. Ej: 109401 → "ADM", 126091 → "FIN"
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
    // Si ya viene como sigla texto, devolver tal cual
    if (/^[A-Z]{2,4}$/i.test(s)) return s.toUpperCase()
    // Coincidencia exacta
    if (PLAN_MAP[s]) return PLAN_MAP[s]
    // Coincidencia por primeros 6 caracteres (variantes de turno al final)
    const base = s.slice(0, 6)
    if (PLAN_MAP[base]) return PLAN_MAP[base]
    // Fallback
    return s
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

    // ── Construye el cuerpo completo (todos los docentes en una sola tabla) ──
    const body = []

    for (let di = 0; di < docentes.length; di++) {
        const docente = docentes[di]
        const horarios = docente.horarios ?? []

        // Fila con el nombre del docente
        body.push([{
            content: `${docente.docente}  ${(docente.apellidos ?? '').toUpperCase()} ${(docente.nombres ?? '').toUpperCase()}`,
            colSpan: 10,
            styles: {
                fontStyle: 'normal', fontSize: 8.5, halign: 'left',
                fillColor: C_WHITE, lineWidth: 0,
                cellPadding: { top: di === 0 ? 1.5 : 3.5, bottom: 1, left: 1.5, right: 1.5 },
            },
        }])

        // Filas de horarios
        for (const h of horarios) {
            const compTexto = h.COMPARTIDO ?? h.comp ?? ''
            const cFlag = h.C ?? (compTexto.toLowerCase().startsWith('comparte de') ? 1 : 0)
            const materiaTexto = [h.MATERIA, h.NOMBRE].filter(Boolean).join(' ')

            // Col 0: carrera + grupo → "ADM - 20", "FIN - 29"
            const planAbrev = planAAbreviacion(h.PLAN ?? '')
            const planGrupo = planAbrev ? `${planAbrev} - ${h.GRUPO ?? ''}` : (h.GRUPO ?? '')

            body.push([
                planGrupo,          // 0: carrera - grupo
                materiaTexto,       // 1: MATERIA
                '',                 // 2: GRP (vacío, ya incluido en col 0)
                h.DIA ?? '',        // 3: DIA
                h.AMBIENTE ?? '',   // 4: AULA
                h.HORARIO ?? '',    // 5: HORA
                h.CARGA_HORARIA ?? '', // 6: CH
                h.TOTAL_NORMAL ?? '', // 7: INSC-N
                String(cFlag),      // 8: C
                compTexto,          // 9: COMPARTIDO
            ])
        }

        // ── Calcular totales ────────────────────────────────────────────────
        // CH: suma solo filas con C=0 (excluye "Comparte de...")
        // INSC-N: una sola vez por combinación materia+grupo (no repetir por día)
        //         y solo de filas con C=0

        const totalCH = horarios
            .filter(h => {
                const comp = h.COMPARTIDO ?? h.comp ?? ''
                const c = h.C ?? (comp.toLowerCase().startsWith('comparte de') ? 1 : 0)
                return Number(c) === 0
            })
            .reduce((acc, h) => acc + Number(h.CARGA_HORARIA ?? 0), 0)

        // Para INSC-N: agrupar por MATERIA+GRUPO y tomar un solo valor de inscritos
        const gruposVistos = new Set()
        let totalInscN = 0
        for (const h of horarios) {
            const comp = h.COMPARTIDO ?? h.comp ?? ''
            const c = h.C ?? (comp.toLowerCase().startsWith('comparte de') ? 1 : 0)
            if (Number(c) !== 0) continue // excluir compartidos recibidos

            const clave = `${h.MATERIA ?? ''}_${h.GRUPO ?? ''}`
            if (!gruposVistos.has(clave)) {
                gruposVistos.add(clave)
                totalInscN += Number(h.TOTAL_NORMAL ?? 0)
            }
        }

        // Fila TOTAL alineada: TOTAL en col 5 (HORA), valor CH en col 6, INSC-N en col 7
        body.push([
            { content: '', colSpan: 1, styles: { fillColor: C_WHITE, lineWidth: 0 } },
            { content: '', colSpan: 1, styles: { fillColor: C_WHITE, lineWidth: 0 } },
            { content: '', colSpan: 1, styles: { fillColor: C_WHITE, lineWidth: 0 } },
            { content: '', colSpan: 1, styles: { fillColor: C_WHITE, lineWidth: 0 } },
            { content: '', colSpan: 1, styles: { fillColor: C_WHITE, lineWidth: 0 } },
            { content: 'TOTAL', colSpan: 1, styles: { halign: 'right', fontStyle: 'bold', fontSize: 8, fillColor: C_WHITE, lineWidth: 0 } },
            { content: String(totalCH), colSpan: 1, styles: { halign: 'center', fontStyle: 'bold', fontSize: 8, fillColor: C_WHITE, lineWidth: 0 } },
            { content: String(totalInscN), colSpan: 1, styles: { halign: 'center', fontStyle: 'bold', fontSize: 8, fillColor: C_WHITE, lineWidth: 0 } },
            { content: '', colSpan: 1, styles: { fillColor: C_WHITE, lineWidth: 0 } },
            { content: '', colSpan: 1, styles: { fillColor: C_WHITE, lineWidth: 0 } },
        ])
    }

    // Encabezado de la primera página
    drawPageHeader()

    autoTable(doc, {
        startY: HEADER_H,
        margin: { left: ML, right: MR, top: HEADER_H, bottom: 12 },
        tableWidth: CW,
        head: [['DOC. PLAN', 'MATERIA', 'GRP', 'DIA', 'AULA', 'HORA', 'CH', 'INSC-N', 'C', 'COMPARTIDO']],
        body,
        // Sin filas alternadas
        alternateRowStyles: { fillColor: C_WHITE },
        styles: {
            font: 'helvetica', fontSize: 7,
            cellPadding: { top: 0.5, bottom: 0.5, left: 1.5, right: 1.5 },
            textColor: C_BLACK, lineColor: C_GRAY_LINE, lineWidth: 0,
            fillColor: C_WHITE,
            overflow: 'linebreak', valign: 'middle',
        },
        headStyles: {
            fillColor: C_HEAD_BG, textColor: C_BLACK, fontStyle: 'bold',
            fontSize: 7, halign: 'left', valign: 'middle',
            lineColor: C_GRAY_LINE, lineWidth: { top: 0, right: 0, bottom: 0.3, left: 0 },
        },
        columnStyles: {
            0: { cellWidth: 20 },
            1: { cellWidth: 58 },
            2: { cellWidth: 9, halign: 'center' },
            3: { cellWidth: 9, halign: 'center' },
            4: { cellWidth: 16, halign: 'center' },
            5: { cellWidth: 24, halign: 'center' },
            6: { cellWidth: 8, halign: 'center' },
            7: { cellWidth: 13, halign: 'center' },
            8: { cellWidth: 8, halign: 'center' },
            9: { cellWidth: 'auto' },
        },
        didParseCell(data) {
            if (data.section === 'head') {
                if ([2, 3, 4, 5, 6, 7, 8].includes(data.column.index)) {
                    data.cell.styles.halign = 'center'
                }
                if (data.column.index === 1) data.cell.styles.halign = 'center'
                if (data.column.index === 0) data.cell.styles.fontSize = 6
                return
            }

            if (data.section !== 'body') return

            const raw = data.row.raw
            const isDataRow = Array.isArray(raw) && raw.length === 10
            if (!isDataRow) return

            // Saltar filas de cabecera docente o total (tienen objetos con colSpan)
            const firstCell = raw[0]
            if (typeof firstCell === 'object' && firstCell !== null && 'colSpan' in firstCell) return

            const col = data.column.index

            // Líneas horizontales solo desde columna DIA (índice 3) en adelante
            if (col <= 2) {
                data.cell.styles.lineWidth = { top: 0, right: 0, bottom: 0, left: 0 }
            } else {
                data.cell.styles.lineWidth = { top: 0, right: 0, bottom: 0.2, left: 0 }
            }

            // Col 0: texto normal
            if (col === 0) {
                data.cell.styles.fontStyle = 'normal'
                data.cell.styles.fontSize = 6.5
            }

            // COMPARTIDO: texto negro normal
            if (col === 9 && data.cell.raw) {
                data.cell.styles.textColor = C_BLACK
                data.cell.styles.fontStyle = 'normal'
                data.cell.styles.fontSize = 6.5
            }
        },
        didDrawCell(data) {
            // Subrayado manual para el encabezado "INSC-N"
            if (data.section === 'head' && data.column.index === 7) {
                doc.setFont('helvetica', 'bold')
                doc.setFontSize(7)
                const label = 'INSC-N'
                const textW = doc.getTextWidth(label)
                const cx = data.cell.x + data.cell.width / 2
                const lineY = data.cell.y + data.cell.height / 2 + 1.4
                doc.setDrawColor(...C_BLACK)
                doc.setLineWidth(0.2)
                doc.line(cx - textW / 2, lineY, cx + textW / 2, lineY)
            }
        },
        didAddPage(data) {
            drawPageHeader()
            data.settings.margin.top = HEADER_H
        },
    })

    drawFooters()

    const blob = doc.output('blob')
    const url = URL.createObjectURL(blob)
    return { url, filename: `CargaHoraria_${anio}_${periodo}.pdf` }
}