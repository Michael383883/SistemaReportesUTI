import { ref } from 'vue'
import axios from 'axios'

const API_BASE = import.meta.env.VITE_API_URL

export function useDocumentoTitulo() {
    const cargandoId = ref(null)
    const errorDoc = ref(null)

    // doc.id = ID_DOCUMENTO de CLASIFICACION_DOCUMENTO
    const obtenerBlob = async (doc) => {
        const token = localStorage.getItem('token')
        const { data } = await axios.get(
            `${API_BASE}/api/clasificaciones/${doc.id}/pdf`,
            { responseType: 'blob', headers: { Authorization: `Bearer ${token}` } }
        )
        return new Blob([data], { type: 'application/pdf' })
    }

    const ver = async (doc) => {
        cargandoId.value = doc.id
        errorDoc.value = null
        try {
            const url = URL.createObjectURL(await obtenerBlob(doc))
            window.open(url, '_blank')
            setTimeout(() => URL.revokeObjectURL(url), 60000)
        } catch (e) {
            errorDoc.value = 'No se pudo abrir el documento'
        } finally {
            cargandoId.value = null
        }
    }

    const descargar = async (doc) => {
        cargandoId.value = doc.id
        errorDoc.value = null
        try {
            const url = URL.createObjectURL(await obtenerBlob(doc))
            const a = document.createElement('a')
            a.href = url
            a.download = doc.nombre_archivo || `titulo_${doc.id}.pdf`
            document.body.appendChild(a)
            a.click()
            document.body.removeChild(a)
            URL.revokeObjectURL(url)
        } catch (e) {
            errorDoc.value = 'No se pudo descargar el documento'
        } finally {
            cargandoId.value = null
        }
    }

    return { cargandoId, errorDoc, ver, descargar }
}