/**
 * exportExcelService.js
 * Exportación a Excel – Estudiantes en Talleres
 *
 * Requiere: xlsx (SheetJS)
 *   npm install xlsx
 *   o  import * as XLSX from 'xlsx'
 */

import * as XLSX from 'xlsx'

// ─── helpers ────────────────────────────────────────────────────────────────

const PLANES = {
    '109401': 'Lic. en Administración de Empresas',
    '125091': 'Licenciatura en Ingeniería Comercial',
    '089801': 'Licenciatura en Contaduría Pública',
    '126091': 'Licenciatura en Ingeniería Financiera',
    '059801': 'Licenciatura en Economía',
}

const nombrePlan = (codigo) => PLANES[codigo] || codigo

/**
 * Devuelve la fecha actual formateada  dd/mm/yyyy hh:mm
 */
const fechaHora = () => {
    const d = new Date()
    const pad = (n) => String(n).padStart(2, '0')
    return (
        `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()} ` +
        `${pad(d.getHours())}:${pad(d.getMinutes())}`
    )
}

/**
 * Aplica ancho automático a las columnas de una hoja SheetJS.
 * @param {XLSX.WorkSheet} ws
 * @param {Array<Array>}   rows  – misma matriz enviada a aoa_to_sheet
 */
function autoAncho(ws, rows) {
    const anchos = []
    rows.forEach((row) => {
        row.forEach((cel, c) => {
            const len = cel == null ? 0 : String(cel).length
            anchos[c] = Math.max(anchos[c] ?? 8, len + 2)
        })
    })
    ws['!cols'] = anchos.map((w) => ({ wch: Math.min(w, 50) }))
}

/**
 * Abre una vista previa HTML en una pestaña nueva.
 * IMPORTANTE: un .xlsx no se puede "mostrar" en el navegador (no es un
 * formato que el navegador sepa renderizar inline como el PDF), por eso
 * para el modo 'ver' generamos una tabla HTML equivalente en vez de
 * descargar el archivo.
 *
 * @param {string} tituloPestana
 * @param {Array<{titulo: string, ws: XLSX.WorkSheet}>} hojas
 */
function abrirVistaPrevia(tituloPestana, hojas) {
    const ventana = window.open('', '_blank')

    if (!ventana) {
        // El navegador bloqueó el popup: avisamos en vez de fallar en silencio.
        alert('Tu navegador bloqueó la ventana de vista previa. Habilita las ventanas emergentes para este sitio e inténtalo de nuevo.')
        return
    }

    const bloques = hojas
        .map(({ titulo, ws }) => {
            const tablaHtml = XLSX.utils.sheet_to_html(ws, { editable: false, header: '', footer: '' })
            return `
                <section class="hoja">
                    <h2>${titulo}</h2>
                    ${tablaHtml}
                </section>
            `
        })
        .join('<hr class="separador" />')

    ventana.document.write(`
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="utf-8" />
            <title>${tituloPestana}</title>
            <style>
                body { font-family: Arial, Helvetica, sans-serif; padding: 24px; color: #1e293b; }
                h2 { font-size: 14px; text-transform: uppercase; letter-spacing: .04em; color: #334155; margin: 24px 0 8px; }
                table { border-collapse: collapse; width: 100%; font-size: 12px; margin-bottom: 12px; }
                td, th { border: 1px solid #e2e8f0; padding: 6px 10px; text-align: left; white-space: nowrap; }
                tr:first-child td, tr:nth-child(-n+5) td { border: none; }
                .separador { border: none; border-top: 1px solid #e2e8f0; margin: 24px 0; }
                .toolbar { margin-bottom: 16px; }
                .toolbar button {
                    background: #2563eb; color: #fff; border: none; border-radius: 8px;
                    padding: 8px 16px; font-size: 13px; font-weight: 600; cursor: pointer;
                }
                .toolbar button:hover { background: #1d4ed8; }
            </style>
        </head>
        <body>
            <div class="toolbar">
                <button onclick="window.print()">Imprimir / Guardar como PDF</button>
            </div>
            ${bloques}
        </body>
        </html>
    `)
    ventana.document.close()
}

// ─── Exportar Normal ─────────────────────────────────────────────────────────

/**
 * Exportación simple (una fila por estudiante, sin datos de contacto).
 *
 * @param {Array}  estudiantes  – lista completa (ya filtrada si se desea)
 * @param {Object} [opciones]
 * @param {string} [opciones.anio]
 * @param {string} [opciones.periodo]
 * @param {'ver'|'descargar'} [opciones.modo]  – 'ver' abre vista previa, 'descargar' baja el .xlsx
 */
export function exportarExcelNormal(estudiantes, opciones = {}) {
    const { anio = '2026', periodo = '1', modo = 'descargar' } = opciones

    const encabezado = [
        'N°',
        'Código',
        'Nombre del Estudiante',
        'Carrera',
        'Materia',
        'Grupo',
        'Docente',
    ]

    const filasMeta = [
        ['UNIVERSIDAD MAYOR DE SAN SIMÓN'],
        ['FACULTAD DE CIENCIAS ECONÓMICAS'],
        [`Talleres – Gestión Académica ${periodo}/${anio}`],
        [`Generado: ${fechaHora()}`],
        [],
        encabezado,
    ]

    const filasDatos = estudiantes.map((est, i) => [
        i + 1,
        est.cod_estudiante || est.codigo,
        est.nom_estudiante,
        nombrePlan(est.plan),
        est.nom_materia,
        est.grupo,
        est.docente,
    ])

    const filas = [...filasMeta, ...filasDatos]

    const ws = XLSX.utils.aoa_to_sheet(filas)

    // Combinar celdas del título (columnas A-G, filas 1-4)
    ws['!merges'] = [
        { s: { r: 0, c: 0 }, e: { r: 0, c: 6 } },
        { s: { r: 1, c: 0 }, e: { r: 1, c: 6 } },
        { s: { r: 2, c: 0 }, e: { r: 2, c: 6 } },
        { s: { r: 3, c: 0 }, e: { r: 3, c: 6 } },
    ]

    autoAncho(ws, filas)

    // ── Modo "ver": vista previa en pestaña nueva, sin descargar nada ──
    if (modo === 'ver') {
        abrirVistaPrevia(`Vista previa – Talleres ${periodo}/${anio}`, [
            { titulo: `Estudiantes – Gestión ${periodo}/${anio}`, ws },
        ])
        return
    }

    // ── Modo "descargar": comportamiento original ──
    const wb = XLSX.utils.book_new()
    XLSX.utils.book_append_sheet(wb, ws, 'Estudiantes')

    XLSX.writeFile(wb, `Talleres_Estudiantes_${anio}_P${periodo}.xlsx`)
}

// ─── Exportar Detalle (con contacto) ─────────────────────────────────────────

/**
 * Exportación detallada: incluye correo y celular, además agrupa
 * en hojas separadas por materia.
 *
 * @param {Array}  estudiantes
 * @param {Object} [opciones]
 * @param {string} [opciones.anio]
 * @param {string} [opciones.periodo]
 * @param {'ver'|'descargar'} [opciones.modo]  – 'ver' abre vista previa, 'descargar' baja el .xlsx
 */
export function exportarExcelDetalle(estudiantes, opciones = {}) {
    const { anio = '2026', periodo = '1', modo = 'descargar' } = opciones

    // ── Hoja 1: Todos los estudiantes ──
    const encabezado = [
        'N°',
        'Código',
        'Nombre del Estudiante',
        'Carrera',
        'Materia',
        'Código Materia',
        'Grupo',
        'Docente',
        'Correo',
        'Celular',
    ]

    const filasMeta = [
        ['UNIVERSIDAD MAYOR DE SAN SIMÓN'],
        ['FACULTAD DE CIENCIAS ECONÓMICAS'],
        [`Talleres – Detalle con Contacto – Gestión ${periodo}/${anio}`],
        [`Generado: ${fechaHora()}`],
        [],
        encabezado,
    ]

    const filasTodos = estudiantes.map((est, i) => [
        i + 1,
        est.cod_estudiante || est.codigo,
        est.nom_estudiante,
        nombrePlan(est.plan),
        est.nom_materia,
        est.materia,
        est.grupo,
        est.docente,
        est.correo || '—',
        est.celular || '—',
    ])

    const wsTodos = XLSX.utils.aoa_to_sheet([...filasMeta, ...filasTodos])
    wsTodos['!merges'] = [
        { s: { r: 0, c: 0 }, e: { r: 0, c: 9 } },
        { s: { r: 1, c: 0 }, e: { r: 1, c: 9 } },
        { s: { r: 2, c: 0 }, e: { r: 2, c: 9 } },
        { s: { r: 3, c: 0 }, e: { r: 3, c: 9 } },
    ]
    autoAncho(wsTodos, [...filasMeta, ...filasTodos])

    // ── Hojas por materia (se usan tanto para vista previa como descarga) ──
    const porMateria = estudiantes.reduce((acc, est) => {
        const key = `${est.materia}_${est.grupo}`
        if (!acc[key]) {
            acc[key] = { nombre: est.nom_materia, grupo: est.grupo, estudiantes: [] }
        }
        acc[key].estudiantes.push(est)
        return acc
    }, {})

    const hojasPorMateria = Object.values(porMateria).map(({ nombre, grupo, estudiantes: lista }) => {
        const enc = ['N°', 'Código', 'Nombre', 'Carrera', 'Grupo', 'Docente', 'Correo', 'Celular']

        const meta = [
            ['UNIVERSIDAD MAYOR DE SAN SIMÓN'],
            ['FACULTAD DE CIENCIAS ECONÓMICAS'],
            [nombre],
            [`Gestión ${periodo}/${anio}   ·   Generado: ${fechaHora()}`],
            [],
            enc,
        ]

        const filas = lista.map((est, i) => [
            i + 1,
            est.cod_estudiante || est.codigo,
            est.nom_estudiante,
            nombrePlan(est.plan),
            est.grupo,
            est.docente,
            est.correo || '—',
            est.celular || '—',
        ])

        const ws = XLSX.utils.aoa_to_sheet([...meta, ...filas])
        ws['!merges'] = [
            { s: { r: 0, c: 0 }, e: { r: 0, c: 7 } },
            { s: { r: 1, c: 0 }, e: { r: 1, c: 7 } },
            { s: { r: 2, c: 0 }, e: { r: 2, c: 7 } },
            { s: { r: 3, c: 0 }, e: { r: 3, c: 7 } },
        ]
        autoAncho(ws, [...meta, ...filas])

        return { nombre: `${nombre} G${grupo}`, grupo, ws }
    })

    // ── Modo "ver": vista previa en pestaña nueva (hoja "Todos" + una por materia) ──
    if (modo === 'ver') {
        const hojasPreview = [
            { titulo: 'Todos los estudiantes', ws: wsTodos },
            ...hojasPorMateria.map((h) => ({ titulo: h.nombre, ws: h.ws })),
        ]
        abrirVistaPrevia(`Vista previa – Talleres (detalle) ${periodo}/${anio}`, hojasPreview)
        return
    }

    // ── Modo "descargar": comportamiento original ──
    const wb = XLSX.utils.book_new()
    XLSX.utils.book_append_sheet(wb, wsTodos, 'Todos')

    const nombresUsados = new Set()
    hojasPorMateria.forEach(({ nombre, ws }) => {
        // Nombre de hoja: materia + grupo (máx 31 chars). Sufijo si colisiona.
        let nombreHoja = nombre.substring(0, 31)
        let sufijo = 2
        while (nombresUsados.has(nombreHoja)) {
            nombreHoja = nombre.substring(0, 28) + `-${sufijo}`
            sufijo++
        }
        nombresUsados.add(nombreHoja)
        XLSX.utils.book_append_sheet(wb, ws, nombreHoja)
    })

    XLSX.writeFile(wb, `Talleres_Detalle_Contacto_${anio}_P${periodo}.xlsx`)
}