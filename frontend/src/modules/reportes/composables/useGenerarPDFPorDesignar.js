// composables/useGenerarPDFPorDesignar.js
// Reporte de materias POR DESIGNAR (docente 100) – formato horizontal, completo.
// Dependencias: jspdf + jspdf-autotable

import { jsPDF } from 'jspdf'
import autoTable from 'jspdf-autotable'

// ─── Utilidades ──────────────────────────────────────────────────────────
function norm(v) {
    if (v === null || v === undefined) return ''
    return String(v).trim()
}

const PLAN_MAP = {
    '089801': { abrev: 'CCP', nombre: 'Contaduría Pública' },
    '109401': { abrev: 'ADM', nombre: 'Administración de Empresas' },
    '125091': { abrev: 'COM', nombre: 'Ingeniería Comercial' },
    '126091': { abrev: 'FIN', nombre: 'Ingeniería Financiera' },
    '059801': { abrev: 'ECO', nombre: 'Economía' },
}

function infoPlan(m) {
    const codMateria = norm(m.materia_codigo) || norm(m.materia).split(/\s+/)[0]
    if (codMateria.startsWith('240')) return { abrev: 'DESC', nombre: 'Materias de descentralización' }

    const cod = norm(m.plan ?? m.PLAN)
    if (PLAN_MAP[cod]) return PLAN_MAP[cod]

    const ab = norm(m.plan_abrev) === 'CON' ? 'CCP' : norm(m.plan_abrev)
    const porAbrev = Object.values(PLAN_MAP).find((p) => p.abrev === ab)
    if (porAbrev) return porAbrev

    return { abrev: ab || cod || '—', nombre: cod ? `Plan ${cod}` : 'Sin plan' }
}

function partirMateria(m) {
    const texto = norm(m.materia)
    const partes = texto.split(/\s+/)
    const codigo = norm(m.materia_codigo) || partes[0] || ''
    const nombre = texto.startsWith(codigo) ? texto.slice(codigo.length).trim() : texto
    return { codigo, nombre: nombre || texto }
}

const ORDEN_PERIODO = { '1': 1, '4': 2, '2': 3, '3': 4 }

function ordenGestion(gestion) {
    const [a, ...r] = norm(gestion).split('/')
    const anio = parseInt(a, 10) || 0
    const per = norm(r.join('/')).charAt(0)
    return anio * 10 + (ORDEN_PERIODO[per] ?? 0)
}

function esCompartida(m) {
    return norm(m.compartido) === 'COMPARTIDO' || norm(m.comp) === '1'
}

function pct(parte, total) {
    return total ? `${((parte / total) * 100).toFixed(1)}%` : '0%'
}

// ─── Generador ───────────────────────────────────────────────────────────
/**
 * @param {{docente:{codigo,nombre}, materias:Array}} datos
 * @param {{
 *   action?: 'open'|'save'|'print',
 *   filtroAnio?: string,
 *   filtroGestion?: string,
 *   nombreArchivo?: string,
 *   incluirResumen?: boolean   // false = directo a la tabla de materias
 * }} opts
 */
export function generarPDFPorDesignar(datos, opts = {}) {
    const {
        action = 'open',
        filtroAnio = '',
        filtroGestion = '',
        nombreArchivo = '',
        // false = va directo a la tabla de materias (sin indicadores ni resúmenes)
        incluirResumen = true,
    } = opts
    const docente = datos.docente || {}
    const materias = [...(datos.materias || [])]

    // Orden: gestión más reciente primero, luego plan y materia
    materias.sort((a, b) => {
        const d = ordenGestion(b.gestion) - ordenGestion(a.gestion)
        if (d !== 0) return d
        const p = infoPlan(a).abrev.localeCompare(infoPlan(b).abrev)
        if (p !== 0) return p
        return norm(a.materia).localeCompare(norm(b.materia))
    })

    const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'letter' })
    const PAGE_W = doc.internal.pageSize.getWidth()
    const PAGE_H = doc.internal.pageSize.getHeight()
    const ML = 12
    const MR = 12
    const CW = PAGE_W - ML - MR

    const NEGRO = [0, 0, 0]
    const GRIS_BG = [240, 240, 240]
    const GRIS_MED = [225, 225, 225]
    const LINEA = [170, 170, 170]
    const TEXTO = [40, 40, 40]

    const fechaGen = (() => {
        const n = new Date()
        const p = (x) => String(x).padStart(2, '0')
        const hora = n.toLocaleString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true })
        return `${p(n.getDate())}/${p(n.getMonth() + 1)}/${n.getFullYear()} ${hora}`
    })()

    // ── Estadísticas ─────────────────────────────────────────────────────
    const total = materias.length
    const porCarrera = new Map()
    const porGestion = new Map()
    let sinTipo = 0
    let sinResolucion = 0
    let compartidas = 0

    materias.forEach((m) => {
        const pl = infoPlan(m)
        const c = porCarrera.get(pl.abrev) || { abrev: pl.abrev, nombre: pl.nombre, n: 0 }
        c.n++
        porCarrera.set(pl.abrev, c)

        const g = norm(m.gestion) || 'Sin gestión'
        porGestion.set(g, (porGestion.get(g) || 0) + 1)

        if (!norm(m.tipo_ingreso ?? m.TIPO_INGRESO)) sinTipo++
        const res = norm(m.resolucion ?? m.RESOLUCION)
        if (!res || res.toLowerCase() === 'sin resolución') sinResolucion++
        if (esCompartida(m)) compartidas++
    })

    const textoFiltro = (() => {
        if (!filtroAnio && !filtroGestion) return 'Todas las gestiones'
        return `Año: ${filtroAnio || 'todos'}  ·  Gestión: ${filtroGestion || 'todas'}`
    })()

    doc.setProperties({ title: 'Reporte de materias por designar' })

    // ── Encabezado grande (página 1) ─────────────────────────────────────
    function headerGrande() {
        doc.setFont('helvetica', 'bold')
        doc.setFontSize(6)
        doc.setTextColor(...NEGRO)
        doc.text('UNIVERSIDAD MAYOR DE SAN SIMÓN', ML, 7)
        doc.text('FACULTAD DE CIENCIAS ECONÓMICAS', ML, 9.5)

        doc.setFontSize(14)
        doc.text('REPORTE DE MATERIAS POR DESIGNAR', PAGE_W / 2, 14, { align: 'center' })

        doc.setFont('helvetica', 'normal')
        doc.setFontSize(7)
        doc.setTextColor(...TEXTO)
        doc.text(
            'Materias y grupos con docente aún no asignado, registrados en el SISS. Documento de apoyo para la programación y designación docente.',
            PAGE_W / 2, 19, { align: 'center' },
        )

        doc.setDrawColor(...NEGRO)
        doc.setLineWidth(0.5)
        doc.line(ML, 22, PAGE_W - MR, 22)

        doc.setFont('helvetica', 'bold')
        doc.setFontSize(8.5)
        doc.setTextColor(...NEGRO)
        doc.text(`DOCENTE : (${docente.codigo || ''}) - ${docente.nombre || 'POR DESIGNAR'}`, ML, 28)

        doc.setFont('helvetica', 'normal')
        doc.setFontSize(7.5)
        doc.setTextColor(...TEXTO)
        doc.text(`Filtro aplicado: ${textoFiltro}`, ML, 32.5)
        doc.text(`Generado: ${fechaGen}`, PAGE_W - MR, 28, { align: 'right' })
        doc.text(`Total de registros: ${total}`, PAGE_W - MR, 32.5, { align: 'right' })

        return 36
    }

    // ── Encabezado compacto (páginas siguientes) ─────────────────────────
    function headerCompacto() {
        doc.setFont('helvetica', 'bold')
        doc.setFontSize(6)
        doc.setTextColor(...NEGRO)
        doc.text('UNIVERSIDAD MAYOR DE SAN SIMÓN · FACULTAD DE CIENCIAS ECONÓMICAS', ML, 7)
        doc.setFontSize(9)
        doc.text('REPORTE DE MATERIAS POR DESIGNAR', PAGE_W - MR, 7, { align: 'right' })
        doc.setFont('helvetica', 'normal')
        doc.setFontSize(6.5)
        doc.setTextColor(...TEXTO)
        doc.text(`(${docente.codigo || ''}) ${docente.nombre || 'POR DESIGNAR'}  ·  ${textoFiltro}`, ML, 11)
        doc.setDrawColor(...LINEA)
        doc.setLineWidth(0.3)
        doc.line(ML, 13, PAGE_W - MR, 13)
    }

    function tituloSeccion(texto, y, x = ML) {
        doc.setFont('helvetica', 'bold')
        doc.setFontSize(8)
        doc.setTextColor(...NEGRO)
        doc.text(texto, x, y)
        return y + 2
    }

    const estiloBase = {
        font: 'helvetica',
        fontSize: 6.8,
        cellPadding: { top: 1.3, bottom: 1.3, left: 1.8, right: 1.8 },
        textColor: NEGRO,
        lineColor: LINEA,
        lineWidth: 0.1,
        valign: 'middle',
        overflow: 'linebreak',
    }
    const estiloHead = {
        fillColor: GRIS_BG,
        textColor: NEGRO,
        fontStyle: 'bold',
        halign: 'center',
        lineColor: [130, 130, 130],
        lineWidth: 0.3,
    }
    const estiloFoot = {
        fillColor: GRIS_MED,
        textColor: NEGRO,
        fontStyle: 'bold',
        lineColor: [130, 130, 130],
        lineWidth: 0.3,
    }

    // Celdas de totales centradas (para que queden bajo su columna)
    const c = (v) => ({ content: String(v), styles: { halign: 'center' } })

    // ── Indicadores: una sola fila compacta, alineada con las tablas ─────
    function dibujarIndicadores(y) {
        const items = [
            { valor: total, label: 'MATERIAS POR DESIGNAR' },
            { valor: porCarrera.size, label: 'CARRERAS / PLANES' },
            { valor: porGestion.size, label: 'GESTIONES' },
            { valor: sinTipo, label: 'SIN TIPO DE INGRESO' },
            { valor: sinResolucion, label: 'SIN RESOLUCIÓN' },
        ]
        const anchoCol = CW / items.length

        autoTable(doc, {
            startY: y,
            margin: { left: ML, right: MR },
            tableWidth: CW,
            head: [items.map((it) => it.label)],
            body: [items.map((it) => String(it.valor))],
            styles: { ...estiloBase, halign: 'center' },
            headStyles: { ...estiloHead, fontSize: 6.3 },
            bodyStyles: {
                fontSize: 11,
                fontStyle: 'bold',
                cellPadding: { top: 2, bottom: 2, left: 1.8, right: 1.8 },
            },
            columnStyles: Object.fromEntries(items.map((_, i) => [i, { cellWidth: anchoCol }])),
        })

        return doc.lastAutoTable.finalY + 7
    }

    // ════════════════════════════════════════════════════════════════════
    // PÁGINA 1: indicadores + resúmenes (solo si incluirResumen)
    // ════════════════════════════════════════════════════════════════════
    let y = headerGrande()

    if (incluirResumen) {
        y = dibujarIndicadores(y + 2)

        // Resumen por carrera (mitad izquierda) y por gestión (mitad derecha)
        const mitad = (CW - 8) / 2
        const xDer = ML + mitad + 8
        const yResumen = y

        tituloSeccion('RESUMEN POR CARRERA', yResumen)
        const filasCarrera = [...porCarrera.values()]
            .sort((a, b) => b.n - a.n)
            .map((x) => [x.abrev, x.nombre, x.n, pct(x.n, total)])

        autoTable(doc, {
            startY: yResumen + 2,
            margin: { left: ML, right: PAGE_W - ML - mitad },
            tableWidth: mitad,
            head: [['PLAN', 'CARRERA', 'MATERIAS', '%']],
            body: filasCarrera,
            foot: [['', 'TOTAL', c(total), c('100%')]],
            styles: estiloBase,
            headStyles: estiloHead,
            footStyles: estiloFoot,
            columnStyles: {
                0: { cellWidth: 16, halign: 'center', fontStyle: 'bold' },
                1: { cellWidth: 'auto' },
                2: { cellWidth: 20, halign: 'center' },
                3: { cellWidth: 16, halign: 'center' },
            },
        })
        const yFinCarrera = doc.lastAutoTable.finalY

        tituloSeccion('RESUMEN POR GESTIÓN', yResumen, xDer)
        const filasGestion = [...porGestion.entries()]
            .sort((a, b) => ordenGestion(b[0]) - ordenGestion(a[0]))
            .map(([g, n]) => [g, n, pct(n, total)])

        autoTable(doc, {
            startY: yResumen + 2,
            margin: { left: xDer, right: MR },
            tableWidth: mitad,
            head: [['GESTIÓN', 'MATERIAS', '%']],
            body: filasGestion,
            foot: [['TOTAL', c(total), c('100%')]],
            styles: estiloBase,
            headStyles: estiloHead,
            footStyles: estiloFoot,
            columnStyles: {
                0: { cellWidth: 'auto' },
                1: { cellWidth: 22, halign: 'center' },
                2: { cellWidth: 18, halign: 'center' },
            },
        })
        const yFinGestion = doc.lastAutoTable.finalY

        y = Math.max(yFinCarrera, yFinGestion) + 5

        // Nota de compartidas (si aplica)
        if (compartidas > 0) {
            doc.setFont('helvetica', 'italic')
            doc.setFontSize(6.5)
            doc.setTextColor(...TEXTO)
            doc.text(
                `Nota: ${compartidas} materia${compartidas !== 1 ? 's' : ''} figura${compartidas !== 1 ? 'n' : ''} como COMPARTIDA${compartidas !== 1 ? 'S' : ''} con otro grupo.`,
                ML, y,
            )
            y += 5
        }
    } else {
        y += 2
    }

    // ════════════════════════════════════════════════════════════════════
    // DETALLE agrupado por gestión
    // ════════════════════════════════════════════════════════════════════
    if (y > PAGE_H - 50) {
        doc.addPage()
        headerCompacto()
        y = 18
    }

    y = tituloSeccion('DETALLE DE MATERIAS POR DESIGNAR', y)

    const NCOLS = 9
    const body = []
    let nro = 1

    const grupos = new Map()
    materias.forEach((m) => {
        const g = norm(m.gestion) || 'Sin gestión'
        if (!grupos.has(g)) grupos.set(g, [])
        grupos.get(g).push(m)
    })

    for (const [gestion, lista] of grupos) {
        body.push([{
            content: `GESTIÓN ${gestion}   ·   ${lista.length} materia${lista.length !== 1 ? 's' : ''}`,
            colSpan: NCOLS,
            styles: { fillColor: GRIS_MED, fontStyle: 'bold', halign: 'left', fontSize: 7.2 },
        }])

        lista.forEach((m) => {
            const pl = infoPlan(m)
            const { codigo, nombre } = partirMateria(m)
            const tipo = norm(m.tipo_ingreso ?? m.TIPO_INGRESO)
            const res = norm(m.resolucion ?? m.RESOLUCION)
            const resValida = res && res.toLowerCase() !== 'sin resolución'

            body.push([
                nro++,
                pl.abrev,
                codigo,
                nombre + (esCompartida(m) ? '  [COMPARTIDA]' : ''),
                norm(m.grp),
                resValida ? res : 'Sin resolución',
                norm(m.designacion) || '—',
                tipo || 'Sin tipo de ingreso',
                '', // Docente a designar: se completa a mano
            ])
        })
    }

    autoTable(doc, {
        startY: y + 1,
        margin: { top: 18, left: ML, right: MR, bottom: 14 },
        tableWidth: CW,
        head: [['Nº', 'PLAN', 'CÓD.', 'MATERIA', 'GRP', 'RESOLUCIÓN', 'DESIGNACIÓN', 'TIPO DE INGRESO', 'DOCENTE A DESIGNAR']],
        body,
        styles: { ...estiloBase, fillColor: false },
        headStyles: { ...estiloHead, fontSize: 6.8 },
        showHead: 'everyPage',
        columnStyles: {
            0: { cellWidth: 9, halign: 'center' },
            1: { cellWidth: 13, halign: 'center', fontStyle: 'bold' },
            2: { cellWidth: 18, halign: 'center', font: 'courier' },
            3: { cellWidth: 'auto' },
            4: { cellWidth: 9, halign: 'center', fontStyle: 'bold' },
            5: { cellWidth: 28 },
            6: { cellWidth: 34 },
            7: { cellWidth: 32 },
            8: { cellWidth: 42 },
        },
        didParseCell(data) {
            if (data.section !== 'body' || data.row.raw?.length === 1) return
            // Resalta lo que falta (sin resolución / sin tipo) en cursiva
            const txt = String(data.cell.raw ?? '')
            if (txt === 'Sin resolución' || txt === 'Sin tipo de ingreso') {
                data.cell.styles.fontStyle = 'italic'
                data.cell.styles.textColor = [110, 110, 110]
            }
        },
        didDrawCell(data) {
            if (data.section !== 'body') return
            // La celda de "Docente a designar" lleva una línea para escribir
            if (data.column.index === 8 && data.row.raw?.length !== 1) {
                const { x, y: cy, width, height } = data.cell
                doc.setDrawColor(...LINEA)
                doc.setLineWidth(0.2)
                doc.line(x + 2, cy + height - 2, x + width - 2, cy + height - 2)
            }
        },
    })

    // ════════════════════════════════════════════════════════════════════
    // Cabeceras compactas (pág. ≥2) y pie de página en todas
    // ════════════════════════════════════════════════════════════════════
    const totalPaginas = doc.getNumberOfPages()
    for (let p = 1; p <= totalPaginas; p++) {
        doc.setPage(p)
        if (p > 1) headerCompacto()

        const footerY = PAGE_H - 5
        doc.setDrawColor(...LINEA)
        doc.setLineWidth(0.2)
        doc.line(ML, footerY - 3.5, PAGE_W - MR, footerY - 3.5)

        doc.setFont('helvetica', 'normal')
        doc.setFontSize(6)
        doc.setTextColor(90, 90, 90)
        doc.text('Procesado UTi - Facultad de Ciencias Económicas', ML, footerY)
        doc.text(`Página ${p} de ${totalPaginas}`, PAGE_W / 2, footerY, { align: 'center' })
        doc.text(fechaGen, PAGE_W - MR, footerY, { align: 'right' })
    }

    // ── Salida ───────────────────────────────────────────────────────────
    const fileName = nombreArchivo ||
        `materias_por_designar_${norm(docente.codigo) || '100'}${incluirResumen ? '' : '_detalle'}.pdf`

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