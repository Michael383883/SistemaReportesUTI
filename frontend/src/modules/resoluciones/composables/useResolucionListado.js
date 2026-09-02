import { ref, computed } from 'vue'
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
    const anioSeleccionado = ref('') // '' = todos los años

    let debounceId = null

    // ─────────────────────────────────────────────────────────────
    // Ver / Descargar PDF vía axios (blob), en vez de <a href> directo,
    // para que el token Bearer viaje en el header de la petición.
    // ─────────────────────────────────────────────────────────────
    const abriendoPdf = ref(false)
    const descargandoPdf = ref(false)

    async function verPdf(id) {
        abriendoPdf.value = true
        error.value = ''
        try {
            const { data } = await axios.get(
                `${API_BASE}/api/resoluciones/${id}/pdf`,
                {
                    headers: authHeaders(),
                    responseType: 'blob',
                }
            )
            const blobUrl = URL.createObjectURL(new Blob([data], { type: 'application/pdf' }))
            window.open(blobUrl, '_blank')
            setTimeout(() => URL.revokeObjectURL(blobUrl), 60_000)
        } catch (e) {
            error.value = e.response?.status === 401
                ? 'Tu sesión expiró. Inicia sesión nuevamente.'
                : 'No se pudo abrir el PDF.'
        } finally {
            abriendoPdf.value = false
        }
    }

    async function descargarPdf(id, nombreArchivo = `resolucion-${id}.pdf`) {
        descargandoPdf.value = true
        error.value = ''
        try {
            const { data } = await axios.get(
                `${API_BASE}/api/resoluciones/${id}/pdf`,
                {
                    headers: authHeaders(),
                    params: { modo: 'descargar' },
                    responseType: 'blob',
                }
            )
            const blobUrl = URL.createObjectURL(new Blob([data], { type: 'application/pdf' }))
            const link = document.createElement('a')
            link.href = blobUrl
            link.download = nombreArchivo
            document.body.appendChild(link)
            link.click()
            link.remove()
            URL.revokeObjectURL(blobUrl)
        } catch (e) {
            error.value = e.response?.status === 401
                ? 'Tu sesión expiró. Inicia sesión nuevamente.'
                : 'No se pudo descargar el PDF.'
        } finally {
            descargandoPdf.value = false
        }
    }

    function formatearFecha(fecha) {
        if (!fecha) return '-'
        const d = new Date(fecha)
        if (Number.isNaN(d.getTime())) return fecha
        return d.toLocaleDateString('es-BO', { day: '2-digit', month: '2-digit', year: 'numeric' })
    }

    // Lista de años disponibles en los datos cargados, para llenar el <select>
    const aniosDisponibles = computed(() => {
        const set = new Set(filas.value.map(f => String(f.anio)).filter(Boolean))
        return Array.from(set).sort((a, b) => b - a) // años más recientes primero en el select
    })

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

            let filasMapeadas = mapKeys(lista)

            // Filtro real del lado del cliente: aunque el backend devuelva
            // filas que no coinciden, acá solo se muestran las que sí
            // tienen el número buscado (sin importar mayúsculas/espacios).
            if (termino) {
                filasMapeadas = filasMapeadas.filter(fila =>
                    normalizar(fila.nroResolucion).includes(normalizar(termino))
                )
            }

            // Filtro por año (cliente)
            if (anioSeleccionado.value) {
                filasMapeadas = filasMapeadas.filter(
                    fila => String(fila.anio) === String(anioSeleccionado.value)
                )
            }

            // Orden ascendente (de menor a mayor) por ID de resolución.
            // Si preferís ordenar por fecha de subida en vez de ID, cambiá
            // la comparación a fechaSubida.
            filasMapeadas = [...filasMapeadas].sort(
                (a, b) => Number(a.idResolucion) - Number(b.idResolucion)
            )

            filas.value = filasMapeadas

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

    function filtrarPorAnio(anio) {
        anioSeleccionado.value = anio
        cargarListado()
    }

    function limpiarBusqueda() {
        if (debounceId) clearTimeout(debounceId)
        busqueda.value = ''
        cargarListado()
    }

    const eliminando = ref(false)
    const errorEliminar = ref('')

    // Borra la resolución en el backend: el controller se encarga de
    // borrar también los docentes/materias asignados (RESOLUCION_DETALLE)
    // y el archivo PDF físico. Acá solo refrescamos la lista al terminar.
    async function eliminarResolucion(id) {
        eliminando.value = true
        errorEliminar.value = ''

        try {
            const { data } = await axios.delete(
                `${API_BASE}/api/resoluciones/${id}`,
                { headers: authHeaders() }
            )

            if (data?.ok === false) {
                errorEliminar.value = data.error ?? 'No se pudo eliminar la resolución.'
                return false
            }

            // Quitamos la fila de la lista local sin esperar otro fetch
            filas.value = filas.value.filter(f => String(f.idResolucion) !== String(id))

            return true

        } catch (e) {
            errorEliminar.value = e.response?.data?.error
                ?? e.response?.data?.message
                ?? e.message
                ?? 'Error al eliminar la resolución.'
            return false
        } finally {
            eliminando.value = false
        }
    }


    const editando = ref(false)
    const errorEditar = ref('')

    async function actualizarResolucion(id, formData) {
        editando.value = true
        errorEditar.value = ''
        try {
            const { data } = await axios.post(
                `${API_BASE}/api/resoluciones/${id}`,
                formData,
                { headers: authHeaders() }
            )

            if (data?.ok === false) {
                errorEditar.value = data.error ?? 'No se pudo actualizar la resolución.'
                return false
            }

            await cargarListado()
            return true

        } catch (e) {
            errorEditar.value = e.response?.data?.error
                ?? e.message
                ?? 'Error al actualizar la resolución.'
            return false
        } finally {
            editando.value = false
        }
    }
    return {
        filas,
        loading,
        error,
        busqueda,
        anioSeleccionado,
        aniosDisponibles,
        verPdf,
        descargarPdf,
        abriendoPdf,
        descargandoPdf,
        formatearFecha,
        cargarListado,
        buscar,
        filtrarPorAnio,
        limpiarBusqueda,
        eliminando,
        errorEliminar,
        eliminarResolucion,
        editando,
        errorEditar,
        actualizarResolucion,
    }
}