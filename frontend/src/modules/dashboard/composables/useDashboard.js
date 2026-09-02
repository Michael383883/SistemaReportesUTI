import { ref, computed } from 'vue'
import axios from 'axios'

const API_BASE = import.meta.env.VITE_API_URL

export function useDashboard() {
    const loading = ref(false)
    const error = ref(null)
    const kpis = ref(null)

    async function fetchKpis() {
        loading.value = true
        error.value = null
        try {
            const token = localStorage.getItem('token')
            const { data } = await axios.get(`${API_BASE}/api/admin/dashboard/kpis`, {
                headers: { Authorization: `Bearer ${token}` },
            })

            if (data.success) kpis.value = data.data
        } catch (e) {
            error.value = e?.response?.data?.message ?? 'Error al cargar el dashboard.'
        } finally {
            loading.value = false
        }
    }

    const resumen = computed(() => kpis.value?.resumen ?? {})
    const topDocentes = computed(() => kpis.value?.top_docentes ?? [])
    const resolucionesRecientes = computed(() => kpis.value?.resoluciones_recientes ?? [])
    const documentosRecientes = computed(() => kpis.value?.documentos_recientes ?? [])
    const distribucionTipo = computed(() => kpis.value?.distribucion_tipo ?? [])
    const porcentajeDocActivos = computed(() => {
        const r = resumen.value
        if (!r.total_docentes) return 0
        return Math.round((r.docentes_activos / r.total_docentes) * 100)
    })

    return {
        loading, error, resumen,
        topDocentes, resolucionesRecientes, documentosRecientes,
        distribucionTipo, porcentajeDocActivos,
        fetchKpis,
    }
}