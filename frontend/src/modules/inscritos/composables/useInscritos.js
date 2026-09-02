import { ref } from 'vue'

const BASE_URL = import.meta.env.VITE_API_URL 

function authHeaders(extra = {}) {
    const token = localStorage.getItem('token')
    return {
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
        ...extra,
    }
}

export function useInscritos() {
    const data = ref([])
    const loading = ref(false)
    const error = ref(null)
    const meta = ref({ anio: null, periodo: null, total_docentes: 0 })

    /**
     * Carga todos los docentes con sus inscritos.
     * @param {number} anio
     * @param {number} periodo
     */
    async function fetchInscritos(anio, periodo) {
        loading.value = true
        error.value = null
        try {
            const res = await fetch(
                `${BASE_URL}/api/admin/horarios/inscritos/listado?anio=${anio}&periodo=${periodo}`,
                { headers: authHeaders() }
            )
            if (!res.ok) throw new Error(`Error ${res.status}: ${res.statusText}`)
            const json = await res.json()
            data.value = json.data ?? []
            meta.value = {
                anio: json.anio,
                periodo: json.periodo,
                total_docentes: json.total_docentes,
            }
        } catch (e) {
            error.value = e.message
            data.value = []
        } finally {
            loading.value = false
        }
    }

    /**
     * Carga un docente específico con sus inscritos.
     * @param {string|number} codigoDocente
     * @param {number} anio
     * @param {number} periodo
     */
    async function fetchInscritosDocente(codigoDocente, anio, periodo) {
        loading.value = true
        error.value = null
        try {
            const res = await fetch(
                `${BASE_URL}/api/admin/horarios/inscritos/docente/${codigoDocente}?anio=${anio}&periodo=${periodo}`,
                { headers: authHeaders() }
            )
            if (!res.ok) throw new Error(`Error ${res.status}: ${res.statusText}`)
            const json = await res.json()
            data.value = json.data ?? []
            meta.value = {
                anio: json.anio,
                periodo: json.periodo,
                total_docentes: json.total_docentes,
            }
        } catch (e) {
            error.value = e.message
            data.value = []
        } finally {
            loading.value = false
        }
    }

    return { data, loading, error, meta, fetchInscritos, fetchInscritosDocente }
}