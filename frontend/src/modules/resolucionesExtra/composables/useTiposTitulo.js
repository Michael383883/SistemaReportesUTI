import { ref } from 'vue'
import axios from 'axios'

const API_BASE = import.meta.env.VITE_API_URL 

const tipos = ref([])
const cargado = ref(false)
const loading = ref(false)

function authHeaders(extra = {}) {
    const token = localStorage.getItem('token')
    return {
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
        ...extra,
    }
}

function normalizar(valor) {
    return (valor || '').trim().toUpperCase()
}

// ─── GET /api/categorias-clasificacion/titulo ───
// Mismo endpoint que usa Configuración → Tipos de título.
async function cargarTipos(force = false) {
    if (cargado.value && !force) return
    loading.value = true
    try {
        const { data } = await axios.get(`${API_BASE}/api/categorias-clasificacion/titulo`, {
            headers: authHeaders(),
        })
        tipos.value = (Array.isArray(data) ? data : [])
            .map(normalizar)
            .filter(Boolean)
            .sort()
        cargado.value = true
    } catch (e) {
        console.error('No se pudieron cargar los tipos de título', e)
    } finally {
        loading.value = false
    }
}

// ─── Ahora persiste de verdad en vez de solo memoria local ───
async function agregarTipoLocal(valor) {
    const v = normalizar(valor)
    if (!v) return

    if (tipos.value.includes(v)) return // ya existe, nada que hacer

    try {
        const { data } = await axios.post(
            `${API_BASE}/api/categorias-clasificacion/titulo`,
            { nombre: v },
            { headers: authHeaders() }
        )
        tipos.value = [...tipos.value, data.nombre].sort()
    } catch (e) {
        // Si ya existía (422) u otro error, no rompemos la UI: el combobox
        // seguirá funcionando con lo que ya está cargado.
        console.error('No se pudo crear el tipo de título', e)
    }
}

export function useTiposTitulo() {
    return {
        tipos,
        loading,
        cargarTipos,
        agregarTipoLocal,
    }
}