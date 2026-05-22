// composables/useHorario.js
import { ref } from 'vue'
import axios from 'axios'

const API_BASE = import.meta.env.VITE_API_URL || '/api'

// ── Transforma la respuesta del backend al formato del frontend ──────────────
// Backend devuelve por docente:
//   { codigo, apellidos, nombres, materias: [{ plan, carrera, nivel, materia,
//     nombre, tipo, tipo2, grupo, carga_horaria, comp, compartido, orden }] }
//
// Frontend / PDF espera:
//   { codigo, nombre, total_ch, materias: [{ plan, materia, grp, ch, comp, compartido }] }
//
// Regla de total_ch: sumar carga_horaria SOLO de filas con comp == 0
// (las filas compartidas comp==1 son "recibidas" y no cuentan como carga propia)
function transformarHorario(data) {
    const docentesMap = {}

    for (const d of data.docentes) {
        const codigo = d.codigo

        if (!docentesMap[codigo]) {
            docentesMap[codigo] = {
                codigo,
                nombre: `${d.apellidos} ${d.nombres}`,
                total_ch: 0,
                materias: [],
            }
        }

        for (const m of d.materias) {
            const ch    = Number(m.carga_horaria) || 0
            const comp  = Number(m.comp)          || 0   // 0 = normal/comparte-a, 1 = comparte-de

            // Solo sumar al total las horas que el docente "posee" (comp != 1)
            if (comp !== 1) {
                docentesMap[codigo].total_ch += ch
            }

            docentesMap[codigo].materias.push({
                plan:       `${m.carrera} - ${m.nivel}`,          // "ADM - F"
                materia:    `${m.materia} ${m.nombre}`,            // "1301029 FINANZAS I"
                grp:        String(m.grupo).padStart(2, '0'),      // "20"
                ch,
                comp,
                compartido: m.compartido || '',                    // "Comparte a FIN"
            })
        }
    }

    // Gestión: backend devuelve { anio, periodo } → mostrar "1/2026"
    const g = data.gestion || {}
    const gestionStr = g.anio
        ? `${g.periodo}/${g.anio}`
        : String(g)

    return {
        gestion:  gestionStr,
        docentes: Object.values(docentesMap),
    }
}

export function useHorario() {
    const horario = ref(null)
    const loading = ref(false)
    const error   = ref(null)

    /**
     * Obtiene la carga horaria de la gestión actual.
     * @param {string|number|null} docente  – código docente; null = todos
     */
    const generarHorario = async (docente = null) => {
        loading.value = true
        error.value   = null
        horario.value = null

        try {
            const token   = localStorage.getItem('token')
            const payload = {}
            if (docente) payload.docente = String(docente)

            const { data } = await axios.post(
                `${API_BASE}/api/reporte-horario`,   // API_BASE ya incluye /api
                payload,
                { headers: { Authorization: `Bearer ${token}` } }
            )

            horario.value = transformarHorario(data)
        } catch (err) {
            error.value = err.response?.data?.message || 'Error al generar el horario'
        } finally {
            loading.value = false
        }
    }

    const limpiarHorario = () => {
        horario.value = null
        error.value   = null
    }

    return { horario, loading, error, generarHorario, limpiarHorario }
}