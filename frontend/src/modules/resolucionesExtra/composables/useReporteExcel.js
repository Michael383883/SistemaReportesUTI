import { ref } from 'vue'
import axios from 'axios'

const API_BASE = import.meta.env.VITE_API_URL ?? ''

export function useReporteExcel() {
    const loading = ref(false)
    const error = ref(null)
    const preview = ref([])
    const gestionEtiqueta = ref('')
    const versionEtiqueta = ref('')
    const totalFilas = ref(0)

    function authHeaders(extra = {}) {
        const token = localStorage.getItem('token')
        return {
            ...(token ? { Authorization: `Bearer ${token}` } : {}),
            ...extra,
        }
    }

    // GET /api/reportes/docentes-clasificados/preview
    async function previsualizar({ gestion_desde, gestion_hasta, periodo, version } = {}) {
        loading.value = true
        error.value = null
        try {
            const { data } = await axios.get(
                `${API_BASE}/api/reportes/docentes-clasificados/preview`,
                {
                    params: {
                        gestion_desde: gestion_desde || undefined,
                        gestion_hasta: gestion_hasta || undefined,
                        periodo: periodo || undefined,
                        version: version || undefined,
                    },
                    headers: authHeaders(),
                }
            )

            if (!data.ok) {
                error.value = data.error || 'No se pudo obtener la vista previa'
                preview.value = []
                return
            }

            preview.value = data.data
            gestionEtiqueta.value = data.gestion
            versionEtiqueta.value = data.version
            totalFilas.value = data.total_filas
        } catch (e) {
            error.value = e?.response?.data?.error || 'No se pudo obtener la vista previa'
            preview.value = []
            throw e
        } finally {
            loading.value = false
        }
    }

    // Construye la URL de descarga real del Excel (mismo endpoint que ya existe)
    function urlDescarga({ gestion_desde, gestion_hasta, periodo, version } = {}) {
        const params = new URLSearchParams()
        if (gestion_desde) params.set('gestion_desde', gestion_desde)
        if (gestion_hasta) params.set('gestion_hasta', gestion_hasta)
        if (periodo) params.set('periodo', periodo)
        if (version) params.set('version', version)

        return `${API_BASE}/api/reportes/docentes-clasificados/excel?${params.toString()}`
    }

    function reset() {
        error.value = null
        preview.value = []
        gestionEtiqueta.value = ''
        versionEtiqueta.value = ''
        totalFilas.value = 0
    }

    return {
        loading,
        error,
        preview,
        gestionEtiqueta,
        versionEtiqueta,
        totalFilas,
        previsualizar,
        urlDescarga,
        reset,
    }
}