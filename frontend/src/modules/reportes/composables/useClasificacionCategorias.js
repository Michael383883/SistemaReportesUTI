import { ref } from 'vue'
import axios from 'axios'

const API_BASE = import.meta.env.VITE_API_URL || '/api'

export function useClasificacionCategorias() {
    const categorias = ref([])
    const tieneDocumentos = ref(false)
    const documentos = ref([])
    const categoriasSeleccionadas = ref([]) // array de strings
    const loadingCategorias = ref(false)
    const loadingDocumentos = ref(false)
    const error = ref(null)

    const authHeaders = () => {
        const token = localStorage.getItem('token')
        return { Authorization: `Bearer ${token}` }
    }

    async function cargarCategorias(codDocente) {
        if (!codDocente) return
        loadingCategorias.value = true
        error.value = null
        try {
            const { data } = await axios.get(
                `${API_BASE}/api/clasificaciones/docente/${codDocente}/categorias`,
                { headers: authHeaders() }
            )
            categorias.value = data.categorias || []
            tieneDocumentos.value = !!data.tiene_documentos
        } catch (err) {
            console.error('[useClasificacionCategorias] ERROR categorías →', err.response?.data || err.message)
            error.value = err.response?.data?.error || 'No se pudieron cargar las categorías'
            categorias.value = []
            tieneDocumentos.value = false
        } finally {
            loadingCategorias.value = false
        }
    }

    // categoriasElegidas: array de strings, ej: ['Docentes Titulares', 'Docentes Temporales']
    // array vacío = no se aplicó ningún filtro → tabla se limpia
    async function cargarDocumentos(codDocente, categoriasElegidas) {
        if (!codDocente) return
        const lista = (categoriasElegidas || []).filter(Boolean)
        categoriasSeleccionadas.value = lista

        if (!lista.length) {
            documentos.value = []
            return
        }

        loadingDocumentos.value = true
        error.value = null
        try {
            const { data } = await axios.get(
                `${API_BASE}/api/clasificaciones/docente/${codDocente}/documentos`,
                {
                    params: { categorias: lista.join(',') },
                    headers: authHeaders(),
                }
            )
            documentos.value = data.documentos || []
        } catch (err) {
            console.error('[useClasificacionCategorias] ERROR documentos →', err.response?.data || err.message)
            error.value = err.response?.data?.error || 'No se pudieron cargar los documentos'
            documentos.value = []
        } finally {
            loadingDocumentos.value = false
        }
    }

    function limpiar() {
        documentos.value = []
        categoriasSeleccionadas.value = []
    }

    async function verPdf(idDocumento, descargar = false) {
        const url = `${API_BASE}/api/clasificaciones/${idDocumento}/pdf`
        const modo = descargar ? { modo: 'descargar' } : {}

        try {
            const blob = await axios.get(url, {
                params: modo,
                responseType: 'blob',
                headers: authHeaders(),
            })
            const blobUrl = URL.createObjectURL(new Blob([blob.data], { type: 'application/pdf' }))

            if (descargar) {
                const link = document.createElement('a')
                link.href = blobUrl
                link.setAttribute('download', `documento_${idDocumento}.pdf`)
                document.body.appendChild(link)
                link.click()
                document.body.removeChild(link)
                URL.revokeObjectURL(blobUrl)
            } else {
                window.open(blobUrl, '_blank')
            }
        } catch (err) {
            console.error('[useClasificacionCategorias] ERROR pdf →', err.response?.data || err.message)
            error.value = 'No se pudo abrir el documento'
        }
    }

    return {
        categorias, tieneDocumentos, documentos, categoriasSeleccionadas,
        loadingCategorias, loadingDocumentos, error,
        cargarCategorias, cargarDocumentos, limpiar, verPdf,
    }
}