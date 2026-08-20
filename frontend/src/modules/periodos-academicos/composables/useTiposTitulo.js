import { ref } from 'vue'
import axios from 'axios'

const API_BASE = import.meta.env.VITE_API_URL ?? ''

// Tipos base que siempre están disponibles como referencia
const TIPOS_BASE = ['DIPLOMADO', 'ESPECIALIDAD', 'MAESTRÍA', 'DOCTORADO']

// Estado compartido (singleton): al declarar el ref FUERA de la función
// exportada, todos los componentes que importen este composable comparten
// la misma lista reactiva.
const tipos = ref([...TIPOS_BASE])
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

// Trae los tipos de título reales desde el backend y los mezcla con los base
async function cargarTipos(force = false) {
    if (cargado.value && !force) return
    loading.value = true
    try {
        const { data } = await axios.get(`${API_BASE}/api/reporte-docentes/tipos-titulo`, {
            headers: authHeaders(),
        })
        const delBackend = Array.isArray(data) ? data.map(normalizar) : []
        const combinados = new Set([...TIPOS_BASE, ...delBackend, ...tipos.value])
        tipos.value = [...combinados].filter(Boolean).sort()
        cargado.value = true
    } catch (e) {
        console.error('No se pudieron cargar los tipos de título', e)
    } finally {
        loading.value = false
    }
}

// Agrega un tipo "al vuelo" en memoria (usado desde la sección de gestión).
// Al no existir tabla propia de tipos, un tipo "nuevo" solo queda realmente
// persistido cuando se guarda un título académico que lo usa.
function agregarTipoLocal(valor) {
    const v = normalizar(valor)
    if (!v) return { success: false, message: 'El nombre no puede estar vacío' }
    if (tipos.value.includes(v)) {
        return { success: false, message: 'Ya existe un tipo con ese nombre' }
    }
    tipos.value = [...tipos.value, v].sort()
    return { success: true, data: v }
}

// Renombra un tipo de título ya existente en TODOS los títulos que lo usan.
// Requiere el endpoint PUT /api/reporte-docentes/tipos-titulo { anterior, nuevo }
// en el backend (ver ClasificacionDocenteController::actualizarTipoTitulo).
async function actualizarTipo(anterior, nuevo) {
    const anteriorNorm = normalizar(anterior)
    const nuevoNorm = normalizar(nuevo)
    if (!nuevoNorm) return { success: false, message: 'El nombre no puede estar vacío' }

    try {
        const { data } = await axios.put(
            `${API_BASE}/api/reporte-docentes/tipos-titulo`,
            { anterior: anteriorNorm, nuevo: nuevoNorm },
            { headers: authHeaders() }
        )
        tipos.value = tipos.value
            .map((t) => (t === anteriorNorm ? nuevoNorm : t))
            .filter((t, i, arr) => arr.indexOf(t) === i) // por si el rename fusiona con uno existente
            .sort()
        return { success: true, data }
    } catch (e) {
        return {
            success: false,
            message: e.response?.data?.error ?? e.response?.data?.message ?? 'Error al actualizar el tipo de título',
        }
    }
}

export function useTiposTitulo() {
    return {
        tipos,
        loading,
        cargarTipos,
        agregarTipoLocal,
        actualizarTipo,
    }
}