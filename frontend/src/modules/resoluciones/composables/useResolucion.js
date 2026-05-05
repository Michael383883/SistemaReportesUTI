// composables/useResolucion.js
import { ref } from 'vue'
import axios from 'axios'

const API_BASE = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api'

function authHeaders() {
    const token = localStorage.getItem('token')
    return token ? { Authorization: `Bearer ${token}` } : {}
}

export function useResolucion() {
    const loading = ref(false)
    const error = ref('')
    const resolucionId = ref(null)
    const resolucionGuardada = ref(null)
    const detallesGuardados = ref([])

    // ─────────────────────────────────────────────────────────────
    // 1. POST /api/resoluciones
    // ─────────────────────────────────────────────────────────────
    async function guardarResolucion({ numero, descripcion, anio, periodo, archivo }) {
        loading.value = true
        error.value = ''

        try {
            const form = new FormData()

            // Todos los campos como string — FormData siempre serializa a string,
            // pero lo hacemos explícito para evitar problemas con null/undefined
            form.append('nro_resolucion', String(numero).trim())
            form.append('descripcion', String(descripcion ?? '').trim())
            form.append('anio', String(anio))
            form.append('periodo', String(periodo))       // "1" o "2"
            form.append('subido_por', 'admin')               // nullable en backend

            if (archivo) {
                form.append('archivo_pdf', archivo)
                form.append('nombre_archivo', archivo.name)
                form.append('tamanio_kb', String(Math.round(archivo.size / 1024)))
            }

            // Log de depuración — quitar en producción
            console.group('📤 POST /api/resoluciones')
            for (const [key, val] of form.entries()) {
                console.log(key, val instanceof File ? `File(${val.name}, ${val.size}B)` : val)
            }
            console.groupEnd()

            const { data } = await axios.post(
                `${API_BASE}/api/resoluciones`,
                form,
                {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                        ...authHeaders(),
                    },
                }
            )

            // El controller devuelve { ok: true, id_resolucion: N }
            if (!data.ok) throw new Error(data.error ?? 'Error al guardar la resolución.')

            resolucionId.value = data.id_resolucion
            return data.id_resolucion

        } catch (e) {
            // Muestra los errores de validación de Laravel si existen
            const laravelErrors = e.response?.data?.errores
            if (laravelErrors) {
                const msgs = Object.values(laravelErrors).flat().join(' | ')
                error.value = msgs
            } else {
                error.value = e.response?.data?.error
                    ?? e.response?.data?.message
                    ?? e.message
                    ?? 'Error al guardar la resolución.'
            }
            console.error('❌ guardarResolucion:', e.response?.data ?? e.message)
            throw e
        } finally {
            loading.value = false
        }
    }

    // ─────────────────────────────────────────────────────────────
    // 2. POST /api/resoluciones/{id}/detalles/bulk
    // ─────────────────────────────────────────────────────────────
    async function guardarDetalles(idResolucion, detalles) {
        loading.value = true
        error.value = ''

        try {
            const { data } = await axios.post(
                `${API_BASE}/api/resoluciones/${idResolucion}/detalles/bulk`,
                { detalles },
                { headers: { ...authHeaders() } }
            )

            return data

        } catch (e) {
            const laravelErrors = e.response?.data?.errors ?? e.response?.data?.errores
            if (laravelErrors) {
                error.value = Object.values(laravelErrors).flat().join(' | ')
            } else {
                error.value = e.response?.data?.message ?? e.message ?? 'Error al guardar los detalles.'
            }
            console.error('❌ guardarDetalles:', e.response?.data ?? e.message)
            throw e
        } finally {
            loading.value = false
        }
    }

    // ─────────────────────────────────────────────────────────────
    // 3. GET /api/resoluciones/{id}  +  GET /api/resoluciones/{id}/detalles
    // ─────────────────────────────────────────────────────────────
    async function cargarResolucionCompleta(idResolucion) {
        loading.value = true
        error.value = ''

        try {
            const [resRes, detRes] = await Promise.all([
                axios.get(`${API_BASE}/api/resoluciones/${idResolucion}`, { headers: authHeaders() }),
                axios.get(`${API_BASE}/api/resoluciones/${idResolucion}/detalles`, { headers: authHeaders() }),
            ])

            resolucionGuardada.value = resRes.data
            detallesGuardados.value = detRes.data

        } catch (e) {
            error.value = e.response?.data?.message ?? e.message ?? 'Error al cargar la resolución.'
            console.error('❌ cargarResolucionCompleta:', e.response?.data ?? e.message)
            throw e
        } finally {
            loading.value = false
        }
    }

    // ─────────────────────────────────────────────────────────────
    // 4. Reset
    // ─────────────────────────────────────────────────────────────
    function reset() {
        loading.value = false
        error.value = ''
        resolucionId.value = null
        resolucionGuardada.value = null
        detallesGuardados.value = []
    }
    // ─────────────────────────────────────────────────────────────
    // 5. POST /api/resoluciones/{id}/aplicar-grupos
    // ─────────────────────────────────────────────────────────────
    async function aplicarEnGrupos(idResolucion) {
        loading.value = true
        error.value = ''

        try {
            const { data } = await axios.post(
                `${API_BASE}/api/resoluciones/${idResolucion}/aplicar-grupos`,
                {},
                { headers: { ...authHeaders() } }
            )

            if (!data.ok) throw new Error(data.error ?? 'Error al aplicar en grupos.')

            return data // { ok, filas_afectadas, grupos }

        } catch (e) {
            error.value = e.response?.data?.error
                ?? e.response?.data?.message
                ?? e.message
                ?? 'Error al aplicar en grupos.'
            console.error('❌ aplicarEnGrupos:', e.response?.data ?? e.message)
            throw e
        } finally {
            loading.value = false
        }
    }

    


    return {
        loading,
        error,
        resolucionId,
        resolucionGuardada,
        detallesGuardados,
        guardarResolucion,
        guardarDetalles,
        cargarResolucionCompleta,
        reset,
        aplicarEnGrupos,  
    }
}