/**
 * usePeriodoActual.js
 * ─────────────────────────────────────────────────────────────────
 * Devuelve el período académico y año que corresponden a HOY,
 * siguiendo el calendario de la FCE – UMSS:
 *
 *   Período 1 → 07 feb  al 30 jun   (mismo año)
 *   Período 2 → 01 sep  al 28/29 dic (mismo año)
 *   Período 3 → 01 ene  al 06 feb   (mismo año)
 *   Período 4 → 01 jul  al 31 ago   (curso de invierno, mismo año)
 *
 * El "año" que se expone es siempre el año calendario en curso,
 * salvo en Período 3 (ene–feb), donde se considera que el reporte
 * pertenece aún a la gestión del año anterior (ej. enero 2026 → 2025).
 * Si prefieres el año calendario puro, cambia la constante
 * PERIODO_3_USA_ANIO_ANTERIOR a false.
 * ─────────────────────────────────────────────────────────────────
 */

const PERIODO_3_USA_ANIO_ANTERIOR = true

/**
 * Dado un objeto Date, retorna { periodo, anio }.
 * @param {Date} fecha
 * @returns {{ periodo: number, anio: number }}
 */
export function calcularPeriodo(fecha = new Date()) {
    const mes = fecha.getMonth() + 1 // 1–12
    const dia = fecha.getDate()
    const anio = fecha.getFullYear()


    // ── Período 1: 1 en → 30 jun ─────────────────────────────────
    if (
        (mes === 1 || (mes === 6 && dia <= 30))
    ) {
        return { periodo: 1, anio }
    }


    // ── Período 2: 1 julio  → 31 dic ─────────────────────────────────
    // (mes 9, 10, 11, 12)
    return { periodo: 2, anio }
}

/**
 * Composable Vue 3.
 * Exporta `anio` y `periodo` como refs reactivos inicializados
 * con el período que corresponde a la fecha de hoy.
 * Los filtros de la vista pueden seguir modificándolos libremente.
 *
 * Uso:
 *   import { usePeriodoActual } from '@/modules/horiosadmin/composables/usePeriodoActual'
 *   const { anio, periodo } = usePeriodoActual()
 */
import { ref } from 'vue'

export function usePeriodoActual() {
    const { periodo: p, anio: a } = calcularPeriodo(new Date())
    //const { periodo: p, anio: a } = calcularPeriodo(new Date('2026-01-24'))
    const anio = ref(a)
    const periodo = ref(p)
    return { anio, periodo }
}