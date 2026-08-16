// composables/useGenerarPDFCompartido.js
// Genera un PDF oficial estilo UMSS – Materias dictadas de un docente,
// versión "Compartidos": agrupa cada materia padre con sus materias
// hermanas (compartidas) en una sola fila, igual que ReporteTablaCom.vue.
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

// ── Mapa de códigos de plan ──────────────────────────────────────────────────
const PLAN_MAP = {
    '089801': 'CCP',
    '109401': 'ADM',
    '125091': 'COM',
    '126091': 'FIN',
    '059801': 'ECO',
}

// ── Agrupa materias compartidas ──────────────────────────────────────────────
//
// PASO 1 — Semestre regular (1 y 2): usa la tabla GRUPOS_COMPARTIDOS
// (comp='0' padre, comp='1' hija), agrupado por orden_comparte+gestión.
//
// PASO 2 — Verano/Invierno (3 y 4): no hay orden_comparte ahí, así que
// se agrupa por gestión. El flag compartido="COMPARTIDO" marca al PADRE
// (fila propia); la otra materia de la misma gestión, sin ese flag, es
// su HIJA (se cuelga en "Comparte", sin fila propia).
function agruparCompartidas(materias) {
    const lista = materias || []

    // ── PASO 1: compartidos de semestre regular (1 y 2) ──
    const porClave = new Map()
    lista.forEach((m, idx) => {
        const orden = norm(m.orden_comparte)
        if (!orden) return
        const clave = `${orden}__${norm(m.gestion)}`
        if (!porClave.has(clave)) porClave.set(clave, [])
        porClave.get(clave).push(idx)
    })

    const hermanasDe = new Map()
    const usadaComoHermana = new Array(lista.length).fill(false)

    for (const [, indices] of porClave) {
        if (indices.length < 2) continue
        const origenes = indices.filter((i) => norm(lista[i].comp) === '0')
        const derivadas = indices.filter((i) => norm(lista[i].comp) === '1')

        if (origenes.length === 1 && derivadas.length >= 1) {
            hermanasDe.set(origenes[0], derivadas)
            derivadas.forEach((i) => { usadaComoHermana[i] = true })
        } else {
            const pares = Math.min(origenes.length, derivadas.length)
            for (let p = 0; p < pares; p++) {
                hermanasDe.set(origenes[p], [derivadas[p]])
                usadaComoHermana[derivadas[p]] = true
            }
        }
    }

    // ── PASO 2: compartidos de verano/invierno (3 y 4) ──
    const esCompartido = (m) => norm(m.compartido) === 'COMPARTIDO'

    const porGestionVI = new Map()
    lista.forEach((m, idx) => {
        if (usadaComoHermana[idx] || hermanasDe.has(idx)) return
        if (norm(m.orden_comparte)) return // ya resuelto en el PASO 1
        const esVI = norm(m.gestion).includes('Verano') || norm(m.gestion).includes('Invierno')
        if (!esVI) return
        const clave = norm(m.gestion)
        if (!porGestionVI.has(clave)) porGestionVI.set(clave, [])
        porGestionVI.get(clave).push(idx)
    })

    for (const [, indices] of porGestionVI) {
        if (indices.length < 2) continue
        const padres = indices.filter((i) => esCompartido(lista[i]))   // CON flag → padre
        const hijas = indices.filter((i) => !esCompartido(lista[i]))   // SIN flag → hijo

        if (padres.length === 1 && hijas.length >= 1) {
            hermanasDe.set(padres[0], hijas)
            hijas.forEach((i) => { usadaComoHermana[i] = true })
        }
    }

    // ── PASO 3: arma las filas finales, en orden original ──
    const filas = []
    lista.forEach((m, idx) => {
        if (usadaComoHermana[idx]) return
        filas.push({
            principal: m,
            hermanas: (hermanasDe.get(idx) || []).map((i) => lista[i]),
        })
    })

    return filas
}

// 🔥 COMPARTE: formato sin número, solo "1302196 ADMINISTRACION FINANCIERA II (FIN - G) - G"
function formatComparte(hermanas) {
    if (!hermanas || !hermanas.length) return ''
    return hermanas
        .map((h) => {
            const codigo = h.codigo || h.materia_codigo || ''
            const materia = h.materia || ''
            const plan = PLAN_MAP[h.plan] || h.plan || ''
            const nivel = h.nivel || ''
            const grp = h.grp ? ` - ${h.grp}` : ''

            // Formato: "1302196 ADMINISTRACION FINANCIERA II (FIN - G) - G"
            let resultado = ''
            if (plan) {
                resultado += `${plan}`
                if (nivel) resultado += ` - ${nivel}`
                resultado += '-'
            }
            if (codigo) resultado += `${codigo} `
            resultado += materia

            if (grp) resultado += grp

            return resultado.trim()
        })
        .join('\n')
}

export function generarPDFCompartido(reporte, opts = {}) {
    const { action = 'open', documentosCategoria = [], categoriasSeleccionadas = [] } = opts

    const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'letter' })

    const PAGE_W = doc.internal.pageSize.getWidth()
    const PAGE_H = doc.internal.pageSize.getHeight()
    const MARGIN_L = 12
    const MARGIN_R = 12
    const CONTENT_W = PAGE_W - MARGIN_L - MARGIN_R

    const COLOR_BLACK = [0, 0, 0]
    const COLOR_GRAY_BG = [240, 240, 240]
    const COLOR_ROW_LINE = [170, 170, 170]

    function drawHeader() {
        doc.setFont('helvetica', 'bold')
        doc.setFontSize(5.5)
        doc.setTextColor(...COLOR_BLACK)
        doc.text('UNIVERSIDAD MAYOR DE SAN SIMÓN', MARGIN_L, 8)
        doc.text('FACULTAD DE CIENCIAS ECONÓMICAS', MARGIN_L, 6)

        doc.setFontSize(11)
        doc.text('MATERIAS DICTADAS DE UN DOCENTE ', PAGE_W / 2, 12, { align: 'center' })

        doc.setDrawColor(...COLOR_ROW_LINE)
        doc.setLineWidth(0.3)
        doc.line(MARGIN_L, 17, PAGE_W - MARGIN_R, 17)

        doc.setFont('helvetica', 'normal')
        doc.setFontSize(6.5)
        doc.setTextColor(40, 40, 40)
        const descripcion =
            'Datos históricos pertenecientes a la Facultad de Ciencias Económicas registrados en el SISS ' +
            'a partir de la gestión 2001. Esta versión agrupa cada materia con los planes/grupos con los ' +
            'que comparte grupo, mostrados en la columna "Comparte".'
        const descLines = doc.splitTextToSize(descripcion, CONTENT_W)
        doc.text(descLines, MARGIN_L, 21)

        const docenteY = 21 + descLines.length * 3.0 + 3
        doc.setFont('helvetica', 'bold')
        doc.setFontSize(8)
        doc.setTextColor(...COLOR_BLACK)
        const codigoDoc = reporte.docente?.codigo || ''
        const nombreDoc = reporte.docente?.nombre || ''
        doc.text(`DOCENTE : (${codigoDoc}) - ${nombreDoc}`, MARGIN_L, docenteY)

        return docenteY + 3
    }

    doc.setProperties({ title: 'Materias dictadas de un docente (Compartidos)' })

    const startY = drawHeader()

    const columnas = [
        { header: 'N°', dataKey: 'nro' },
        { header: 'GESTIÓN', dataKey: 'gestion' },
        { header: 'PLAN', dataKey: 'plan' },
        { header: 'MATERIA', dataKey: 'materia' },
        { header: 'GRP', dataKey: 'grp' },
        { header: 'COMPARTE', dataKey: 'comparte' },
        { header: 'RESOLUCIÓN', dataKey: 'resolucion' },
        { header: 'DESIGNACIÓN', dataKey: 'designacion' },
    ]

    const filasAgrupadas = agruparCompartidas(reporte.materias)

    //  Nº: solo los padres (materias origen), no las hijas
    // Como ya filtramos en agruparCompartidas, cada fila es un padre
    const filas = filasAgrupadas.map(({ principal: m, hermanas }, index) => ({
        nro: index + 1,
        gestion: formatGestion(m.gestion),
        plan: PLAN_MAP[m.plan] || m.plan || '',
        materia: m.materia || '',
        grp: m.grp || '',
        comparte: formatComparte(hermanas),
        resolucion: m.resolucion || '',
        designacion: m.designacion || '',
    }))

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
            0: { cellWidth: 7, halign: 'center' },
            1: { cellWidth: 22, halign: 'center' },
            2: { cellWidth: 10, halign: 'center' },
            3: { cellWidth: 45, halign: 'left' },
            4: { cellWidth: 8, halign: 'center' },
            5: { cellWidth: 43, halign: 'left' },
            6: { cellWidth: 18, halign: 'left' },
            7: { cellWidth: 'auto', halign: 'left' },
        },

        didDrawCell(data) {
            if (data.section !== 'body') return

            const isLastCol = data.column.index === data.table.columns.length - 1
            if (!isLastCol) return

            const { y, height } = data.cell

            doc.setDrawColor(...COLOR_ROW_LINE)
            doc.setLineWidth(0.2)

            doc.line(MARGIN_L, y + height, MARGIN_L + CONTENT_W, y + height)
        },

        didParseCell(data) {
            if (data.section === 'body' && [3, 5, 7].includes(data.column.index)) {
                data.cell.styles.fontSize = 6.0
            }
            if (data.section === 'body' && data.column.index === 1) {
                data.cell.styles.fontSize = 6.5
                data.cell.styles.halign = 'center'
            }
            if (data.section === 'body' && data.column.index === 4) {
                data.cell.styles.fontSize = 5.5
            }
            if (data.section === 'head' && data.column.index === 0) {
                data.cell.styles.fontSize = 6.2
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

    // ════════════════════════════════════════════════════════════════
    // Tabla de documentos por categoría (al final, sin columna Documento)
    // ════════════════════════════════════════════════════════════════
    if (documentosCategoria.length) {
        let y = (doc.lastAutoTable?.finalY ?? startY) + 8

        if (y > PAGE_H - 30) {
            doc.addPage()
            y = drawHeader()
        }

        doc.setFont('helvetica', 'bold')
        doc.setFontSize(8)
        doc.setTextColor(...COLOR_BLACK)
        
        y += 1

        const columnasCat = [
            { header: 'Nº', dataKey: 'nro' },
            { header: 'GESTIÓN', dataKey: 'gestion' },
            { header: 'TIPO DE DOCUMENTO', dataKey: 'tipo' },
            { header: 'DETALLE GENERAL', dataKey: 'detalle' },
            { header: 'CATEGORÍA', dataKey: 'categoria' },
        ]

        const filasCat = documentosCategoria.map((d) => ({
            nro: d.nro,
            gestion: `${d.GESTION ?? ''}${d.PERIODO ? '/' + d.PERIODO : ''}`,
            tipo: d.TIPO_DOCUMENTO || '',
            detalle: d.DETALLE_GENERAL || '',
            categoria: d.CATEGORIA || '',
        }))

        autoTable(doc, {
            startY: y,
            margin: { left: MARGIN_L, right: MARGIN_R },
            tableWidth: CONTENT_W,
            head: [columnasCat.map((c) => c.header)],
            body: filasCat.map((f) => columnasCat.map((c) => f[c.dataKey])),
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
            alternateRowStyles: { fillColor: false },
            columnStyles: {
                0: { cellWidth: 10, halign: 'center' },
                1: { cellWidth: 22 },
                2: { cellWidth: 35 },
                3: { cellWidth: 'auto' },
                4: { cellWidth: 25, halign: 'center' },
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
            didAddPage() {
                drawHeader()
            },
        })
    }

    if (typeof doc.putTotalPages === 'function') {
        doc.putTotalPages('{totalPages}')
    }

    const codigoDoc = reporte.docente?.codigo || 'doc'
    const fileName = `reporte_docente_compartidos_${codigoDoc}.pdf`

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