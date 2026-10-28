import { ref } from 'vue'
import axios from 'axios'

const API_BASE = import.meta.env.VITE_API_URL

export function useReporteDocente() {
    const loading = ref(false)
    const error = ref(null)
    const datos = ref([])
    const total = ref(0)
    const tiposTitulo = ref([])
    const loadingTipos = ref(false)

    function authHeaders(extra = {}) {
        const token = localStorage.getItem('token')
        return {
            ...(token ? { Authorization: `Bearer ${token}` } : {}),
            ...extra,
        }
    }

    // GET /api/reporte-docentes/tipos-titulo
    async function cargarTiposTitulo() {
        loadingTipos.value = true
        try {
            const { data } = await axios.get(`${API_BASE}/api/reporte-docentes/tipos-titulo`, {
                headers: authHeaders(),
            })
            tiposTitulo.value = data
        } catch (e) {
            tiposTitulo.value = []
        } finally {
            loadingTipos.value = false
        }
    }

    // GET /api/reporte-docentes/con-titulo
    async function buscar(filtros) {
        loading.value = true
        error.value = null
        try {
            const { data } = await axios.get(`${API_BASE}/api/reporte-docentes/con-titulo`, {
                params: filtros,
                headers: authHeaders(),
            })
            datos.value = data.data
            total.value = data.total
        } catch (e) {
            error.value = e?.response?.data?.error || 'No se pudo obtener el reporte'
            datos.value = []
            total.value = 0
            throw e
        } finally {
            loading.value = false
        }
    }

    // GET /api/reporte-docentes/con-titulo/excel (con los mismos filtros)
    async function descargarExcel(filtros) {
        const params = new URLSearchParams()
        params.set('anio', filtros.anio)
        params.set('periodo', filtros.periodo)
        if (filtros.tipo_titulo) params.set('tipo_titulo', filtros.tipo_titulo)
            ; (filtros.campos || []).forEach(c => params.append('campos[]', c))
            ; (filtros.campos_titulo || []).forEach(c => params.append('campos_titulo[]', c))

        const url = `${API_BASE}/api/reporte-docentes/con-titulo/excel?${params.toString()}`

        const res = await fetch(url, { headers: authHeaders() })
        if (!res.ok) throw new Error('No se pudo generar el Excel')

        const blob = await res.blob()
        const link = document.createElement('a')
        link.href = window.URL.createObjectURL(blob)
        link.download = `reporte_docentes_titulo_${filtros.anio}_${filtros.periodo}.xlsx`
        link.click()
        window.URL.revokeObjectURL(link.href)
    }

    return {
        loading, error, datos, total,
        tiposTitulo, loadingTipos,
        cargarTiposTitulo, buscar, descargarExcel,
    }
}