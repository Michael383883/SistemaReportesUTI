import { ref } from 'vue'
import axios from 'axios'

const API_BASE = import.meta.env.VITE_API_URL ?? ''

// Categorías base como respaldo, por si el backend aún no responde o
// falla la carga inicial (para que el combobox no se quede vacío)
const CATEGORIAS_BASE = [
    'DOCENTES TITULARES',
    'DOCENTES TEMPORALES',
    'EXAMEN DE SUFICIENCIA',
    'ACEFALA',
    'SIN EXAMEN DE SUFICIENCIA',
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
// Trae las categorías reales: son los valores DISTINTOS que ya se usaron
// en CLASIFICACION_DOCUMENTO.CATEGORIA (el backend no tiene una tabla
// CATEGORIAS aparte, las deriva directo de los documentos guardados).
async function cargarCategorias(force = false) {
    if (cargado.value && !force) return
    loading.value = true
    error.value = null
    try {
        const { data } = await axios.get(`${API_BASE}/api/categorias`, {
            headers: authHeaders(),
        })
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

// ─── "Crear" categoría ───
// No existe un endpoint POST /api/categorias en el backend: las categorías
// se derivan de CLASIFICACION_DOCUMENTO.CATEGORIA, así que una categoría
// "nueva" se persiste sola en cuanto se guarda el documento que la usa.
// Aquí solo se agrega en memoria para que aparezca de inmediato en el
// combobox mientras el usuario sigue en el formulario. Si ya existe
// (comparación sin importar mayúsculas/minúsculas), simplemente se
// reutiliza tal cual está guardada, sin duplicarla.
async function crearCategoria(valor) {
    const v = normalizar(valor)
    if (!v) return null

    const existente = categorias.value.find(c => c.toLowerCase() === v.toLowerCase())
    if (existente) return existente

    categorias.value = [...categorias.value, v]
    return v
}

// Alias explícito para el mismo comportamiento, por si se prefiere un
// nombre que no sugiera una llamada al backend.
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