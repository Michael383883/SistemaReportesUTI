import { ref } from 'vue'
import axios from 'axios'

const API_BASE = import.meta.env.VITE_API_URL 

const categorias = ref([])
const cargado = ref(false)
const loading = ref(false)
const error = ref(null)

function authHeaders(extra = {}) {
    const token = localStorage.getItem('token')
    return {
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
        ...extra,
    }
}

function normalizar(valor) {
    return (valor || '').trim()
}

// ─── GET /api/categorias-clasificacion/documento ───
// Mismo endpoint que usa Configuración → Categorías: trae la unión de
// categorías "de uso" (ya usadas en algún documento) + las dadas de alta
// manualmente en el catálogo. Así ambas pantallas ven siempre lo mismo.
async function cargarCategorias(force = false) {
    if (cargado.value && !force) return
    loading.value = true
    error.value = null
    try {
        const { data } = await axios.get(`${API_BASE}/api/categorias-clasificacion/documento`, {
            headers: authHeaders(),
        })
        categorias.value = (Array.isArray(data) ? data : [])
            .map(normalizar)
            .filter(Boolean)
        cargado.value = true
    } catch (e) {
        error.value = e?.response?.data?.error || 'No se pudieron cargar las categorías'
        console.error('❌ Error al cargar categorías:', e)
    } finally {
        loading.value = false
    }
}

// ─── POST real al catálogo compartido ───
async function crearCategoria(valor) {
    const v = normalizar(valor)
    if (!v) return null

    const existente = categorias.value.find(c => c.toLowerCase() === v.toLowerCase())
    if (existente) return existente

    try {
        const { data } = await axios.post(
            `${API_BASE}/api/categorias-clasificacion/documento`,
            { nombre: v },
            { headers: authHeaders() }
        )
        categorias.value = [...categorias.value, data.nombre].sort()
        return data.nombre
    } catch (e) {
        console.error('❌ Error al crear categoría:', e)
        return null
    }
}

// Alias — ahora también persiste de verdad, ya no es solo memoria local.
async function agregarCategoriaLocal(valor) {
    return crearCategoria(valor)
}

export function useCategorias() {
    return {
        categorias,
        loading,
        error,
        cargarCategorias,
        crearCategoria,
        agregarCategoriaLocal,
    }
}