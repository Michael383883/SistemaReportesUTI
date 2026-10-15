// composables/useExcelReportes.js
// Exportación a EXCEL (.xlsx) de los reportes de inscritos:
//   1) Lista completa de inscritos            -> exportarExcelLista
//   2) Aprobados/reprobados completo (plano)  -> exportarExcelAprobadosCompleto
//   3) Inscritos totales por docente          -> exportarExcelTotales
//   4) Aprobados/reprobados resumido (matriz) -> exportarExcelAprobadosResumen
//
// Cada hoja replica el contenido y el orden de su PDF (mismo encabezado
// institucional, mismas columnas, mismos totales). Los números se guardan
// como números reales para poder filtrar y sumar en Excel.
//
// Requiere:  npm i exceljs   (se carga bajo demanda al pulsar el botón)

import { ref } from 'vue'

// ── Estilos compartidos ──────────────────────────────────────────────────
const FONT = 'Arial'
const GRIS_LINEA = 'FF8C8C8C'
const GRIS_DIV = 'FF5A5A5A'
const GRIS_CERO = 'FF969696'
const GRIS_TXT = 'FF5A5A5A'

const fillHead = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF0F0F0' } }
const fillGrupo = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFEBEBEB' } }
const lineaFina = { style: 'thin', color: { argb: GRIS_LINEA } }
const lineaMedia = { style: 'medium', color: { argb: GRIS_LINEA } }
const lineaDiv = { style: 'thin', color: { argb: GRIS_DIV } }

function fechaFormateada() {
    const d = new Date()
    const dia = String(d.getDate()).padStart(2, '0')
    const mes = String(d.getMonth() + 1).padStart(2, '0')
    const hora = d.toLocaleString('en-US', {
        hour: 'numeric', minute: '2-digit', second: '2-digit', hour12: true,
    })
    return `${dia}/${mes}/${d.getFullYear()} ${hora}`
}

async function crearLibro() {
    const ExcelJS = (await import('exceljs')).default
    const wb = new ExcelJS.Workbook()
    wb.creator = 'UTI - Facultad de Ciencias Económicas'
    wb.created = new Date()
    return wb
}

function crearHoja(wb, nombre, { anchos, orientacion = 'portrait', congelar = 0, titulosImpresion = null }) {
    const ws = wb.addWorksheet(nombre, {
        views: congelar ? [{ state: 'frozen', ySplit: congelar }] : [],
        pageSetup: {
            paperSize: 1, // Letter
            orientation: orientacion,
            fitToPage: true,
            fitToWidth: 1,
            fitToHeight: 0,
            margins: { left: 0.3, right: 0.3, top: 0.4, bottom: 0.5, header: 0.2, footer: 0.2 },
            ...(titulosImpresion ? { printTitlesRow: titulosImpresion } : {}),
        },
    })
    ws.columns = anchos.map(width => ({ width }))
    return ws
}

// Filas 1-4: encabezado institucional (la fila 5 queda en blanco, la tabla empieza en la 6)
function encabezado(ws, { titulo, anio, periodo, nota, nCols }) {
    const fila = (n, texto, font, align = 'left') => {
        ws.mergeCells(n, 1, n, nCols)
        const c = ws.getCell(n, 1)
        c.value = texto
        c.font = { name: FONT, ...font }
        c.alignment = { horizontal: align, vertical: 'middle' }
    }
    fila(1, 'UNIVERSIDAD MAYOR DE SAN SIMÓN - FACULTAD DE CIENCIAS ECONÓMICAS', { size: 8, bold: true })
    fila(2, titulo, { size: 14, bold: true }, 'center')
    fila(3, `Gestión Académica ${periodo}/${anio}`, { size: 10, bold: true }, 'center')

    // Fila 4: nota a la izquierda (desborda sobre celdas vacías) y fecha a la derecha
    const nota4 = ws.getCell(4, 1)
    nota4.value = nota ?? ''
    nota4.font = { name: FONT, size: 8 }
    const fecha4 = ws.getCell(4, nCols)
    fecha4.value = fechaFormateada()
    fecha4.font = { name: FONT, size: 8 }
    fecha4.alignment = { horizontal: 'right' }

    ws.getRow(5) // fila en blanco
}

function estiloCabecera(cell, izquierda = false) {
    cell.font = { name: FONT, size: 9, bold: true }
    cell.fill = fillHead
    cell.alignment = { horizontal: izquierda ? 'left' : 'center', vertical: 'middle', wrapText: true }
    cell.border = { bottom: lineaMedia }
}

// Fila de tabla con el mismo criterio visual que los PDF:
// texto normal, línea fina inferior, ceros/guiones en gris, negrilla solo en totales.
function filaTabla(ws, valores, {
    nCols, izq = [], divisores = [], bold = false, fill = false, sinFill = 0, lineas = true, size = 9,
}) {
    const r = ws.addRow(valores)
    for (let c = 1; c <= nCols; c++) {
        const cell = r.getCell(c)
        const gris = cell.value === '—'
        cell.font = {
            name: FONT, size, bold,
            ...(gris ? { color: { argb: GRIS_CERO } } : {}),
        }
        cell.alignment = { horizontal: izq.includes(c) ? 'left' : 'center', vertical: 'middle' }
        const border = {}
        if (lineas) border.bottom = lineaFina
        if (divisores.includes(c)) border.right = lineaDiv
        cell.border = border
        if (fill && c > sinFill) cell.fill = fillHead
    }
    return r
}

async function descargar(wb, filename) {
    const buffer = await wb.xlsx.writeBuffer()
    const blob = new Blob([buffer], {
        type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = filename
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    setTimeout(() => URL.revokeObjectURL(url), 10_000)
}

const num = v => Number(v) || 0

// ════════════════════════════════════════════════════════════════════════════
// 1) LISTA COMPLETA DE INSCRITOS (docente → materia → estudiantes)
// ════════════════════════════════════════════════════════════════════════════
async function generarListaCompleta(data, anio, periodo) {
    const NCOLS = 3
    const wb = await crearLibro()
    const ws = crearHoja(wb, 'Lista de inscritos', { anchos: [7, 16, 70] })
    encabezado(ws, {
        titulo: 'LISTA DE INSCRITOS POR DOCENTE', anio, periodo,
        nota: 'No incluye alumnos de examen de mesa.', nCols: NCOLS,
    })

    // Fila de una sola celda combinada en las 3 columnas
    const filaCombinada = (texto, estiloCelda) => {
        const r = ws.addRow([texto])
        ws.mergeCells(r.number, 1, r.number, NCOLS)
        const c = r.getCell(1)
        estiloCelda(c)
        return r
    }

    const filaEstudiante = (n, est) => {
        const r = ws.addRow([n, est.codigo, est.nombre])
        for (let c = 1; c <= NCOLS; c++) {
            const cell = r.getCell(c)
            cell.font = { name: FONT, size: 9, ...(c <= 2 ? { color: { argb: GRIS_TXT } } : {}) }
            cell.alignment = { horizontal: c === 1 ? 'center' : 'left', vertical: 'middle' }
            cell.border = { bottom: lineaFina }
        }
    }

    data.forEach((docente, dIdx) => {
        if (dIdx > 0) ws.addRow([]) // separación entre docentes

        filaCombinada(
            `${dIdx + 1}.  COD. ${docente.cod_docente}   ${(docente.apellidos ?? '').toUpperCase()}, ${(docente.nombres ?? '').toUpperCase()}`,
            c => { c.font = { name: FONT, size: 10, bold: true } }
        )

        const materias = []
        docente.carreras.forEach(carrera => {
            carrera.materias.forEach(materia => materias.push({ carrera, materia }))
        })

        materias.forEach(({ carrera, materia }, mIdx) => {
            const esUltima = mIdx === materias.length - 1

            // Banda de materia
            filaCombinada(
                `${materia.nom_materia}  (Gr. ${materia.grupo})  ·  ${carrera.carrera}  ·  Plan ${carrera.plan}  —  ${materia.subtotal} inscritos`,
                c => {
                    c.font = { name: FONT, size: 9, bold: true, color: { argb: GRIS_TXT } }
                    c.fill = fillGrupo
                    c.alignment = { horizontal: 'left', vertical: 'middle', indent: 1 }
                }
            )

            // Cabecera de columnas
            const head = ws.addRow(['N°', 'CÓDIGO', 'NOMBRE DEL ESTUDIANTE'])
            for (let c = 1; c <= NCOLS; c++) estiloCabecera(head.getCell(c), c > 1)

                // Estudiantes regulares
                ; (materia.inscritos ?? []).forEach((est, i) => filaEstudiante(i + 1, est))

            // Abandonos
            if (materia.subtotal_abandonos) {
                filaCombinada(
                    `Abandono — ${materia.subtotal_abandonos} estudiantes`,
                    c => {
                        c.font = { name: FONT, size: 8.5, bold: true, italic: true, color: { argb: GRIS_TXT } }
                        c.alignment = { horizontal: 'left', vertical: 'middle', indent: 2 }
                    }
                )
                    ; (materia.inscritos_abandonos ?? []).forEach((est, i) => filaEstudiante(i + 1, est))
            }

            // Total del docente (al final de su última materia)
            if (esUltima) {
                const totalTexto = docente.total_abandonos
                    ? `TOTAL INSCRITOS DEL DOCENTE: ${docente.total_inscritos}  (+ ${docente.total_abandonos} abandono)`
                    : `TOTAL INSCRITOS DEL DOCENTE: ${docente.total_inscritos}`
                filaCombinada(totalTexto, c => {
                    c.font = { name: FONT, size: 9, bold: true }
                    c.alignment = { horizontal: 'right', vertical: 'middle' }
                    c.border = { top: lineaFina }
                })
            } else {
                ws.addRow([]) // separación entre materias
            }
        })
    })

    await descargar(wb, `Lista_Inscritos_${anio}_${periodo}.xlsx`)
}

// ════════════════════════════════════════════════════════════════════════════
// 2) APROBADOS/REPROBADOS COMPLETO — una fila por grupo/materia (plano)
// ════════════════════════════════════════════════════════════════════════════
async function generarAprobadosCompleto(filas, anio, periodo) {
    const NCOLS = 13
    const HEAD = 6
    const wb = await crearLibro()
    const ws = crearHoja(wb, 'Aprobados y reprobados', {
        anchos: [6, 12, 10, 8, 38, 14, 42, 8, 9, 8, 8, 9, 9],
        orientacion: 'landscape',
        congelar: HEAD,
        titulosImpresion: `${HEAD}:${HEAD}`,
    })
    encabezado(ws, {
        titulo: 'APROBADOS Y REPROBADOS COMPLETO', anio, periodo,
        nota: 'La columna N = Modalidad Normal , No se incluye inscritos de mesa', nCols: NCOLS,
    })

    const head = ws.getRow(HEAD)
    head.values = ['N°', 'Plan', 'Carrera', 'Nivel', 'Docente', 'Cód. Materia', 'Materia', 'Grupo', 'T.Exam',
        'Insc.', 'Aprob.', 'Reprob.', 'Aband.']
    for (let c = 1; c <= NCOLS; c++) estiloCabecera(head.getCell(c), c === 5 || c === 7)

    filas.forEach((f, i) => {
        filaTabla(ws, [
            i + 1,
            f.PLAN ?? '—', f.CARRERA ?? '—', f.NIVEL ?? '—',
            f.NOMBRE_DOCENTE ?? '—',
            f.MATERIA ?? '—', f.NOMBRE ?? '—',
            f.GRUPO ?? '—', f.TIPO_EXAMEN ?? '—',
            num(f.INSCRITOS), num(f.APROBADOS), num(f.REPROBADOS), num(f.ABANDONOS),
        ], { nCols: NCOLS, izq: [5, 7] })
    })

    // Filtro automático sobre el detalle (no incluye el total)
    ws.autoFilter = {
        from: { row: HEAD, column: 1 },
        to: { row: HEAD + filas.length, column: NCOLS },
    }

    const suma = k => filas.reduce((s, f) => s + num(f[k]), 0)
    filaTabla(ws, [
        '', '', '', '', '', '', 'TOTAL GENERAL', '', '',
        suma('INSCRITOS'), suma('APROBADOS'), suma('REPROBADOS'), suma('ABANDONOS'),
    ], { nCols: NCOLS, bold: true, fill: true, sinFill: 6, lineas: false })

    // "TOTAL GENERAL" alineado a la derecha, como en el PDF
    const rTot = ws.lastRow
    rTot.getCell(7).alignment = { horizontal: 'right', vertical: 'middle' }

    await descargar(wb, `Aprobados_Reprobados_Resumido_${anio}_${periodo}.xlsx`)
}

// ════════════════════════════════════════════════════════════════════════════
// 3) INSCRITOS — SOLO TOTALES (docente × carrera + Aprob./Reprob./Aband./Total)
//    TOTAL = APROBADOS + REPROBADOS + ABANDONOS (igual que el PDF)
// ════════════════════════════════════════════════════════════════════════════
async function generarTotalesDocente(data, anio, periodo) {
    const HEAD = 6
    const carreras = []
    data.forEach(d => d.carreras.forEach(c => {
        if (!carreras.includes(c.carrera)) carreras.push(c.carrera)
    }))
    if (!carreras.length) carreras.push('ADM', 'ECO', 'CCP', 'COM', 'FIN')

    const COL_FIJAS = 3
    const NCOLS = COL_FIJAS + carreras.length + 4
    const divisores = [2 + 1, ...carreras.map((_, k) => COL_FIJAS + 1 + k)] // tras DOCENTE y tras cada carrera

    const wb = await crearLibro()
    const ws = crearHoja(wb, 'Inscritos totales', {
        anchos: [5, 14, 40, ...carreras.map(() => 8), 9, 9, 9, 9],
        congelar: HEAD,
        titulosImpresion: `${HEAD}:${HEAD}`,
    })
    encabezado(ws, {
        titulo: 'INSCRITOS — SOLO TOTALES', anio, periodo,
        nota: 'No incluye alumnos de mesa', nCols: NCOLS,
    })

    const head = ws.getRow(HEAD)
    head.values = ['N°', 'CÓDIGO', 'DOCENTE', ...carreras, 'APROB.', 'REPROB.', 'ABAND.', 'TOTAL']
    for (let c = 1; c <= NCOLS; c++) {
        const cell = head.getCell(c)
        estiloCabecera(cell, c === 3)
        if (divisores.includes(c)) cell.border = { ...cell.border, right: lineaDiv }
    }

    const totalCarrera = c =>
        (c?.subtotal_aprobados ?? 0) + (c?.subtotal_reprobados ?? 0) + (c?.subtotal_abandonos ?? 0)
    const sumCar = (docente, k) => docente.carreras.reduce((s, c) => s + (c[k] ?? 0), 0)

    data.forEach((docente, idx) => {
        const porCarrera = {}
        docente.carreras.forEach(c => { porCarrera[c.carrera] = c })

        const apr = sumCar(docente, 'subtotal_aprobados')
        const rep = sumCar(docente, 'subtotal_reprobados')
        const abd = sumCar(docente, 'subtotal_abandonos')

        filaTabla(ws, [
            idx + 1,
            docente.cod_docente,
            `${docente.apellidos}, ${docente.nombres}`,
            ...carreras.map(c => {
                const v = totalCarrera(porCarrera[c])
                return v > 0 ? v : '—'
            }),
            apr, rep, abd, apr + rep + abd,
        ], { nCols: NCOLS, izq: [3], divisores })
    })

    // Total general
    const gApr = data.reduce((s, d) => s + sumCar(d, 'subtotal_aprobados'), 0)
    const gRep = data.reduce((s, d) => s + sumCar(d, 'subtotal_reprobados'), 0)
    const gAbd = data.reduce((s, d) => s + sumCar(d, 'subtotal_abandonos'), 0)

    filaTabla(ws, [
        '', '', 'TOTAL GENERAL',
        ...carreras.map(c => {
            const s = data.reduce((acc, d) => acc + totalCarrera(d.carreras.find(x => x.carrera === c)), 0)
            return s > 0 ? s : '—'
        }),
        gApr, gRep, gAbd, gApr + gRep + gAbd,
    ], { nCols: NCOLS, izq: [3], divisores, bold: true, fill: true, sinFill: 2, lineas: false })

    await descargar(wb, `Resumen_Totales_Inscritos_${anio}_${periodo}.xlsx`)
}

// ════════════════════════════════════════════════════════════════════════════
// 4) APROBADOS/REPROBADOS RESUMIDO — matriz docente × carrera (Ins/Apr/Rep/Abd)
// ════════════════════════════════════════════════════════════════════════════
async function generarAprobadosResumen(data, anio, periodo) {
    const HEAD = 6 // la cabecera ocupa 2 filas: 6 y 7
    const SUB = ['Ins.', 'Apr.', 'Rep.', 'Abd.']
    const POR_BLOQUE = SUB.length

    const carreras = []
    data.forEach(d => d.carreras.forEach(c => {
        if (!carreras.includes(c.carrera)) carreras.push(c.carrera)
    }))
    if (!carreras.length) carreras.push('ADM', 'ECO', 'CCP', 'COM', 'FIN')

    const COL_FIJAS = 3
    const NCOLS = COL_FIJAS + (carreras.length + 1) * POR_BLOQUE // carreras + TOTAL
    // Divisor tras DOCENTE y al final de cada bloque de carrera (antes de TOTAL)
    const divisores = [COL_FIJAS, ...carreras.map((_, k) => COL_FIJAS + (k + 1) * POR_BLOQUE)]

    const wb = await crearLibro()
    const ws = crearHoja(wb, 'Aprobados y reprobados', {
        anchos: [5, 14, 38, ...carreras.flatMap(() => [6.5, 6.5, 6.5, 6.5]), 7, 7, 7, 7],
        orientacion: 'landscape',
        congelar: HEAD + 1,
        titulosImpresion: `${HEAD}:${HEAD + 1}`,
    })
    encabezado(ws, {
        titulo: 'APROBADOS Y REPROBADOS RESUMEN', anio, periodo,
        nota: 'No incluye alumnos de mesa', nCols: NCOLS,
    })

    // Cabecera de 2 filas: primero valores y estilos, luego combinar celdas
    const textosFila1 = ['N°', 'CÓDIGO', 'DOCENTE']
    for (let c = 1; c <= NCOLS; c++) {
        const c1 = ws.getCell(HEAD, c)
        const c2 = ws.getCell(HEAD + 1, c)
        if (c <= COL_FIJAS) {
            c1.value = textosFila1[c - 1]
        } else {
            const idxBloque = Math.floor((c - COL_FIJAS - 1) / POR_BLOQUE)
            const posEnBloque = (c - COL_FIJAS - 1) % POR_BLOQUE
            if (posEnBloque === 0) c1.value = idxBloque < carreras.length ? carreras[idxBloque] : 'TOTAL'
            c2.value = SUB[posEnBloque]
        }
        estiloCabecera(c1, c === 3)
        estiloCabecera(c2, c === 3)
        if (divisores.includes(c)) {
            c1.border = { ...c1.border, right: lineaDiv }
            c2.border = { ...c2.border, right: lineaDiv }
        }
    }
    for (let c = 1; c <= COL_FIJAS; c++) ws.mergeCells(HEAD, c, HEAD + 1, c)
    for (let b = 0; b <= carreras.length; b++) {
        const c0 = COL_FIJAS + 1 + b * POR_BLOQUE
        ws.mergeCells(HEAD, c0, HEAD, c0 + POR_BLOQUE - 1)
    }
    // El merge copia el estilo del primer celda: reponer el divisor en el último de cada bloque
    divisores.forEach(c => {
        for (const r of [HEAD, HEAD + 1]) {
            const cell = ws.getCell(r, c)
            cell.border = { ...(cell.border || {}), right: lineaDiv }
        }
    })

    // Cuerpo
    data.forEach((docente, idx) => {
        const porCarrera = {}
        docente.carreras.forEach(c => { porCarrera[c.carrera] = c })

        const celdas = carreras.flatMap(c => {
            const info = porCarrera[c]
            const ins = info?.subtotal_inscritos ?? 0
            if (ins <= 0) return ['—', '—', '—', '—']
            return [ins, info?.subtotal_aprobados ?? 0, info?.subtotal_reprobados ?? 0, info?.subtotal_abandonos ?? 0]
        })

        filaTabla(ws, [
            idx + 1,
            docente.cod_docente,
            `${docente.apellidos}, ${docente.nombres}`,
            ...celdas,
            docente.total_inscritos ?? 0,
            docente.total_aprobados ?? 0,
            docente.total_reprobados ?? 0,
            docente.total_abandonos ?? 0,
        ], { nCols: NCOLS, izq: [3], divisores })
    })

    // Total general
    const sumCarrera = (c, k) =>
        data.reduce((s, d) => s + (d.carreras.find(x => x.carrera === c)?.[k] ?? 0), 0)
    const sumDoc = k => data.reduce((s, d) => s + (d[k] ?? 0), 0)

    filaTabla(ws, [
        '', '', 'TOTAL GENERAL',
        ...carreras.flatMap(c => {
            const ins = sumCarrera(c, 'subtotal_inscritos')
            if (ins <= 0) return ['—', '—', '—', '—']
            return [ins, sumCarrera(c, 'subtotal_aprobados'), sumCarrera(c, 'subtotal_reprobados'), sumCarrera(c, 'subtotal_abandonos')]
        }),
        sumDoc('total_inscritos'), sumDoc('total_aprobados'), sumDoc('total_reprobados'), sumDoc('total_abandonos'),
    ], { nCols: NCOLS, izq: [3], divisores, bold: true, fill: true, sinFill: 2, lineas: false })

    await descargar(wb, `Resumen_Aprobados_Reprobados_${anio}_${periodo}.xlsx`)
}

// ════════════════════════════════════════════════════════════════════════════
// Composable público
// ════════════════════════════════════════════════════════════════════════════
export function useExcelReportes() {
    const generandoExcel = ref(false)

    async function ejecutar(fn, data, ...args) {
        if (!data?.length) return
        generandoExcel.value = true
        try {
            await fn(data, ...args)
        } catch (e) {
            console.error('No se pudo generar el Excel', e)
            throw e
        } finally {
            generandoExcel.value = false
        }
    }

    return {
        generandoExcel,
        exportarExcelLista: (data, anio, periodo) => ejecutar(generarListaCompleta, data, anio, periodo),
        exportarExcelAprobadosCompleto: (filas, anio, periodo) => ejecutar(generarAprobadosCompleto, filas, anio, periodo),
        exportarExcelTotales: (data, anio, periodo) => ejecutar(generarTotalesDocente, data, anio, periodo),
        exportarExcelAprobadosResumen: (data, anio, periodo) => ejecutar(generarAprobadosResumen, data, anio, periodo),
    }
}