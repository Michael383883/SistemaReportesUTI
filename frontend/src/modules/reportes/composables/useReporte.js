import { ref } from 'vue'
import axios from 'axios'

const API_BASE = import.meta.env.VITE_API_URL || '/api'

export function useReporte() {
    const reporte = ref(null)
    const loading = ref(false)
    const error = ref(null)

    /**
     * Genera el reporte de un docente.
     * @param {number|string} codigoDocente - Código SIS del docente
     * @param {number|null}   anio          - Año desde (opcional)
     */
    const generarReporte = async (codigoDocente, anio = null) => {
        loading.value = true
        error.value = null
        reporte.value = null

        try {
            const token = localStorage.getItem('token')
            const payload = { docente: Number(codigoDocente) }
            if (anio) payload.anio = Number(anio)

            const response = await axios.post(
                `${API_BASE}/api/reporte-docente`,
                payload,
                { headers: { Authorization: `Bearer ${token}` } }
            )
            reporte.value = response.data
        } catch (err) {
            error.value = err.response?.data?.message || 'Error al generar el reporte'
        } finally {
            loading.value = false
        }
    }

    const limpiarReporte = () => {
        reporte.value = null
        error.value = null
    }

    return {
        reporte,
        loading,
        error,
        generarReporte,
        limpiarReporte,
    }
}