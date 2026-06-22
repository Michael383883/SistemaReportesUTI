import { ref } from 'vue'
import axios from 'axios'

const API_BASE = import.meta.env.VITE_API_URL

function authHeaders() {
    const token = localStorage.getItem('token')
    return token ? { Authorization: `Bearer ${token}` } : {}
}

function toCamel(str) {
    return str
        .toLowerCase()
        .replace(/_([a-z])/g, (_, c) => c.toUpperCase())
}

function mapKeys(obj) {
    if (Array.isArray(obj)) return obj.map(mapKeys)
    if (obj && typeof obj === 'object') {
        return Object.fromEntries(
            Object.entries(obj).map(([k, v]) => [toCamel(k), mapKeys(v)])
        )
    }
    return obj
}

function normalizar(valor) {
    return (valor ?? '')
        .toString()
        .toUpperCase()
        .replace(/\s+/g, '')
}

export function useResolucionListado() {
    const filas = ref([])
    const loading = ref(false)
    const error = ref('')
    const busqueda = ref('')

    let debounceId = null

    function urlVer(id) {
        return `${API_BASE}/api/resoluciones/${id}/pdf`
    }

    function urlDescargar(id) {
        return `${API_BASE}/api/resoluciones/${id}/pdf?modo=descargar`
    }

    function formatearFecha(fecha) {
        if (!fecha) return '-'
        const d = new Date(fecha)
        if (Number.isNaN(d.getTime())) return fecha
        return d.toLocaleDateString('es-BO', { day: '2-digit', month: '2-digit', year: 'numeric' })
    }

    async function cargarListado() {
        loading.value = true
        error.value = ''
        filas.value = []

        const termino = busqueda.value.trim()

        try {
            const { data } = await axios.get(
                `${API_BASE}/api/resoluciones/listado`,
                {
                    headers: authHeaders(),
                    params: termino ? { nro: termino } : {},
                }
            )

            const lista = Array.isArray(data)
                ? data
                : Array.isArray(data?.data)
                    ? data.data
                    : []

            const filasMapeadas = mapKeys(lista)

            // Filtro real del lado del cliente: aunque el backend devuelva
            // filas que no coinciden, acá solo se muestran las que sí
            // tienen el número buscado (sin importar mayúsculas/espacios).
            filas.value = termino
                ? filasMapeadas.filter(fila =>
                    normalizar(fila.nroResolucion).includes(normalizar(termino))
                )
                : filasMapeadas

        } catch (e) {
            error.value = e.response?.data?.message
                ?? e.message
                ?? 'Error al cargar el listado.'
        } finally {
            loading.value = false
        }
    }

    function buscar(termino) {
        busqueda.value = termino

        if (debounceId) clearTimeout(debounceId)
        debounceId = setTimeout(() => {
            cargarListado()
        }, 400)
    }

    function limpiarBusqueda() {
        if (debounceId) clearTimeout(debounceId)
        busqueda.value = ''
        cargarListado()
    }

    return {
        filas,
        loading,
        error,
        busqueda,
        urlVer,
        urlDescargar,
        formatearFecha,
        cargarListado,
        buscar,
        limpiarBusqueda,
    }
}