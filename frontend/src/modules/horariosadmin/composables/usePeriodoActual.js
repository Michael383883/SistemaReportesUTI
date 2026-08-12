/**
 * usePeriodoActual.js
 * ─────────────────────────────────────────────────────────────────
 * Devuelve el período académico (1 o 2) y año que corresponden a HOY.
 *
 * El endpoint de horarios (ReporteDocenteController::horario) solo
 * trabaja con los 2 semestres regulares, no con verano/invierno.
 *
 * El umbral entre período 1 y período 2 ya NO está hardcodeado: se lee
 * del `inicio` del Semestre II en la tabla `periodos_academicos`
 * (la misma que edita el admin en /periodos-academicos). Así, si el
 * admin mueve la fecha de inicio del Semestre II, este composable lo
 * refleja automáticamente sin tocar código.
 * ─────────────────────────────────────────────────────────────────
 */

import { ref } from 'vue'
import { usePeriodosAcademicos } from '@/modules/periodos-academicos/composables/usePeriodosAcademicos'

/**
 * Fallback mientras llega la respuesta del backend (o si falla):
 * mismo criterio simple de siempre, enero-junio = 1, julio-diciembre = 2.
 */
function calcularPeriodoFallback(fecha = new Date()) {
    const mes = fecha.getMonth() + 1 // 1–12
    const anio = fecha.getFullYear()
    return { periodo: mes <= 6 ? 1 : 2, anio }
}

/**
 * Usa el `inicio` real del Semestre II (formato 'MM-DD') guardado en la
 * DB como umbral: antes de esa fecha = período 1, desde esa fecha = período 2.
 */
function calcularPeriodoDesdeRangos(rangos, fecha = new Date()) {
    const anio = fecha.getFullYear()
    const mesDia = `${String(fecha.getMonth() + 1).padStart(2, '0')}-${String(fecha.getDate()).padStart(2, '0')}`

    const semestre2 = rangos.find((r) => r.periodo === '2')
    if (!semestre2) return calcularPeriodoFallback(fecha)

    const periodo = mesDia < semestre2.inicio ? 1 : 2
    return { periodo, anio }
}

/**
 * Composable Vue 3.
 * Retorna `anio` y `periodo` como refs reactivos. Al montar, empiezan
 * con el cálculo de respaldo (instantáneo) y se actualizan solos en
 * cuanto llega la respuesta real de /api/periodos-academicos.
 * Los filtros de la vista pueden seguir modificándolos libremente.
 *
 * Uso:
 *   import { usePeriodoActual } from '@/modules/horiosadmin/composables/usePeriodoActual'
 *   const { anio, periodo } = usePeriodoActual()
 */
export function usePeriodoActual() {
    const fallback = calcularPeriodoFallback()
    const anio = ref(fallback.anio)
    const periodo = ref(fallback.periodo)

    const { periodos, fetchPeriodos } = usePeriodosAcademicos()

    fetchPeriodos().then(() => {
        if (periodos.value.length) {
            const actual = calcularPeriodoDesdeRangos(periodos.value)
            anio.value = actual.anio
            periodo.value = actual.periodo
        }
    })

    return { anio, periodo }
}