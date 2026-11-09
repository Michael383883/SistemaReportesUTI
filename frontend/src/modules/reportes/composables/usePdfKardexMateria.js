import { jsPDF } from 'jspdf'
import autoTable from 'jspdf-autotable'

const ROSA = [253, 242, 248]

/**
 * Genera el PDF del kardex con las secciones tal como se ven en pantalla.
 * Devuelve { url, filename } (la url es un blob: hay que liberarla con URL.revokeObjectURL).
 */
export function generarPDFKardexMateria(secciones, opts = {}) {
    const {
        titulo = 'Kardex de materias',
        subtitulo = '',
        conAsignacion = true,
        soloSinDocumento = false,
    } = opts

    const doc = new jsPDF({ orientation: 'landscape', unit: 'pt', format: 'a4' })
    const ancho = doc.internal.pageSize.getWidth()
    const alto = doc.internal.pageSize.getHeight()
    const margen = 28

    const blanco = conAsignacion ? null : ''
    const val = (v) => (v === null || v === undefined || v === '' ? '—' : String(v))

    secciones.forEach((s, idx) => {
        if (idx > 0) doc.addPage()

        const body = s.filas.map((f) => {
            const fill = f.es_hija ? { fillColor: ROSA } : {}
            const celda = (content, extra = {}) => ({ content, styles: { ...fill, ...extra } })

            // Horario: una línea por bloque (en las compartidas va vacío)
            const horario = Array.isArray(f.horario) && f.horario.length
                ? f.horario.join('\n')
                : '—'

            const fila = []
            if (f.nro !== null) {
                fila.push({
                    content: String(f.nro),
                    rowSpan: f.rowspan || 1,
                    styles: { halign: 'center', valign: 'middle', fontStyle: 'bold' },
                })
            }
            fila.push(
                celda(`${f.plan_abrev}\n${f.cod_plan}`, { halign: 'center', fontStyle: 'bold' }),
                celda(val(f.cod_materia), { halign: 'center' }),
                celda(val(f.nombre_materia)),
                celda(val(f.grupo), { halign: 'center' }),
                celda(val(f.ch), { halign: 'center' }),
                celda(conAsignacion ? val(f.docente_actual) : blanco),
                celda(horario),
                celda(String(f.inscritos ?? 0), { halign: 'center', fontStyle: 'bold' }),
                celda(f.compartido || '', { fontStyle: 'bold', textColor: [190, 24, 93] }),
                celda(conAsignacion ? val(f.resolucion) : blanco),
                celda(conAsignacion ? val(f.designacion) : blanco),
                celda(val(f.tipo_ingreso), { halign: 'center', fontStyle: 'bold' }),
            )
            return fila
        })

        autoTable(doc, {
            head: [[
                'Nº', 'Plan', 'SIS-Mat', 'Materia', 'Grupo', 'CH',
                'Docente actual', 'Día - Horario - Aula', 'Insc.',
                'Compartido', 'Resolución', 'Designación', 'Tipo',
            ]],
            body,
            startY: 78,
            margin: { top: 78, left: margen, right: margen, bottom: 34 },
            theme: 'grid',
            styles: { fontSize: 7, cellPadding: 3, lineColor: [229, 231, 235], lineWidth: 0.4, overflow: 'linebreak' },
            headStyles: { fillColor: [243, 244, 246], textColor: [75, 85, 99], fontStyle: 'bold', halign: 'center' },
            columnStyles: {
                0: { cellWidth: 24 },   // Nº
                1: { cellWidth: 40 },   // Plan
                2: { cellWidth: 44 },   // SIS-Mat
                4: { cellWidth: 30 },   // Grupo
                5: { cellWidth: 24 },   // CH
                7: { cellWidth: 82 },   // Día - Horario - Aula
                8: { cellWidth: 30 },   // Insc.
                12: { cellWidth: 40 },  // Tipo
            },
            // Encabezado repetido en cada página de la carrera
            didDrawPage: () => {
                doc.setFont('helvetica', 'bold')
                doc.setFontSize(13)
                doc.setTextColor(17, 24, 39)
                doc.text(titulo, margen, 34)

                doc.setFont('helvetica', 'normal')
                doc.setFontSize(9)
                doc.setTextColor(107, 114, 128)
                if (subtitulo) doc.text(subtitulo, margen, 48)

                doc.setFont('helvetica', 'bold')
                doc.setFontSize(10)
                doc.setTextColor(17, 24, 39)
                doc.text(`Carrera ${s.carrera}  (${s.total_materias} materias)`, margen, 66)
            },
        })
    })

    // Numeración de páginas
    const total = doc.getNumberOfPages()
    for (let i = 1; i <= total; i++) {
        doc.setPage(i)
        doc.setFont('helvetica', 'normal')
        doc.setFontSize(8)
        doc.setTextColor(156, 163, 175)
        doc.text(`Página ${i} de ${total}`, ancho - margen, alto - 14, { align: 'right' })
    }

    const sufijo = soloSinDocumento ? '_sin_documento' : ''
    const filename = `kardex_materias_${opts.anio || ''}_${opts.periodo || ''}${sufijo}.pdf`
    const url = URL.createObjectURL(doc.output('blob'))
    return { url, filename }
}