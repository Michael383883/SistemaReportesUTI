import { ref } from 'vue'
import axios from 'axios'

const API_BASE = import.meta.env.VITE_API_URL 

export function useReporteClasificacion() {
    const loading = ref(false)
    const error = ref(null)

    function authHeaders(extra = {}) {
        const token = localStorage.getItem('token')
        return {
            ...(token ? { Authorization: `Bearer ${token}` } : {}),
            ...extra,
        }
    }

    // GET /api/reportes/clasificacion/docente/{cod_docente}
    async function porDocente(cod_docente) {
        loading.value = true
        error.value = null
        try {
            const { data } = await axios.get(
                `${API_BASE}/api/reportes/clasificacion/docente/${cod_docente}`,
                { headers: authHeaders() }
            )
            return data
        } catch (e) {
            error.value = e?.response?.data?.error || 'No se pudo obtener la línea de tiempo del docente'
            throw e
        } finally {
            loading.value = false
        }
    }

    // GET /api/reportes/clasificacion/por-referencia?nro=...
    async function porReferencia(nro) {
        loading.value = true
        error.value = null
        try {
            const { data } = await axios.get(
                `${API_BASE}/api/reportes/clasificacion/por-referencia`,
                { params: { nro }, headers: authHeaders() }
            )
            return data
        } catch (e) {
            error.value = e?.response?.data?.error || 'No se pudo obtener docentes por referencia'
            throw e
        } finally {
            loading.value = false
        }
    }

    return { loading, error, porDocente, porReferencia }
}