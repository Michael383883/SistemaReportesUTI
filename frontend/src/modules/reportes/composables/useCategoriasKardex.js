import { ref } from 'vue'
import axios from 'axios'

const API_BASE = import.meta.env.VITE_API_URL

export function useCategoriasKardex() {
    const categorias = ref([])
    const loading = ref(false)
    const error = ref(null)

    const authHeaders = () => {
        const token = localStorage.getItem('token')
        return { Authorization: `Bearer ${token}` }
    }

    async function cargar() {
        loading.value = true
        error.value = null
        try {
            const { data } = await axios.get(
                `${API_BASE}/api/categorias-clasificacion/kardex`,
                { headers: authHeaders() }
            )
            if (!Array.isArray(data)) {
                throw new Error('Respuesta inesperada del servidor (no es un array)')
            }
            categorias.value = data
        } catch (e) {
            console.error('[useCategoriasKardex] ERROR cargar →', e.response?.data || e.message)
            error.value = e.response?.data?.error || 'No se pudieron cargar las categorías de tipo de ingreso'
            categorias.value = []
        } finally {
            loading.value = false
        }
    }

    async function agregar(nombre) {
        const nombreLimpio = (nombre ?? '').trim()
        if (!nombreLimpio) return null

        try {
            const { data } = await axios.post(
                `${API_BASE}/api/categorias-clasificacion/kardex`,
                { nombre: nombreLimpio },
                { headers: authHeaders() }
            )
            if (!categorias.value.includes(data.nombre)) {
                categorias.value = [...categorias.value, data.nombre].sort()
            }
            return data.nombre
        } catch (e) {
            console.error('[useCategoriasKardex] ERROR agregar →', e.response?.data || e.message)
            error.value = e?.response?.data?.error || 'No se pudo crear la categoría'
            throw e
        }
    }

    return { categorias, loading, error, cargar, agregar }
}