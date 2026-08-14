import { ref } from 'vue'
import axios from 'axios'

const API_BASE = import.meta.env.VITE_API_URL ?? ''

// Tipos base que siempre están disponibles como referencia
const TIPOS_BASE = ['DIPLOMADO', 'ESPECIALIDAD', 'MAESTRÍA', 'DOCTORADO']

// Estado compartido (singleton): al declarar el ref FUERA de la función
// exportada, todos los componentes que importen este composable comparten
// la misma lista reactiva. Así, si en TituloCard se crea un tipo nuevo,
// también se ve reflejado donde sea que se use este composable.
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
        // si falla, se queda con los base + lo que ya hubiera en memoria
        console.error('No se pudieron cargar los tipos de título', e)
    } finally {
        loading.value = false
    }
}

// Agrega un tipo "al vuelo" cuando el usuario escribe uno que no existía,
// para que quede disponible de inmediato en todos los combobox (sin
// esperar a que el backend lo devuelva en el próximo fetch)
function agregarTipoLocal(valor) {
    const v = normalizar(valor)
    if (!v) return
    if (!tipos.value.includes(v)) {
        tipos.value = [...tipos.value, v].sort()
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