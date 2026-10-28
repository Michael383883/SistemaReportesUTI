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

    // Consulta si la categoría está en uso y en qué tablas.
    // Devuelve { success: true, en_uso, total, detalle: [{ tabla, etiqueta, cantidad }] }
    // o { success: false, message } si la verificación falló.
    async function consultarUso(nombre) {
        try {
            const { data } = await api.get('/api/categorias-clasificacion/documento/uso', {
                params: { nombre },
            })
            return { success: true, ...data }
        } catch (e) {
            return {
                success: false,
                message: e.response?.data?.error ?? e.response?.data?.message ?? 'No se pudo verificar el uso de la categoría',
            }
        }
    }

    // Elimina la categoría. El backend vuelve a validar el uso:
    // si está en uso responde 409 con el detalle de dónde se usa.
    async function eliminarCategoria(nombre) {
        try {
            await api.delete('/api/categorias-clasificacion/documento', { data: { nombre } })
            categorias.value = categorias.value.filter((c) => c.nombre !== nombre)
            return { success: true }
        } catch (e) {
            if (e.response?.status === 409) {
                return {
                    success: false,
                    enUso: true,
                    detalle: e.response.data?.detalle ?? [],
                    total: e.response.data?.total ?? 0,
                    message: e.response.data?.error ?? 'La categoría está en uso',
                }
            }
            return {
                success: false,
                message: e.response?.data?.error ?? e.response?.data?.message ?? 'Error al eliminar la categoría',
            }
        }
    }

    return {
        categorias,
        loading,
        error,
        cargarCategorias,
        crearCategoria,
        actualizarCategoria,
        consultarUso,
        eliminarCategoria,
        clearError,
    }
}