import { ref } from 'vue'
import axios from 'axios'

const API_BASE = import.meta.env.VITE_API_URL

export function usePeriodoTitulos() {
    const datos = ref([])
    const loading = ref(false)
    const error = ref(null)

    const buscar = async ({ anio, periodo, tipo_titulo }) => {
        loading.value = true
        error.value = null
        try {
            const token = localStorage.getItem('token')
            const { data } = await axios.get(`${API_BASE}/api/docentes/periodo-titulos`, {
                params: { anio, periodo, tipo_titulo: tipo_titulo || undefined },
                headers: { Authorization: `Bearer ${token}` },
            })
            datos.value = (data.docentes || []).map(d => ({ ...d, titulos: d.titulos || [] }))
        } catch (e) {
            datos.value = []
            error.value = e.response?.data?.message || 'Error al generar la lista'
        } finally {
            loading.value = false
        }
    }

    return { datos, loading, error, buscar }
}