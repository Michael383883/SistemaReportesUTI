// composables/useReporteMateriaInscritos.js
// Genera el PDF de la lista de inscritos de UNA SOLA materia/grupo,
// tal como se ve en el panel expandido de DocenteInscritosCard.
// Incluye Regulares + Mesa, con numeración N° correlativa continua.

import { ref } from 'vue'
import { jsPDF } from 'jspdf'
import autoTable from 'jspdf-autotable'

const C_BLACK = [0, 0, 0]
const C_WHITE = [255, 255, 255]
const C_HEAD_BG = [240, 240, 240]
const C_GRAY_LINE = [140, 140, 140]

function crearDocumento() {
    return new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'letter' })
}

function fechaFormateada() {
    const d = new Date()
    const dia = String(d.getDate()).padStart(2, '0')
    const mes = String(d.getMonth() + 1).padStart(2, '0')
    const anio = d.getFullYear()
    const hora = d.toLocaleString('en-US', {
        hour: 'numeric', minute: '2-digit', second: '2-digit', hour12: true,
    })
    return `${dia}/${mes}/${anio} ${hora}`
}

function drawPageHeader(doc, { titulo, subtitulo, anio, periodo, fechaActual }) {
    const PAGE_W = doc.internal.pageSize.getWidth()
    const ML = 8
    const MR = 8
    const HEADER_H = 28

    doc.setFont('helvetica', 'bold')
    doc.setFontSize(6)
    doc.setTextColor(...C_BLACK)
    doc.text('UNIVERSIDAD MAYOR DE SAN SIMÓN', ML, 8)
    doc.text('FACULTAD DE CIENCIAS ECONÓMICAS', ML, 10)

    doc.setFontSize(11.5)
    doc.text('LISTA DE INSCRITOS POR MATERIA', PAGE_W / 2, 9, { align: 'center' })
    doc.setFontSize(9)
    doc.setFont('helvetica', 'normal')
    doc.text(titulo, PAGE_W / 2, 13.5, { align: 'center' })

    doc.setFont('helvetica', 'bold')
    doc.setFontSize(9)
    doc.text(`Gestión Académica ${periodo}/${anio}`, PAGE_W / 2, 18.5, { align: 'center' })

    doc.setFont('helvetica', 'normal')
    doc.setFontSize(7.5)
    doc.text(subtitulo, ML, 24)
    doc.text(fechaActual, PAGE_W - MR, 24, { align: 'right' })

    return HEADER_H
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
        doc.text('Procesado SIA-UTI - Facultad de Ciencias Económicas', ML, fy)
        doc.text(`Página ${i} de ${total}`, PAGE_W / 2, fy, { align: 'center' })
        doc.text(fechaActual, PAGE_W - MR, fy, { align: 'right' })
    }
}

function finalizarSalida(doc, filename, modo, ventanaPreabierta) {
    if (modo === 'imprimir') {
        const blob = doc.output('blob')
        const url = URL.createObjectURL(blob)
        if (ventanaPreabierta && !ventanaPreabierta.closed) ventanaPreabierta.close()

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

// ────────────────────────────────────────────────────────────────────────
// PDF de UNA sola materia/grupo (Regulares + Mesa, con N° correlativo)
// ────────────────────────────────────────────────────────────────────────
function generarListaMateria({ docente, carrera, materia, anio, periodo, modo, ventanaPreabierta }) {
    const doc = crearDocumento()
    const PAGE_W = doc.internal.pageSize.getWidth()
    const ML = 8
    const MR = 8
    const CW = PAGE_W - ML - MR
    const fechaActual = fechaFormateada()

    const titulo = `${materia.cod_materia} - ${materia.nom_materia} - GRP ${materia.grupo} (${carrera.carrera})`
    const subtitulo = `Docente: ${docente.cod_docente} - ${docente.apellidos} ${docente.nombres}`

    const HEADER_H = drawPageHeader(doc, { titulo, subtitulo, anio, periodo, fechaActual })

    const regulares = materia.inscritos ?? []
    const mesa = materia.inscritos_examen_mesa ?? []

    const head = [['N°', 'Código', 'Nombre', 'Modalidad']]

    const body = [
        ...regulares.map((e, i) => [String(i + 1), e.codigo, e.nombre, 'Regular']),
        ...mesa.map((e, i) => [String(regulares.length + i + 1), e.codigo, e.nombre, 'Mesa']),
    ]

    if (!body.length) {
        body.push([{ content: 'Sin inscritos', colSpan: 4, styles: { halign: 'center', fontStyle: 'italic', textColor: [150, 150, 150] } }])
    }

    autoTable(doc, {
        startY: HEADER_H,
        margin: { left: ML, right: MR, top: HEADER_H, bottom: 12 },
        tableWidth: CW,
        head,
        body,
        alternateRowStyles: { fillColor: C_WHITE },
        styles: {
            font: 'helvetica', fontSize: 8.5,
            cellPadding: { top: 1.4, bottom: 1.4, left: 2, right: 2 },
            textColor: C_BLACK, lineColor: C_GRAY_LINE, lineWidth: { top: 0, right: 0, bottom: 0.15, left: 0 },
            fillColor: C_WHITE,
        },
        headStyles: {
            fillColor: C_HEAD_BG, textColor: C_BLACK, fontStyle: 'bold',
            fontSize: 8.5, halign: 'center', valign: 'middle',
            lineColor: C_GRAY_LINE, lineWidth: { top: 0, right: 0, bottom: 0.3, left: 0 },
        },
        columnStyles: {
            0: { cellWidth: 12, halign: 'center' },
            1: { cellWidth: 25, font: 'courier' },
            2: { cellWidth: 'auto', halign: 'left' },
            3: { cellWidth: 25, halign: 'center' },
        },
        didDrawPage() {
            if (doc.internal.getCurrentPageInfo().pageNumber > 1) {
                drawPageHeader(doc, { titulo, subtitulo, anio, periodo, fechaActual })
            }
        },
    })

    drawFooters(doc, fechaActual)

    const filename = `Lista_${materia.cod_materia}_GRP${materia.grupo}_${anio}_${periodo}.pdf`
    finalizarSalida(doc, filename, modo, ventanaPreabierta)
}

// ────────────────────────────────────────────────────────────────────────
export function useReporteMateriaInscritos() {
    const generandoMateria = ref(false)

    async function exportarListaMateria(docente, carrera, materia, anio, periodo, modo = 'descargar', ventanaPreabierta = null) {
        generandoMateria.value = true
        try {
            generarListaMateria({ docente, carrera, materia, anio, periodo, modo, ventanaPreabierta })
        } finally {
            generandoMateria.value = false
        }
    }

    return {
        generandoMateria,
        exportarListaMateria,
    }
}