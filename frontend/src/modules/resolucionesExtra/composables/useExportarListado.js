// composables/useExportarListado.js
// Exporta a PDF (estilo UMSS) o Excel el listado de documentos de docentes.
//
// IMPORTANTE: recibe TODOS los documentos que cumplen los filtros (uno por fila,
// SIN agrupar). El agrupado por docente se hace aquí:
//   · cada docente lleva un solo N° (aunque tenga 3 documentos)
//   · su nombre aparece una sola vez, con sus filas debajo
//   · sin la columna "Nivel"
//
// Opciones extra (se marcan en el menú Exportar):
//   · incluirObs      → agrega la OBSERVACIÓN de cada documento
//   · incluirMaterias → agrega las MATERIAS (con su nota) de cada documento
//
// Dependencias: jspdf, jspdf-autotable, exceljs
//   npm install jspdf jspdf-autotable exceljs

import { jsPDF } from 'jspdf'
import autoTable from 'jspdf-autotable'
import ExcelJS from 'exceljs'

const v = (x) => (x === null || x === undefined || x === '' ? '' : String(x).trim())

const cmp = (a, b) => String(a ?? '').localeCompare(String(b ?? ''), 'es', { numeric: true })

// Devuelve las materias de un documento como lista de textos.
// El backend (index) las manda en c.MATERIAS con las columnas de CLASIFICACION_MATERIA:
//   NOMBRE_MATERIA, NOTA, GRUPO, DETALLE
// Ej: "INFORMATICA (Nota: 62.90)"  ·  "CALCULO (Sin calificación)"
function listaMaterias(c) {
    const lista = Array.isArray(c.MATERIAS) ? c.MATERIAS : []
    return lista
        .map((m) => {
            const nombre = v(m.NOMBRE_MATERIA)
            if (!nombre) return ''
            const detalle = v(m.DETALLE)
            const grupo = v(m.GRUPO)
            const nota = v(m.NOTA)
            return (
                nombre +
                (grupo ? ` [Gr. ${grupo}]` : '') +
                (detalle ? ` - ${detalle}` : '') +
                ` (${nota ? `Nota: ${nota}` : 'Sin calificación'})`
            )
        })
        .filter(Boolean)
}

// Agrupa los documentos por docente.
// Devuelve [{ nro, docente, docs: [{ documento, detalle, observacion, materias, categoria, gestion, periodo }] }]
// Docentes ordenados por nombre; dentro de cada uno, gestión más reciente primero.
export function agruparPorDocente(listado) {
    const mapa = new Map()

    for (const c of listado || []) {
        const clave = c.COD_DOCENTE ?? c.NOMBRE_DOCENTE ?? 'SIN-DOCENTE'
        if (!mapa.has(clave)) {
            mapa.set(clave, { docente: v(c.NOMBRE_DOCENTE) || 'Sin docente', docs: [] })
        }
        mapa.get(clave).docs.push({
            documento: v(c.TIPO_DOCUMENTO),
            detalle: v(c.DETALLE_GENERAL),
            observacion: v(c.OBSERVACION),
            materias: listaMaterias(c), // array de textos
            categoria: v(c.CATEGORIA),
            gestion: v(c.GESTION),
            periodo: v(c.PERIODO),
        })
    }

    const grupos = [...mapa.values()].sort((a, b) => cmp(a.docente, b.docente))
    grupos.forEach((g, i) => {
        g.nro = i + 1
        g.docs.sort((a, b) => cmp(b.gestion, a.gestion) || cmp(a.periodo, b.periodo))
    })
    return grupos
}

function totales(grupos) {
    return {
        docentes: grupos.length,
        documentos: grupos.reduce((s, g) => s + g.docs.length, 0),
    }
}

function fechaHoraActual() {
    const now = new Date()
    const p = (n) => String(n).padStart(2, '0')
    const hora = now.toLocaleString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true })
    return `${p(now.getDate())}/${p(now.getMonth() + 1)}/${now.getFullYear()} ${hora}`
}

function nombreBase() {
    const d = new Date()
    const p = (n) => String(n).padStart(2, '0')
    return `listado_documentos_docentes_${d.getFullYear()}${p(d.getMonth() + 1)}${p(d.getDate())}`
}

// ───────────────────────── PDF ─────────────────────────
export function exportarPDF(
    listado,
    { action = 'open', filtrosTexto = '', incluirObs = false, incluirMaterias = false } = {}
) {
    const grupos = agruparPorDocente(listado)
    const tot = totales(grupos)

    const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'letter' })
    const PAGE_W = doc.internal.pageSize.getWidth()
    const PAGE_H = doc.internal.pageSize.getHeight()
    const ML = 12
    const MR = 12
    const CONTENT_W = PAGE_W - ML - MR

    const BLACK = [0, 0, 0]
    const GRAY_BG = [240, 240, 240]
    const GROUP_BG = [248, 248, 248]
    const LINE = [170, 170, 170]

    // Anchos fijos de columna (el de DOCUMENTO se calcula con lo que sobra)
    const W_NRO = 10
    const W_DOCENTE = 52
    const W_CAT = 30
    const W_GESTION = 16
    const W_PERIODO = 16
    const W_DOCUMENTO = CONTENT_W - W_NRO - W_DOCENTE - W_CAT - W_GESTION - W_PERIODO
    const X_DOCUMENTO = ML + W_NRO + W_DOCENTE // donde empieza la columna DOCUMENTO

    const titulo = 'LISTADO DE DOCUMENTOS DE DOCENTES'
    const descripcion =
        'Documentos de docentes registrados en el SISS, correspondientes a la Facultad de Ciencias Económicas.'
    const lineaFiltros = filtrosTexto ? `FILTROS : ${filtrosTexto}` : 'FILTROS : Ninguno (todos los registros)'

    function drawHeader() {
        doc.setFont('helvetica', 'bold')
        doc.setFontSize(5.5)
        doc.setTextColor(...BLACK)
        doc.text('UNIVERSIDAD MAYOR DE SAN SIMÓN', ML, 6)
        doc.text('FACULTAD DE CIENCIAS ECONÓMICAS', ML, 8)

        doc.setFontSize(11)
        doc.text(titulo, PAGE_W / 2, 12, { align: 'center' })

        doc.setDrawColor(...LINE)
        doc.setLineWidth(0.3)
        doc.line(ML, 17, PAGE_W - MR, 17)

        doc.setFont('helvetica', 'normal')
        doc.setFontSize(6.5)
        doc.setTextColor(40, 40, 40)
        const descLines = doc.splitTextToSize(descripcion, CONTENT_W)
        doc.text(descLines, ML, 21)

        const y = 21 + descLines.length * 3.0 + 3
        doc.setFont('helvetica', 'bold')
        doc.setFontSize(7.5)
        doc.setTextColor(...BLACK)
        const filtLines = doc.splitTextToSize(lineaFiltros, CONTENT_W)
        doc.text(filtLines, ML, y)

        return y + filtLines.length * 3.2 + 1
    }

    doc.setProperties({ title: 'Listado de documentos de docentes' })
    const startY = drawHeader()

    // ── Cuerpo: N° y DOCENTE combinados (rowSpan) por docente ──
    const body = []
    const ultimaFilaDeGrupo = new Set() // índices de fila (body) donde termina un docente

    grupos.forEach((g, gi) => {
        const fondo = gi % 2 ? GROUP_BG : [255, 255, 255]
        const span = g.docs.length

        g.docs.forEach((d, i) => {
            const fila = []

            if (i === 0) {
                fila.push({
                    content: String(g.nro),
                    rowSpan: span,
                    styles: { halign: 'center', valign: 'middle', fontStyle: 'bold', fillColor: fondo },
                })
                fila.push({
                    content: span > 1 ? `${g.docente}\n(${span} documentos)` : g.docente,
                    rowSpan: span,
                    styles: { valign: 'middle', fontStyle: 'bold', fillColor: fondo },
                })
            }

            // Celda DOCUMENTO: documento + detalle + (opcional) observación + (opcional) materias
            const textoDocumento = [
                d.documento,
                d.detalle,
                incluirObs && d.observacion ? `Obs: ${d.observacion}` : '',
                incluirMaterias && d.materias.length
                    ? `Materias:\n${d.materias.map((m) => `  • ${m}`).join('\n')}`
                    : '',
            ]
                .filter(Boolean)
                .join('\n')

            fila.push(
                { content: textoDocumento, styles: { fillColor: fondo } },
                { content: d.categoria, styles: { halign: 'center', fillColor: fondo } },
                { content: d.gestion, styles: { halign: 'center', fillColor: fondo } },
                { content: d.periodo, styles: { halign: 'center', fillColor: fondo } },
            )

            body.push(fila)
            if (i === span - 1) ultimaFilaDeGrupo.add(body.length - 1)
        })
    })

    autoTable(doc, {
        startY,
        // margin.top se usa en las páginas siguientes: deja espacio para el encabezado
        margin: { left: ML, right: MR, top: startY, bottom: 12 },
        tableWidth: CONTENT_W,
        head: [['Nº', 'DOCENTE', 'DOCUMENTO', 'CATEGORÍA', 'GESTIÓN', 'PERIODO']],
        body,
        rowPageBreak: 'avoid', // no parte a un docente a la mitad entre dos páginas
        styles: {
            font: 'helvetica',
            fontSize: 6.2,
            cellPadding: { top: 1.2, bottom: 1.2, left: 1.5, right: 1.5 },
            textColor: BLACK,
            lineColor: LINE,
            lineWidth: 0,
            overflow: 'linebreak',
            valign: 'middle',
            fillColor: false,
        },
        headStyles: {
            fillColor: GRAY_BG,
            textColor: BLACK,
            fontStyle: 'bold',
            fontSize: 6.2,
            halign: 'center',
            valign: 'middle',
            lineColor: [130, 130, 130],
            lineWidth: 0.3,
        },
        alternateRowStyles: { fillColor: false },
        columnStyles: {
            0: { cellWidth: W_NRO, halign: 'center' },
            1: { cellWidth: W_DOCENTE },
            2: { cellWidth: W_DOCUMENTO },
            3: { cellWidth: W_CAT, halign: 'center' },
            4: { cellWidth: W_GESTION, halign: 'center' },
            5: { cellWidth: W_PERIODO, halign: 'center' },
        },
        // Línea inferior de cada fila:
        //   · entre documentos del MISMO docente → solo desde la columna DOCUMENTO
        //     (para no cortar las celdas combinadas de N° y DOCENTE)
        //   · al terminar un docente → de lado a lado
        didDrawCell(data) {
            if (data.section !== 'body') return
            if (data.column.index !== data.table.columns.length - 1) return
            const { y, height } = data.cell
            const finDeGrupo = ultimaFilaDeGrupo.has(data.row.index)
            doc.setDrawColor(...LINE)
            doc.setLineWidth(finDeGrupo ? 0.3 : 0.15)
            doc.line(finDeGrupo ? ML : X_DOCUMENTO, y + height, ML + CONTENT_W, y + height)
        },
        didAddPage() {
            drawHeader()
        },
        didDrawPage() {
            const pageNum = doc.internal.getCurrentPageInfo().pageNumber
            const footerY = PAGE_H - 5
            doc.setDrawColor(...LINE)
            doc.setLineWidth(0.2)
            doc.line(ML, footerY - 3.5, PAGE_W - MR, footerY - 3.5)
            doc.setFont('helvetica', 'normal')
            doc.setFontSize(6)
            doc.setTextColor(90, 90, 90)
            doc.text('Procesado UTi - Facultad de Ciencias Económicas', ML, footerY)
            doc.text(`Página ${pageNum} de {totalPages}`, PAGE_W / 2, footerY, { align: 'center' })
            doc.text(fechaHoraActual(), PAGE_W - MR, footerY, { align: 'right' })
        },
    })

    // Totales al final
    const yFin = (doc.lastAutoTable?.finalY ?? startY) + 5
    if (yFin < PAGE_H - 15) {
        doc.setFont('helvetica', 'bold')
        doc.setFontSize(7)
        doc.setTextColor(...BLACK)
        doc.text(`Total de docentes: ${tot.docentes}   ·   Total de documentos: ${tot.documentos}`, ML, yFin)
    }

    if (typeof doc.putTotalPages === 'function') doc.putTotalPages('{totalPages}')

    const fileName = `${nombreBase()}.pdf`

    if (action === 'save') {
        doc.save(fileName)
    } else if (action === 'print') {
        const url = URL.createObjectURL(doc.output('blob'))
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
        const url = URL.createObjectURL(doc.output('blob'))
        window.open(url, '_blank')
        setTimeout(() => URL.revokeObjectURL(url), 60_000)
    }
}

// ───────────────────────── EXCEL ─────────────────────────
// Mismo formato que el PDF: título, filtros, N° y DOCENTE combinados por docente,
// bordes, encabezado gris y colores alternados por docente.
// (Usa ExcelJS porque SheetJS gratuito no soporta estilos.)

// Convierte a número si el texto es numérico (para que Excel ordene y sume bien)
const num = (x) => {
    const s = v(x)
    return s !== '' && /^-?\d+([.,]\d+)?$/.test(s) ? Number(s.replace(',', '.')) : s
}

const BORDE_FINO = { style: 'thin', color: { argb: 'FFAAAAAA' } }
const BORDE_GRUESO = { style: 'medium', color: { argb: 'FF777777' } }
const BORDE_HAIR = { style: 'hair', color: { argb: 'FFAAAAAA' } }
const FONT = 'Arial'

// Estima la altura de una fila según el texto con saltos de línea
function alturaFila(textos, anchos) {
    let maxLineas = 1
    textos.forEach((t, i) => {
        const ancho = Math.max(anchos[i] * 1.05, 1)
        const lineas = String(t ?? '')
            .split('\n')
            .reduce((s, l) => s + Math.max(1, Math.ceil(l.length / ancho)), 0)
        if (lineas > maxLineas) maxLineas = lineas
    })
    return Math.max(18, maxLineas * 13 + 4)
}

async function descargarLibro(wb, nombre) {
    const buffer = await wb.xlsx.writeBuffer()
    const blob = new Blob([buffer], {
        type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = nombre
    document.body.appendChild(a)
    a.click()
    a.remove()
    setTimeout(() => URL.revokeObjectURL(url), 5000)
}

export async function exportarExcel(
    listado,
    { filtrosTexto = '', incluirObs = false, incluirMaterias = false } = {}
) {
    const grupos = agruparPorDocente(listado)
    const tot = totales(grupos)

    const wb = new ExcelJS.Workbook()
    wb.creator = 'Procesado UTi - Facultad de Ciencias Económicas'
    wb.created = new Date()

    // ── Columnas (dinámicas según lo que se marcó) ──
    const cols = [
        { t: 'Nº', w: 6, c: true },
        { t: 'DOCENTE', w: 36 },
        { t: 'DOCUMENTO', w: 30 },
        { t: 'DETALLE', w: 36 },
    ]
    if (incluirObs) cols.push({ t: 'OBSERVACIÓN', w: 32 })
    if (incluirMaterias) cols.push({ t: 'MATERIAS', w: 52 })
    cols.push(
        { t: 'CATEGORÍA', w: 22, c: true },
        { t: 'GESTIÓN', w: 10, c: true },
        { t: 'PERIODO', w: 10, c: true }
    )
    const NC = cols.length
    const anchos = cols.map((c) => c.w)

    const ws = wb.addWorksheet('Documentos', {
        pageSetup: {
            paperSize: 1, // Carta
            orientation: 'landscape',
            fitToPage: true,
            fitToWidth: 1,
            fitToHeight: 0,
            margins: { left: 0.4, right: 0.4, top: 0.5, bottom: 0.5, header: 0.3, footer: 0.3 },
        },
    })
    ws.columns = cols.map((c) => ({ width: c.w }))

    // ── Encabezado del reporte ──
    const filaTexto = (n, texto, font, alto, align = 'left') => {
        ws.mergeCells(n, 1, n, NC)
        const cell = ws.getCell(n, 1)
        cell.value = texto
        cell.font = { name: FONT, ...font }
        cell.alignment = { horizontal: align, vertical: 'middle', wrapText: true }
        ws.getRow(n).height = alto
    }
    filaTexto(1, 'UNIVERSIDAD MAYOR DE SAN SIMÓN', { bold: true, size: 9 }, 14)
    filaTexto(2, 'FACULTAD DE CIENCIAS ECONÓMICAS', { bold: true, size: 9 }, 14)
    filaTexto(3, 'LISTADO DE DOCUMENTOS DE DOCENTES', { bold: true, size: 15 }, 28, 'center')
    filaTexto(
        4,
        'Documentos de docentes registrados en el SISS, correspondientes a la Facultad de Ciencias Económicas.',
        { size: 9, color: { argb: 'FF333333' } },
        16
    )
    filaTexto(
        5,
        filtrosTexto ? `FILTROS : ${filtrosTexto}` : 'FILTROS : Ninguno (todos los registros)',
        { bold: true, size: 10 },
        18
    )
    ws.getRow(6).height = 6

    // ── Fila de títulos de columna ──
    const HEAD_ROW = 7
    const head = ws.getRow(HEAD_ROW)
    cols.forEach((c, i) => {
        const cell = head.getCell(i + 1)
        cell.value = c.t
        cell.font = { name: FONT, bold: true, size: 10 }
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF0F0F0' } }
        cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true }
        cell.border = { top: BORDE_GRUESO, bottom: BORDE_GRUESO, left: BORDE_FINO, right: BORDE_FINO }
    })
    head.height = 22
    ws.views = [{ state: 'frozen', ySplit: HEAD_ROW }] // el encabezado queda fijo al hacer scroll
    ws.pageSetup.printTitlesRow = `${HEAD_ROW}:${HEAD_ROW}` // y se repite al imprimir

    // ── Cuerpo: un bloque por docente, con N° y DOCENTE combinados ──
    let cursor = HEAD_ROW // última fila escrita

    grupos.forEach((g, gi) => {
        const rgb = gi % 2 ? 'FFF8F8F8' : 'FFFFFFFF'
        const primera = cursor + 1

        g.docs.forEach((d, i) => {
            const textoDocente =
                g.docs.length > 1 ? `${g.docente}\n(${g.docs.length} documentos)` : g.docente
            const valores = [i === 0 ? g.nro : null, i === 0 ? textoDocente : null, d.documento, d.detalle]
            if (incluirObs) valores.push(d.observacion)
            if (incluirMaterias) valores.push(d.materias.map((m) => `• ${m}`).join('\n'))
            valores.push(d.categoria, num(d.gestion), num(d.periodo))

            const row = ws.getRow(++cursor)
            valores.forEach((val, k) => {
                row.getCell(k + 1).value = val
            })
            const esUltima = i === g.docs.length - 1

            // N° y docente van combinados: no cuentan para la altura
            row.height = alturaFila(
                valores.map((x, k) => (k < 2 ? '' : x)),
                anchos
            )

            cols.forEach((c, k) => {
                const cell = row.getCell(k + 1)
                const esNroODocente = k < 2
                cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: rgb } }
                cell.font = { name: FONT, size: 9, bold: esNroODocente }
                cell.alignment = {
                    horizontal: c.c ? 'center' : 'left',
                    vertical: 'middle',
                    wrapText: true,
                }
                cell.border = {
                    left: BORDE_FINO,
                    right: BORDE_FINO,
                    bottom: esUltima ? BORDE_GRUESO : BORDE_HAIR,
                }
            })
        })

        // Combinar N° y DOCENTE si el docente tiene más de un documento
        if (cursor > primera) {
            ws.mergeCells(primera, 1, cursor, 1)
            ws.mergeCells(primera, 2, cursor, 2)
        }
    })

    // ── Totales ──
    cursor += 2
    ws.mergeCells(cursor, 1, cursor, NC)
    const celdaTot = ws.getCell(cursor, 1)
    celdaTot.value = `Total de docentes: ${tot.docentes}   ·   Total de documentos: ${tot.documentos}`
    celdaTot.font = { name: FONT, bold: true, size: 10 }

    cursor += 2
    ws.mergeCells(cursor, 1, cursor, NC)
    const celdaGen = ws.getCell(cursor, 1)
    celdaGen.value = `Generado: ${fechaHoraActual()}  ·  Procesado UTi - Facultad de Ciencias Económicas`
    celdaGen.font = { name: FONT, italic: true, size: 8, color: { argb: 'FF666666' } }

    // ── Hoja 2 (opcional): una fila por materia, plana y con filtros ──
    if (incluirMaterias) {
        const wsM = wb.addWorksheet('Materias')
        const headM = ['DOCENTE', 'DOCUMENTO', 'GESTIÓN', 'PERIODO', 'MATERIA', 'GRUPO', 'NOTA', 'DETALLE']
        wsM.columns = [38, 28, 10, 10, 40, 8, 16, 30].map((width) => ({ width }))

        const hr = wsM.addRow(headM)
        hr.height = 22
        hr.eachCell((cell) => {
            cell.font = { name: FONT, bold: true, size: 10 }
            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF0F0F0' } }
            cell.alignment = { horizontal: 'center', vertical: 'middle' }
            cell.border = { top: BORDE_FINO, bottom: BORDE_GRUESO, left: BORDE_FINO, right: BORDE_FINO }
        })

        const filas = [...(listado || [])].sort(
            (a, b) =>
                cmp(a.NOMBRE_DOCENTE, b.NOMBRE_DOCENTE) ||
                cmp(b.GESTION, a.GESTION) ||
                cmp(a.PERIODO, b.PERIODO)
        )
        for (const c of filas) {
            for (const m of Array.isArray(c.MATERIAS) ? c.MATERIAS : []) {
                if (!v(m.NOMBRE_MATERIA)) continue
                const r = wsM.addRow([
                    v(c.NOMBRE_DOCENTE),
                    v(c.TIPO_DOCUMENTO),
                    num(c.GESTION),
                    num(c.PERIODO),
                    v(m.NOMBRE_MATERIA),
                    v(m.GRUPO),
                    v(m.NOTA) === '' ? 'Sin calificación' : num(m.NOTA),
                    v(m.DETALLE),
                ])
                r.eachCell((cell) => {
                    cell.font = { name: FONT, size: 9 }
                    cell.alignment = { vertical: 'middle', wrapText: true }
                    cell.border = { bottom: BORDE_HAIR }
                })
            }
        }
        wsM.views = [{ state: 'frozen', ySplit: 1 }]
        wsM.autoFilter = { from: { row: 1, column: 1 }, to: { row: 1, column: headM.length } }
    }

    await descargarLibro(wb, `${nombreBase()}.xlsx`)
}