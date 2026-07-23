// composables/useReporteAprobadosReprobadosResumido.js
// Genera el PDF de APROBADOS Y REPROBADOS RESUMIDOS, en formato
// PLANO por grupo/materia (una fila por grupo), tal como lo entrega
// el endpoint resumenPorGrupo. NO agrupa por docente/carrera —
// es el detalle crudo grupo por grupo.

import { ref } from 'vue'
import { jsPDF } from 'jspdf'
import autoTable from 'jspdf-autotable'

// ── Paleta institucional (misma que los otros reportes) ─────────────────
const C_BLACK = [0, 0, 0]
const C_WHITE = [255, 255, 255]
const C_HEAD_BG = [240, 240, 240]
const C_GRAY_LINE = [140, 140, 140]

function crearDocumento() {
    return new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'letter' })
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

function drawPageHeader(doc, { titulo, anio, periodo, fechaActual, notaSuperior }) {
    const PAGE_W = doc.internal.pageSize.getWidth()
    const ML = 8
    const MR = 8
    const HEADER_H = 24

    doc.setFont('helvetica', 'bold')
    doc.setFontSize(6)
    doc.setTextColor(...C_BLACK)
    doc.text('UNIVERSIDAD MAYOR DE SAN SIMÓN', ML, 8)
    doc.text('FACULTAD DE CIENCIAS ECONÓMICAS', ML, 10)

    doc.setFontSize(12.5)
    doc.text(titulo, PAGE_W / 2, 9, { align: 'center' })
    doc.setFontSize(10.5)
    doc.text('FACULTAD DE CIENCIAS ECONÓMICAS', PAGE_W / 2, 13.5, { align: 'center' })

    doc.setFont('helvetica', 'bold')
    doc.setFontSize(10)
    doc.setTextColor(...C_BLACK)
    doc.text(`Gestión Académica ${periodo}/${anio}`, PAGE_W / 2, 19.5, { align: 'center' })

    doc.setFont('helvetica', 'normal')
    doc.setFontSize(7)
    doc.text(fechaActual, PAGE_W - MR, 19.5, { align: 'right' })
    doc.text(notaSuperior ?? '', ML, 19.5)

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
// RESUMEN PLANO por grupo/materia — misma forma que entrega el backend
// ────────────────────────────────────────────────────────────────────────
function generarResumenAprobadosReprobadosResumido(data, anio, periodo, modo = 'descargar', ventanaPreabierta = null) {
    const doc = crearDocumento()
    const PAGE_W = doc.internal.pageSize.getWidth()
    const ML = 8
    const MR = 8
    const CW = PAGE_W - ML - MR
    const fechaActual = fechaFormateada()
    const TITULO = 'APROBADOS Y REPROBADOS RESUMIDOS'
    const NOTA = 'Detalle por grupo'
    const HEADER_H = drawPageHeader(doc, { titulo: TITULO, anio, periodo, fechaActual, notaSuperior: NOTA })

    const totalIns = data.reduce((s, d) => s + (Number(d.INSCRITOS) || 0), 0)
    const totalApr = data.reduce((s, d) => s + (Number(d.APROBADOS) || 0), 0)
    const totalRep = data.reduce((s, d) => s + (Number(d.REPROBADOS) || 0), 0)

    const head = [[
        'N°', 'Plan', 'Carrera', 'Nivel', 'Docente', 'Cód. Materia', 'Materia', 'Grupo', 'T.Exam',
        'Insc.', 'Aprob.', 'Reprob.',
    ]]

    const body = data.map((fila, idx) => [
        String(idx + 1),
        fila.PLAN ?? '—',
        fila.CARRERA ?? '—',
        fila.NIVEL ?? '—',
        fila.NOMBRE_DOCENTE ?? '—',
        fila.MATERIA ?? '—',
        fila.NOMBRE ?? '—',
        fila.GRUPO ?? '—',
        fila.TIPO_EXAMEN ?? '—',
        String(fila.INSCRITOS ?? 0),
        String(fila.APROBADOS ?? 0),
        String(fila.REPROBADOS ?? 0),
    ])

    body.push([
        { content: '', styles: { fillColor: C_WHITE, lineWidth: 0 } },
        { content: '', styles: { fillColor: C_WHITE, lineWidth: 0 } },
        { content: '', styles: { fillColor: C_WHITE, lineWidth: 0 } },
        { content: '', styles: { fillColor: C_WHITE, lineWidth: 0 } },
        { content: '', styles: { fillColor: C_WHITE, lineWidth: 0 } },
        { content: '', styles: { fillColor: C_WHITE, lineWidth: 0 } },
        { content: 'TOTAL GENERAL', styles: { fontStyle: 'bold', fillColor: C_HEAD_BG, lineWidth: 0, halign: 'right' } },
        { content: '', styles: { fillColor: C_HEAD_BG, lineWidth: 0 } },
        { content: '', styles: { fillColor: C_HEAD_BG, lineWidth: 0 } },
        { content: String(totalIns), styles: { fontStyle: 'bold', halign: 'center', fillColor: C_HEAD_BG, lineWidth: 0 } },
        { content: String(totalApr), styles: { fontStyle: 'bold', halign: 'center', fillColor: C_HEAD_BG, lineWidth: 0 } },
        { content: String(totalRep), styles: { fontStyle: 'bold', halign: 'center', fillColor: C_HEAD_BG, lineWidth: 0 } },
    ])

    autoTable(doc, {
        startY: HEADER_H,
        margin: { left: ML, right: MR, top: HEADER_H, bottom: 12 },
        tableWidth: CW,
        head,
        body,
        alternateRowStyles: { fillColor: C_WHITE },
        styles: {
            font: 'helvetica', fontSize: 7,
            cellPadding: { top: 0.7, bottom: 0.7, left: 1.5, right: 1.5 },
            textColor: C_BLACK, lineColor: C_GRAY_LINE, lineWidth: { top: 0, right: 0, bottom: 0.15, left: 0 },
            fillColor: C_WHITE, halign: 'center', valign: 'middle',
        },
        headStyles: {
            fillColor: C_HEAD_BG, textColor: C_BLACK, fontStyle: 'bold',
            fontSize: 7.2, halign: 'center', valign: 'middle',
            lineColor: C_GRAY_LINE, lineWidth: { top: 0, right: 0, bottom: 0.3, left: 0 },
        },
        columnStyles: {
            0: { cellWidth: 10 },
            1: { cellWidth: 16 },
            2: { cellWidth: 16 },
            3: { cellWidth: 12 },
            4: { cellWidth: 42, halign: 'left' },
            5: { cellWidth: 18, font: 'courier' },
            6: { cellWidth: 46, halign: 'left' },
            7: { cellWidth: 14 },
            8: { cellWidth: 14 },
        },
        didDrawPage() {
            if (doc.internal.getCurrentPageInfo().pageNumber > 1) {
                drawPageHeader(doc, { titulo: TITULO, anio, periodo, fechaActual, notaSuperior: NOTA })
            }
        },
    })

    drawFooters(doc, fechaActual)

    const filename = `Aprobados_Reprobados_Resumido_${anio}_${periodo}.pdf`
    finalizarSalida(doc, filename, modo, ventanaPreabierta)
}

// ────────────────────────────────────────────────────────────────────────
// Composable público
// ────────────────────────────────────────────────────────────────────────
export function useReporteAprobadosReprobadosResumido() {
    const generandoAprobadosResumido = ref(false)

    async function exportarAprobadosReprobadosResumido(data, anio, periodo, modo = 'descargar', ventanaPreabierta = null) {
        if (!data?.length) {
            if (ventanaPreabierta && !ventanaPreabierta.closed) ventanaPreabierta.close()
            return
        }
        generandoAprobadosResumido.value = true
        try {
            generarResumenAprobadosReprobadosResumido(data, anio, periodo, modo, ventanaPreabierta)
        } finally {
            generandoAprobadosResumido.value = false
        }
    }

    return {
        generandoAprobadosResumido,
        exportarAprobadosReprobadosResumido,
    }
}