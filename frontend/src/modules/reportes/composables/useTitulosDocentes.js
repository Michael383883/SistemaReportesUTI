import { ref } from 'vue'
import axios from 'axios'

const API_BASE = import.meta.env.VITE_API_URL

export function useTitulosDocentes() {
    const mapa = ref({})          // { [codigo]: [ {titulo...} ] }
    const cargando = ref(false)
    const errorTitulos = ref(null)

    // Solo consulta los códigos que aún no están en caché
    const cargar = async (codigos) => {
        const nuevos = [...new Set(codigos.map(String))].filter(c => !(c in mapa.value))
        if (!nuevos.length) return

        cargando.value = true
        errorTitulos.value = null
        try {
            const token = localStorage.getItem('token')
            const { data } = await axios.post(
                `${API_BASE}/api/docentes/titulos-resumen`,
                { codigos: nuevos },
                { headers: { Authorization: `Bearer ${token}` } }
            )
            const res = Array.isArray(data) ? {} : (data || {})
            const siguiente = { ...mapa.value }
            for (const c of nuevos) siguiente[c] = res[c] || []
            mapa.value = siguiente
        } catch (e) {
            errorTitulos.value = e.response?.data?.message || 'No se pudieron cargar los títulos'
        } finally {
            cargando.value = false
        }
    }

    const limpiar = () => { mapa.value = {} }

    return { mapa, cargando, errorTitulos, cargar, limpiar }
}