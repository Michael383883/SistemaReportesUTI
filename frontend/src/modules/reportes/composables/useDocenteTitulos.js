import { ref } from 'vue'
import axios from 'axios'

const API_BASE = import.meta.env.VITE_API_URL

// Acepta claves en mayúscula o minúscula
const pick = (o, ...keys) => {
    for (const k of keys) {
        if (o?.[k] !== undefined && o?.[k] !== null) return o[k]
    }
    return ''
}

const normalizarTitulo = (t) => ({
    id: pick(t, 'id', 'ID', 'id_titulo'),
    tipo_titulo: pick(t, 'tipo_titulo', 'TIPO_TITULO'),
    titulo: pick(t, 'titulo', 'TITULO'),
    nombre_titulo: pick(t, 'nombre_titulo', 'NOMBRE_TITULO'),
    universidad: pick(t, 'universidad', 'UNIVERSIDAD'),
    pais: pick(t, 'pais', 'PAIS'),
    fecha_titulo: pick(t, 'fecha_titulo', 'FECHA_TITULO'),
    numero: pick(t, 'numero', 'NUMERO'),
    documentos: pick(t, 'documentos', 'DOCUMENTOS') || [],
})

const normalizarDocente = (d) => ({
    codigo: pick(d, 'codigo', 'CODIGO'),
    apellidos: pick(d, 'apellidos', 'APELLIDOS'),
    nombres: pick(d, 'nombres', 'NOMBRES'),
    ci: pick(d, 'ci', 'CI'),
    fecha_nac: pick(d, 'fecha_nac', 'FECHA_NAC'),
    sexo: pick(d, 'sexo', 'SEXO'),
    fecha_nombramiento: pick(d, 'fecha_nombramiento', 'FECHA_NOMBRAMIENTO'),
})

export function useDocenteTitulos() {
    const docente = ref(null)
    const titulos = ref([])
    const loading = ref(false)
    const error = ref(null)

    const cargar = async (codigo) => {
        loading.value = true
        error.value = null
        try {
            const token = localStorage.getItem('token')
            // AJUSTA esta URL a la de tu backend
            const { data } = await axios.get(
                `${API_BASE}/api/docentes/${codigo}/titulos`,
                { headers: { Authorization: `Bearer ${token}` } }
            )
            docente.value = normalizarDocente(data.docente ?? data.DOCENTE ?? {})
            const lista = data.titulos ?? data.TITULOS ?? []
            titulos.value = lista.map(normalizarTitulo)
        } catch (err) {
            error.value = err.response?.data?.message || 'Error al cargar los títulos del docente'
        } finally {
            loading.value = false
        }
    }

    return { docente, titulos, loading, error, cargar }
}