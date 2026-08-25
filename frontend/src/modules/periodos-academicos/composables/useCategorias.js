import { ref } from 'vue'
import api from '@/shared/services/api'

const categorias = ref([])
const loading = ref(false)
const error = ref(null)
let yaCargado = false

export function useCategorias() {
    function clearError() {
        error.value = null
    }

    async function cargarCategorias(forzar = false) {
        if (yaCargado && !forzar) return
        loading.value = true
        error.value = null
        try {
            const { data } = await api.get('/api/categorias-clasificacion/documento')
            categorias.value = (Array.isArray(data) ? data : []).map((c) => ({ nombre: c }))
            yaCargado = true
        } catch (e) {
            error.value = 'No se pudieron cargar las categorías'
        } finally {
            loading.value = false
        }
    }

    async function crearCategoria(nombre) {
        const valor = nombre.trim()
        if (!valor) return { success: false, message: 'El nombre no puede estar vacío' }

        try {
            const { data } = await api.post('/api/categorias-clasificacion/documento', { nombre: valor })
            categorias.value = [...categorias.value, { nombre: data.nombre }]
            return { success: true, data }
        } catch (e) {
            return {
                success: false,
                message: e.response?.data?.error ?? e.response?.data?.message ?? 'Error al crear la categoría',
            }
        }
    }

    async function actualizarCategoria(nombreAnterior, nombreNuevo) {
        try {
            const { data } = await api.put('/api/categorias-clasificacion/documento', {
                anterior: nombreAnterior,
                nuevo: nombreNuevo,
            })
            categorias.value = categorias.value.map((c) =>
                c.nombre === nombreAnterior ? { nombre: data.nombre } : c
            )
            return { success: true, data }
        } catch (e) {
            return {
                success: false,
                message: e.response?.data?.error ?? e.response?.data?.message ?? 'Error al actualizar la categoría',
            }
        }
    }

    return { categorias, loading, error, cargarCategorias, crearCategoria, actualizarCategoria, clearError }
}