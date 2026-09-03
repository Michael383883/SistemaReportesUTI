import { ref, computed } from 'vue'
import axios from 'axios'

const API_BASE = import.meta.env.VITE_API_URL

/**
 * Lista/busca documentos de CLASIFICACION_DOCUMENTO para el paso 2 de
 * "Asignar documento a docentes" — mismo rol que useResolucionListado.js
 * pero apuntando a GET /api/clasificaciones.
 */
export function useDocumentoListado() {
    const filas = ref([])
    const loading = ref(false)
    const error = ref(null)
    const busqueda = ref('')

    function authHeaders(extra = {}) {
        const token = localStorage.getItem('token')
        return {
            ...(token ? { Authorization: `Bearer ${token}` } : {}),
            ...extra,
        }
    }

    function normalizar(d) {
        return {
            idClasificacionDocente: d.ID_CLASIFICACION_DOCENTE,
            idDocumento: d.ID_DOCUMENTO,
            codDocente: d.COD_DOCENTE,
            nombreDocente: d.NOMBRE_DOCENTE,
            categoria: d.CATEGORIA,
            nivel: d.NIVEL,
            tipoDocumento: d.TIPO_DOCUMENTO,
            gestion: d.GESTION,
            periodo: d.PERIODO,
            nombreArchivo: d.NOMBRE_ARCHIVO,
            fechaRegistro: d.FECHA_REGISTRO,
        }
    }

    async function cargar() {
        loading.value = true
        error.value = null
        try {
            const { data } = await axios.get(`${API_BASE}/api/clasificaciones`, {
                headers: authHeaders(),
            })
            filas.value = (data ?? []).map(normalizar)
        } catch (e) {
            error.value = e?.response?.data?.error || 'No se pudo obtener el listado de documentos'
            console.error('❌ Error al cargar documentos:', e)
        } finally {
            loading.value = false
        }
    }

    // Filtro en cliente por texto libre (tipo, detalle, docente, gestión/periodo)
    const filasFiltradas = computed(() => {
        const texto = busqueda.value.trim().toLowerCase()
        if (!texto) return filas.value
        return filas.value.filter(f =>
            [f.tipoDocumento, f.nombreDocente, f.categoria, f.gestion, f.periodo, f.nombreArchivo]
                .filter(Boolean)
                .some(campo => String(campo).toLowerCase().includes(texto))
        )
    })

    function buscar(texto) {
        busqueda.value = texto ?? ''
        if (filas.value.length === 0) cargar()
    }

    function limpiarBusqueda() {
        busqueda.value = ''
    }

    cargar()

    return {
        filas: filasFiltradas,
        loading,
        error,
        busqueda,
        buscar,
        limpiarBusqueda,
        recargar: cargar,
    }
}