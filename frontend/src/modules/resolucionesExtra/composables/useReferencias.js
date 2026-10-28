import { ref } from 'vue'
import axios from 'axios'

const API_BASE = import.meta.env.VITE_API_URL 

export function useReferencias() {
    const loading = ref(false)
    const error = ref(null)
    const referencias = ref([])
    const anios = ref([])

    function authHeaders(extra = {}) {
        const token = localStorage.getItem('token')
        return {
            ...(token ? { Authorization: `Bearer ${token}` } : {}),
            ...extra,
        }
    }

    // GET /api/referencias
    async function listar(filtros = {}) {
        loading.value = true
        error.value = null
        try {
            const { data } = await axios.get(`${API_BASE}/api/referencias`, {
                params: filtros,
                headers: authHeaders(),
            })
            referencias.value = data
            return data
        } catch (e) {
            error.value = e?.response?.data?.error || 'No se pudo obtener las referencias'
            console.error('Error al listar referencias:', e)
            referencias.value = []
            throw e
        } finally {
            loading.value = false
        }
    }

    // GET /api/referencias/anios
    async function obtenerAnios() {
        try {
            const { data } = await axios.get(`${API_BASE}/api/referencias/anios`, {
                headers: authHeaders(),
            })
            anios.value = data
            return data
        } catch (e) {
            console.error('Error cargando años:', e)
            anios.value = []
            throw e
        }
    }

    function reset() {
        referencias.value = []
        error.value = null
    }

    return {
        loading,
        error,
        referencias,
        anios,
        listar,
        obtenerAnios,
        reset,
    }
}