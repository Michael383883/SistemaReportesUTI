import { ref } from 'vue'
import axios from 'axios'

const API_BASE = import.meta.env.VITE_API_URL ?? ''

export function useReporteClasificacion() {
    const loading = ref(false)
    const error = ref(null)

    function authHeaders(extra = {}) {
        const token = localStorage.getItem('token')
        return {
            ...(token ? { Authorization: `Bearer ${token}` } : {}),
            ...extra,
        }
    }

    async function porDocente(cod_docente) {
        loading.value = true
        error.value = null
        try {
            const { data } = await axios.get(
                `${API_BASE}/api/reportes/clasificacion/docente/${cod_docente}`,
                { headers: authHeaders() }
            )
            return data
        } catch (e) {
            error.value = e?.response?.data?.error || 'No se pudo obtener la línea de tiempo del docente'
            throw e
        } finally {
            loading.value = false
        }
    }

    async function porReferencia(nro) {
        loading.value = true
        error.value = null
        try {
            const { data } = await axios.get(
                `${API_BASE}/api/reportes/clasificacion/por-referencia`,
                { params: { nro }, headers: authHeaders() }
            )
            return data
        } catch (e) {
            error.value = e?.response?.data?.error || 'No se pudo obtener docentes por referencia'
            throw e
        } finally {
            loading.value = false
        }
    }

    // NUEVO: abre o descarga el PDF guardado en CLASIFICACION_DOCUMENTO
    async function verPdfClasificacion(nroReferencia, codDocente, descargar = false) {
        // 1) buscar el ID_CLASIFICACION_DOCENTE
        const { data } = await axios.get(
            `${API_BASE}/api/reportes/clasificacion/id-por-referencia`,
            {
                params: { nro: nroReferencia, cod_docente: codDocente },
                headers: authHeaders(),
            }
        )

        if (!data.ok) {
            throw new Error('No encontrado en clasificación docente')
        }

        // 2) usar el endpoint YA EXISTENTE que sirve el PDF
        const url = `${API_BASE}/api/clasificaciones/${data.id}/pdf`

        const resp = await axios.get(url, {
            responseType: 'blob',
            headers: authHeaders(),
        })

        const blobUrl = URL.createObjectURL(new Blob([resp.data], { type: 'application/pdf' }))

        if (descargar) {
            const link = document.createElement('a')
            link.href = blobUrl
            link.setAttribute('download', 'documento_clasificacion.pdf')
            document.body.appendChild(link)
            link.click()
            document.body.removeChild(link)
            URL.revokeObjectURL(blobUrl)
        } else {
            window.open(blobUrl, '_blank')
        }
    }

    
    return { loading, error, porDocente, porReferencia, verPdfClasificacion }
}