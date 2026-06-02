// composables/useGenerarPDFCargaHoraria.js
// ─────────────────────────────────────────────────────────────────────────────
// Replica EXACTA del reporte oficial  "CARGA HORARIA DOCENTES – FCE/UMSS"
// Orientación: PORTRAIT Letter (216 × 279 mm)
// Dependencias: jspdf  +  jspdf-autotable
//   npm install jspdf jspdf-autotable
// ─────────────────────────────────────────────────────────────────────────────

import { jsPDF } from 'jspdf'
import autoTable from 'jspdf-autotable'

// ── Paleta exacta del PDF de referencia ──────────────────────────────────────
const C_BLACK = [0, 0, 0]
const C_WHITE = [255, 255, 255]
const C_GRAY_HEAD = [218, 218, 218]   // fondo de encabezados de columna
const C_GRAY_LINE = [160, 160, 160]   // líneas de la tabla
const C_ALT_ROW = [242, 242, 242]   // filas alternas (gris muy suave)
const C_TOTAL_BG = [230, 230, 230]   // fila TOTAL
const C_COMP_TEXT = [180, 50, 0]     // texto "Comparte…" en rojo/naranja oscuro
const C_ZERO_TEXT = [150, 150, 150]   // el "0" de la col C cuando no comparte

// ═════════════════════════════════════════════════════════════════════════════
export function generarPDFCargaHoraria(docentes = [], { anio, periodo } = {}) {

    const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'letter' })

    const PAGE_W = doc.internal.pageSize.getWidth()    // 215.9 mm
    const PAGE_H = doc.internal.pageSize.getHeight()   // 279.4 mm
    const ML = 10   // margen izquierdo
    const MR = 10   // margen derecho
    const CW = PAGE_W - ML - MR                    // ~195.9 mm útiles

    // ── Fecha igual al PDF: "4/21/2026 10:51:41 AM" ──────────────────────────
    const fechaActual = new Date().toLocaleString('en-US', {
        month: 'numeric',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        second: '2-digit',
        hour12: true,
    })

    const gestionLabel = `${periodo}/${anio}`

    // ── Cursor vertical global ────────────────────────────────────────────────
    let Y = 0

    // ═════════════════════════════════════════════════════════════════════════
    // ENCABEZADO INSTITUCIONAL  (se dibuja en cada página nueva)
    // ═════════════════════════════════════════════════════════════════════════
    function drawPageHeader() {
        doc.setFont('helvetica', 'bold')
        doc.setFontSize(7.5)
        doc.setTextColor(...C_BLACK)
        doc.text('UNIVERSIDAD MAYOR DE SAN SIMON', ML, 9)
        doc.text('FACULTAD DE CIENCIAS ECONOMICAS', ML, 13)

        // Título centrado
        doc.setFontSize(11)
        doc.text('CARGA HORARIA DOCENTES', PAGE_W / 2, 9, { align: 'center' })
        doc.setFontSize(9)
        doc.text('FACULTAD DE CIENCIAS ECONOMICAS', PAGE_W / 2, 13, { align: 'center' })

        // Línea divisoria
        doc.setDrawColor(...C_GRAY_LINE)
        doc.setLineWidth(0.4)
        doc.line(ML, 15, PAGE_W - MR, 15)

        // Gestión académica — centrado en la segunda línea
        doc.setFont('helvetica', 'bold')
        doc.setFontSize(9)
        doc.setTextColor(...C_BLACK)
        doc.text(`Gestión Académica ${gestionLabel}`, PAGE_W / 2, 20, { align: 'center' })

        // Fecha — derecha
        doc.setFont('helvetica', 'normal')
        doc.setFontSize(7)
        doc.text(fechaActual, PAGE_W - MR, 20, { align: 'right' })

        // Aviso izquierda
        doc.setFontSize(6.5)
        doc.text('La carga horaria incluye Grupos Compartidos.', ML, 20)

        // Segunda línea divisoria
        doc.setDrawColor(...C_GRAY_LINE)
        doc.setLineWidth(0.3)
        doc.line(ML, 22, PAGE_W - MR, 22)

        return 25  // Y donde empieza el contenido
    }

    // ── Pie de página ─────────────────────────────────────────────────────────
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
            doc.text(
                `Página ${i} de ${total}`,
                PAGE_W / 2, fy, { align: 'center' }
            )
            doc.text(fechaActual, PAGE_W - MR, fy, { align: 'right' })
        }
    }

    // ════════════════════════════════════════════════════════════════════════
    // Primera página
    // ════════════════════════════════════════════════════════════════════════
    Y = drawPageHeader()

    // ════════════════════════════════════════════════════════════════════════
    // Iterar docentes
    // ════════════════════════════════════════════════════════════════════════
    for (let di = 0; di < docentes.length; di++) {
        const docente = docentes[di]

        // ── ¿Hay espacio para la cabecera del docente + al menos 1 fila? ──────
        if (Y > PAGE_H - 35) {
            doc.addPage()
            Y = drawPageHeader()
        }

        // ── Cabecera del docente: "199100005 AGREDA MONTANO GUIDO" ───────────
        // Sin fondo de color — texto en negrita, igual que el PDF original
        doc.setFont('helvetica', 'bold')
        doc.setFontSize(8.5)
        doc.setTextColor(...C_BLACK)
        doc.text(`${docente.docente}  ${(docente.apellidos ?? '').toUpperCase()} ${(docente.nombres ?? '').toUpperCase()}`, ML, Y + 4)
        Y += 8

        // ── Construir filas ────────────────────────────────────────────────────
        // Cada registro del array horarios es UNA fila (una sesión)
        const rows = []

        for (const h of (docente.horarios ?? [])) {
            // plan: el campo PLAN del API puede venir como "ADM - F" o "109401"
            // El PDF original muestra "ADM - F" en la col DOCENTE/PLAN
            // Construimos el texto de la col 0 igual al original: "ADM - F"
            const planTexto = h.PLAN ?? ''   // ej: "ADM - F" o "109401"

            // Texto compartido
            const compTexto = h.COMPARTIDO ?? h.comp ?? ''

            // Flag C: 1 si comparte de otra carrera (le llegan inscritos extra)
            const cFlag = h.C ?? (compTexto.toLowerCase().startsWith('comparte de') ? 1 : 0)

            rows.push([
                planTexto,                              // col 0: DOCENTE/PLAN
                `${h.MATERIA ?? ''}\n${h.NOMBRE ?? ''}`,// col 1: MATERIA (código + nombre)
                h.GRUPO ?? h.GRUPO ?? '',            // col 2: GRP
                h.DIA ?? '',                        // col 3: DIA
                h.AMBIENTE ?? '',                       // col 4: AULA
                h.HORARIO ?? '',                       // col 5: HORA
                h.CARGA_HORARIA ?? '',                  // col 6: CH
                h.TOTAL_NORMAL ?? '',                  // col 7: INSC-N
                String(cFlag),                          // col 8: C
                compTexto,                              // col 9: COMPARTIDO
            ])
        }

        // ── autoTable ─────────────────────────────────────────────────────────
        autoTable(doc, {
            startY: Y,
            margin: { left: ML, right: MR, bottom: 12 },
            tableWidth: CW,

            head: [[
                'DOCENTE / PLAN',
                'MATERIA',
                'GRP',
                'DIA',
                'AULA',
                'HORA',
                'CH',
                'INSC-N',
                'C',
                'COMPARTIDO',
            ]],

            body: rows,

            foot: [[
                {
                    content: 'TOTAL',
                    colSpan: 6,
                    styles: {
                        halign: 'right',
                        fontStyle: 'bold',
                        fillColor: C_TOTAL_BG,
                        textColor: C_BLACK,
                        fontSize: 7.5,
                    },
                },
                {
                    content: String(docente.total_ch ?? ''),
                    colSpan: 4,
                    styles: {
                        halign: 'center',
                        fontStyle: 'bold',
                        fillColor: C_TOTAL_BG,
                        textColor: C_BLACK,
                        fontSize: 8,
                    },
                },
            ]],

            // ── Estilos globales ───────────────────────────────────────────────
            styles: {
                font: 'helvetica',
                fontSize: 7,
                cellPadding: { top: 1.0, bottom: 1.0, left: 1.5, right: 1.5 },
                textColor: C_BLACK,
                lineColor: C_GRAY_LINE,
                lineWidth: 0.2,
                overflow: 'linebreak',
                valign: 'middle',
            },

            headStyles: {
                fillColor: C_GRAY_HEAD,
                textColor: C_BLACK,
                fontStyle: 'bold',
                fontSize: 7,
                halign: 'center',
                valign: 'middle',
                lineColor: [120, 120, 120],
                lineWidth: 0.3,
            },

            alternateRowStyles: {
                fillColor: C_ALT_ROW,
            },

            footStyles: {
                fillColor: C_TOTAL_BG,
                lineColor: C_GRAY_LINE,
                lineWidth: 0.3,
            },

            // ── Anchos de columna (portrait ~195.9 mm útiles) ─────────────────
            // PLAN(22) MATERIA(52) GRP(8) DIA(8) AULA(20) HORA(24) CH(8) INSC-N(12) C(8) COMP(auto≈33.9)
            columnStyles: {
                0: { cellWidth: 22 },                  // PLAN
                1: { cellWidth: 52 },                  // MATERIA
                2: { cellWidth: 8, halign: 'center' },// GRP
                3: { cellWidth: 8, halign: 'center' },// DIA
                4: { cellWidth: 20 },                  // AULA
                5: { cellWidth: 24 },                  // HORA
                6: { cellWidth: 8, halign: 'center' },// CH
                7: { cellWidth: 12, halign: 'center' },// INSC-N
                8: { cellWidth: 8, halign: 'center' },// C
                9: { cellWidth: 'auto' },              // COMPARTIDO
            },

            // ── Colorear celdas específicas ────────────────────────────────────
            didParseCell(data) {
                if (data.section !== 'body') return

                const row = rows[data.row.index]
                if (!row) return

                // Col 8 (C): "0" en gris, "1" en negro normal
                if (data.column.index === 8) {
                    if (data.cell.raw === '0') {
                        data.cell.styles.textColor = C_ZERO_TEXT
                    }
                }

                // Col 9 (COMPARTIDO): texto en rojo-naranja si hay contenido
                if (data.column.index === 9 && data.cell.raw) {
                    data.cell.styles.textColor = C_COMP_TEXT
                    data.cell.styles.fontStyle = 'bold'
                    data.cell.styles.fontSize = 6.5
                }
            },

            // ── Nueva página: redibujar encabezado institucional ───────────────
            didAddPage(data) {
                Y = drawPageHeader()
                data.settings.margin.top = Y
            },
        })

        Y = doc.lastAutoTable.finalY + 6
    }

    // ── Pies en todas las páginas ─────────────────────────────────────────────
    drawFooters()

    // ── Guardar ───────────────────────────────────────────────────────────────
    doc.save(`CargaHoraria_${anio}_${periodo}.pdf`)
}