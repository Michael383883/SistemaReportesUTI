// composables/useGenerarExcel.js
// Genera el reporte "Materias dictadas de un docente" en formato Excel (.xlsx).
// Cuatro variantes, igual que los PDF:
//   - 'estandar'      → equivalente a useGenerarPDF
//   - 'tipo-ingreso'  → estándar + columna "Modalidad de ingreso"
//   - 'compartido'    → equivalente a useGenerarPDFCompartido (columna "Comparte")
//   - 'documento'     → solo la hoja de documentos por categoría
//
// Dependencia: exceljs
//   npm install exceljs

function norm(v) {
    if (v === null || v === undefined) return ''
    return String(v).trim()
}

// ── Mapa de códigos de plan (igual que en los generadores de PDF) ────────────
const PLAN_MAP = {
    '089801': 'CCP',
    '109401': 'ADM',
    '125091': 'COM',
    '126091': 'FIN',
    '059801': 'ECO',
}

// Si el código de MATERIA empieza con "240" se muestra "DESC" en vez del plan.
function getPlanAbbrev(plan, materiaCodigo) {
    if (norm(materiaCodigo).startsWith('240')) return 'DESC'
    const codigo = norm(plan)
    return PLAN_MAP[codigo] || codigo
}

// ═════════════════════════════════════════════════════════════════════════════
// Detección de compartidos (misma lógica que useGenerarPDF / useGenerarPDFCompartido)
// ═════════════════════════════════════════════════════════════════════════════

// Devuelve el Set de índices de filas "hijas" (se marcan como COMPARTIDO).
function calcularHijasIndices(materias) {
    const hijas = new Set()

    // PASO 1: semestre regular (1 y 2) — orden_comparte + gestión, comp '0' padre / '1' hija
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

    // PASO 2: verano/invierno (3 y 4) — por gestión; el flag COMPARTIDO marca a la hija
    const esCompartido = (m) => norm(m.compartido) === 'COMPARTIDO'

    const porGestionVI = new Map()
    materias.forEach((m, idx) => {
        if (resueltoEnPaso1[idx]) return
        if (norm(m.orden_comparte)) return
        const esVI = norm(m.gestion).includes('Verano') || norm(m.gestion).includes('Invierno')
        if (!esVI) return
        const clave = norm(m.gestion)
        if (!porGestionVI.has(clave)) porGestionVI.set(clave, [])
        porGestionVI.get(clave).push(idx)
    })

    for (const [, indices] of porGestionVI) {
        if (indices.length < 2) continue
        const padres = indices.filter((i) => !esCompartido(materias[i]))
        const hijasVI = indices.filter((i) => esCompartido(materias[i]))
        if (padres.length === 1 && hijasVI.length >= 1) {
            hijasVI.forEach((i) => hijas.add(i))
        }
    }

    return hijas
}

// Agrupa cada materia padre con sus hermanas (las hijas no tienen fila propia).
function agruparCompartidas(materias) {
    const lista = materias || []

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

    const esCompartido = (m) => norm(m.compartido) === 'COMPARTIDO'

    const porGestionVI = new Map()
    lista.forEach((m, idx) => {
        if (usadaComoHermana[idx] || hermanasDe.has(idx)) return
        if (norm(m.orden_comparte)) return
        const esVI = norm(m.gestion).includes('Verano') || norm(m.gestion).includes('Invierno')
        if (!esVI) return
        const clave = norm(m.gestion)
        if (!porGestionVI.has(clave)) porGestionVI.set(clave, [])
        porGestionVI.get(clave).push(idx)
    })

    for (const [, indices] of porGestionVI) {
        if (indices.length < 2) continue
        const padres = indices.filter((i) => !esCompartido(lista[i]))
        const hijas = indices.filter((i) => esCompartido(lista[i]))
        if (padres.length === 1 && hijas.length >= 1) {
            hermanasDe.set(padres[0], hijas)
            hijas.forEach((i) => { usadaComoHermana[i] = true })
        }
    }

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

// Una línea por hermana: "FIN - G-1302196 ADMINISTRACION FINANCIERA II - G"
function formatComparte(hermanas) {
    if (!hermanas || !hermanas.length) return ''
    return hermanas
        .map((h) => {
            const codigo = h.codigo || h.materia_codigo || ''
            const materia = h.materia || ''
            const plan = getPlanAbbrev(h.plan, codigo)
            const nivel = h.nivel || ''
            const grp = h.grp ? ` - ${h.grp}` : ''

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

// ═════════════════════════════════════════════════════════════════════════════
// Definición de columnas
// ═════════════════════════════════════════════════════════════════════════════
const COL = {
    nro: { header: 'Nº', width: 6, align: 'center' },
    gestion: { header: 'GESTIÓN', width: 18, align: 'center' },
    plan: { header: 'PLAN', width: 8, align: 'center' },
    materia: { header: 'MATERIA', width: 44, align: 'left', wrap: true },
    compartido: { header: 'COMPARTIDO', width: 15, align: 'center' },
    grp: { header: 'GRP', width: 7, align: 'center' },
    comparte: { header: 'COMPARTE', width: 52, align: 'left', wrap: true },
    resolucion: { header: 'RESOLUCIÓN', width: 20, align: 'left' },
    designacion: { header: 'DESIGNACIÓN', width: 42, align: 'left', wrap: true },
    tipo_ingreso: { header: 'MODALIDAD DE INGRESO', width: 28, align: 'left', wrap: true },
}

const ORDEN_COLUMNAS = {
    'estandar': ['nro', 'gestion', 'plan', 'materia', 'compartido', 'grp', 'resolucion', 'designacion'],
    'tipo-ingreso': ['nro', 'gestion', 'plan', 'materia', 'compartido', 'grp', 'resolucion', 'designacion', 'tipo_ingreso'],
    'compartido': ['nro', 'gestion', 'plan', 'materia', 'grp', 'comparte', 'resolucion', 'designacion'],
}

const COLUMNAS_CATEGORIA = [
    { key: 'nro', header: 'Nº', width: 6, align: 'center' },
    { key: 'gestion', header: 'GESTIÓN', width: 14, align: 'center' },
    { key: 'tipo', header: 'TIPO DE DOCUMENTO', width: 34, align: 'left', wrap: true },
    { key: 'detalle', header: 'DETALLE GENERAL', width: 60, align: 'left', wrap: true },
    { key: 'categoria', header: 'CATEGORÍA', width: 22, align: 'center' },
]

const DESCRIPCIONES = {
    'estandar':
        'Datos Históricos pertenecientes a la Facultad de Ciencias Económicas registrados en el SISS ' +
        'a partir de la gestión 2001. El reporte también detalla los grupos compartidos solo para los ' +
        'cursos Intersemestrales de Verano e Invierno.',
    'compartido':
        'Datos históricos pertenecientes a la Facultad de Ciencias Económicas registrados en el SISS ' +
        'a partir de la gestión 2001. Esta versión agrupa cada materia con los planes/grupos con los ' +
        'que comparte grupo, mostrados en la columna "Comparte".',
    'documento':
        'Documentos registrados en el SISS para el docente, clasificados por categoría.',
}

// ═════════════════════════════════════════════════════════════════════════════
// Construcción de hojas
// ═════════════════════════════════════════════════════════════════════════════
const HEADER_ROWS = 6   // filas de encabezado institucional (la fila de títulos de columnas es la 7)
const BORDE_HEADER = { style: 'thin', color: { argb: 'FF828282' } }
const BORDE_FILA = { style: 'thin', color: { argb: 'FFAAAAAA' } }

function escribirEncabezado(ws, nColumnas, { titulo, descripcion, docente }) {
    const unir = (fila) => ws.mergeCells(fila, 1, fila, nColumnas)

        ;[
            [1, 'UNIVERSIDAD MAYOR DE SAN SIMÓN', { bold: true, size: 8 }, 'left'],
            [2, 'FACULTAD DE CIENCIAS ECONÓMICAS', { bold: true, size: 8 }, 'left'],
            [3, titulo, { bold: true, size: 14 }, 'center'],
            [4, descripcion, { size: 9, color: { argb: 'FF282828' } }, 'left'],
            [5, docente, { bold: true, size: 11 }, 'left'],
        ].forEach(([fila, texto, font, align]) => {
            unir(fila)
            const cell = ws.getCell(fila, 1)
            cell.value = texto
            cell.font = { name: 'Arial', ...font }
            cell.alignment = { horizontal: align, vertical: 'middle', wrapText: fila === 4 }
        })

    ws.getRow(3).height = 24
    ws.getRow(4).height = 38
    ws.getRow(5).height = 20
}

// columnas: [{ key, header, width, align, wrap }]   filas: [{ [key]: valor }]
function construirHoja(wb, nombreHoja, columnas, filas, encabezado) {
    const ws = wb.addWorksheet(nombreHoja, {
        views: [{ state: 'frozen', ySplit: HEADER_ROWS + 1 }],
    })
    const n = columnas.length

    columnas.forEach((c, i) => { ws.getColumn(i + 1).width = c.width })

    escribirEncabezado(ws, n, encabezado)

    // ── Fila de títulos de columnas ──
    const filaHead = HEADER_ROWS + 1
    const head = ws.getRow(filaHead)
    columnas.forEach((c, i) => {
        const cell = head.getCell(i + 1)
        cell.value = c.header
        cell.font = { name: 'Arial', bold: true, size: 9 }
        cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true }
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF0F0F0' } }
        cell.border = { top: BORDE_HEADER, bottom: BORDE_HEADER, left: BORDE_HEADER, right: BORDE_HEADER }
    })
    head.height = 22

    // ── Filas de datos (sin líneas verticales, solo línea inferior) ──
    filas.forEach((f, r) => {
        const row = ws.getRow(filaHead + 1 + r)
        columnas.forEach((c, i) => {
            const cell = row.getCell(i + 1)
            cell.value = f[c.key] ?? ''
            cell.font = { name: 'Arial', size: 9 }
            cell.alignment = { horizontal: c.align, vertical: 'middle', wrapText: !!c.wrap }
            cell.border = { bottom: BORDE_FILA }
        })
    })

    // ── Filtros y configuración de impresión ──
    ws.autoFilter = {
        from: { row: filaHead, column: 1 },
        to: { row: filaHead, column: n },
    }

    ws.pageSetup = {
        paperSize: 1, // Letter
        orientation: 'portrait',
        fitToPage: true,
        fitToWidth: 1,
        fitToHeight: 0,
        printTitlesRow: `1:${filaHead}`,
        margins: { left: 0.4, right: 0.4, top: 0.5, bottom: 0.6, header: 0.3, footer: 0.3 },
    }
    ws.headerFooter.oddFooter =
        '&L&8Procesado UTi - Facultad de Ciencias Económicas&C&8Página &P de &N&R&8&D &T'

    return ws
}

// ═════════════════════════════════════════════════════════════════════════════
// Filas según la variante
// ═════════════════════════════════════════════════════════════════════════════
function filasMaterias(reporte, variante) {
    const materias = reporte.materias || []

    if (variante === 'compartido') {
        return agruparCompartidas(materias).map(({ principal: m, hermanas }, i) => ({
            nro: i + 1,
            gestion: m.gestion || '',
            plan: getPlanAbbrev(m.plan, m.codigo || m.materia_codigo),
            materia: m.materia || '',
            grp: m.grp || '',
            comparte: formatComparte(hermanas),
            resolucion: m.resolucion || '',
            designacion: m.designacion || '',
        }))
    }

    const hijas = calcularHijasIndices(materias)
    return materias.map((m, idx) => ({
        nro: m.nro,
        gestion: m.gestion || '',
        plan: getPlanAbbrev(m.plan, m.materia_codigo),
        materia: m.materia || '',
        compartido: hijas.has(idx) ? 'COMPARTIDO' : '',
        grp: m.grp || '',
        resolucion: m.resolucion || '',
        designacion: m.designacion || '',
        tipo_ingreso: m.tipo_ingreso || '',
    }))
}

function filasDocumentos(documentosCategoria) {
    return documentosCategoria.map((d) => ({
        nro: d.nro,
        gestion: `${d.GESTION ?? ''}${d.PERIODO ? '/' + d.PERIODO : ''}`,
        tipo: d.TIPO_DOCUMENTO || '',
        detalle: d.DETALLE_GENERAL || '',
        categoria: d.CATEGORIA || '',
    }))
}

// ═════════════════════════════════════════════════════════════════════════════
// API pública
// ═════════════════════════════════════════════════════════════════════════════

// Arma el libro (sin descargarlo). Exportado para poder probarlo.
export async function construirLibro(reporte, opts = {}) {
    const {
        variante = 'estandar',
        documentosCategoria = [],
        sinResolucionDesignacion = false,
    } = opts

    const ExcelJS = (await import('exceljs')).default

    const wb = new ExcelJS.Workbook()
    wb.creator = 'Procesado UTi - Facultad de Ciencias Económicas'
    wb.created = new Date()

    const docente = `DOCENTE : (${reporte.docente?.codigo || ''}) - ${reporte.docente?.nombre || ''}`

    // ── Hoja de materias (no aplica al modo "documento") ──
    if (variante !== 'documento') {
        const claves = ORDEN_COLUMNAS[variante] || ORDEN_COLUMNAS.estandar
        const columnas = claves
            .filter((k) => !(sinResolucionDesignacion && (k === 'resolucion' || k === 'designacion')))
            .map((k) => ({ key: k, ...COL[k] }))

        construirHoja(
            wb,
            'Materias dictadas',
            columnas,
            filasMaterias(reporte, variante),
            {
                titulo: 'MATERIAS DICTADAS DE UN DOCENTE',
                descripcion: variante === 'compartido' ? DESCRIPCIONES.compartido : DESCRIPCIONES.estandar,
                docente,
            },
        )
    }

    // ── Hoja de documentos por categoría ──
    if (documentosCategoria.length) {
        construirHoja(
            wb,
            'Documentos por categoría',
            COLUMNAS_CATEGORIA,
            filasDocumentos(documentosCategoria),
            {
                titulo: 'REPORTE DE DOCUMENTOS DEL DOCENTE',
                descripcion: DESCRIPCIONES.documento,
                docente,
            },
        )
    }

    return wb
}

function nombreArchivo(variante, codigo) {
    switch (variante) {
        case 'tipo-ingreso': return `reporte_docente_modalidad_${codigo}.xlsx`
        case 'compartido': return `reporte_docente_compartidos_${codigo}.xlsx`
        case 'documento': return `reporte_documentos_docente_${codigo}.xlsx`
        default: return `reporte_docente_${codigo}.xlsx`
    }
}

function descargar(buffer, nombre) {
    const blob = new Blob([buffer], {
        type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = nombre
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    setTimeout(() => URL.revokeObjectURL(url), 10_000)
}

// Genera y descarga el Excel.
export async function generarExcel(reporte, opts = {}) {
    try {
        const wb = await construirLibro(reporte, opts)
        const buffer = await wb.xlsx.writeBuffer()
        descargar(buffer, nombreArchivo(opts.variante || 'estandar', reporte.docente?.codigo || 'doc'))
    } catch (e) {
        console.error('[useGenerarExcel] No se pudo generar el Excel', e)
        alert('No se pudo generar el archivo Excel.')
    }
}