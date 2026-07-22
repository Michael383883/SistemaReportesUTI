// composables/useGenerarPDF.js
// Genera un PDF oficial estilo UMSS – Materias dictadas de un docente
// Orientación: PORTRAIT (vertical) – Letter 216 × 279 mm
// Dependencias: jspdf  +  jspdf-autotable
//   npm install jspdf jspdf-autotable

import { jsPDF } from 'jspdf'
import autoTable from 'jspdf-autotable'

function formatGestion(g) {
    return g || ''
}

function norm(v) {
    if (v === null || v === undefined) return ''
    return String(v).trim()
}

// ── Detecta qué filas son "hijas" (compartidas) para marcarlas como
// COMPARTIDO en el PDF, sin quitarlas ni agruparlas — misma lógica
// usada en el componente Vue de reportes.
//
// PASO 1 — Semestre regular (1 y 2): tabla GRUPOS_COMPARTIDOS
// (comp='0' padre, comp='1' hija), agrupado por orden_comparte+gestión
// (el mismo orden_comparte se repite en varias gestiones, así que se
// escopa por gestión para no mezclar años distintos).
//
// PASO 2 — Verano/Invierno (3 y 4): no hay orden_comparte, se agrupa
// por gestión. El flag compartido="COMPARTIDO" marca al PADRE; la
// otra materia de la misma gestión, sin ese flag, es la HIJA. ──────
function calcularHijasIndices(materias) {
    const hijas = new Set()

    // ── PASO 1: compartidos de semestre regular (1 y 2) ──
    const porClave = new Map()
    materias.forEach((m, idx) => {
        const orden = norm(m.orden_comparte)
        if (!orden) return
        const clave = `${orden}__${norm(m.gestion)}`
        if (!porClave.has(clave)) porClave.set(clave, [])
        porClave.get(clave).push(idx)
    })

    const resueltoEnPaso1 = new Array(materias.length).fill(false)

    for (const [, indices] of porClave) {
        if (indices.length < 2) continue
        const origenes = indices.filter((i) => norm(materias[i].comp) === '0')
        const derivadas = indices.filter((i) => norm(materias[i].comp) === '1')

        if (origenes.length === 1 && derivadas.length >= 1) {
            derivadas.forEach((i) => {
                hijas.add(i)
                resueltoEnPaso1[i] = true
            })
            resueltoEnPaso1[origenes[0]] = true
        } else {
            const pares = Math.min(origenes.length, derivadas.length)
            for (let p = 0; p < pares; p++) {
                hijas.add(derivadas[p])
                resueltoEnPaso1[derivadas[p]] = true
                resueltoEnPaso1[origenes[p]] = true
            }
        }
    }

    // ── PASO 2: compartidos de verano/invierno (3 y 4) ──
    const esCompartido = (m) => norm(m.compartido) === 'COMPARTIDO'

    const porGestionVI = new Map()
    materias.forEach((m, idx) => {
        if (resueltoEnPaso1[idx]) return
        if (norm(m.orden_comparte)) return // ya resuelto en el PASO 1
        const esVI = norm(m.gestion).includes('Verano') || norm(m.gestion).includes('Invierno')
        if (!esVI) return
        const clave = norm(m.gestion)
        if (!porGestionVI.has(clave)) porGestionVI.set(clave, [])
        porGestionVI.get(clave).push(idx)
    })

    for (const [, indices] of porGestionVI) {
        if (indices.length < 2) continue
        const padres = indices.filter((i) => esCompartido(materias[i])) // CON flag → padre
        const hijasVI = indices.filter((i) => !esCompartido(materias[i])) // SIN flag → hijo

        if (padres.length === 1 && hijasVI.length >= 1) {
            hijasVI.forEach((i) => hijas.add(i))
        }
    }

    return hijas
}

export function generarPDF(reporte, opts = {}) {
    const { action = 'open' } = opts

    // ── PORTRAIT Letter (216 × 279 mm) ──────────────────────────────────────────
    const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'letter' })

    const PAGE_W = doc.internal.pageSize.getWidth()   // ~215.9 mm
    const PAGE_H = doc.internal.pageSize.getHeight()  // ~279.4 mm
    const MARGIN_L = 12
    const MARGIN_R = 12
    const CONTENT_W = PAGE_W - MARGIN_L - MARGIN_R    // ~191.9 mm útiles

    const COLOR_BLACK = [0, 0, 0]
    const COLOR_GRAY_BG = [240, 240, 240]
    const COLOR_ROW_LINE = [170, 170, 170]   // líneas horizontales de fila

    // ── Mapa de códigos de plan ──────────────────────────────────────────────────
    const PLAN_MAP = {
        '089801': 'CCP',
        '109401': 'ADM',
        '125091': 'COM',
        '126091': 'FIN',
        '059801': 'ECO',
    }

    // ════════════════════════════════════════════════════════════════════════════
    // Encabezado institucional — se repite en cada página
    // ════════════════════════════════════════════════════════════════════════════
    function drawHeader() {
        // ── Institución izquierda ────────────────────────────────────────────────
        doc.setFont('helvetica', 'bold')
        doc.setFontSize(5.5)
        doc.setTextColor(...COLOR_BLACK)
        doc.text('UNIVERSIDAD MAYOR DE SAN SIMÓN', MARGIN_L, 6)
        doc.text('FACULTAD DE CIENCIAS ECONÓMICAS', MARGIN_L, 8)

        // ── Título centrado ──────────────────────────────────────────────────────
        doc.setFontSize(11)
        doc.text('MATERIAS DICTADAS DE UN DOCENTE', PAGE_W / 2, 12, { align: 'center' })

        // ── Línea divisoria ──────────────────────────────────────────────────────
        doc.setDrawColor(...COLOR_ROW_LINE)
        doc.setLineWidth(0.3)
        doc.line(MARGIN_L, 17, PAGE_W - MARGIN_R, 17)

        // ── Descripción (6.5 pt) ─────────────────────────────────────────────────
        doc.setFont('helvetica', 'normal')
        doc.setFontSize(6.5)
        doc.setTextColor(40, 40, 40)
        const descripcion =
            'Datos Históricos pertenecientes a la Facultad de Ciencias Económicas registrados en el SISS ' +
            'a partir de la gestión 2001. El reporte también detalla los grupos compartidos solo para los ' +
            'cursos Intersemestrales de Verano e Invierno.'
        const descLines = doc.splitTextToSize(descripcion, CONTENT_W)
        doc.text(descLines, MARGIN_L, 21)

        // ── Nombre del docente ───────────────────────────────────────────────────
        const docenteY = 21 + descLines.length * 3.0 + 3
        doc.setFont('helvetica', 'bold')
        doc.setFontSize(8)
        doc.setTextColor(...COLOR_BLACK)
        const codigoDoc = reporte.docente?.codigo || ''
        const nombreDoc = reporte.docente?.nombre || ''
        doc.text(`DOCENTE : (${codigoDoc}) - ${nombreDoc}`, MARGIN_L, docenteY)

        return docenteY + 3
    }

    doc.setProperties({ title: 'Materias dictadas de un docente' })

    const startY = drawHeader()

    // ── Columnas ──────────────────────────────────────────────────────────────────
    const columnas = [
        { header: 'Nº', dataKey: 'nro' },
        { header: 'GESTIÓN', dataKey: 'gestion' },
        { header: 'PLAN', dataKey: 'plan' },
        { header: 'MATERIA', dataKey: 'materia' },
        { header: 'COMPARTIDO', dataKey: 'compartido' },
        { header: 'GRP', dataKey: 'grp' },
        { header: 'RESOLUCIÓN', dataKey: 'resolucion' },
        { header: 'DESIGNACIÓN', dataKey: 'designacion' },
        ...(reporte.__extraColumnas || []),
    ]

    const materiasFuente = reporte.materias || []
    const hijasIndices = calcularHijasIndices(materiasFuente)

    const filas = materiasFuente.map((m, idx) => ({
        nro: m.nro,
        gestion: formatGestion(m.gestion),
        plan: PLAN_MAP[m.plan] || m.plan || '',
        materia: m.materia || '',
        compartido: hijasIndices.has(idx) ? 'COMPARTIDO' : '',
        grp: m.grp || '',
        resolucion: m.resolucion || '',
        designacion: m.designacion || '',
        // ── pasa cualquier campo extra que haya inyectado el hijo ──
        ...(reporte.__extraColumnas || []).reduce((acc, col) => {
            acc[col.dataKey] = m[col.dataKey] ?? ''
            return acc
        }, {}),
    }))

    // ── Tabla ─────────────────────────────────────────────────────────────────────
    autoTable(doc, {
        startY,
        margin: { left: MARGIN_L, right: MARGIN_R },
        tableWidth: CONTENT_W,
        head: [columnas.map((c) => c.header)],
        body: filas.map((f) => columnas.map((c) => f[c.dataKey])),

        styles: {
            font: 'helvetica',
            fontSize: 6.2,
            cellPadding: { top: 1.2, bottom: 1.2, left: 1.5, right: 1.5 },
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
            fontSize: 6.2,
            halign: 'center',
            valign: 'middle',
            lineColor: [130, 130, 130],
            lineWidth: 0.3,
        },

        alternateRowStyles: {
            fillColor: false,
        },

        columnStyles: {
            0: { cellWidth: 10, halign: 'center' },  // Nº — cabe hasta 3 dígitos
            1: { cellWidth: 22 },
            2: { cellWidth: 12, halign: 'center' },  // PLAN — ancho suficiente para 1 línea
            3: { cellWidth: 50 },
            4: { cellWidth: 20, halign: 'center' },
            5: { cellWidth: 8, halign: 'center' },
            6: { cellWidth: 22 },
            7: { cellWidth: reporte.__extraColumnas?.length ? 30 : 'auto' },
            8: { cellWidth: 22, halign: 'center' },
        },

        // ── Solo líneas horizontales entre filas, sin verticales ──────────────
        didDrawCell(data) {
            if (data.section !== 'body') return

            // Dibuja una sola vez por fila (en la última columna)
            const isLastCol = data.column.index === data.table.columns.length - 1
            if (!isLastCol) return

            const { y, height } = data.cell

            doc.setDrawColor(...COLOR_ROW_LINE)
            doc.setLineWidth(0.2)

            // Línea horizontal inferior de cada fila
            doc.line(MARGIN_L, y + height, MARGIN_L + CONTENT_W, y + height)
        },

        didParseCell(data) {
            // Materia y Designación: fuente ligeramente menor
            if (data.section === 'body' && (data.column.index === 3 || data.column.index === 7)) {
                data.cell.styles.fontSize = 6.0
            }
        },

        didAddPage() {
            drawHeader()
        },

        didDrawPage() {
            const pageNum = doc.internal.getCurrentPageInfo().pageNumber
            const footerY = PAGE_H - 5

            doc.setDrawColor(...COLOR_ROW_LINE)
            doc.setLineWidth(0.2)
            doc.line(MARGIN_L, footerY - 3.5, PAGE_W - MARGIN_R, footerY - 3.5)

            doc.setFont('helvetica', 'normal')
            doc.setFontSize(6)
            doc.setTextColor(90, 90, 90)

            doc.text('Procesado UTi - Facultad de Ciencias Económicas', MARGIN_L, footerY)

            doc.text(
                `Página ${pageNum} de {totalPages}`,
                PAGE_W / 2,
                footerY,
                { align: 'center' },
            )

            const now = new Date()
            const ahora = `${String(now.getDate()).padStart(2, '0')}/${String(now.getMonth() + 1).padStart(2, '0')}/${now.getFullYear()} ${now.toLocaleString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true })}`
            doc.text(ahora, PAGE_W - MARGIN_R, footerY, { align: 'right' })
        },
    })

    if (typeof doc.putTotalPages === 'function') {
        doc.putTotalPages('{totalPages}')
    }

    // ── Acción ────────────────────────────────────────────────────────────────────
    const codigoDoc = reporte.docente?.codigo || 'doc'
    const fileName = `reporte_docente_${codigoDoc}.pdf`

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