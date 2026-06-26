// composables/useGenerarPDFConTipoIngreso.js
// Hereda TODO de useGenerarPDF y solo agrega la columna TIPO INGRESO

import { generarPDF } from './useGenerarPDF'

export function generarPDFConTipoIngreso(reporte, opts = {}) {
    // Inyectamos la columna extra en las materias no modifica el original
    const reporteExtendido = {
        ...reporte,
        __extraColumnas: [
            { header: 'TIPO INGRESO', dataKey: 'tipo_ingreso' }
        ],
        materias: (reporte.materias || []).map((m) => ({
            ...m,
            tipo_ingreso: m.tipo_ingreso || m.TIPO_INGRESO || '—',
        })),
    }

    generarPDF(reporteExtendido, opts)
}