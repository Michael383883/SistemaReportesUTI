// modules/periodos-academicos/composables/useCategorias.js
import { ref } from 'vue'
import api from '@/shared/services/api' // mismo cliente que usa usePeriodosAcademicos (con baseURL configurada)

// Estado compartido entre todos los componentes que usan este composable.
const categorias = ref([])
const loading = ref(false)
const error = ref(null)

let yaCargado = false

// Convierte la respuesta cruda del backend en un array de categorías.
function normalizarCategorias(data) {
    if (Array.isArray(data)) return data
    if (data && Array.isArray(data.data)) return data.data
    if (data && Array.isArray(data.categorias)) return data.categorias
    return []
}

export function useCategorias() {
    function clearError() {
        error.value = null
    }

    async function cargarCategorias(forzar = false) {
        if (yaCargado && !forzar) return
        loading.value = true
        error.value = null
        try {
            const { data } = await api.get('/api/categorias')
            const lista = normalizarCategorias(data)

            if (!Array.isArray(data) && !lista.length) {
                console.error('[useCategorias] Respuesta inesperada del backend:', data)
                error.value = 'No se pudieron cargar las categorías (respuesta inesperada del servidor)'
            }

            // El backend devuelve strings (valores distintos de CLASIFICACION_DOCUMENTO.CATEGORIA).
            // Los normalizamos a { nombre } para tratarlos de forma uniforme en la UI.
            categorias.value = lista.map((c) => (typeof c === 'string' ? { nombre: c } : c))
            yaCargado = true
        } catch (e) {
            error.value = 'No se pudieron cargar las categorías'
        } finally {
            loading.value = false
        }
    }

    // No existe una tabla CATEGORIAS ni un endpoint de creación "suelta":
    // una categoría solo queda persistida cuando algún CLASIFICACION_DOCUMENTO
    // la usa. Por eso se agrega solo en memoria acá; se hará "real" apenas
    // se guarde un documento con ese nombre de categoría.
    function crearCategoria(nombre) {
        const valor = nombre.trim()
        if (!valor) return { success: false, message: 'El nombre no puede estar vacío' }

        const yaExiste = categorias.value.some(
            (c) => (c.nombre ?? c).toString().trim().toLowerCase() === valor.toLowerCase()
        )
        if (yaExiste) {
            return { success: false, message: 'Ya existe una categoría con ese nombre' }
        }

        categorias.value = [...categorias.value, { nombre: valor }]
        return { success: true, data: { nombre: valor } }
    }

    // Coincide con el backend real: PUT /api/categorias { anterior, nuevo }
    // Renombra la categoría en TODOS los documentos que ya la usan.
    async function actualizarCategoria(nombreAnterior, nombreNuevo) {
        try {
            const { data } = await api.put('/api/categorias', {
                anterior: nombreAnterior,
                nuevo: nombreNuevo,
            })
            categorias.value = categorias.value.map((c) =>
                (c.nombre ?? c) === nombreAnterior ? { nombre: nombreNuevo } : c
            )
            return { success: true, data }
        } catch (e) {
            return {
                success: false,
                message: e.response?.data?.error ?? e.response?.data?.message ?? 'Error al actualizar la categoría',
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
        clearError,
    }
}