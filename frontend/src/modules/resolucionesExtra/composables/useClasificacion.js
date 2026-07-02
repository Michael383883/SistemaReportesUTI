import { ref } from 'vue'
import axios from 'axios'

const API_BASE = import.meta.env.VITE_API_URL ?? ''

export function useClasificacion() {
    const loading = ref(false)
    const error = ref(null)
    const listado = ref([])
    const actual = ref(null)

    function authHeaders(extra = {}) {
        const token = localStorage.getItem('token')
        return {
            ...(token ? { Authorization: `Bearer ${token}` } : {}),
            ...extra,
        }
    }

    function buildFormData({ cod_docente, categoria, nivel, gestion, periodo, detalle_general, observacion, observacion2, materias, referencias, archivo }) {
        const fd = new FormData()
        fd.append('cod_docente', cod_docente)
        fd.append('categoria', categoria)      // ← Cambio: antes era 'nivel'
        fd.append('nivel', nivel)              // ← Cambio: antes era 'orden_nivel'
        if (gestion) fd.append('gestion', gestion)
        if (periodo) fd.append('periodo', periodo)
        if (detalle_general) fd.append('detalle_general', detalle_general)
        if (observacion) fd.append('observacion', observacion)
        if (observacion2) fd.append('observacion2', observacion2)
        fd.append('materias', JSON.stringify(materias || []))
        fd.append('referencias', JSON.stringify(referencias || []))
        if (archivo) fd.append('archivo_pdf', archivo)
        return fd
    }

    // GET /api/clasificaciones
    async function listar(filtros = {}) {
        loading.value = true
        error.value = null
        try {
            const { data } = await axios.get(`${API_BASE}/api/clasificaciones`, {
                params: filtros,
                headers: authHeaders(),
            })
            listado.value = data
            return data
        } catch (e) {
            error.value = e?.response?.data?.error || 'No se pudo obtener el listado'
            throw e
        } finally {
            loading.value = false
        }
    }

    // GET /api/clasificaciones/{id}
    async function obtener(id) {
        loading.value = true
        error.value = null
        try {
            const { data } = await axios.get(`${API_BASE}/api/clasificaciones/${id}`, {
                headers: authHeaders(),
            })
            actual.value = data
            return data
        } catch (e) {
            error.value = e?.response?.data?.error || 'Clasificación no encontrada'
            throw e
        } finally {
            loading.value = false
        }
    }

    // POST /api/clasificaciones
    async function guardarClasificacion(payload) {
        loading.value = true
        error.value = null
        try {
            const fd = buildFormData(payload)
            const { data } = await axios.post(`${API_BASE}/api/clasificaciones`, fd, {
                headers: authHeaders({ 'Content-Type': 'multipart/form-data' }),
            })
            if (!data.ok) {
                error.value = data.error || 'No se pudo guardar la clasificación'
                throw new Error(error.value)
            }
            return data.id_clasificacion
        } catch (e) {
            if (e?.response?.data?.tipo === 'validacion') {
                const primerError = Object.values(e.response.data.errores)[0]?.[0]
                error.value = primerError || 'Error de validación'
            } else if (!error.value) {
                error.value = e?.response?.data?.error || e.message || 'No se pudo guardar la clasificación'
            }
            throw e
        } finally {
            loading.value = false
        }
    }

    // DELETE /api/clasificaciones/{id}
    async function eliminar(id) {
        loading.value = true
        error.value = null
        try {
            const { data } = await axios.delete(`${API_BASE}/api/clasificaciones/${id}`, {
                headers: authHeaders(),
            })
            return data
        } catch (e) {
            error.value = e?.response?.data?.error || 'No se pudo eliminar la clasificación'
            throw e
        } finally {
            loading.value = false
        }
    }

    // GET /api/clasificaciones/{id}/pdf
    function urlPdf(id, modo = 'inline') {
        return `${API_BASE}/api/clasificaciones/${id}/pdf?modo=${modo}`
    }

    function reset() {
        error.value = null
        actual.value = null
    }

    return {
        loading,
        error,
        listado,
        actual,
        listar,
        obtener,
        guardarClasificacion,
        eliminar,
        urlPdf,
        reset,
    }
}