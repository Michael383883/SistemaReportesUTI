// composables/useReporteInscritos.js
// Genera PDFs directamente en el navegador con jsPDF (sin backend).
// Instalación: npm install jspdf
//
// Formato: documento blanco y negro puro. El único color es el texto
// (negro/gris) y las líneas divisorias. No hay ningún rectángulo de
// fondo relleno (excepto la franja de título en la parte superior de
// cada página, que es estándar incluso en documentos formales y permite
// ahorrar tinta porque ocupa muy poco espacio).

import { ref } from 'vue'

// ── Escala de grises para texto y líneas (sin tinte de color) ───────────────
const NEGRO = { r: 20, g: 20, b: 20 }       // texto principal
const GRIS_OSCURO = { r: 60, g: 60, b: 60 } // subtítulos / texto secundario fuerte
const GRIS_MEDIO = { r: 110, g: 110, b: 110 } // texto muted, códigos
const GRIS_CLARO = { r: 170, g: 170, b: 170 } // texto muy secundario
const LINEA_FUERTE = { r: 60, g: 60, b: 60 }  // líneas de separación de docente/carrera
const LINEA_SUAVE = { r: 200, g: 200, b: 200 } // líneas de separación de fila/materia
const BLANCO = { r: 255, g: 255, b: 255 }

// ── Helpers ──────────────────────────────────────────────────────────────────
function setTextColor(doc, color) {
    doc.setTextColor(color.r, color.g, color.b)
}

function setDrawColor(doc, color) {
    doc.setDrawColor(color.r, color.g, color.b)
}

function lineaH(doc, x1, y, x2, color, width = 0.2) {
    setDrawColor(doc, color)
    doc.setLineWidth(width)
    doc.line(x1, y, x2, y)
}

/**
 * Dibuja el encabezado del documento (solo en la primera página) o un
 * encabezado simplificado de continuación en páginas siguientes.
 * @returns {number} Y después del header
 */
function dibujarHeader(doc, anio, periodo, titulo, pageW, esPrimeraPagina = false) {
    doc.setFont('helvetica', 'bold')
    doc.setFontSize(esPrimeraPagina ? 16 : 11)
    setTextColor(doc, NEGRO)
    doc.text(titulo, 14, 16)

    doc.setFontSize(9)
    doc.setFont('helvetica', 'normal')
    setTextColor(doc, GRIS_OSCURO)
    doc.text(`Gestión ${anio} · Período ${periodo}`, pageW - 14, 16, { align: 'right' })

    if (esPrimeraPagina) {
        doc.setFontSize(8)
        setTextColor(doc, GRIS_MEDIO)
        const fecha = new Date().toLocaleDateString('es-BO', {
            day: '2-digit', month: 'long', year: 'numeric',
        })
        doc.text(`Generado el ${fecha}`, 14, 22)
    }

    lineaH(doc, 14, 26, pageW - 14, NEGRO, 0.5)

    return 34 // Y inicial del contenido
}

/**
 * Pie de página con número.
 */
function dibujarFooter(doc, pageNum, totalPages, pageW, pageH) {
    lineaH(doc, 14, pageH - 12, pageW - 14, LINEA_SUAVE, 0.2)
    doc.setFontSize(7)
    doc.setFont('helvetica', 'normal')
    setTextColor(doc, GRIS_MEDIO)
    doc.text(`Página ${pageNum} de ${totalPages}`, pageW / 2, pageH - 7, { align: 'center' })
}

/**
 * Verifica si cabe más contenido; si no, agrega página y redibuja header.
 * @returns {number} nuevo Y
 */
function checkPage(doc, y, needed, anio, periodo, titulo, pageW, pageH) {
    if (y + needed > pageH - 16) {
        doc.addPage()
        return dibujarHeader(doc, anio, periodo, titulo, pageW, false)
    }
    return y
}

// ────────────────────────────────────────────────────────────────────────────
// REPORTE 1 — Lista completa (docente → carrera → materia → estudiantes)
// ────────────────────────────────────────────────────────────────────────────
async function generarListaCompleta(data, anio, periodo) {
    const { jsPDF } = await import('jspdf')
    const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' })
    const pageW = doc.internal.pageSize.getWidth()
    const pageH = doc.internal.pageSize.getHeight()
    const TITULO = 'Lista de Inscritos por Docente'

    let y = dibujarHeader(doc, anio, periodo, TITULO, pageW, true)

    data.forEach((docente, dIdx) => {
        // ── Bloque docente ──────────────────────────────────────────────────
        y = checkPage(doc, y, 16, anio, periodo, TITULO, pageW, pageH)

        doc.setFont('helvetica', 'bold')
        doc.setFontSize(11)
        setTextColor(doc, NEGRO)
        doc.text(`${dIdx + 1}.  ${docente.apellidos}, ${docente.nombres}`, 14, y + 5)

        doc.setFont('helvetica', 'normal')
        doc.setFontSize(8.5)
        setTextColor(doc, GRIS_MEDIO)
        doc.text(`Cód. ${docente.cod_docente}`, pageW - 14, y + 5, { align: 'right' })

        y += 7
        lineaH(doc, 14, y, pageW - 14, LINEA_FUERTE, 0.4)
        y += 5

        docente.carreras.forEach(carrera => {
            // ── Bloque carrera ────────────────────────────────────────────────
            y = checkPage(doc, y, 9, anio, periodo, TITULO, pageW, pageH)

            doc.setFont('helvetica', 'bold')
            doc.setFontSize(9)
            setTextColor(doc, GRIS_OSCURO)
            doc.text(
                `${carrera.carrera}  ·  Plan ${carrera.plan}  ·  Total: ${carrera.subtotal} inscritos`,
                14, y + 4
            )
            y += 6
            lineaH(doc, 14, y, pageW - 14, LINEA_SUAVE, 0.2)
            y += 5

            carrera.materias.forEach(materia => {
                // ── Encabezado materia ──────────────────────────────────────────
                y = checkPage(doc, y, 8, anio, periodo, TITULO, pageW, pageH)

                doc.setFont('helvetica', 'bolditalic')
                doc.setFontSize(8)
                setTextColor(doc, GRIS_OSCURO)
                doc.text(
                    `${materia.nom_materia}  (Gr. ${materia.grupo})  —  ${materia.subtotal} inscritos`,
                    16, y + 4
                )
                y += 6

                // ── Tabla de estudiantes regulares ──────────────────────────────
                materia.inscritos.forEach((est, idx) => {
                    y = checkPage(doc, y, 5.5, anio, periodo, TITULO, pageW, pageH)

                    doc.setFont('helvetica', 'normal')
                    doc.setFontSize(7.5)
                    setTextColor(doc, GRIS_CLARO)
                    doc.text(`${idx + 1}`, 20, y + 3.6)

                    setTextColor(doc, GRIS_MEDIO)
                    doc.setFont('courier', 'normal')
                    doc.text(`${est.codigo}`, 27, y + 3.6)

                    doc.setFont('helvetica', 'normal')
                    setTextColor(doc, NEGRO)
                    doc.text(`${est.nombre}`, 54, y + 3.6)
                    y += 5
                })

                // ── Examen de mesa (si existen) ─────────────────────────────────
                if (materia.subtotal_examen_mesa) {
                    y = checkPage(doc, y, 6, anio, periodo, TITULO, pageW, pageH)

                    doc.setFont('helvetica', 'bolditalic')
                    doc.setFontSize(7.5)
                    setTextColor(doc, GRIS_OSCURO)
                    doc.text(
                        `Examen de mesa — ${materia.subtotal_examen_mesa} estudiantes`,
                        16, y + 3.6
                    )
                    y += 5

                    materia.inscritos_examen_mesa.forEach((est, idx) => {
                        y = checkPage(doc, y, 5.5, anio, periodo, TITULO, pageW, pageH)

                        doc.setFont('helvetica', 'normal')
                        doc.setFontSize(7.5)
                        setTextColor(doc, GRIS_CLARO)
                        doc.text(`${idx + 1}`, 20, y + 3.6)

                        setTextColor(doc, GRIS_MEDIO)
                        doc.setFont('courier', 'normal')
                        doc.text(`${est.codigo}`, 27, y + 3.6)

                        doc.setFont('helvetica', 'normal')
                        setTextColor(doc, NEGRO)
                        doc.text(`${est.nombre}`, 54, y + 3.6)
                        y += 5
                    })
                }

                y += 2.5 // espacio entre materias
            })

            y += 3 // espacio entre carreras
        })

        // ── Total del docente ─────────────────────────────────────────────
        y = checkPage(doc, y, 8, anio, periodo, TITULO, pageW, pageH)
        lineaH(doc, 14, y, pageW - 14, LINEA_SUAVE, 0.2)
        doc.setFont('helvetica', 'bold')
        doc.setFontSize(8.5)
        setTextColor(doc, GRIS_OSCURO)
        const totalLinea = docente.total_examen_mesa
            ? `Total inscritos del docente: ${docente.total_inscritos}  (+ ${docente.total_examen_mesa} examen de mesa)`
            : `Total inscritos del docente: ${docente.total_inscritos}`
        doc.text(totalLinea, pageW - 14, y + 5, { align: 'right' })
        y += 10
    })

    // Pies de página
    const totalPages = doc.internal.getNumberOfPages()
    for (let p = 1; p <= totalPages; p++) {
        doc.setPage(p)
        dibujarFooter(doc, p, totalPages, pageW, pageH)
    }

    doc.save(`inscritos_lista_completa_${anio}_${periodo}.pdf`)
}

// ────────────────────────────────────────────────────────────────────────────
// REPORTE 2 — Solo totales y cantidades (sin lista de estudiantes)
// ────────────────────────────────────────────────────────────────────────────
async function generarResumenTotales(data, anio, periodo) {
    const { jsPDF } = await import('jspdf')
    const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' })
    const pageW = doc.internal.pageSize.getWidth()
    const pageH = doc.internal.pageSize.getHeight()
    const TITULO = 'Resumen de Inscritos — Solo Totales'

    let y = dibujarHeader(doc, anio, periodo, TITULO, pageW, true)

    // ── Tabla global resumen ─────────────────────────────────────────────────
    const totalGlobal = data.reduce((s, d) => s + d.total_inscritos, 0)
    const carreras = ['ADM', 'ECO', 'CCP', 'COM', 'FIN']
    const xCols = [pageW - 72, pageW - 60, pageW - 48, pageW - 36, pageW - 24]

    // Encabezado de tabla
    y = checkPage(doc, y, 9, anio, periodo, TITULO, pageW, pageH)
    doc.setFont('helvetica', 'bold')
    doc.setFontSize(8)
    setTextColor(doc, NEGRO)
    doc.text('N°', 14, y + 4)
    doc.text('Código', 20, y + 4)
    doc.text('Docente', 40, y + 4)
    carreras.forEach((c, i) => doc.text(c, xCols[i], y + 4, { align: 'center' }))
    doc.text('TOTAL', pageW - 14, y + 4, { align: 'right' })
    y += 6
    lineaH(doc, 14, y, pageW - 14, LINEA_FUERTE, 0.4)
    y += 4

    // Filas por docente
    data.forEach((docente, idx) => {
        y = checkPage(doc, y, 6, anio, periodo, TITULO, pageW, pageH)

        // Construir mapa carrera → subtotal
        const totPorCarrera = {}
        docente.carreras.forEach(c => { totPorCarrera[c.carrera] = c.subtotal })

        doc.setFont('helvetica', 'normal')
        doc.setFontSize(7.5)
        setTextColor(doc, GRIS_MEDIO)
        doc.text(`${idx + 1}`, 14, y + 3.6)

        doc.setFont('courier', 'normal')
        doc.text(`${docente.cod_docente}`, 20, y + 3.6)

        doc.setFont('helvetica', 'normal')
        setTextColor(doc, NEGRO)
        const nombre = `${docente.apellidos}, ${docente.nombres}`.substring(0, 32)
        doc.text(nombre, 40, y + 3.6)

        carreras.forEach((c, i) => {
            const val = totPorCarrera[c] ?? 0
            setTextColor(doc, val > 0 ? NEGRO : GRIS_CLARO)
            doc.setFont('helvetica', val > 0 ? 'bold' : 'normal')
            doc.text(val > 0 ? `${val}` : '—', xCols[i], y + 3.6, { align: 'center' })
        })

        doc.setFont('helvetica', 'bold')
        doc.setFontSize(8)
        setTextColor(doc, NEGRO)
        doc.text(`${docente.total_inscritos}`, pageW - 14, y + 3.6, { align: 'right' })

        y += 4.5
        lineaH(doc, 14, y, pageW - 14, LINEA_SUAVE, 0.15)
        y += 1.5
    })

    // ── Fila de totales globales ─────────────────────────────────────────────
    y = checkPage(doc, y, 9, anio, periodo, TITULO, pageW, pageH)
    lineaH(doc, 14, y, pageW - 14, LINEA_FUERTE, 0.4)
    y += 5

    doc.setFont('helvetica', 'bold')
    doc.setFontSize(9)
    setTextColor(doc, NEGRO)
    doc.text('TOTAL GENERAL', 40, y + 2)

    carreras.forEach((c, i) => {
        const sum = data.reduce((s, d) => {
            const car = d.carreras.find(x => x.carrera === c)
            return s + (car?.subtotal ?? 0)
        }, 0)
        doc.text(sum > 0 ? `${sum}` : '—', xCols[i], y + 2, { align: 'center' })
    })

    doc.text(`${totalGlobal}`, pageW - 14, y + 2, { align: 'right' })
    y += 12

    // ── Sección detalle por carrera y materia (sin nombres) ──────────────────
    y = checkPage(doc, y, 12, anio, periodo, TITULO, pageW, pageH)
    doc.setFont('helvetica', 'bold')
    doc.setFontSize(10)
    setTextColor(doc, NEGRO)
    doc.text('Detalle por Docente → Carrera → Materia', 14, y + 4)
    y += 6
    lineaH(doc, 14, y, pageW - 14, NEGRO, 0.5)
    y += 6

    data.forEach((docente, dIdx) => {
        y = checkPage(doc, y, 10, anio, periodo, TITULO, pageW, pageH)

        doc.setFont('helvetica', 'bold')
        doc.setFontSize(9)
        setTextColor(doc, NEGRO)
        doc.text(
            `${dIdx + 1}.  ${docente.apellidos}, ${docente.nombres}  (Cód. ${docente.cod_docente})`,
            14, y + 4
        )
        doc.setFont('helvetica', 'normal')
        setTextColor(doc, GRIS_MEDIO)
        const totalDocLinea = docente.total_examen_mesa
            ? `${docente.total_inscritos} inscritos (+${docente.total_examen_mesa} ex. mesa)`
            : `${docente.total_inscritos} inscritos`
        doc.text(totalDocLinea, pageW - 14, y + 4, { align: 'right' })
        y += 6
        lineaH(doc, 14, y, pageW - 14, LINEA_FUERTE, 0.3)
        y += 4

        docente.carreras.forEach(carrera => {
            y = checkPage(doc, y, 7, anio, periodo, TITULO, pageW, pageH)

            doc.setFont('helvetica', 'bold')
            doc.setFontSize(8.5)
            setTextColor(doc, GRIS_OSCURO)
            doc.text(`${carrera.carrera}  ·  Plan ${carrera.plan}`, 16, y + 3.8)
            doc.text(`${carrera.subtotal}`, pageW - 14, y + 3.8, { align: 'right' })
            y += 5.5

            // Tabla de materias (sin estudiantes)
            carrera.materias.forEach((mat) => {
                y = checkPage(doc, y, 5.5, anio, periodo, TITULO, pageW, pageH)

                doc.setFont('helvetica', 'normal')
                doc.setFontSize(7.5)
                setTextColor(doc, GRIS_MEDIO)
                doc.text(`${mat.cod_materia}`, 20, y + 3.6)

                setTextColor(doc, NEGRO)
                const nomMat = mat.nom_materia.substring(0, 40)
                doc.text(nomMat, 40, y + 3.6)

                setTextColor(doc, GRIS_MEDIO)
                doc.text(`Gr. ${mat.grupo}`, pageW - 36, y + 3.6)

                doc.setFont('helvetica', 'bold')
                setTextColor(doc, NEGRO)
                const subtotalTexto = mat.subtotal_examen_mesa
                    ? `${mat.subtotal} (+${mat.subtotal_examen_mesa})`
                    : `${mat.subtotal}`
                doc.text(subtotalTexto, pageW - 14, y + 3.6, { align: 'right' })

                y += 5
                lineaH(doc, 18, y, pageW - 14, LINEA_SUAVE, 0.12)
                y += 0.8
            })

            y += 2.5
        })

        y += 4
    })

    // Pies de página
    const totalPages = doc.internal.getNumberOfPages()
    for (let p = 1; p <= totalPages; p++) {
        doc.setPage(p)
        dibujarFooter(doc, p, totalPages, pageW, pageH)
    }

    doc.save(`inscritos_resumen_totales_${anio}_${periodo}.pdf`)
}

// ────────────────────────────────────────────────────────────────────────────
// Composable público
// ────────────────────────────────────────────────────────────────────────────
export function useReporteInscritos() {
    const generandoLista = ref(false)
    const generandoResumen = ref(false)

    async function exportarListaCompleta(data, anio, periodo) {
        if (!data?.length) return
        generandoLista.value = true
        try {
            await generarListaCompleta(data, anio, periodo)
        } finally {
            generandoLista.value = false
        }
    }

    async function exportarResumenTotales(data, anio, periodo) {
        if (!data?.length) return
        generandoResumen.value = true
        try {
            await generarResumenTotales(data, anio, periodo)
        } finally {
            generandoResumen.value = false
        }
    }

    return {
        generandoLista,
        generandoResumen,
        exportarListaCompleta,
        exportarResumenTotales,
    }
}