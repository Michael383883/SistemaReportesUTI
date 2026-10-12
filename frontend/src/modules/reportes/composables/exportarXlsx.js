// Genera y descarga un .xlsx real SIN dependencias externas, con el formato
// del "Reporte de Docentes con Título" (cabecera institucional, nota + fecha,
// tabla con filtros y total de registros).
//
// Uso:
//   exportarReporteTitulos({
//     nombre: 'archivo.xlsx',
//     anio: 2026, periodo: 1,
//     encabezados: ['Nº', 'Código', ...],
//     filas: [[1, '1991...', ...], ...],     // la 1.ª columna (Nº) va numérica
//     columnasCentradas: [0, 1],             // índices centrados (Nº y Código)
//   })

const enc = new TextEncoder()

// ═════════════════════════ ZIP (sin compresión) ═════════════════════════
const CRC_TABLE = (() => {
    const t = new Uint32Array(256)
    for (let n = 0; n < 256; n++) {
        let c = n
        for (let k = 0; k < 8; k++) c = c & 1 ? 0xedb88320 ^ (c >>> 1) : c >>> 1
        t[n] = c >>> 0
    }
    return t
})()

function crc32(bytes) {
    let c = 0xffffffff
    for (let i = 0; i < bytes.length; i++) c = CRC_TABLE[(c ^ bytes[i]) & 0xff] ^ (c >>> 8)
    return (c ^ 0xffffffff) >>> 0
}

function zip(files) {
    const partes = []
    const central = []
    let offset = 0

    for (const f of files) {
        const nombre = enc.encode(f.name)
        const crc = crc32(f.data)
        const len = f.data.length

        const loc = new DataView(new ArrayBuffer(30))
        loc.setUint32(0, 0x04034b50, true)
        loc.setUint16(4, 20, true)
        loc.setUint16(6, 0x0800, true) // nombres en UTF-8
        loc.setUint16(8, 0, true) // sin compresión
        loc.setUint16(12, 0x21, true) // fecha 1980-01-01
        loc.setUint32(14, crc, true)
        loc.setUint32(18, len, true)
        loc.setUint32(22, len, true)
        loc.setUint16(26, nombre.length, true)
        partes.push(new Uint8Array(loc.buffer), nombre, f.data)

        const cen = new DataView(new ArrayBuffer(46))
        cen.setUint32(0, 0x02014b50, true)
        cen.setUint16(4, 20, true)
        cen.setUint16(6, 20, true)
        cen.setUint16(8, 0x0800, true)
        cen.setUint16(14, 0x21, true)
        cen.setUint32(16, crc, true)
        cen.setUint32(20, len, true)
        cen.setUint32(24, len, true)
        cen.setUint16(28, nombre.length, true)
        cen.setUint32(42, offset, true)
        central.push(new Uint8Array(cen.buffer), nombre)

        offset += 30 + nombre.length + len
    }

    const tamCentral = central.reduce((s, c) => s + c.length, 0)
    const fin = new DataView(new ArrayBuffer(22))
    fin.setUint32(0, 0x06054b50, true)
    fin.setUint16(8, files.length, true)
    fin.setUint16(10, files.length, true)
    fin.setUint32(12, tamCentral, true)
    fin.setUint32(16, offset, true)

    return new Blob([...partes, ...central, new Uint8Array(fin.buffer)], {
        type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    })
}

// ═════════════════════════ Helpers XML ═════════════════════════
const escXml = (v) =>
    String(v ?? '')
        // eslint-disable-next-line no-control-regex
        .replace(/[\u0000-\u0008\u000B\u000C\u000E-\u001F]/g, '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')

function letraColumna(i) {
    let s = ''
    let n = i + 1
    while (n > 0) {
        const r = (n - 1) % 26
        s = String.fromCharCode(65 + r) + s
        n = Math.floor((n - 1) / 26)
    }
    return s
}

// Índices de estilo (deben coincidir con <cellXfs> de STYLES)
const S = {
    NORMAL: 0,
    TITULO: 1, // título principal
    SUBTITULO: 2, // facultad / gestión
    UNIV: 3, // universidad (2 líneas)
    NOTA: 4, // nota (con línea inferior)
    FECHA: 5, // fecha (con línea inferior)
    ENC: 6, // encabezado de tabla
    DATO: 7,
    DATO_C: 8,
    DATO_ULT: 9, // última fila (borde grueso)
    DATO_C_ULT: 10,
    TOTAL: 11,
}

const STYLES =
    '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
    '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
    '<fonts count="7">' +
    '<font><sz val="11"/><name val="Calibri"/></font>' + // 0 normal
    '<font><b/><sz val="14"/><name val="Calibri"/></font>' + // 1 título
    '<font><b/><sz val="11"/><name val="Calibri"/></font>' + // 2 negrita 11
    '<font><b/><sz val="10"/><name val="Calibri"/></font>' + // 3 universidad
    '<font><sz val="8"/><name val="Calibri"/></font>' + // 4 nota / fecha
    '<font><b/><sz val="10"/><name val="Calibri"/></font>' + // 5 total
    '<font><sz val="10"/><name val="Calibri"/></font>' + // 6 datos
    '</fonts>' +
    '<fills count="2">' +
    '<fill><patternFill patternType="none"/></fill>' +
    '<fill><patternFill patternType="gray125"/></fill>' +
    '</fills>' +
    '<borders count="4">' +
    '<border><left/><right/><top/><bottom/><diagonal/></border>' + // 0 sin borde
    '<border><left/><right/><top/><bottom style="thin"><color rgb="FF000000"/></bottom><diagonal/></border>' + // 1 fino negro
    '<border><left/><right/><top/><bottom style="thin"><color rgb="FF7F7F7F"/></bottom><diagonal/></border>' + // 2 fino gris
    '<border><left/><right/><top/><bottom style="medium"><color rgb="FF000000"/></bottom><diagonal/></border>' + // 3 grueso
    '</borders>' +
    '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>' +
    '<cellXfs count="12">' +
    '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>' + // 0
    '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' + // 1
    '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' + // 2
    '<xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' + // 3
    '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>' + // 4
    '<xf numFmtId="0" fontId="4" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>' + // 5
    '<xf numFmtId="0" fontId="2" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>' + // 6
    '<xf numFmtId="0" fontId="6" fillId="0" borderId="2" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>' + // 7
    '<xf numFmtId="0" fontId="6" fillId="0" borderId="2" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' + // 8
    '<xf numFmtId="0" fontId="6" fillId="0" borderId="3" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>' + // 9
    '<xf numFmtId="0" fontId="6" fillId="0" borderId="3" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' + // 10
    '<xf numFmtId="0" fontId="5" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>' + // 11
    '</cellXfs>' +
    '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>' +
    '</styleSheet>'

const nombreHoja = (s) => String(s || 'Hoja1').replace(/[:\\/?*[\]]/g, ' ').slice(0, 31)

// Fecha como la del reporte original: 12/10/2026 08:06:48 PM
function fechaHora(d = new Date()) {
    const p = (n) => String(n).padStart(2, '0')
    const h = d.getHours()
    const h12 = h % 12 === 0 ? 12 : h % 12
    return `${p(d.getDate())}/${p(d.getMonth() + 1)}/${d.getFullYear()} ${p(h12)}:${p(d.getMinutes())}:${p(d.getSeconds())} ${h >= 12 ? 'PM' : 'AM'}`
}

// ═════════════════════════ Hoja ═════════════════════════
function construirHoja({ anio, periodo, encabezados, filas, columnasCentradas, universidad, titulo, facultad, fecha }) {
    const nCols = encabezados.length
    const ultima = nCols - 1
    const L = letraColumna

    // Mapa de filas: { r: número de fila, alto, celdas: Map(colIndex -> {v, s}) }
    const mapa = []
    const fila = (r, alto) => {
        const f = { r, alto, celdas: new Map() }
        mapa.push(f)
        return f
    }
    const put = (f, c, v, s) => f.celdas.set(c, { v, s })

    // Posiciones fijas del bloque de cabecera
    const colTitulo = Math.min(4, ultima) // el título arranca en la columna E (como el original)
    const merges = []

    // Filas 1-3: universidad (izquierda) + título / facultad / gestión (centro)
    const f1 = fila(1, 24)
    const f2 = fila(2, 20)
    const f3 = fila(3, 20)
    put(f1, 0, universidad, S.UNIV)
    merges.push(`A1:${L(Math.min(3, ultima))}3`)
    put(f1, colTitulo, titulo, S.TITULO)
    put(f2, colTitulo, facultad, S.SUBTITULO)
    put(f3, colTitulo, `Gestión Académica ${periodo}/${anio}`, S.SUBTITULO)
    merges.push(`${L(colTitulo)}1:${L(ultima)}1`)
    merges.push(`${L(colTitulo)}2:${L(ultima)}2`)
    merges.push(`${L(colTitulo)}3:${L(ultima)}3`)

    // Fila 4: nota (izquierda) + fecha (derecha), con línea inferior en todo el ancho
    const f4 = fila(4, 16)
    const corte = Math.min(3, ultima) // la nota ocupa A:D
    for (let c = 0; c <= ultima; c++) put(f4, c, '', c <= corte ? S.NOTA : S.FECHA)
    put(f4, 0, 'Nota: Este es un documento generado automáticamente por el sistema.', S.NOTA)
    put(f4, corte + 1, fecha, S.FECHA)
    merges.push(`A4:${L(corte)}4`)
    if (corte + 1 < ultima) merges.push(`${L(corte + 1)}4:${L(ultima)}4`)

    // Fila 5: separación
    fila(5, 10)

    // Fila 6: encabezado de tabla
    const fh = fila(6, 20)
    encabezados.forEach((h, c) => put(fh, c, h, S.ENC))

    // Filas de datos
    const inicio = 7
    filas.forEach((datos, i) => {
        const f = fila(inicio + i, 16)
        const esUlt = i === filas.length - 1
        datos.forEach((v, c) => {
            const centrada = columnasCentradas.includes(c)
            const s = esUlt ? (centrada ? S.DATO_C_ULT : S.DATO_ULT) : centrada ? S.DATO_C : S.DATO
            put(f, c, v, s)
        })
    })

    // Total de registros
    const rTotal = inicio + filas.length + 1
    const ft = fila(rTotal, 18)
    const desde = Math.min(4, ultima)
    put(ft, desde, `Total de registros: ${filas.length}`, S.TOTAL)
    if (desde < ultima) merges.push(`${L(desde)}${rTotal}:${L(ultima)}${rTotal}`)

    // Anchos de columna según el contenido de la tabla
    const anchos = encabezados.map((h, c) => {
        const max = Math.max(String(h).length, ...filas.map(f => String(f[c] ?? '').length))
        return Math.min(60, Math.max(c === 0 ? 5 : 11, max + 3))
    })
    const cols = anchos
        .map((w, i) => `<col min="${i + 1}" max="${i + 1}" width="${w}" customWidth="1"/>`)
        .join('')

    // XML de filas
    const rowsXml = mapa
        .map(f => {
            const cs = [...f.celdas.entries()]
                .sort((a, b) => a[0] - b[0])
                .map(([c, { v, s }]) => {
                    const ref = `${L(c)}${f.r}`
                    if (typeof v === 'number') return `<c r="${ref}" s="${s}"><v>${v}</v></c>`
                    if (v === '' || v == null) return `<c r="${ref}" s="${s}"/>`
                    return `<c r="${ref}" s="${s}" t="inlineStr"><is><t xml:space="preserve">${escXml(v)}</t></is></c>`
                })
                .join('')
            return `<row r="${f.r}" ht="${f.alto}" customHeight="1">${cs}</row>`
        })
        .join('')

    const rangoFiltro = `A6:${L(ultima)}${inicio + filas.length - 1}`

    return (
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
        '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' +
        '<sheetPr><pageSetUpPr fitToPage="1"/></sheetPr>' +
        '<sheetViews><sheetView showGridLines="0" workbookViewId="0"/></sheetViews>' +
        `<cols>${cols}</cols>` +
        `<sheetData>${rowsXml}</sheetData>` +
        `<autoFilter ref="${rangoFiltro}"/>` +
        `<mergeCells count="${merges.length}">${merges.map(m => `<mergeCell ref="${m}"/>`).join('')}</mergeCells>` +
        '<pageMargins left="0.5" right="0.5" top="0.6" bottom="0.6" header="0.3" footer="0.3"/>' +
        '<pageSetup paperSize="9" orientation="landscape" fitToWidth="1" fitToHeight="0"/>' +
        '</worksheet>'
    )
}

// ═════════════════════════ Libro ═════════════════════════
export function construirXlsx(opts) {
    const {
        hoja = 'Reporte',
        anio,
        periodo,
        encabezados,
        filas,
        columnasCentradas = [0, 1],
        universidad = 'UNIVERSIDAD MAYOR DE\nSAN SIMÓN',
        titulo = 'REPORTE DE DOCENTES CON TÍTULO',
        facultad = 'FACULTAD DE CIENCIAS ECONÓMICAS',
        fecha = fechaHora(),
    } = opts

    const nombreH = nombreHoja(hoja)
    const ultima = letraColumna(encabezados.length - 1)
    const filtroRef = `'${nombreH.replace(/'/g, "''")}'!$A$6:$${ultima}$${6 + filas.length}`

    const archivos = [
        {
            name: '[Content_Types].xml',
            data: enc.encode(
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
                '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' +
                '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' +
                '<Default Extension="xml" ContentType="application/xml"/>' +
                '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' +
                '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' +
                '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' +
                '</Types>'
            ),
        },
        {
            name: '_rels/.rels',
            data: enc.encode(
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
                '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' +
                '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' +
                '</Relationships>'
            ),
        },
        {
            name: 'xl/workbook.xml',
            data: enc.encode(
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
                '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' +
                `<sheets><sheet name="${escXml(nombreH)}" sheetId="1" r:id="rId1"/></sheets>` +
                `<definedNames><definedName name="_xlnm._FilterDatabase" localSheetId="0" hidden="1">${escXml(filtroRef)}</definedName></definedNames>` +
                '</workbook>'
            ),
        },
        {
            name: 'xl/_rels/workbook.xml.rels',
            data: enc.encode(
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' +
                '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' +
                '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' +
                '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>' +
                '</Relationships>'
            ),
        },
        { name: 'xl/styles.xml', data: enc.encode(STYLES) },
        {
            name: 'xl/worksheets/sheet1.xml',
            data: enc.encode(
                construirHoja({ anio, periodo, encabezados, filas, columnasCentradas, universidad, titulo, facultad, fecha })
            ),
        },
    ]
    return zip(archivos)
}

export function exportarReporteTitulos({ nombre = 'reporte.xlsx', ...opts }) {
    const blob = construirXlsx(opts)
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = nombre
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    setTimeout(() => URL.revokeObjectURL(url), 1000)
}