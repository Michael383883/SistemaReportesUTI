import { ref } from 'vue'
import axios from 'axios'

const API_BASE = import.meta.env.VITE_API_URL ?? ''

export function useMaterias() {
    const loading = ref(false)
    const error = ref(null)
    const materias = ref([])
    const periodos = ref([])

    function authHeaders(extra = {}) {
        const token = localStorage.getItem('token')
        return {
            ...(token ? { Authorization: `Bearer ${token}` } : {}),
            ...extra,
        }
    }

    // ─── Buscador general (SIN filtro por docente) — se mantiene ───
    async function listar(filtros = {}) {
        if (!filtros.anio) {
            materias.value = []
            return []
        }

        loading.value = true
        error.value = null
        try {
            const { data } = await axios.get(`${API_BASE}/api/materias`, {
                params: filtros,
                headers: authHeaders(),
            })
            materias.value = data
            return data
        } catch (e) {
            error.value = e?.response?.data?.error || 'No se pudo obtener las materias'
            console.error('Error al listar materias:', e)
            materias.value = []
            throw e
        } finally {
            loading.value = false
        }
    }

    // ─── NUEVO: buscador filtrado por DOCENTE (lo que realmente dictó) ───
    async function listarPorDocente(filtros = {}) {
        // docente es obligatorio para este endpoint
        if (!filtros.docente) {
            materias.value = []
            return []
        }

        loading.value = true
        error.value = null
        try {
            const { data } = await axios.get(`${API_BASE}/api/materias/docente`, {
                params: filtros, // { docente, anio, periodo, search }
                headers: authHeaders(),
            })
            materias.value = data
            return data
        } catch (e) {
            error.value = e?.response?.data?.error || 'No se pudo obtener las materias del docente'
            console.error('Error al listar materias por docente:', e)
            materias.value = []
            throw e
        } finally {
            loading.value = false
        }
    }

    async function obtenerPeriodos() {
        try {
            const { data } = await axios.get(`${API_BASE}/api/materias/periodos`, {
                headers: authHeaders(),
            })
            periodos.value = data
            return data
        } catch (e) {
            console.error('Error cargando periodos:', e)
            periodos.value = []
            throw e
        }
    }

    function reset() {
        materias.value = []
        error.value = null
    }

    return {
        loading,
        error,
        materias,
        periodos,
        listar,
        listarPorDocente, // ← NUEVO
        obtenerPeriodos,
        reset,
    }
}