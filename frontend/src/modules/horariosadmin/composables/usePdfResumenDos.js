// composables/usePdfResumenDos.js
import { jsPDF } from 'jspdf'
import autoTable from 'jspdf-autotable'
import { useHorarioAdmin } from './useHorarioAdmin'

const C_BLACK = [0, 0, 0]
const C_WHITE = [255, 255, 255]
const C_HEAD_BG = [240, 240, 240]
const C_GRAY_LINE = [140, 140, 140]
const C_DAY_SEPARATOR = [180, 180, 180]

// ── Función para determinar si una materia es compartida y su tipo ──
function getTipoCompartido(mat) {
    const compTexto = mat.compartido ?? ''
    const comp = mat.comp ?? ''

    if (compTexto !== '' && String(comp) === '1') return 'derivada'
    if (compTexto !== '' && String(comp) === '0') return 'origen'
    return 'normal'
}

// ── Función para determinar la CH a mostrar ──
function chMostrada(mat) {
    const tipo = getTipoCompartido(mat)
    if (tipo === 'derivada') return 0
    return Number(mat.carga) || 0
}

export function generarPDFResumenDos(docentes = [], { anio, periodo } = {}) {
    const { agruparPorMateriaGrupo, DIAS_ORDEN, DIAS_LABEL } = useHorarioAdmin()

    const dias = DIAS_ORDEN ?? ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado']

    function abrevDia(dia) {
        const label = DIAS_LABEL?.[dia] ?? dia
        return String(label).slice(0, 2).toUpperCase()
    }

    const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'letter' })

    const PAGE_W = doc.internal.pageSize.getWidth()
    const PAGE_H = doc.internal.pageSize.getHeight()
    const ML = 6
    const MR = 6
    const CW = PAGE_W - ML - MR

    const d = new Date()
    const fechaActual = `${String(d.getDate()).padStart(2, '0')}/${String(d.getMonth() + 1).padStart(2, '0')}/${d.getFullYear()} ${d.toLocaleString('en-US', { hour: 'numeric', minute: '2-digit', second: '2-digit', hour12: true })}`

    const gestionLabel = `${periodo}/${anio}`
    const HEADER_H = 22

    function drawPageHeader() {
        doc.setFont('helvetica', 'bold')
        doc.setFontSize(6)
        doc.setTextColor(...C_BLACK)
        doc.text('UNIVERSIDAD MAYOR DE SAN SIMÓN', ML, 7)
        doc.text('FACULTAD DE CIENCIAS ECONÓMICAS', ML, 9)

        doc.setFontSize(11.5)
        doc.text('CARGA HORARIA DOCENTES - RESUMEN', PAGE_W / 2, 8, { align: 'center' })
        doc.setFontSize(9.5)
        doc.text('FACULTAD DE CIENCIAS ECONÓMICAS', PAGE_W / 2, 12, { align: 'center' })

        doc.setFont('helvetica', 'bold')
        doc.setFontSize(9)
        doc.setTextColor(...C_BLACK)
        doc.text(`Gestión Académica ${gestionLabel}`, PAGE_W / 2, 17.5, { align: 'center' })

        doc.setFont('helvetica', 'normal')
        doc.setFontSize(6.5)
        doc.text(fechaActual, PAGE_W - MR, 17.5, { align: 'right' })
        doc.text('La carga horaria incluye Grupos Compartidos.', ML, 17.5)

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

    // 6 columnas fijas + días
    const nCols = 6 + dias.length
    const idxCH = 3 + dias.length          // columna de CH
    const idxIns = idxCH + 1               // INS.
    const idxComp = idxIns + 1             // COMP.

    function makeSubHead() {
        return [
            'PLAN-NIV', 'MATERIA', 'GRP',
            ...dias.map(abrevDia),
            'CH', 'INS.', 'C'
        ].map(label => ({
            content: label,
            styles: {
                fontStyle: 'bold',
                fontSize: 6.3,
                halign: 'center',
                fillColor: C_HEAD_BG,
                textColor: C_BLACK,
                lineWidth: { top: 0, right: 0, bottom: 0.3, left: 0 },
                lineColor: C_GRAY_LINE,
                cellPadding: { top: 0.8, bottom: 0.8, left: 0.3, right: 0.3 },
            },
        }))
    }

    const body = []
    const totalRowIndices = []

    for (let di = 0; di < docentes.length; di++) {
        const docente = docentes[di]
        const materiasAgrupadas = agruparPorMateriaGrupo(docente.horarios ?? [])

        body.push([{
            content: `${docente.docente}  ${(docente.apellidos ?? '').toUpperCase()} ${(docente.nombres ?? '').toUpperCase()}`,
            colSpan: nCols,
            styles: {
                fontStyle: 'normal',
                fontSize: 7.8,
                halign: 'left',
                fillColor: C_WHITE,
                lineWidth: 0,
                cellPadding: { top: di === 0 ? 1 : 2, bottom: 0.6, left: 1, right: 1 },
            },
        }])

        let totalCH = 0
        let totalInscritos = 0
        const gruposVistosInsc = new Set()

        for (const mat of materiasAgrupadas) {
            const planNiv = [mat.carrera, mat.nivel].filter(Boolean).join(' - ')
            const materiaTexto = [mat.nombre, mat.materia].filter(Boolean).join('\n')

            const fila = [planNiv, materiaTexto, mat.grupo ?? '']

            for (const dia of dias) {
                const sesionesDia = mat.sesiones.filter(s => s.dia === dia)
                const texto = sesionesDia
                    .map(s => [s.horario, s.ambiente].filter(Boolean).join('\n'))
                    .join('\n')
                fila.push(texto)
            }

            const chValue = chMostrada(mat)
            fila.push(String(chValue))
            fila.push(String(mat.inscritos ?? ''))

            const tipo = getTipoCompartido(mat)
            let compDisplay
            if (tipo === 'origen') compDisplay = '0'
            else if (tipo === 'derivada') compDisplay = '1'
            else compDisplay = '0'
            fila.push(compDisplay)

            body.push(fila)

            totalCH += chValue

            const clave = `${mat.carrera ?? ''}_${mat.grupo ?? ''}_${mat.materia ?? ''}_${mat.nivel ?? ''}`
            if (!gruposVistosInsc.has(clave)) {
                gruposVistosInsc.add(clave)
                totalInscritos += Number(mat.inscritos) || 0
            }
        }

        const filaTotal = []
        for (let c = 0; c < nCols; c++) {
            if (c === idxCH - 1) {
                filaTotal.push({ content: 'TOTAL', styles: { halign: 'right', fontStyle: 'bold', fontSize: 7, fillColor: C_WHITE, lineWidth: 0 } })
            } else if (c === idxCH) {
                const mes = totalCH * 4
                filaTotal.push({ content: `${totalCH} Mes(${mes})`, styles: { halign: 'center', fontStyle: 'bold', fontSize: 7, fillColor: C_WHITE, lineWidth: 0 } })
            } else if (c === idxIns) {
                filaTotal.push({ content: String(totalInscritos), styles: { halign: 'center', fontStyle: 'bold', fontSize: 7, fillColor: C_WHITE, lineWidth: 0 } })
            } else {
                filaTotal.push({ content: '', styles: { fillColor: C_WHITE, lineWidth: 0 } })
            }
        }
        totalRowIndices.push(body.length)
        body.push(filaTotal)
    }

    drawPageHeader()

    const PLAN_W = 12
    const MATERIA_W = 44
    const GRUPO_W = 7
    const CH_W = 14
    const INS_W = 9
    const COMP_W = 5
    const fixedW = PLAN_W + MATERIA_W + GRUPO_W + CH_W + INS_W + COMP_W
    const diaW = Math.max(14, (CW - fixedW) / dias.length)

    const columnStyles = {
        0: { cellWidth: PLAN_W, halign: 'left', cellPadding: { top: 0.3, bottom: 0.3, left: 0.3, right: 0.3 } },
        1: { cellWidth: MATERIA_W, halign: 'left', cellPadding: { top: 0.3, bottom: 0.3, left: 0.3, right: 0.3 } },
        2: { cellWidth: GRUPO_W, halign: 'center' },
        [idxCH]: { cellWidth: CH_W, halign: 'center' },
        [idxIns]: { cellWidth: INS_W, halign: 'center' },
        [idxComp]: { cellWidth: COMP_W, halign: 'center' },
    }

    dias.forEach((_, i) => {
        const colIndex = 3 + i
        const isLastDay = i === dias.length - 1
        columnStyles[colIndex] = {
            cellWidth: diaW,
            halign: 'center',
            lineWidth: { top: 0, right: isLastDay ? 0 : 0.3, bottom: 0, left: 0 },
            lineColor: C_DAY_SEPARATOR,
        }
    })

    autoTable(doc, {
        startY: HEADER_H,
        margin: { left: ML, right: MR, top: HEADER_H, bottom: 10 },
        tableWidth: CW,
        head: [makeSubHead()],
        showHead: 'everyPage',
        body,
        alternateRowStyles: { fillColor: C_WHITE },
        styles: {
            font: 'helvetica', fontSize: 6.3,
            cellPadding: { top: 0.4, bottom: 0.4, left: 0.5, right: 0.5 },
            textColor: C_BLACK, lineColor: C_GRAY_LINE, lineWidth: 0,
            fillColor: C_WHITE,
            overflow: 'linebreak', valign: 'top',
        },
        columnStyles,
        didParseCell(data) {
            if (data.section === 'head') {
                if (data.column.index === 0) data.cell.styles.fontSize = 5.5
                return
            }
            if (data.section !== 'body') return
            const raw = data.row.raw
            const isDataRow = Array.isArray(raw) && raw.length === nCols && typeof raw[0] === 'string' && typeof raw[1] === 'string'
            if (!isDataRow) return
            const col = data.column.index
            data.cell.styles.lineWidth = { top: 0, right: 0, bottom: 0.2, left: 0 }
            if (col === 1) data.cell.styles.fontStyle = 'normal'
            if (col === 0) data.cell.styles.fontSize = 5.5
        },
        didDrawCell(data) {
            if (data.section !== 'body') return;

            const rowIndex = data.row.index;
            const col = data.column.index;
            const raw = data.row.raw;

            if (totalRowIndices.includes(rowIndex)) return;

            const dayStartCol = 3;
            const dayEndCol = 3 + dias.length - 1;
            const isNameRow = typeof raw === 'object' && raw !== null && raw.colSpan;

            // Líneas en filas de materias
            if (!isNameRow && col >= dayStartCol && col < dayEndCol) {
                const x = data.cell.x + data.cell.width;
                const y1 = data.cell.y;
                const y2 = data.cell.y + data.cell.height;
                doc.setDrawColor(...C_DAY_SEPARATOR);
                doc.setLineWidth(0.3);
                doc.line(x, y1, x, y2);
            }

            // Líneas en la fila del nombre (cálculo manual con anchos fijos)
            if (isNameRow) {
                const startX = data.cell.x;
                const y1 = data.cell.y;
                const y2 = data.cell.y + data.cell.height;
                const fixedWidth = PLAN_W + MATERIA_W + GRUPO_W;
                for (let d = 0; d < dias.length - 1; d++) {
                    const x = startX + fixedWidth + (d + 1) * diaW;
                    doc.setDrawColor(...C_DAY_SEPARATOR);
                    doc.setLineWidth(0.3);
                    doc.line(x, y1, x, y2);
                }
            }
        },
        didDrawPage(data) {
            if (doc.internal.getCurrentPageInfo().pageNumber > 1) {
                drawPageHeader()
            }
        },
    })

    drawFooters()

    const blob = doc.output('blob')
    const url = URL.createObjectURL(blob)
    return { url, filename: `CargaHorariaResumen_${anio}_${periodo}.pdf` }
}