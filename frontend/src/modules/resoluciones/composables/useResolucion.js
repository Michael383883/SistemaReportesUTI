// composables/useResolucion.js
import { ref } from 'vue'
import axios from 'axios'

const API_BASE = import.meta.env.VITE_API_URL 

function authHeaders() {
    const token = localStorage.getItem('token')
    return token ? { Authorization: `Bearer ${token}` } : {}
}

// ─────────────────────────────────────────────────────────────
// mapKeys: convierte SNAKE_UPPER a camelCase correcto
// ID_RESOLUCION → idResolucion  (no iDResolucion)
// COD_DOCENTE   → codDocente
// NRO_RESOLUCION → nroResolucion
// ─────────────────────────────────────────────────────────────
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

// ─────────────────────────────────────────────────────────────
// mensajeError: traduce cualquier error de axios/backend a un
// mensaje claro y accionable para el usuario final.
// ─────────────────────────────────────────────────────────────
function mensajeError(e, fallback = 'Ocurrió un error inesperado. Inténtalo de nuevo.') {
    // Sin respuesta del servidor: caída de red, CORS, servidor apagado, timeout
    if (!e.response) {
        if (e.code === 'ECONNABORTED') {
            return 'La solicitud tardó demasiado en responder. Verifica tu conexión e inténtalo de nuevo.'
        }
        return 'No se pudo conectar con el servidor. Verifica tu conexión e inténtalo de nuevo.'
    }

    const status = e.response.status
    const data = e.response.data

    // Errores de validación de Laravel (422)
    const laravelErrors = data?.errores ?? data?.errors
    if (laravelErrors && typeof laravelErrors === 'object') {
        return Object.values(laravelErrors).flat().join(' | ')
    }

    switch (status) {
        case 401:
        case 403:
            return 'Tu sesión expiró o no tienes permisos para esta acción. Inicia sesión nuevamente.'
        case 404:
            return 'No se encontró la resolución solicitada. Puede que haya sido eliminada.'
        case 409:
            return data?.error ?? 'Ya existe una resolución con ese número para el año y periodo seleccionados.'
        case 413:
            return 'El archivo PDF es demasiado grande. El tamaño máximo permitido es de 20 MB.'
        case 415:
            return 'El archivo debe estar en formato PDF.'
        case 422:
            return data?.error ?? data?.message ?? 'Algunos datos no son válidos. Revisa el formulario.'
        case 429:
            return 'Se realizaron demasiadas solicitudes. Espera un momento e inténtalo de nuevo.'
        default:
            if (status >= 500) {
                return 'Ocurrió un error en el servidor al procesar la solicitud. Inténtalo más tarde.'
            }
            return data?.error ?? data?.message ?? fallback
    }
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
            // Validación de archivo en cliente antes de enviar al servidor
            if (archivo) {
                if (archivo.type !== 'application/pdf') {
                    error.value = 'El archivo debe ser un PDF.'
                    throw new Error(error.value)
                }
                const MAX_MB = 20
                if (archivo.size > MAX_MB * 1024 * 1024) {
                    error.value = `El archivo PDF supera el tamaño máximo de ${MAX_MB} MB.`
                    throw new Error(error.value)
                }
            }

            const form = new FormData()

            form.append('nro_resolucion', String(numero).trim())
            form.append('descripcion', String(descripcion ?? '').trim())
            form.append('anio', String(anio))
            form.append('periodo', String(periodo))
            form.append('subido_por', 'admin')

            if (archivo) {
                form.append('archivo_pdf', archivo)
                form.append('nombre_archivo', archivo.name)
                form.append('tamanio_kb', String(Math.round(archivo.size / 1024)))
            }

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

            if (!data.ok) {
                error.value = data.error ?? 'No se pudo guardar la resolución. Inténtalo de nuevo.'
                throw new Error(error.value)
            }

            resolucionId.value = data.id_resolucion
            return data.id_resolucion

        } catch (e) {
            // Si ya seteamos error.value arriba (validación local), no lo pisamos
            if (!error.value) {
                error.value = mensajeError(e, 'No se pudo guardar la resolución. Inténtalo de nuevo.')
            }
            console.error('❌ guardarResolucion:', e.response?.data ?? e.message)
            throw e
        } finally {
            loading.value = false
        }
    }

    // ─────────────────────────────────────────────────────────────
    // 2. POST /api/resoluciones/{id}/detalles/bulk
    //
    // El backend ahora devuelve también "ids_detalle": los ID_DETALLE
    // recién insertados. Hay que conservarlos y pasarlos a
    // aplicarEnGrupos() para que esa operación afecte solo lo que se
    // acaba de guardar en ESTA sesión, no todo el historial de detalles
    // que pueda tener acumulados la misma resolución de sesiones
    // anteriores (otros docentes/materias asignados en otro momento).
    // ─────────────────────────────────────────────────────────────
    async function guardarDetalles(idResolucion, detalles) {
        loading.value = true
        error.value = ''

        try {
            if (!detalles || detalles.length === 0) {
                error.value = 'No hay docentes/materias para asignar.'
                throw new Error(error.value)
            }

            const { data } = await axios.post(
                `${API_BASE}/api/resoluciones/${idResolucion}/detalles/bulk`,
                { detalles },
                { headers: { ...authHeaders() } }
            )

            // data.ids_detalle: array de IDs insertados en este request.
            return data

        } catch (e) {
            if (!error.value) {
                error.value = mensajeError(e, 'No se pudieron guardar las asignaciones. Inténtalo de nuevo.')
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

            resolucionGuardada.value = mapKeys(resRes.data)
            detallesGuardados.value = mapKeys(detRes.data)

            console.log('✅ resolucionGuardada:', resolucionGuardada.value)
            console.log('✅ detallesGuardados:', detallesGuardados.value)

        } catch (e) {
            error.value = mensajeError(e, 'No se pudo cargar la resolución.')
            console.error('❌ cargarResolucionCompleta:', e.response?.data ?? e.message)
            throw e
        } finally {
            loading.value = false
        }
    }

    // ─────────────────────────────────────────────────────────────
    // 4. POST /api/resoluciones/{id}/aplicar-grupos
    //
    // idsDetalle (opcional pero MUY recomendado): array de ID_DETALLE
    // recién insertados (lo que devuelve guardarDetalles().ids_detalle).
    // Si se pasa, el backend solo aplica/reporta sobre esos detalles
    // puntuales. Si se omite, cae al comportamiento antiguo (aplica
    // sobre TODO el historial de la resolución) — solo por compatibilidad,
    // no debería usarse así desde el flujo normal de asignación.
    // ─────────────────────────────────────────────────────────────
    async function aplicarEnGrupos(idResolucion, idsDetalle = []) {
        loading.value = true
        error.value = ''

        try {
            const { data } = await axios.post(
                `${API_BASE}/api/resoluciones/${idResolucion}/aplicar-grupos`,
                { ids_detalle: idsDetalle },
                { headers: { ...authHeaders() } }
            )

            if (!data.ok) {
                error.value = data.error ?? 'No se pudo aplicar la asignación a los grupos.'
                throw new Error(error.value)
            }

            console.log('✅ aplicarEnGrupos:', data)

            return data  // { ok, filas_afectadas, grupos }

        } catch (e) {
            if (!error.value) {
                error.value = mensajeError(e, 'No se pudo aplicar la asignación a los grupos.')
            }
            console.error('❌ aplicarEnGrupos:', e.response?.data ?? e.message)
            throw e
        } finally {
            loading.value = false
        }
    }

    // ─────────────────────────────────────────────────────────────
    // 5. Reset
    // ─────────────────────────────────────────────────────────────
    function reset() {
        loading.value = false
        error.value = ''
        resolucionId.value = null
        resolucionGuardada.value = null
        detallesGuardados.value = []
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
        aplicarEnGrupos,
        reset,
    }
}