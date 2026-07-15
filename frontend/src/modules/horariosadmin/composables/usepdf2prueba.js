// composables/usePdfResumenDos.js
import { jsPDF } from 'jspdf'
import autoTable from 'jspdf-autotable'

const C_BLACK = [0, 0, 0]
const C_WHITE = [255, 255, 255]
const C_HEAD_BG = [211, 211, 211]
const C_GRAY_LINE = [140, 140, 140]
const C_ZERO_TEXT = [150, 150, 150]
const C_TEXT_MUTED = [110, 110, 110]

function norm(v) {
    if (v === null || v === undefined) return ''
    return String(v).trim()
}

/**
 * Misma lógica que el computed `filasAgrupadas` de Resumendocentecarddos.vue,
 * trasladada a JS plano. Empareja materias "origen" (COMP=0) con TODAS sus
 * "derivadas" (COMP=1) cuando comparten el mismo ORDEN (un origen puede
 * compartir con varias carreras a la vez), o como fallback 1 a 1 cuando
 * tienen la misma carga horaria y distinta carrera.
 */
function agruparFilas(materias = []) {
    const usada = new Array(materias.length).fill(false)
    const filas = []

    const porOrden = new Map()
    materias.forEach((m, idx) => {
        const orden = norm(m.ORDEN)
        if (!orden) return
        if (!porOrden.has(orden)) porOrden.set(orden, [])
        porOrden.get(orden).push(idx)
    })

    for (const [, indices] of porOrden) {
        if (indices.length < 2) continue
        const origenes = indices.filter(i => norm(materias[i].COMP) === '0')
        const derivadas = indices.filter(i => norm(materias[i].COMP) === '1')

        if (origenes.length === 1 && derivadas.length >= 1) {
            // ✅ Un solo origen puede tener VARIAS derivadas (comparte con más de una carrera)
            const iOrigen = origenes[0]
            filas.push({
                principal: materias[iOrigen],
                hermanas: derivadas.map(i => materias[i]),
            })
            usada[iOrigen] = true
            derivadas.forEach(i => { usada[i] = true })
        } else {
            // Caso general / fallback: varios orígenes en el mismo ORDEN, emparejar 1 a 1
            const pares = Math.min(origenes.length, derivadas.length)
            for (let p = 0; p < pares; p++) {
                const iOrigen = origenes[p]
                const iDerivada = derivadas[p]
                if (usada[iOrigen] || usada[iDerivada]) continue
                filas.push({ principal: materias[iOrigen], hermanas: [materias[iDerivada]] })
                usada[iOrigen] = true
                usada[iDerivada] = true
            }
            ;[...origenes, ...derivadas].forEach(i => {
                if (!usada[i]) {
                    filas.push({ principal: materias[i], hermanas: [] })
                    usada[i] = true
                }
            })
        }
    }

    const sinProcesar = materias
        .map((m, idx) => ({ m, idx }))
        .filter(({ idx }) => !usada[idx] && norm(materias[idx].COMPARTIDO) !== '')

    const usadaSP = new Set()
    for (let a = 0; a < sinProcesar.length; a++) {
        if (usadaSP.has(a)) continue
        const ma = sinProcesar[a].m
        let encontrado = -1
        for (let b = a + 1; b < sinProcesar.length; b++) {
            if (usadaSP.has(b)) continue
            const mb = sinProcesar[b].m
            const mismaChYDistintaCarrera =
                norm(ma.CARGA_HORARIA) === norm(mb.CARGA_HORARIA) &&
                norm(ma.CARRERA) !== norm(mb.CARRERA)
            if (mismaChYDistintaCarrera) {
                encontrado = b
                break
            }
        }
        if (encontrado !== -1) {
            const [origen, derivada] =
                norm(sinProcesar[a].m.COMP) === '0'
                    ? [sinProcesar[a], sinProcesar[encontrado]]
                    : [sinProcesar[encontrado], sinProcesar[a]]
            filas.push({ principal: origen.m, hermanas: [derivada.m] })
            usadaSP.add(a)
            usadaSP.add(encontrado)
            usada[sinProcesar[a].idx] = true
            usada[sinProcesar[encontrado].idx] = true
        } else {
            filas.push({ principal: ma, hermanas: [] })
            usadaSP.add(a)
            usada[sinProcesar[a].idx] = true
        }
    }

    materias.forEach((m, idx) => {
        if (!usada[idx]) filas.push({ principal: m, hermanas: [] })
    })

    return filas
}

function calcularTotal(fila) {
    const p = Number(fila.principal.TOTAL_NORMAL) || 0
    const h = (fila.hermanas || []).reduce(
        (acc, m) => acc + (Number(m.TOTAL_NORMAL) || 0),
        0
    )
    return p + h
}

export function generarPDFResumenDos(
    docentes = [],
    { anio, periodo, modo = 'descargar' } = {}
) {
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
        doc.text('RESUMEN DE CARGA HORARIA', PAGE_W / 2, 9, { align: 'center' })
        doc.setFontSize(10.5)
        doc.text('Detalle con materias compartidas', PAGE_W / 2, 13.5, { align: 'center' })

        doc.setFont('helvetica', 'bold')
        doc.setFontSize(10)
        doc.setTextColor(...C_BLACK)
        doc.text(`Gestión Académica ${gestionLabel}`, PAGE_W / 2, 19.5, { align: 'center' })

        doc.setFont('helvetica', 'normal')
        doc.setFontSize(7)
        doc.text(`Generado: ${fechaActual}`, PAGE_W - MR, 19.5, { align: 'right' })
        //doc.text(`Total docentes: ${docentes.length}`, ML, 19.5)
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
        const filas = agruparFilas(docente.materias ?? [])

        // Fila con el nombre del docente
        body.push([{
            content: `${docente.docente ?? ''}  ${(docente.apellidos ?? '').toUpperCase()} ${(docente.nombres ?? '').toUpperCase()}`,
            colSpan: 7,
            styles: {
                fontStyle: 'normal', fontSize: 8.5, halign: 'left',
                fillColor: C_WHITE, lineWidth: 0,
                cellPadding: { top: di === 0 ? 1.5 : 3.5, bottom: 1, left: 1.5, right: 1.5 },
            },
        }])

        // Filas de materias (con TODAS sus hermanas de "comparte con", si existen)
        for (const fila of filas) {
            const p = fila.principal
            const hermanas = fila.hermanas || []

            const materiaTexto = [p.MATERIA, p.NOMBRE].filter(Boolean).join(' ')
            const nivelTexto = `${p.CARRERA ?? ''} - ${p.NIVEL ?? ''}`

            // Cada hermana en su propia línea dentro de la celda (\n = salto de línea en autotable)
            const compTexto = hermanas
                .map(h => {
                    const hMateriaTexto = [h.MATERIA, h.NOMBRE].filter(Boolean).join(' ')
                    return `${hMateriaTexto} (${h.CARRERA ?? ''} - ${h.NIVEL ?? ''}) · Grp ${h.GRUPO ?? ''} · Ins: ${h.TOTAL_NORMAL ?? 0}`
                })
                .join('\n')

            body.push([
                nivelTexto,                          // 0: carrera - nivel
                materiaTexto,                         // 1: MATERIA
                norm(p.GRUPO),                        // 2: GRP
                String(p.CARGA_HORARIA ?? ''),        // 3: CH
                String(p.TOTAL_NORMAL ?? ''),         // 4: INSCRITOS
                compTexto,                            // 5: COMPARTE CON
                String(calcularTotal(fila)),          // 6: INS. TOTAL
            ])
        }

        // ── Calcular totales (CH real sumando solo la fila principal de cada par) ──
        const totalCH = filas.reduce((acc, f) => acc + (Number(f.principal.CARGA_HORARIA) || 0), 0)
        const totalGeneral = filas.reduce((acc, f) => acc + calcularTotal(f), 0)

        // Fila TOTAL alineada: etiqueta TOTAL ocupa NIVEL+MATERIA+GRP, CH y total general en sus columnas
        body.push([
            { content: 'TOTAL', colSpan: 3, styles: { halign: 'right', fontStyle: 'bold', fontSize: 7.5, fillColor: C_WHITE, lineWidth: 0 } },
            { content: String(totalCH), colSpan: 1, styles: { halign: 'center', fontStyle: 'bold', fontSize: 7.5, fillColor: C_WHITE, lineWidth: 0 } },
            { content: '', colSpan: 1, styles: { fillColor: C_WHITE, lineWidth: 0 } },
            { content: '', colSpan: 1, styles: { fillColor: C_WHITE, lineWidth: 0 } },
            { content: String(totalGeneral), colSpan: 1, styles: { halign: 'center', fontStyle: 'bold', fontSize: 7.5, fillColor: C_WHITE, lineWidth: 0 } },
        ])
    }

    // Encabezado de la primera página
    drawPageHeader()

    autoTable(doc, {
        startY: HEADER_H,
        margin: { left: ML, right: MR, top: HEADER_H, bottom: 12 },
        tableWidth: CW,
        head: [['NIVEL', 'MATERIA', 'GRP', 'CH', 'INSC.', 'COMPARTE CON', 'INS. TOT.']],
        body,
        alternateRowStyles: { fillColor: C_WHITE },
        styles: {
            font: 'helvetica', fontSize: 7,
            cellPadding: { top: 0.8, bottom: 0.8, left: 1.5, right: 1.5 },
            textColor: C_BLACK, lineColor: C_GRAY_LINE, lineWidth: 0,
            fillColor: C_WHITE,
            overflow: 'linebreak', valign: 'middle',
        },
        headStyles: {
            fillColor: C_HEAD_BG, textColor: C_BLACK, fontStyle: 'bold',
            fontSize: 6.8, halign: 'center', valign: 'middle',
            cellPadding: { top: 1.2, bottom: 1.2, left: 1.5, right: 1.5 },
            lineColor: C_GRAY_LINE, lineWidth: { top: 0, right: 0, bottom: 0.3, left: 0 },
        },
        columnStyles: {
            0: { cellWidth: 16, halign: 'center' },
            1: { cellWidth: 50 },
            2: { cellWidth: 11, halign: 'center' },
            3: { cellWidth: 9, halign: 'center' },
            4: { cellWidth: 13, halign: 'center' },
            5: { cellWidth: 'auto' },
            6: { cellWidth: 14, halign: 'center' },
        },

        didParseCell(data) {
            if (data.section === 'head') {
                if (data.column.index === 1) data.cell.styles.halign = 'left'
                if (data.column.index === 5) data.cell.styles.halign = 'left'
                return
            }

            if (data.section !== 'body') return

            const raw = data.row.raw
            const isDataRow = Array.isArray(raw) && raw.length === 7
            if (!isDataRow) return

            // Saltar filas de cabecera docente o total (tienen objetos con colSpan)
            const firstCell = raw[0]
            if (typeof firstCell === 'object' && firstCell !== null && 'colSpan' in firstCell) return

            const col = data.column.index

            // Líneas horizontales solo desde columna GRP (índice 2) en adelante
            if (col <= 1) {
                data.cell.styles.lineWidth = { top: 0, right: 0, bottom: 0, left: 0 }
            } else {
                data.cell.styles.lineWidth = { top: 0, right: 0, bottom: 0.2, left: 0 }
            }

            // Col NIVEL: texto normal, fuente más chica
            if (col === 0) {
                data.cell.styles.fontStyle = 'normal'
                data.cell.styles.fontSize = 6.5
            }

            // Col MATERIA: peso normal, mismo tamaño que el resto de la tabla
            if (col === 1) {
                data.cell.styles.fontStyle = 'normal'
                data.cell.styles.fontSize = 7
            }

            // Inscritos sin valor → gris, como un placeholder "0"
            if (col === 4 && (data.cell.raw === '0' || data.cell.raw === '')) {
                data.cell.styles.textColor = C_ZERO_TEXT
            }

            // COMPARTE CON: texto gris/mutado, normal, sin negrita.
            // Con varias hermanas, el texto trae '\n' y autotable lo respeta
            // (cada hermana queda en su propia línea dentro de la celda).
            if (col === 5) {
                data.cell.styles.textColor = data.cell.raw ? C_TEXT_MUTED : C_ZERO_TEXT
                data.cell.styles.fontStyle = 'normal'
                data.cell.styles.fontSize = 6.3
                if (!data.cell.raw) data.cell.text = ['—']
            }

            // INS. TOTAL: peso normal, igual que el resto
            if (col === 6) {
                data.cell.styles.fontStyle = 'normal'
                data.cell.styles.fontSize = 7
            }
        },
        didDrawPage(data) {
            if (doc.internal.getCurrentPageInfo().pageNumber > 1) {
                drawPageHeader()
            }
        },
    })

    drawFooters()

    const filename = `CargaHorariaResumen_${anio}_${periodo}.pdf`

    // Si es modo 'ver', abrir en nueva pestaña
    if (modo === 'ver') {
        const blob = doc.output('blob')
        const url = URL.createObjectURL(blob)

        const ventana = window.open(url, '_blank')

        if (ventana) {
            ventana.addEventListener(
                'load',
                () => URL.revokeObjectURL(url),
                { once: true }
            )
        } else {
            // Si el navegador bloquea la ventana emergente, descargar como fallback
            const link = document.createElement('a')
            link.href = url
            link.download = filename
            document.body.appendChild(link)
            link.click()
            document.body.removeChild(link)
            setTimeout(() => URL.revokeObjectURL(url), 10000)
        }

        return
    }

    // Modo 'descargar' - descarga directa
    doc.save(filename)
}