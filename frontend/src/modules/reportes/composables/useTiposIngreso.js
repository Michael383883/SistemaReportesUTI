// composables/useTiposIngreso.js
import { ref } from 'vue'
import axios from 'axios'

const API_BASE = import.meta.env.VITE_API_URL
const authHeaders = () => ({ Authorization: `Bearer ${localStorage.getItem('token')}` })

export const SIN_CATEGORIA = '__SIN__'
export const CATEGORIA_DEFECTO = 'ACÉFALA'

export function useTipoIngreso() {
    const categorias = ref([CATEGORIA_DEFECTO])
    const datos = ref([])
    const loading = ref(false)
    const error = ref(null)

    async function cargarCategorias() {
        try {
            const { data } = await axios.get(`${API_BASE}/api/reportes/tipo-ingreso/categorias`, {
                headers: authHeaders(),
            })
            const lista = Array.isArray(data) ? data : (data?.data ?? [])
            const set = new Set(lista.map((c) => String(c).trim()).filter(Boolean))
            set.add(CATEGORIA_DEFECTO)
            categorias.value = [...set].sort((a, b) => a.localeCompare(b, 'es'))
        } catch (e) {
            console.error('Categorías tipo-ingreso:', e?.response?.status, e?.response?.data || e.message)
            categorias.value = [CATEGORIA_DEFECTO]
        }
    }

    // categorias: array. Vacío = todas. Puede incluir SIN_CATEGORIA.
    async function buscar({ anio, periodo, categorias: cats = [] }) {
        loading.value = true
        error.value = null
        try {
            const params = { anio, periodo }
            if (cats.length) params.categorias = cats   // axios -> categorias[]=A&categorias[]=B

            const { data } = await axios.get(`${API_BASE}/api/reportes/tipo-ingreso`, {
                params,
                headers: authHeaders(),
            })
            const filas = Array.isArray(data) ? data : (data?.data ?? [])
            datos.value = filas.map((r) => ({ ...r, tiene_archivo: !!r.tiene_archivo }))
        } catch (e) {
            console.error('Reporte tipo-ingreso:', e?.response?.status, e?.response?.data || e.message)
            datos.value = []
            error.value =
                e?.response?.data?.message ||
                'No se pudo generar el reporte de tipo de ingreso'
        } finally {
            loading.value = false
        }
    }

    return { categorias, datos, loading, error, cargarCategorias, buscar }
}