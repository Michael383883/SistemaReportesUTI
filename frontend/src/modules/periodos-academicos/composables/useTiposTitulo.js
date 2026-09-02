import { ref } from 'vue'
import axios from 'axios'

const API_BASE = import.meta.env.VITE_API_URL 

const tipos = ref([])
const cargado = ref(false)
const loading = ref(false)

function authHeaders(extra = {}) {
    const token = localStorage.getItem('token')
    return { ...(token ? { Authorization: `Bearer ${token}` } : {}), ...extra }
}

function normalizar(valor) {
    return (valor || '').trim().toUpperCase()
}

async function cargarTipos(force = false) {
    if (cargado.value && !force) return
    loading.value = true
    try {
        const { data } = await axios.get(`${API_BASE}/api/categorias-clasificacion/titulo`, {
            headers: authHeaders(),
        })
        tipos.value = (Array.isArray(data) ? data : []).map(normalizar).sort()
        cargado.value = true
    } catch (e) {
        console.error('No se pudieron cargar los tipos de título', e)
    } finally {
        loading.value = false
    }
}

async function agregarTipoLocal(valor) {
    const v = normalizar(valor)
    if (!v) return { success: false, message: 'El nombre no puede estar vacío' }

    try {
        const { data } = await axios.post(
            `${API_BASE}/api/categorias-clasificacion/titulo`,
            { nombre: v },
            { headers: authHeaders() }
        )
        tipos.value = [...tipos.value, data.nombre].sort()
        return { success: true, data }
    } catch (e) {
        return {
            success: false,
            message: e.response?.data?.error ?? e.response?.data?.message ?? 'Error al crear el tipo',
        }
    }
}

async function actualizarTipo(anterior, nuevo) {
    const anteriorNorm = normalizar(anterior)
    const nuevoNorm = normalizar(nuevo)
    if (!nuevoNorm) return { success: false, message: 'El nombre no puede estar vacío' }

    try {
        const { data } = await axios.put(
            `${API_BASE}/api/categorias-clasificacion/titulo`,
            { anterior: anteriorNorm, nuevo: nuevoNorm },
            { headers: authHeaders() }
        )
        tipos.value = tipos.value
            .map((t) => (t === anteriorNorm ? data.nombre : t))
            .filter((t, i, arr) => arr.indexOf(t) === i)
            .sort()
        return { success: true, data }
    } catch (e) {
        return {
            success: false,
            message: e.response?.data?.error ?? e.response?.data?.message ?? 'Error al actualizar el tipo',
        }
    }
}

export function useTiposTitulo() {
    return { tipos, loading, cargarTipos, agregarTipoLocal, actualizarTipo }
}