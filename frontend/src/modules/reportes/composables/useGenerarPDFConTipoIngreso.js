// composables/useGenerarPDFConTipoIngreso.js
// Hereda TODO de useGenerarPDF y solo agrega la columna TIPO INGRESO

import { generarPDF } from './useGenerarPDF'

export function generarPDFConTipoIngreso(reporte, opts = {}) {
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