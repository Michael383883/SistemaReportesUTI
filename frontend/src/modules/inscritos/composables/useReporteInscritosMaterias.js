// composables/useReporteInscritosMaterias.js
// PDF de TOTAL DE INSCRITOS POR DOCENTE / MATERIA
// Columnas: PLAN | MATERIA | TIPO | GRP | C | REGULAR | MESA | TOTAL
// Mismo estilo académico que el PDF de Carga Horaria (una sola tabla,
// docente en línea simple, negrilla solo en cabecera y totales).
// Usa los datos ya cargados en pantalla (dataFiltrada), sin fetch.

import { ref } from 'vue'
import { jsPDF } from 'jspdf'
import autoTable from 'jspdf-autotable'

// ── Paleta institucional ─────────────────────────────────────────────────
const C_BLACK = [0, 0, 0]
const C_WHITE = [255, 255, 255]
const C_HEAD_BG = [240, 240, 240]
const C_GRAY_LINE = [140, 140, 140]
const C_ZERO_TEXT = [150, 150, 150]

// ── Helpers de documento ─────────────────────────────────────────────────
function fechaFormateada() {
    const d = new Date()
    const dia = String(d.getDate()).padStart(2, '0')
    const mes = String(d.getMonth() + 1).padStart(2, '0')
    const hora = d.toLocaleString('en-US', {
        hour: 'numeric', minute: '2-digit', second: '2-digit', hour12: true,
    })
    return `${dia}/${mes}/${d.getFullYear()} ${hora}`
}

function drawPageHeader(doc, { titulo, anio, periodo, fechaActual, nota }) {
    const PAGE_W = doc.internal.pageSize.getWidth()
    const ML = 8
    const MR = 8

    doc.setFont('helvetica', 'bold')
    doc.setFontSize(6)
    doc.setTextColor(...C_BLACK)
    doc.text('UNIVERSIDAD MAYOR DE SAN SIMÓN', ML, 8)
    doc.text('FACULTAD DE CIENCIAS ECONÓMICAS', ML, 10)

    doc.setFontSize(12.5)
    doc.text(titulo, PAGE_W / 2, 9, { align: 'center' })
    doc.setFontSize(10.5)
    doc.text('FACULTAD DE CIENCIAS ECONÓMICAS', PAGE_W / 2, 13.5, { align: 'center' })

    doc.setFontSize(10)
    doc.text(`Gestión Académica ${periodo}/${anio}`, PAGE_W / 2, 19.5, { align: 'center' })

    doc.setFont('helvetica', 'normal')
    doc.setFontSize(7)
    doc.text(fechaActual, PAGE_W - MR, 19.5, { align: 'right' })
    doc.text(nota ?? '', ML, 19.5)
}

function drawFooters(doc, fechaActual) {
    const PAGE_W = doc.internal.pageSize.getWidth()
    const PAGE_H = doc.internal.pageSize.getHeight()
    const ML = 8
    const MR = 8
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

function finalizarSalida(doc, filename, modo, ventanaPreabierta) {
    if (modo === 'imprimir') {
        const blob = doc.output('blob')
        const url = URL.createObjectURL(blob)

        if (ventanaPreabierta && !ventanaPreabierta.closed) {
            ventanaPreabierta.close()
        }

        const iframe = document.createElement('iframe')
        iframe.style.position = 'fixed'
        iframe.style.right = '0'
        iframe.style.bottom = '0'
        iframe.style.width = '0'
        iframe.style.height = '0'
        iframe.style.border = '0'
        iframe.src = url

        iframe.onload = () => {
            try {
                iframe.contentWindow.focus()
                iframe.contentWindow.print()
            } catch (e) {
                console.error('No se pudo imprimir automáticamente', e)
                window.open(url, '_blank')
            }
        }

        document.body.appendChild(iframe)

        setTimeout(() => {
            document.body.removeChild(iframe)
            URL.revokeObjectURL(url)
        }, 60_000)

        return
    }

    if (modo === 'ver') {
        const blob = doc.output('blob')
        const url = URL.createObjectURL(blob)

        if (ventanaPreabierta && !ventanaPreabierta.closed) {
            ventanaPreabierta.location.href = url
        } else {
            const ventana = window.open(url, '_blank')
            if (!ventana) {
                doc.save(filename)
                URL.revokeObjectURL(url)
                return
            }
        }

        setTimeout(() => URL.revokeObjectURL(url), 60_000)
    } else {
        doc.save(filename)
    }
}

// ────────────────────────────────────────────────────────────────────────────
// Helpers de datos
// ────────────────────────────────────────────────────────────────────────────
function norm(v) {
    if (v === null || v === undefined) return ''
    return String(v).trim()
}

// ── Columna TIPO: abreviatura corta del tipo de ingreso ─────────────────
// Mismo mapa/lógica que DocenteInscritosCard.vue. Completa con tus siglas fijas.
const ABREV_TIPO = {
    // 'TITULAR': 'TIT',
    // 'INVITADO': 'INV',
    // 'INTERINO': 'INT',
}

function abreviarTipo(texto) {
    const t = norm(texto)
    if (!t) return ''
    const exacto = ABREV_TIPO[t.toUpperCase()]
    if (exacto) return exacto
    return t.length > 10 ? t.slice(0, 9) + '…' : t
}

// ────────────────────────────────────────────────────────────────────────────
// Orden padre / hijo (misma lógica que DocenteInscritosCard.vue):
// el padre queda arriba y sus hijos justo debajo.
// ────────────────────────────────────────────────────────────────────────────
function ordenarMaterias(docente) {
    const base = docente.carreras.flatMap(carrera =>
        carrera.materias.map(materia => ({
            carrera,
            materia,
            key: `${carrera.plan}-${materia.cod_materia}-${materia.grupo}`,
        }))
    )
    const porKey = new Map(base.map(i => [i.key, i]))
    const porCarreraCodGrupo = new Map(
        base.map(i => [`${i.carrera.carrera}|${i.materia.cod_materia}|${i.materia.grupo}`, i])
    )

    const clusters = new Map()
    const hijoDe = new Map()

    function vincular(padre, hijo) {
        if (padre.key === hijo.key) return
        if (hijoDe.has(hijo.key) || hijoDe.has(padre.key)) return
        if (clusters.has(hijo.key)) return
        if (!clusters.has(padre.key)) clusters.set(padre.key, { padre, hijos: [] })
        clusters.get(padre.key).hijos.push(hijo)
        hijoDe.set(hijo.key, padre.key)
    }

    // 1) Por ORDEN
    const porOrden = new Map()
    base.forEach(i => {
        const orden = norm(i.materia.orden_comp)
        if (!orden) return
        if (!porOrden.has(orden)) porOrden.set(orden, [])
        porOrden.get(orden).push(i)
    })

    for (const [, grupo] of porOrden) {
        if (grupo.length < 2) continue
        const origenes = grupo.filter(i => !i.materia.comp)
        const derivadas = grupo.filter(i => i.materia.comp)
        if (!origenes.length || !derivadas.length) continue

        derivadas.forEach(d => {
            let origen = origenes.length === 1 ? origenes[0] : null
            if (!origen) origen = origenes.find(o => o.key === d.materia.padre_clave)
            if (!origen) origen = origenes.find(o => !clusters.has(o.key))
            if (origen) vincular(origen, d)
        })
    }

    // 2) Lo suelto: relación calculada por el backend
    base.forEach(i => {
        if (!i.materia.comp || hijoDe.has(i.key)) return
        let padre = i.materia.padre_clave ? porKey.get(i.materia.padre_clave) : null
        if (!padre) {
            const p = (i.materia.comparte_con ?? []).find(r => r.rol === 'padre')
            if (p) padre = porCarreraCodGrupo.get(`${p.carrera}|${p.cod_materia}|${p.grupo}`)
        }
        if (padre) vincular(padre, i)
    })

    const salida = []
    base.forEach(i => {
        if (hijoDe.has(i.key)) return
        salida.push(i)
        const cl = clusters.get(i.key)
        if (cl) cl.hijos.forEach(h => salida.push(h))
    })
    return salida
}

// ────────────────────────────────────────────────────────────────────────────
// TOTAL DE INSCRITOS POR MATERIA
// Columnas (índices): 0 PLAN | 1 MATERIA | 2 TIPO | 3 GRP | 4 C | 5 REGULAR | 6 MESA | 7 TOTAL
// ────────────────────────────────────────────────────────────────────────────
function generarTotalesPorMateria(data, anio, periodo, modo = 'descargar', ventanaPreabierta = null, opciones = {}) {
    const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'letter' })
    const PAGE_W = doc.internal.pageSize.getWidth()
    const ML = 8
    const MR = 8
    const CW = PAGE_W - ML - MR
    const HEADER_H = 24
    const NCOLS = 8
    const fechaActual = fechaFormateada()
    const TITULO = opciones.titulo ?? 'TOTAL DE INSCRITOS POR MATERIA'
    const NOTA = 'Incluye alumnos regulares y de examen de mesa.'
    const hdr = { titulo: TITULO, anio, periodo, fechaActual, nota: NOTA }

    const numTxt = n => String(n ?? 0)
    const body = []
    let granRegular = 0
    let granMesa = 0

    data.forEach((docente, di) => {
        // ── Línea del docente (sin relleno, texto normal) ───────────────
        // A la derecha, el total por plan (igual que las etiquetas de la pantalla):
        // "ADM 99    FIN 116"
        const totalesPlan = docente.carreras
            .map(c => `${c.carrera} ${c.subtotal ?? 0}`)
            .join('     ')
        const padDoc = { top: di === 0 ? 1.5 : 3.5, bottom: 1, left: 1.5, right: 1.5 }
        const estiloDoc = {
            fontStyle: 'normal', fontSize: 8.5, fillColor: C_WHITE, lineWidth: 0, cellPadding: padDoc,
        }
        body.push([
            {
                content: `${docente.cod_docente}  ${(docente.apellidos ?? '').toUpperCase()} ${(docente.nombres ?? '').toUpperCase()}`,
                colSpan: 5,
                styles: { ...estiloDoc, halign: 'left' },
            },
            {
                content: totalesPlan,
                colSpan: 3,
                styles: { ...estiloDoc, fontSize: 8, halign: 'right' },
            },
        ])

        // ── Materias ────────────────────────────────────────────────────
        ordenarMaterias(docente).forEach(({ carrera, materia: m }) => {
            const regular = m.subtotal ?? 0
            const mesa = m.subtotal_examen_mesa ?? 0
            body.push([
                carrera.carrera,
                `${m.cod_materia} ${m.nom_materia}`,
                abreviarTipo(m.tipo_ingreso) || '—',
                String(m.grupo ?? ''),
                m.comp ? '1' : '0',
                numTxt(regular),
                numTxt(mesa),
                numTxt(regular + mesa),
            ])
        })

        // ── Total del docente ───────────────────────────────────────────
        const totReg = docente.total_inscritos ?? 0
        const totMesa = docente.total_examen_mesa ?? 0
        granRegular += totReg
        granMesa += totMesa

        const base = { fillColor: C_WHITE, lineWidth: 0, fontStyle: 'bold', fontSize: 7.5 }
        const num = { ...base, halign: 'center' }
        body.push([
            { content: '', styles: base },
            { content: 'TOTAL', colSpan: 4, styles: { ...base, halign: 'right' } },
            { content: numTxt(totReg), styles: num },
            { content: numTxt(totMesa), styles: num },
            { content: numTxt(totReg + totMesa), styles: num },
        ])
    })

    // ── Total general ────────────────────────────────────────────────────
    const baseG = {
        fillColor: C_HEAD_BG, fontStyle: 'bold', fontSize: 8,
        lineWidth: { top: 0.3, right: 0, bottom: 0.3, left: 0 }, lineColor: C_GRAY_LINE,
        cellPadding: { top: 1.2, bottom: 1.2, left: 1, right: 1 },
    }
    body.push([
        { content: `TOTAL GENERAL (${data.length} docentes)`, colSpan: 5, styles: { ...baseG, halign: 'right' } },
        { content: numTxt(granRegular), styles: { ...baseG, halign: 'center' } },
        { content: numTxt(granMesa), styles: { ...baseG, halign: 'center' } },
        { content: numTxt(granRegular + granMesa), styles: { ...baseG, halign: 'center' } },
    ])

    drawPageHeader(doc, hdr)

    autoTable(doc, {
        startY: HEADER_H,
        margin: { left: ML, right: MR, top: HEADER_H, bottom: 12 },
        tableWidth: CW,
        head: [['PLAN', 'MATERIA', 'TIPO', 'GRP', 'C', 'REGULAR', 'MESA', 'TOTAL']],
        body,
        alternateRowStyles: { fillColor: C_WHITE },
        styles: {
            font: 'helvetica', fontSize: 7,
            cellPadding: { top: 0.5, bottom: 0.5, left: 1, right: 1 },
            textColor: C_BLACK, lineColor: C_GRAY_LINE, lineWidth: 0,
            fillColor: C_WHITE, overflow: 'linebreak', valign: 'middle',
        },
        headStyles: {
            fillColor: C_HEAD_BG, textColor: C_BLACK, fontStyle: 'bold',
            fontSize: 6.8, halign: 'center', valign: 'middle',
            lineColor: C_GRAY_LINE, lineWidth: { top: 0, right: 0, bottom: 0.3, left: 0 },
            cellPadding: { top: 0.7, bottom: 0.7, left: 0.5, right: 0.5 },
        },
        columnStyles: {
            0: { cellWidth: 14, halign: 'left' },
            1: { cellWidth: 'auto', halign: 'left' },
            2: { cellWidth: 16, halign: 'center' },
            3: { cellWidth: 12, halign: 'center' },
            4: { cellWidth: 8, halign: 'center' },
            5: { cellWidth: 18, halign: 'center' },
            6: { cellWidth: 18, halign: 'center' },
            7: { cellWidth: 18, halign: 'center' },
        },
        didParseCell(cell) {
            if (cell.section === 'head') {
                if (cell.column.index <= 1) cell.cell.styles.halign = 'left'
                return
            }
            if (cell.section !== 'body') return

            // Solo filas de datos (todas las celdas son texto simple)
            const raw = cell.row.raw
            const esDato = Array.isArray(raw) && raw.length === NCOLS && typeof raw[0] === 'string'
            if (!esDato) return

            const col = cell.column.index
            cell.cell.styles.lineWidth = col <= 1
                ? { top: 0, right: 0, bottom: 0, left: 0 }
                : { top: 0, right: 0, bottom: 0.2, left: 0 }

            // Ceros en gris suave (C, Regular, Mesa)
            if (col >= 4 && col <= 6 && cell.cell.raw === '0') {
                cell.cell.styles.textColor = C_ZERO_TEXT
            }
        },
        didDrawPage() {
            if (doc.internal.getCurrentPageInfo().pageNumber > 1) {
                drawPageHeader(doc, hdr)
            }
        },
    })

    drawFooters(doc, fechaActual)

    const filename = opciones.filename ?? `Total_Inscritos_Por_Materia_${anio}_${periodo}.pdf`
    finalizarSalida(doc, filename, modo, ventanaPreabierta)
}

// ────────────────────────────────────────────────────────────────────────────
// EXCEL — mismo contenido y orden que el PDF
// Requiere:  npm i exceljs   (se carga bajo demanda, no pesa en el bundle inicial)
// ────────────────────────────────────────────────────────────────────────────
async function generarExcelTotalesPorMateria(data, anio, periodo, opciones = {}) {
    const ExcelJS = (await import('exceljs')).default

    const wb = new ExcelJS.Workbook()
    wb.creator = 'UTI - Facultad de Ciencias Económicas'
    wb.created = new Date()

    const HEAD_ROW = 6
    const ws = wb.addWorksheet('Inscritos por materia', {
        views: [{ state: 'frozen', ySplit: HEAD_ROW }],
        pageSetup: {
            paperSize: 1, // Letter
            orientation: 'portrait',
            fitToPage: true,
            fitToWidth: 1,
            fitToHeight: 0,
            margins: { left: 0.3, right: 0.3, top: 0.4, bottom: 0.5, header: 0.2, footer: 0.2 },
            printTitlesRow: `${HEAD_ROW}:${HEAD_ROW}`,
        },
    })

    ws.columns = [
        { width: 9 },   // A PLAN
        { width: 54 },  // B MATERIA
        { width: 11 },  // C TIPO
        { width: 7 },   // D GRP
        { width: 5 },   // E C
        { width: 11 },  // F REGULAR
        { width: 11 },  // G MESA
        { width: 11 },  // H TOTAL
    ]

    const GRIS_LINEA = 'FF8C8C8C'
    const GRIS_CERO = 'FF969696'
    const FONT = 'Arial'
    const lineaFina = { style: 'thin', color: { argb: GRIS_LINEA } }

    // ── Encabezado institucional ────────────────────────────────────────
    ws.mergeCells('A1:H1')
    ws.getCell('A1').value = 'UNIVERSIDAD MAYOR DE SAN SIMÓN - FACULTAD DE CIENCIAS ECONÓMICAS'
    ws.getCell('A1').font = { name: FONT, size: 8, bold: true }

    ws.mergeCells('A2:H2')
    ws.getCell('A2').value = opciones.titulo ?? 'TOTAL DE INSCRITOS POR MATERIA'
    ws.getCell('A2').font = { name: FONT, size: 14, bold: true }
    ws.getCell('A2').alignment = { horizontal: 'center' }

    ws.mergeCells('A3:H3')
    ws.getCell('A3').value = `Gestión Académica ${periodo}/${anio}`
    ws.getCell('A3').font = { name: FONT, size: 10, bold: true }
    ws.getCell('A3').alignment = { horizontal: 'center' }

    ws.mergeCells('A4:E4')
    ws.getCell('A4').value = 'Incluye alumnos regulares y de examen de mesa.'
    ws.getCell('A4').font = { name: FONT, size: 8 }
    ws.mergeCells('F4:H4')
    ws.getCell('F4').value = fechaFormateada()
    ws.getCell('F4').font = { name: FONT, size: 8 }
    ws.getCell('F4').alignment = { horizontal: 'right' }

    // ── Cabecera de columnas ────────────────────────────────────────────
    const head = ws.getRow(HEAD_ROW)
    head.values = ['PLAN', 'MATERIA', 'TIPO', 'GRP', 'C', 'REGULAR', 'MESA', 'TOTAL']
    head.eachCell((cell, col) => {
        cell.font = { name: FONT, size: 9, bold: true }
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF0F0F0' } }
        cell.alignment = { horizontal: col <= 2 ? 'left' : 'center', vertical: 'middle' }
        cell.border = { bottom: { style: 'medium', color: { argb: GRIS_LINEA } } }
    })

    // ── Cuerpo ──────────────────────────────────────────────────────────
    let granRegular = 0
    let granMesa = 0

    data.forEach((docente, di) => {
        // Línea del docente + totales por plan a la derecha
        if (di > 0) ws.addRow([]) // renglón en blanco entre docentes
        const rDoc = ws.addRow([])
        const nDoc = rDoc.number
        ws.mergeCells(nDoc, 1, nDoc, 5)
        ws.mergeCells(nDoc, 6, nDoc, 8)
        rDoc.getCell(1).value = `${docente.cod_docente}  ${(docente.apellidos ?? '').toUpperCase()} ${(docente.nombres ?? '').toUpperCase()}`
        rDoc.getCell(1).font = { name: FONT, size: 10 }
        rDoc.getCell(6).value = docente.carreras.map(c => `${c.carrera} ${c.subtotal ?? 0}`).join('     ')
        rDoc.getCell(6).font = { name: FONT, size: 10 }
        rDoc.getCell(6).alignment = { horizontal: 'right' }

        // Materias (padre arriba, hijos debajo)
        ordenarMaterias(docente).forEach(({ carrera, materia: m }) => {
            const regular = m.subtotal ?? 0
            const mesa = m.subtotal_examen_mesa ?? 0
            const r = ws.addRow([
                carrera.carrera,
                `${m.cod_materia} ${m.nom_materia}`,
                abreviarTipo(m.tipo_ingreso) || '—',
                m.grupo ?? '',
                m.comp ? 1 : 0,
                regular,
                mesa,
                regular + mesa,
            ])
            r.eachCell({ includeEmpty: true }, (cell, col) => {
                if (col > 8) return
                cell.font = { name: FONT, size: 9 }
                cell.alignment = { horizontal: col <= 2 ? 'left' : 'center', vertical: 'middle' }
                if (col >= 3) cell.border = { bottom: lineaFina }
                if (col >= 5 && col <= 7 && cell.value === 0) {
                    cell.font = { name: FONT, size: 9, color: { argb: GRIS_CERO } }
                }
            })
        })

        // Total del docente
        const totReg = docente.total_inscritos ?? 0
        const totMesa = docente.total_examen_mesa ?? 0
        granRegular += totReg
        granMesa += totMesa

        const rTot = ws.addRow([])
        const nTot = rTot.number
        ws.mergeCells(nTot, 2, nTot, 5)
        rTot.getCell(2).value = 'TOTAL'
        rTot.getCell(2).alignment = { horizontal: 'right' }
        rTot.getCell(6).value = totReg
        rTot.getCell(7).value = totMesa
        rTot.getCell(8).value = totReg + totMesa
        for (let col = 2; col <= 8; col++) {
            const cell = rTot.getCell(col)
            cell.font = { name: FONT, size: 9, bold: true }
            if (col >= 6) cell.alignment = { horizontal: 'center' }
        }
    })

    // ── Total general ───────────────────────────────────────────────────
    ws.addRow([])
    const rG = ws.addRow([])
    const nG = rG.number
    ws.mergeCells(nG, 1, nG, 5)
    rG.getCell(1).value = `TOTAL GENERAL (${data.length} docentes)`
    rG.getCell(1).alignment = { horizontal: 'right' }
    rG.getCell(6).value = granRegular
    rG.getCell(7).value = granMesa
    rG.getCell(8).value = granRegular + granMesa
    for (let col = 1; col <= 8; col++) {
        const cell = rG.getCell(col)
        cell.font = { name: FONT, size: 10, bold: true }
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF0F0F0' } }
        cell.border = { top: lineaFina, bottom: lineaFina }
        if (col >= 6) cell.alignment = { horizontal: 'center' }
    }

    // ── Descarga ────────────────────────────────────────────────────────
    const buffer = await wb.xlsx.writeBuffer()
    const blob = new Blob([buffer], {
        type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = opciones.filenameExcel ?? `Total_Inscritos_Por_Materia_${anio}_${periodo}.xlsx`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    setTimeout(() => URL.revokeObjectURL(url), 10_000)
}

// ────────────────────────────────────────────────────────────────────────────
// Composable público
// ────────────────────────────────────────────────────────────────────────────
export function useReporteInscritosMaterias() {
    const generandoMaterias = ref(false)

    async function exportarExcelTotalesPorMateria(data, anio, periodo, opciones = {}) {
        if (!data?.length) return
        generandoMaterias.value = true
        try {
            await generarExcelTotalesPorMateria(data, anio, periodo, opciones)
        } catch (e) {
            console.error('No se pudo generar el Excel', e)
            throw e
        } finally {
            generandoMaterias.value = false
        }
    }

    async function exportarTotalesPorMateria(data, anio, periodo, modo = 'descargar', ventanaPreabierta = null, opciones = {}) {
        if (!data?.length) {
            if (ventanaPreabierta && !ventanaPreabierta.closed) ventanaPreabierta.close()
            return
        }
        generandoMaterias.value = true
        try {
            generarTotalesPorMateria(data, anio, periodo, modo, ventanaPreabierta, opciones)
        } finally {
            generandoMaterias.value = false
        }
    }

    return {
        generandoMaterias,
        exportarTotalesPorMateria,
        exportarExcelTotalesPorMateria,
    }
}