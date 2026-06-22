// composables/usePdfResumenDos.js
import { jsPDF } from 'jspdf'
import autoTable from 'jspdf-autotable'
import { useHorarioAdmin } from './useHorarioAdmin'

const C_BLACK = [0, 0, 0]
const C_WHITE = [255, 255, 255]
const C_HEAD_BG = [211, 211, 211]
const C_GRAY_LINE = [140, 140, 140]

export function generarPDFResumenDos(docentes = [], { anio, periodo } = {}) {
    const { agruparPorMateriaGrupo, DIAS_ORDEN, DIAS_LABEL } = useHorarioAdmin()

    const dias = DIAS_ORDEN ?? ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado']

    function abrevDia(dia) {
        const label = DIAS_LABEL?.[dia] ?? dia
        return String(label).slice(0, 2).toUpperCase()
    }

    const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'letter' })

    const PAGE_W = doc.internal.pageSize.getWidth()
    const PAGE_H = doc.internal.pageSize.getHeight()
    const ML = 8
    const MR = 8
    const CW = PAGE_W - ML - MR

    const fechaActual = new Date().toLocaleString('en-US', {
        month: 'numeric', day: 'numeric', year: 'numeric',
        hour: 'numeric', minute: '2-digit', second: '2-digit', hour12: true,
    })

    const gestionLabel = `${periodo}/${anio}`
    const HEADER_H = 24

    function drawPageHeader() {
        doc.setFont('helvetica', 'bold')
        doc.setFontSize(6)
        doc.setTextColor(...C_BLACK)
        doc.text('UNIVERSIDAD MAYOR DE SAN SIMON', ML, 8)
        doc.text('FACULTAD DE CIENCIAS ECONOMICAS', ML, 10)

        doc.setFontSize(12.5)
        doc.text('CARGA HORARIA DOCENTES - RESUMEN', PAGE_W / 2, 9, { align: 'center' })
        doc.setFontSize(10.5)
        doc.text('FACULTAD DE CIENCIAS ECONOMICAS', PAGE_W / 2, 13.5, { align: 'center' })

        doc.setFont('helvetica', 'bold')
        doc.setFontSize(10)
        doc.setTextColor(...C_BLACK)
        doc.text(`Gestión Académica ${gestionLabel}`, PAGE_W / 2, 19.5, { align: 'center' })

        doc.setFont('helvetica', 'normal')
        doc.setFontSize(7)
        doc.text(fechaActual, PAGE_W - MR, 19.5, { align: 'right' })
        doc.text('Vista resumida por materia y grupo · Incluye Grupos Compartidos.', ML, 19.5)

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

    const nCols = 6 + dias.length
    const idxCH = 3 + dias.length
    const idxIns = 4 + dias.length
    const idxComp = 5 + dias.length

    // Sub-encabezado de columnas que se repite bajo cada nombre de docente
    function makeSubHead() {
        return [
            'PLAN - NIV', 'MATERIA', 'GRP',
            ...dias.map(abrevDia),
            'CH', 'INS.', 'COMP.',
        ].map(label => ({
            content: label,
            styles: {
                fontStyle: 'bold',
                fontSize: 6.8,
                halign: 'center',
                fillColor: C_HEAD_BG,
                textColor: C_BLACK,
                lineWidth: { top: 0, right: 0, bottom: 0.3, left: 0 },
                lineColor: C_GRAY_LINE,
                cellPadding: { top: 1.5, bottom: 1.5, left: 1.5, right: 1.5 },
            },
        }))
    }

    // ── SIN head global: la tabla empieza directamente con el primer docente ──
    const body = []

    for (let di = 0; di < docentes.length; di++) {
        const docente = docentes[di]
        const materiasAgrupadas = agruparPorMateriaGrupo(docente.horarios ?? [])

        // Nombre del docente — alineado a la izquierda
        body.push([{
            content: `${docente.docente}  ${(docente.apellidos ?? '').toUpperCase()} ${(docente.nombres ?? '').toUpperCase()}`,
            colSpan: nCols,
            styles: {
                fontStyle: 'bold',
                fontSize: 8.5,
                halign: 'left',
                fillColor: C_WHITE,
                lineWidth: 0,
                cellPadding: { top: di === 0 ? 1.5 : 3.5, bottom: 1, left: 1.5, right: 1.5 },
            },
        }])

        // Sub-encabezado de columnas justo debajo del nombre
        body.push(makeSubHead())

        let chCompartida = 0
        let compartidaContada = false
        let chNormal = 0
        let totalInscritos = 0

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

            fila.push(String(mat.carga ?? ''))
            fila.push(String(mat.inscritos ?? ''))
            fila.push(mat.comp ? String(mat.comp) : '—')

            body.push(fila)

            const esCompartida = mat.compartido !== undefined && mat.compartido !== null && mat.compartido !== ''
            if (esCompartida) {
                if (!compartidaContada) {
                    chCompartida = Number(mat.carga) || 0
                    compartidaContada = true
                }
            } else {
                chNormal += Number(mat.carga) || 0
            }
            totalInscritos += Number(mat.inscritos) || 0
        }

        const totalCH = chNormal + chCompartida

        const filaTotal = []
        for (let c = 0; c < nCols; c++) {
            if (c === idxCH - 1) {
                filaTotal.push({ content: 'TOTAL', styles: { halign: 'right', fontStyle: 'bold', fontSize: 8, fillColor: C_WHITE, lineWidth: 0 } })
            } else if (c === idxCH) {
                filaTotal.push({ content: String(totalCH), styles: { halign: 'center', fontStyle: 'bold', fontSize: 8, fillColor: C_WHITE, lineWidth: 0 } })
            } else if (c === idxIns) {
                filaTotal.push({ content: String(totalInscritos), styles: { halign: 'center', fontStyle: 'bold', fontSize: 8, fillColor: C_WHITE, lineWidth: 0 } })
            } else {
                filaTotal.push({ content: '', styles: { fillColor: C_WHITE, lineWidth: 0 } })
            }
        }
        body.push(filaTotal)
    }

    drawPageHeader()

    const PLAN_W = 24
    const MATERIA_W = 55
    const GRUPO_W = 10
    const CH_W = 10
    const INS_W = 12
    const COMP_W = 16
    const fixedW = PLAN_W + MATERIA_W + GRUPO_W + CH_W + INS_W + COMP_W
    const diaW = Math.max(14, (CW - fixedW) / dias.length)

    const columnStyles = {
        0: { cellWidth: PLAN_W },
        1: { cellWidth: MATERIA_W },
        2: { cellWidth: GRUPO_W, halign: 'center' },
        [idxCH]: { cellWidth: CH_W, halign: 'center' },
        [idxIns]: { cellWidth: INS_W, halign: 'center' },
        [idxComp]: { cellWidth: COMP_W, halign: 'center' },
    }
    dias.forEach((_, i) => {
        columnStyles[3 + i] = { cellWidth: diaW, halign: 'center' }
    })

    autoTable(doc, {
        startY: HEADER_H,
        margin: { left: ML, right: MR, top: HEADER_H, bottom: 12 },
        tableWidth: CW,
        // Sin head: no hay fila de encabezado flotante global
        body,
        alternateRowStyles: { fillColor: C_WHITE },
        styles: {
            font: 'helvetica', fontSize: 6.8,
            cellPadding: { top: 1, bottom: 1, left: 1.5, right: 1.5 },
            textColor: C_BLACK, lineColor: C_GRAY_LINE, lineWidth: 0,
            fillColor: C_WHITE,
            overflow: 'linebreak', valign: 'top',
        },
        columnStyles,
        didParseCell(data) {
            if (data.section !== 'body') return

            const raw = data.row.raw
            // Fila de datos: array de strings
            const isDataRow = Array.isArray(raw) && raw.length === nCols && typeof raw[0] === 'string'
                && typeof raw[1] === 'string'
            if (!isDataRow) return

            const col = data.column.index

            if (col <= 2) {
                data.cell.styles.lineWidth = { top: 0, right: 0, bottom: 0, left: 0 }
            } else {
                data.cell.styles.lineWidth = { top: 0, right: 0, bottom: 0.2, left: 0 }
            }

            if (col === 1) {
                data.cell.styles.fontStyle = 'normal'
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
    return { url, filename: `CargaHorariaResumen_${anio}_${periodo}.pdf` }
}