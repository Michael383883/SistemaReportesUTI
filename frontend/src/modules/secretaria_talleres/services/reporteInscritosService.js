/**
 * reporteInscritosService.js
 * Reporte PDF – Alumnos Inscritos en Talleres
 *
 * Requiere: jsPDF + jspdf-autotable
 *   npm install jspdf jspdf-autotable
 */

import { jsPDF } from 'jspdf'
import autoTable from 'jspdf-autotable'

// ─── Constantes de diseño ────────────────────────────────────────────────────

const FONT = 'helvetica'

const COLOR = {
    negro: [0, 0, 0],
    grisOscuro: [50, 50, 50],
    grisMedio: [100, 100, 100],
    grisSuave: [200, 200, 200],
    blanco: [255, 255, 255],
    fondoFila: [245, 245, 245],
}

const PLANES = {
    '109401': 'Lic. Administración de Empresas',
    '125091': 'Lic. Ingeniería Comercial',
    '089801': 'Lic. Contaduría Pública',
    '126091': 'Lic. Ingeniería Financiera',
    '059801': 'Lic. Economía',
}

const nombrePlan = (cod) => PLANES[cod] || cod

// ─── helpers ─────────────────────────────────────────────────────────────────

const pad = (n) => String(n).padStart(2, '0')

const fechaLarga = () => {
    const d = new Date()
    const meses = [
        'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
        'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre',
    ]
    return `${d.getDate()} de ${meses[d.getMonth()]} de ${d.getFullYear()}`
}

const horaActual = () => {
    const d = new Date()
    return `${pad(d.getHours())}:${pad(d.getMinutes())}`
}

// ─── Encabezado y pie (reutilizable en cada página) ─────────────────────────

function dibujarEncabezado(doc, { anio, periodo, titulo, subtitulo }) {
    const W = doc.internal.pageSize.getWidth()

    // Línea superior
    doc.setDrawColor(...COLOR.grisOscuro)
    doc.setLineWidth(0.5)
    doc.line(14, 12, W - 14, 12)

    // Institución (izquierda)
    doc.setFont(FONT, 'bold')
    doc.setFontSize(8)
    doc.setTextColor(...COLOR.grisOscuro)
    doc.text('UNIVERSIDAD MAYOR DE SAN SIMÓN', 14, 18)
    doc.setFont(FONT, 'normal')
    doc.setFontSize(7)
    doc.text('Facultad de Ciencias Económicas', 14, 22)

    // Título central
    doc.setFont(FONT, 'bold')
    doc.setFontSize(11)
    doc.setTextColor(...COLOR.negro)
    doc.text(titulo, W / 2, 17, { align: 'center' })

    doc.setFont(FONT, 'normal')
    doc.setFontSize(8)
    doc.setTextColor(...COLOR.grisMedio)
    doc.text(subtitulo, W / 2, 22, { align: 'center' })

    // Gestión (derecha)
    doc.setFont(FONT, 'bold')
    doc.setFontSize(8)
    doc.setTextColor(...COLOR.grisOscuro)
    doc.text(`Gestión ${periodo}/${anio}`, W - 14, 18, { align: 'right' })
    doc.setFont(FONT, 'normal')
    doc.setFontSize(7)
    doc.setTextColor(...COLOR.grisMedio)
    doc.text(`Generado: ${fechaLarga()} – ${horaActual()}`, W - 14, 22, { align: 'right' })

    // Línea inferior del encabezado
    doc.setDrawColor(...COLOR.grisSuave)
    doc.setLineWidth(0.3)
    doc.line(14, 25, W - 14, 25)
}

function dibujarPie(doc, totalPaginas) {
    const W = doc.internal.pageSize.getWidth()
    const H = doc.internal.pageSize.getHeight()
    const pN = doc.internal.getCurrentPageInfo().pageNumber

    doc.setDrawColor(...COLOR.grisSuave)
    doc.setLineWidth(0.3)
    doc.line(14, H - 12, W - 14, H - 12)

    doc.setFont(FONT, 'normal')
    doc.setFontSize(7)
    doc.setTextColor(...COLOR.grisMedio)

    doc.text('Procesado – Secretaría de Talleres · UMSS', 14, H - 8)
    doc.text(`Página ${pN} de ${totalPaginas}`, W / 2, H - 8, { align: 'center' })
    doc.text(fechaLarga(), W - 14, H - 8, { align: 'right' })
}

// ─── Función principal ───────────────────────────────────────────────────────

/**
 * Genera un PDF con la lista de alumnos inscritos en talleres,
 * segmentado por materia/grupo.
 *
 * @param {Array}  estudiantes  – array normalizado de estudiantes
 * @param {Object} [opciones]
 * @param {string} [opciones.anio]
 * @param {string} [opciones.periodo]
 */
export function generarReporteInscritos(estudiantes, opciones = {}) {
    const { anio = '2026', periodo = '1' } = opciones

    const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'letter' })

    // ── Agrupar por materia + grupo ──────────────────────────────────────────
    const grupos = estudiantes.reduce((acc, est) => {
        const key = `${est.materia}_${est.grupo}`
        if (!acc[key]) {
            acc[key] = {
                codigoMateria: est.materia,
                materia: est.nom_materia,
                grupo: est.grupo,
                docente: est.docente,
                lista: [],
            }
        }
        acc[key].lista.push(est)
        return acc
    }, {})

    const gruposArr = Object.values(grupos)

    // ── Portada / página de resumen ──────────────────────────────────────────
    dibujarEncabezado(doc, {
        anio,
        periodo,
        titulo: 'ALUMNOS INSCRITOS EN TALLERES',
        subtitulo: 'Resumen General',
    })

    const W = doc.internal.pageSize.getWidth()
    let y = 35

    doc.setFont(FONT, 'bold')
    doc.setFontSize(9)
    doc.setTextColor(...COLOR.negro)
    doc.text('Índice de Materias', 14, y)
    y += 5

    doc.setFont(FONT, 'normal')
    doc.setFontSize(8)
    doc.setTextColor(...COLOR.grisOscuro)

    gruposArr.forEach((g, i) => {
        doc.text(
            `${i + 1}.  ${g.materia}  ·  Grupo ${g.grupo}  ·  Docente: ${g.docente}  (${g.lista.length} inscritos)`,
            18,
            y,
        )
        y += 5
        if (y > 250) { doc.addPage(); y = 35 }
    })

    // Total general
    y += 3
    doc.setDrawColor(...COLOR.grisSuave)
    doc.line(14, y, W - 14, y)
    y += 5
    doc.setFont(FONT, 'bold')
    doc.setFontSize(8)
    doc.setTextColor(...COLOR.negro)
    doc.text(`Total general de alumnos inscritos: ${estudiantes.length}`, 14, y)

    // ── Una tabla por materia/grupo ──────────────────────────────────────────
    gruposArr.forEach((g) => {
        doc.addPage()

        dibujarEncabezado(doc, {
            anio,
            periodo,
            titulo: 'ALUMNOS INSCRITOS EN TALLERES',
            subtitulo: g.materia,
        })

        // Sub-encabezado de la materia
        let sy = 31
        doc.setFont(FONT, 'bold')
        doc.setFontSize(8)
        doc.setTextColor(...COLOR.negro)
        doc.text(`Materia: ${g.materia}  (${g.codigoMateria})`, 14, sy)
        sy += 4
        doc.setFont(FONT, 'normal')
        doc.setFontSize(7.5)
        doc.setTextColor(...COLOR.grisOscuro)
        doc.text(`Grupo: ${g.grupo}     Docente: ${g.docente}     Inscritos: ${g.lista.length}`, 14, sy)
        sy += 3

        // Tabla de alumnos
        autoTable(doc, {
            startY: sy + 2,
            margin: { left: 14, right: 14 },

            head: [[
                { content: '#', styles: { halign: 'center', cellWidth: 8 } },
                { content: 'Código', styles: { halign: 'center', cellWidth: 22 } },
                { content: 'Nombre del Estudiante', cellWidth: 80 },
                { content: 'Carrera', cellWidth: 55 },
                { content: 'Grupo', styles: { halign: 'center', cellWidth: 15 } },
            ]],

            body: g.lista.map((est, i) => [
                { content: i + 1, styles: { halign: 'center' } },
                { content: est.cod_estudiante || est.codigo, styles: { halign: 'center' } },
                est.nom_estudiante,
                nombrePlan(est.plan),
                { content: est.grupo, styles: { halign: 'center' } },
            ]),

            // ── Estilos generales ──
            styles: {
                font: FONT,
                fontSize: 8,
                cellPadding: 2.5,
                textColor: COLOR.grisOscuro,
                lineColor: COLOR.grisSuave,
                lineWidth: 0.2,
            },

            headStyles: {
                fillColor: COLOR.grisOscuro,
                textColor: COLOR.blanco,
                fontStyle: 'bold',
                fontSize: 8,
                halign: 'left',
            },

            alternateRowStyles: {
                fillColor: COLOR.fondoFila,
            },

            bodyStyles: {
                fillColor: COLOR.blanco,
            },

            // Pie de firma al final de cada tabla
            didDrawPage: () => { },
        })

        // Línea de firma / validación
        const finalY = doc.lastAutoTable.finalY + 10
        if (finalY < 240) {
            doc.setFont(FONT, 'normal')
            doc.setFontSize(7)
            doc.setTextColor(...COLOR.grisMedio)
            doc.text('Docente responsable: ___________________________________', 14, finalY)
            doc.text('Firma: _______________', W - 14, finalY, { align: 'right' })
        }
    })

    // ── Numerar todas las páginas ────────────────────────────────────────────
    const totalPaginas = doc.internal.getNumberOfPages()
    for (let p = 1; p <= totalPaginas; p++) {
        doc.setPage(p)
        dibujarPie(doc, totalPaginas)
    }

    // ── Guardar ──────────────────────────────────────────────────────────────
    doc.save(`Reporte_Inscritos_Talleres_${anio}_P${periodo}.pdf`)
}