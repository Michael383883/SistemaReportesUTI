import { ref } from 'vue'
import axios from 'axios'

const API_BASE = import.meta.env.VITE_API_URL ?? ''

// Categorías base como respaldo, por si el backend aún no responde o
// falla la carga inicial (para que el combobox no se quede vacío)
const CATEGORIAS_BASE = [
    'Docentes Titulares',
    'Docentes Temporales',
    'Examen de suficiencia',
    'Acefala',
    'Sin Examen de suficiencia',
]

// Estado compartido (singleton): al declarar los refs FUERA de la función
// exportada, todos los componentes que importen este composable comparten
// la misma lista reactiva y el mismo estado de carga.
const categorias = ref([...CATEGORIAS_BASE])
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

// ─── GET /api/categorias ───
// Trae las categorías reales guardadas en la base de datos.
async function cargarCategorias(force = false) {
    if (cargado.value && !force) return
    loading.value = true
    error.value = null
    try {
        const { data } = await axios.get(`${API_BASE}/api/categorias`, {
            headers: authHeaders(),
        })
        // Se asume que el backend devuelve un array de strings o de objetos
        // { id, nombre }. Cubrimos ambos casos.
        const delBackend = Array.isArray(data)
            ? data.map(item => normalizar(typeof item === 'string' ? item : item?.nombre))
            : []

        const combinadas = new Set([...CATEGORIAS_BASE, ...delBackend.filter(Boolean)])
        categorias.value = [...combinadas]
        cargado.value = true
    } catch (e) {
        error.value = e?.response?.data?.error || 'No se pudieron cargar las categorías'
        console.error('❌ Error al cargar categorías:', e)
        // Se mantiene lo que ya hubiera en memoria (al menos las base)
    } finally {
        loading.value = false
    }
}

// ─── POST /api/categorias ───
// Crea la categoría en la base de datos. Si el backend ya tiene una
// categoría con ese nombre, se asume que puede devolver la existente
// (idealmente el backend hace upsert / valida duplicados por su cuenta).
async function crearCategoria(valor) {
    const v = normalizar(valor)
    if (!v) return null

    // Si ya existe localmente (comparación case-insensitive), no la
    // volvemos a crear en el backend, solo la dejamos seleccionada.
    const yaExiste = categorias.value.some(c => c.toLowerCase() === v.toLowerCase())
    if (yaExiste) return v

    loading.value = true
    error.value = null
    try {
        const { data } = await axios.post(
            `${API_BASE}/api/categorias`,
            { nombre: v },
            { headers: authHeaders() }
        )

        const nombreGuardado = normalizar(
            typeof data?.nombre === 'string' ? data.nombre : (data?.categoria?.nombre ?? v)
        ) || v

        categorias.value = [...categorias.value, nombreGuardado]
        return nombreGuardado
    } catch (e) {
        error.value = e?.response?.data?.error || 'No se pudo guardar la categoría'
        console.error('❌ Error al crear categoría:', e)
        throw e
    } finally {
        loading.value = false
    }
}

// Mantiene disponible el agregado "solo en memoria" por si en algún
// punto quieres seguir usando el combobox sin persistir de inmediato
// (por ejemplo, mientras el usuario todavía está escribiendo).
function agregarCategoriaLocal(valor) {
    const v = normalizar(valor)
    if (!v) return
    const yaExiste = categorias.value.some(c => c.toLowerCase() === v.toLowerCase())
    if (!yaExiste) {
        categorias.value = [...categorias.value, v]
    }
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