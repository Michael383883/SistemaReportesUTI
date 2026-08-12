/**
 * usePeriodoAcademicoActual.js
 * ─────────────────────────────────────────────────────────────────
 * Calcula el período académico (1, 2, 3 o 4) y año que corresponden a
 * HOY, usando los rangos reales guardados en `periodos_academicos`
 * (los mismos que edita el admin en /periodos-academicos).
 *
 * Replica exactamente la lógica de
 * ReporteDocenteController::periodoActualSegunFecha() en el backend,
 * para que el valor por defecto que ve el usuario en el frontend
 * coincida siempre con el que usaría el backend si no se mandan
 * anio/periodo explícitos.
 * ─────────────────────────────────────────────────────────────────
 */

import { ref } from 'vue'
import { usePeriodosAcademicos } from './usePeriodosAcademicos'

function calcularPeriodoFallback(fecha = new Date()) {
    const mes = fecha.getMonth() + 1
    return { periodo: mes <= 6 ? 1 : 2, anio: fecha.getFullYear() }
}

function calcularPeriodoDesdeRangos(rangos, fecha = new Date()) {
    const anio = fecha.getFullYear()
    const mesDia = `${String(fecha.getMonth() + 1).padStart(2, '0')}-${String(fecha.getDate()).padStart(2, '0')}`

    const candidatos = rangos.filter((r) => mesDia >= r.inicio && mesDia <= r.fin)
    if (!candidatos.length) return calcularPeriodoFallback(fecha)

    // Si hay solapamiento entre rangos, gana el que empezó más
    // recientemente (mismo criterio que el backend).
    const elegido = candidatos.reduce((a, b) => (b.inicio > a.inicio ? b : a))
    return { periodo: Number(elegido.periodo), anio }
}

/**
 * Uso:
 *   const { anio, periodo, promesa } = usePeriodoAcademicoActual()
 *   // anio/periodo arrancan con un cálculo de respaldo instantáneo
 *   // (enero-junio=1, julio-diciembre=2) y se corrigen solos apenas
 *   // llega la respuesta real de /api/periodos-academicos.
 *   // 'promesa' resuelve cuando ya se aplicó el valor real, útil si
 *   // necesitas esperarlo antes de hacer el primer fetch.
 */
export function usePeriodoAcademicoActual() {
    const fallback = calcularPeriodoFallback()
    const anio = ref(fallback.anio)
    const periodo = ref(fallback.periodo)
    const listo = ref(false)

    const { periodos, fetchPeriodos } = usePeriodosAcademicos()

    const promesa = fetchPeriodos().then(() => {
        if (periodos.value.length) {
            const actual = calcularPeriodoDesdeRangos(periodos.value)
            anio.value = actual.anio
            periodo.value = actual.periodo
        }
        listo.value = true
    })

    return { anio, periodo, listo, promesa }
}