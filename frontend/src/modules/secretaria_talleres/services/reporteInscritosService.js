/**
 * reporteInscritosService.js
 * Reporte PDF – Alumnos Inscritos en Talleres
 * Estilo institucional UMSS (mismo formato que "Materias dictadas de un docente")
 * Orientación: PORTRAIT – Letter 216 × 279 mm
 *
 * Requiere: jsPDF + jspdf-autotable
 *   npm install jspdf jspdf-autotable
 */

import { jsPDF } from 'jspdf'
import autoTable from 'jspdf-autotable'

// ─── Constantes de diseño (sin colores, solo escala de grises) ───────────────

const COLOR_BLACK = [0, 0, 0]
const COLOR_GRAY_BG = [218, 218, 218]      // fondo de cabecera de tabla
const COLOR_ROW_LINE = [170, 170, 170]     // líneas horizontales de fila
const COLOR_TEXT_DESC = [40, 40, 40]
const COLOR_TEXT_FOOTER = [90, 90, 90]

const PLANES = {
    '109401': 'Lic. Administración de Empresas',
    '125091': 'Lic. Ingeniería Comercial',
    '089801': 'Lic. Contaduría Pública',
    '126091': 'Lic. Ingeniería Financiera',
    '059801': 'Lic. Economía',
}

const nombrePlan = (cod) => PLANES[cod] || cod

// ─── helpers ─────────────────────────────────────────────────────────────────

const pad = (n) => String(n).padStart(2, '0')

const fechaLarga = () => {
    const d = new Date()
    const meses = [
        'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
        'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre',
    ]
    return `${d.getDate()} de ${meses[d.getMonth()]} de ${d.getFullYear()}`
}

const horaActual = () => {
    const d = new Date()
    return `${pad(d.getHours())}:${pad(d.getMinutes())}`
}

// ─── Función principal ───────────────────────────────────────────────────────

/**
 * Genera un PDF con la lista de alumnos inscritos en talleres,
 * segmentado por materia/grupo.
 *
 * @param {Array}  estudiantes  – array normalizado de estudiantes
 * @param {Object} [opciones]
 * @param {string} [opciones.anio]
 * @param {string} [opciones.periodo]
 * @param {string} [opciones.action]  – 'open' | 'save' | 'print'
 */
export function generarReporteInscritos(estudiantes, opciones = {}) {
    const { anio = '2026', periodo = '1', action = 'save' } = opciones

    const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'letter' })

    const PAGE_W = doc.internal.pageSize.getWidth()
    const PAGE_H = doc.internal.pageSize.getHeight()
    const MARGIN_L = 12
    const MARGIN_R = 12
    const CONTENT_W = PAGE_W - MARGIN_L - MARGIN_R

    doc.setProperties({ title: 'Alumnos Inscritos en Talleres' })

    // ── Agrupar por materia + grupo ──────────────────────────────────────────
    const grupos = estudiantes.reduce((acc, est) => {
        const key = `${est.materia}_${est.grupo}`
        if (!acc[key]) {
            acc[key] = {
                codigoMateria: est.materia,
                materia: est.nom_materia,
                grupo: est.grupo,
                docente: est.docente,
                lista: [],
            }
        }
        acc[key].lista.push(est)
        return acc
    }, {})

    const gruposArr = Object.values(grupos)

    // ════════════════════════════════════════════════════════════════════════
    // Encabezado institucional — distinto para portada y para páginas de tabla
    // ════════════════════════════════════════════════════════════════════════
    function drawHeader(subtituloMateria) {
        // ── Institución izquierda ────────────────────────────────────────────
        doc.setFont('helvetica', 'bold')
        doc.setFontSize(5.5)
        doc.setTextColor(...COLOR_BLACK)
        doc.text('UNIVERSIDAD MAYOR DE SAN SIMON', MARGIN_L, 6)
        doc.text('FACULTAD DE CIENCIAS ECONOMICAS', MARGIN_L, 8)

        // ── Gestión arriba a la derecha ──────────────────────────────────────
        doc.setFontSize(5.5)
        doc.text(`GESTION ${periodo}/${anio}`, PAGE_W - MARGIN_R, 6, { align: 'right' })
        doc.setFont('helvetica', 'normal')
        doc.text(`Generado: ${fechaLarga()} - ${horaActual()}`, PAGE_W - MARGIN_R, 8, { align: 'right' })

        // ── Título centrado ──────────────────────────────────────────────────
        doc.setFont('helvetica', 'bold')
        doc.setFontSize(11)
        doc.setTextColor(...COLOR_BLACK)
        doc.text('ALUMNOS INSCRITOS EN TALLERES', PAGE_W / 2, 12, { align: 'center' })

        // ── Línea divisoria ─────────────────────────────────────────────────
        doc.setDrawColor(...COLOR_ROW_LINE)
        doc.setLineWidth(0.3)
        doc.line(MARGIN_L, 17, PAGE_W - MARGIN_R, 17)

        // ── Descripción (6.5 pt) ────────────────────────────────────────────
        doc.setFont('helvetica', 'normal')
        doc.setFontSize(6.5)
        doc.setTextColor(...COLOR_TEXT_DESC)
        const descripcion =
            'Listado de alumnos inscritos en los Talleres de la Facultad de Ciencias Económicas, ' +
            'organizado por materia, grupo y docente responsable.'
        const descLines = doc.splitTextToSize(descripcion, CONTENT_W)
        doc.text(descLines, MARGIN_L, 21)

        let y = 21 + descLines.length * 3.0 + 3

        // ── Subtítulo de materia (solo en páginas de detalle) ───────────────
        if (subtituloMateria) {
            doc.setFont('helvetica', 'bold')
            doc.setFontSize(8)
            doc.setTextColor(...COLOR_BLACK)
            doc.text(
                `MATERIA: (${subtituloMateria.codigoMateria}) - ${subtituloMateria.materia}`,
                MARGIN_L,
                y,
            )
            y += 3.5
            doc.setFont('helvetica', 'normal')
            doc.setFontSize(7)
            doc.setTextColor(...COLOR_TEXT_DESC)
            doc.text(
                `GRUPO: ${subtituloMateria.grupo}     DOCENTE: ${subtituloMateria.docente}     INSCRITOS: ${subtituloMateria.lista.length}`,
                MARGIN_L,
                y,
            )
            y += 3
        }

        return y
    }

    // ── Pie de página institucional (igual estilo que useGenerarPDF) ────────
    function drawFooter() {
        const pageNum = doc.internal.getCurrentPageInfo().pageNumber
        const footerY = PAGE_H - 5

        doc.setDrawColor(...COLOR_ROW_LINE)
        doc.setLineWidth(0.2)
        doc.line(MARGIN_L, footerY - 3.5, PAGE_W - MARGIN_R, footerY - 3.5)

        doc.setFont('helvetica', 'normal')
        doc.setFontSize(6)
        doc.setTextColor(...COLOR_TEXT_FOOTER)

        doc.text('Procesado - Secretaria de Talleres - UMSS', MARGIN_L, footerY)
        doc.text(`Página ${pageNum} de {totalPages}`, PAGE_W / 2, footerY, { align: 'center' })
        doc.text(fechaLarga(), PAGE_W - MARGIN_R, footerY, { align: 'right' })
    }

    // ════════════════════════════════════════════════════════════════════════
    // Portada / página de resumen
    // ════════════════════════════════════════════════════════════════════════
    let y = drawHeader(null)
    y += 3

    doc.setFont('helvetica', 'bold')
    doc.setFontSize(8)
    doc.setTextColor(...COLOR_BLACK)
    doc.text('INDICE DE MATERIAS', MARGIN_L, y)
    y += 2

    doc.setDrawColor(...COLOR_ROW_LINE)
    doc.setLineWidth(0.3)
    doc.line(MARGIN_L, y, PAGE_W - MARGIN_R, y)
    y += 5

    // ── Tabla-índice (misma estética: solo cabecera gris + líneas horizontales) ──
    autoTable(doc, {
        startY: y,
        margin: { left: MARGIN_L, right: MARGIN_R },
        tableWidth: CONTENT_W,
        head: [['Nº', 'MATERIA', 'GRUPO', 'DOCENTE', 'INSCRITOS']],
        body: gruposArr.map((g, i) => [
            i + 1,
            g.materia,
            g.grupo,
            g.docente,
            g.lista.length,
        ]),

        styles: {
            font: 'helvetica',
            fontSize: 7,
            cellPadding: { top: 1.5, bottom: 1.5, left: 1.5, right: 1.5 },
            textColor: COLOR_BLACK,
            lineColor: COLOR_ROW_LINE,
            lineWidth: 0,
            overflow: 'linebreak',
            valign: 'middle',
            fillColor: false,
        },

        headStyles: {
            fillColor: COLOR_GRAY_BG,
            textColor: COLOR_BLACK,
            fontStyle: 'bold',
            fontSize: 7,
            halign: 'center',
            valign: 'middle',
            lineColor: [130, 130, 130],
            lineWidth: 0.3,
        },

        alternateRowStyles: { fillColor: false },

        columnStyles: {
            0: { cellWidth: 10, halign: 'center' },
            1: { cellWidth: 85 },
            2: { cellWidth: 20, halign: 'center' },
            3: { cellWidth: 50 },
            4: { cellWidth: 'auto', halign: 'center' },
        },

        didDrawCell(data) {
            if (data.section !== 'body') return
            const isLastCol = data.column.index === data.table.columns.length - 1
            if (!isLastCol) return
            const { y: cy, height } = data.cell
            doc.setDrawColor(...COLOR_ROW_LINE)
            doc.setLineWidth(0.2)
            doc.line(MARGIN_L, cy + height, MARGIN_L + CONTENT_W, cy + height)
        },

        didDrawPage: drawFooter,
    })

    // Total general
    let totalY = doc.lastAutoTable.finalY + 6
    doc.setDrawColor(...COLOR_ROW_LINE)
    doc.setLineWidth(0.3)
    doc.line(MARGIN_L, totalY, PAGE_W - MARGIN_R, totalY)
    totalY += 4
    doc.setFont('helvetica', 'bold')
    doc.setFontSize(8)
    doc.setTextColor(...COLOR_BLACK)
    doc.text(`TOTAL GENERAL DE ALUMNOS INSCRITOS: ${estudiantes.length}`, MARGIN_L, totalY)

    // ════════════════════════════════════════════════════════════════════════
    // Una tabla por materia/grupo
    // ════════════════════════════════════════════════════════════════════════
    gruposArr.forEach((g) => {
        doc.addPage()
        const startY = drawHeader(g)

        autoTable(doc, {
            startY,
            margin: { left: MARGIN_L, right: MARGIN_R },
            tableWidth: CONTENT_W,

            head: [['Nº', 'CÓDIGO', 'NOMBRE DEL ESTUDIANTE', 'CARRERA', 'GRUPO']],

            body: g.lista.map((est, i) => [
                i + 1,
                est.cod_estudiante || est.codigo,
                est.nom_estudiante,
                nombrePlan(est.plan),
                est.grupo,
            ]),

            styles: {
                font: 'helvetica',
                fontSize: 7,
                cellPadding: { top: 1.5, bottom: 1.5, left: 1.5, right: 1.5 },
                textColor: COLOR_BLACK,
                lineColor: COLOR_ROW_LINE,
                lineWidth: 0,
                overflow: 'linebreak',
                valign: 'middle',
                fillColor: false,
            },

            headStyles: {
                fillColor: COLOR_GRAY_BG,
                textColor: COLOR_BLACK,
                fontStyle: 'bold',
                fontSize: 7,
                halign: 'center',
                valign: 'middle',
                lineColor: [130, 130, 130],
                lineWidth: 0.3,
            },

            alternateRowStyles: { fillColor: false },

            columnStyles: {
                0: { cellWidth: 10, halign: 'center' },
                1: { cellWidth: 22, halign: 'center' },
                2: { cellWidth: 75 },
                3: { cellWidth: 55 },
                4: { cellWidth: 'auto', halign: 'center' },
            },

            // Solo líneas horizontales, sin verticales (misma técnica que useGenerarPDF)
            didDrawCell(data) {
                if (data.section !== 'body') return
                const isLastCol = data.column.index === data.table.columns.length - 1
                if (!isLastCol) return
                const { y: cy, height } = data.cell
                doc.setDrawColor(...COLOR_ROW_LINE)
                doc.setLineWidth(0.2)
                doc.line(MARGIN_L, cy + height, MARGIN_L + CONTENT_W, cy + height)
            },

            didAddPage() {
                drawHeader(g)
            },

            didDrawPage: drawFooter,
        })

        // Línea de firma / validación
        const finalY = doc.lastAutoTable.finalY + 12
        if (finalY < PAGE_H - 20) {
            doc.setFont('helvetica', 'normal')
            doc.setFontSize(7)
            doc.setTextColor(...COLOR_TEXT_FOOTER)
            doc.text('Docente responsable: ___________________________________', MARGIN_L, finalY)
            doc.text('Firma: _______________', PAGE_W - MARGIN_R, finalY, { align: 'right' })
        }
    })

    // ── Total de páginas dinámico (igual que useGenerarPDF) ─────────────────
    if (typeof doc.putTotalPages === 'function') {
        doc.putTotalPages('{totalPages}')
    }

    // ── Acción de salida ─────────────────────────────────────────────────────
    const fileName = `Reporte_Inscritos_Talleres_${anio}_P${periodo}.pdf`

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