import { ref } from 'vue'
import axios from 'axios'

const API_BASE = import.meta.env.VITE_API_URL

function authHeaders() {
    const token = localStorage.getItem('token')
    return { Authorization: `Bearer ${token}` }
}

export function useCategoriasKardex() {
    const categorias = ref([]) // array de strings, ej: ['DOCENTES TITULARES', 'ACEFALA', ...]
    const loading = ref(false)
    const error = ref(null)

    function clearError() {
        error.value = null
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

    // Crea una nueva categoría en el catálogo KARDEX y la agrega en memoria
    // (ordenada) sin necesidad de volver a pedir todo el listado al backend.
    async function agregar(nombre) {
        const nombreLimpio = (nombre ?? '').trim()
        if (!nombreLimpio) return { success: false, message: 'El nombre no puede estar vacío' }

        try {
            const { data } = await axios.post(
                `${API_BASE}/api/categorias-clasificacion/kardex`,
                { nombre: nombreLimpio },
                { headers: authHeaders() }
            )
            if (!categorias.value.includes(data.nombre)) {
                categorias.value = [...categorias.value, data.nombre].sort()
            }
            return { success: true, nombre: data.nombre }
        } catch (e) {
            console.error('[useCategoriasKardex] ERROR agregar →', e.response?.data || e.message)
            const msg = e?.response?.data?.error || 'No se pudo crear la categoría'
            error.value = msg
            return { success: false, message: msg }
        }
    }

    // Renombra una categoría existente (anterior → nuevo). El backend
    // propaga el cambio a GRUPOS y RESOLUCION_DETALLE también.
    async function actualizar(anterior, nuevo) {
        const nuevoLimpio = (nuevo ?? '').trim()
        if (!nuevoLimpio) return { success: false, message: 'El nombre no puede estar vacío' }

        try {
            const { data } = await axios.put(
                `${API_BASE}/api/categorias-clasificacion/kardex`,
                { anterior, nuevo: nuevoLimpio },
                { headers: authHeaders() }
            )
            categorias.value = categorias.value
                .map((c) => (c === anterior ? data.nombre : c))
                .sort()
            return { success: true, nombre: data.nombre }
        } catch (e) {
            console.error('[useCategoriasKardex] ERROR actualizar →', e.response?.data || e.message)
            const msg = e?.response?.data?.error || 'No se pudo actualizar la categoría'
            error.value = msg
            return { success: false, message: msg }
        }
    }

    // Consulta si la categoría está en uso y en qué tablas.
    // Devuelve { success: true, en_uso, total, detalle: [{ tabla, etiqueta, cantidad }] }
    // o { success: false, message } si la verificación falló.
    async function consultarUso(nombre) {
        try {
            const { data } = await axios.get(
                `${API_BASE}/api/categorias-clasificacion/kardex/uso`,
                { params: { nombre }, headers: authHeaders() }
            )
            return { success: true, ...data }
        } catch (e) {
            console.error('[useCategoriasKardex] ERROR consultarUso →', e.response?.data || e.message)
            return {
                success: false,
                message: e?.response?.data?.error || 'No se pudo verificar el uso de la categoría',
            }
        }
    }

    // Elimina la categoría del catálogo. El backend vuelve a validar el uso:
    // si está en uso responde 409 con el detalle de dónde se usa.
    async function eliminar(nombre) {
        try {
            await axios.delete(
                `${API_BASE}/api/categorias-clasificacion/kardex`,
                {
                    data: { nombre },
                    headers: authHeaders(),
                }
            )
            categorias.value = categorias.value.filter((c) => c !== nombre)
            return { success: true }
        } catch (e) {
            if (e?.response?.status === 409) {
                return {
                    success: false,
                    enUso: true,
                    detalle: e.response.data?.detalle ?? [],
                    total: e.response.data?.total ?? 0,
                    message: e.response.data?.error || 'La categoría está en uso',
                }
            }
            console.error('[useCategoriasKardex] ERROR eliminar →', e.response?.data || e.message)
            const msg = e?.response?.data?.error || 'No se pudo eliminar la categoría'
            error.value = msg
            return { success: false, message: msg }
        }
    }

    return { categorias, loading, error, cargar, agregar, actualizar, consultarUso, eliminar, clearError }
}