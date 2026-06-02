// composables/useHorario.js
import { ref } from 'vue'
import axios from 'axios'

const API_BASE = import.meta.env.VITE_API_URL || '/api'

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
            const ch = Number(m.carga_horaria) || 0
            const comp = Number(m.comp) || 0

            if (comp !== 1) {
                docentesMap[codigo].total_ch += ch
            }

            docentesMap[codigo].materias.push({
                plan: `${m.carrera} - ${m.nivel}`,
                materia: `${m.materia} ${m.nombre}`,
                grp: String(m.grupo).padStart(2, '0'),
                ch,
                comp,
                compartido: m.compartido || '',
            })
        }
    }

    const g = data.gestion || {}
    const gestionStr = g.anio
        ? `${g.periodo}/${g.anio}`
        : String(g)

    return {
        gestion: gestionStr,
        docentes: Object.values(docentesMap),
    }
}

export function useHorario() {
    const horario = ref(null)
    const loading = ref(false)
    const error = ref(null)

    /**
     * Obtiene la carga horaria de la gestión actual.
     * @param {string|number|null} docente – código docente; null = todos
     */
    const generarHorario = async (docente = null) => {
        loading.value = true
        error.value = null
        horario.value = null

        try {
            const token = localStorage.getItem('token')

            // GET con parámetros en query string (coincide con Route::get)
            const params = {}
            if (docente) params.docente = String(docente)

            const { data } = await axios.get(
                `${API_BASE}/api/reporte-horario`,   // ← sin /api/ duplicado
                {
                    params,
                    headers: { Authorization: `Bearer ${token}` },
                }
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
        error.value = null
    }

    return { horario, loading, error, generarHorario, limpiarHorario }
}