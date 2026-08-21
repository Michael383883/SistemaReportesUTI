import { ref, computed } from 'vue'
import axios from 'axios'

const API_BASE = import.meta.env.VITE_API_URL ?? ''

// Acepta array (['a','b']) o string ('a') o undefined, y devuelve
// siempre el formato que espera el backend: "a,b" (o undefined si está vacío).
function serializarLista(valor) {
    if (Array.isArray(valor)) {
        return valor.length ? valor.join(',') : undefined
    }
    return valor || undefined
}

// Normaliza el nombre de una materia para comparar "igualito":
// quita espacios extra al inicio/fin, colapsa espacios múltiples y pasa a mayúsculas.
function normalizarNombreMateria(nombre) {
    return (nombre || '')
        .toString()
        .trim()
        .toUpperCase()
        .replace(/\s+/g, ' ')
}

const PLACEHOLDER_SIN_MATERIA = 'NO REGENTA MATERIA EN LA FCE'

export function useReporteExcel() {
    const loading = ref(false)
    const error = ref(null)
    const preview = ref([])
    const gestionEtiqueta = ref('')
    const versionEtiqueta = ref('')
    const totalFilas = ref(0)

    // ── Docentes activos (para el botón "Solo Activos") ──
    const cargandoActivos = ref(false)
    const errorActivos = ref(null)
    const docentesActivos = ref([]) // [{ codigo, apellidos, nombres }, ...]
    const soloActivos = ref(false)
    const anioActivos = ref(new Date().getFullYear())
    const periodoActivos = ref(1)

    // ── Asignar Carga Horaria (experimental) ──
    const cargandoCargaHoraria = ref(false)
    const errorCargaHoraria = ref(null)
    const cargaHorariaAsignada = ref(false) // true si el preview actual ya tiene CH asignada automáticamente

    function authHeaders(extra = {}) {
        const token = localStorage.getItem('token')
        return {
            ...(token ? { Authorization: `Bearer ${token}` } : {}),
            ...extra,
        }
    }

    // GET /api/reportes/docentes-clasificados/preview
    // categoria y tipo_titulo pueden venir como array (multi-selección) o string
    async function previsualizar({ gestion_desde, gestion_hasta, periodo, version, categoria, tipo_titulo } = {}) {
        loading.value = true
        error.value = null
        cargaHorariaAsignada.value = false // un preview nuevo descarta cualquier CH asignada previamente
        try {
            const { data } = await axios.get(
                `${API_BASE}/api/reportes/docentes-clasificados/preview`,
                {
                    params: {
                        gestion_desde: gestion_desde || undefined,
                        gestion_hasta: gestion_hasta || undefined,
                        periodo: periodo || undefined,
                        version: version || undefined,
                        categoria: serializarLista(categoria),
                        tipo_titulo: serializarLista(tipo_titulo),
                    },
                    headers: authHeaders(),
                }
            )

            if (!data.ok) {
                error.value = data.error || 'No se pudo obtener la vista previa'
                preview.value = []
                return
            }

            preview.value = data.data
            gestionEtiqueta.value = data.gestion
            versionEtiqueta.value = data.version
            totalFilas.value = data.total_filas
        } catch (e) {
            error.value = e?.response?.data?.error || 'No se pudo obtener la vista previa'
            preview.value = []
            throw e
        } finally {
            loading.value = false
        }
    }

    // GET /api/reportes/docentes-activos?anio=X&periodo=Y
    // Trae la lista de docentes que tienen materia asignada en esa gestión/periodo
    // (según HORARIOS2/GRUPOS), para poder filtrar el preview por "Solo Activos".
    async function cargarDocentesActivos({ anio, periodo } = {}) {
        cargandoActivos.value = true
        errorActivos.value = null
        try {
            const { data } = await axios.get(
                `${API_BASE}/api/reportes/docentes-activos`,
                {
                    params: {
                        anio: anio ?? anioActivos.value,
                        periodo: periodo ?? periodoActivos.value,
                    },
                    headers: authHeaders(),
                }
            )
            docentesActivos.value = data.data || []
        } catch (e) {
            errorActivos.value = e?.response?.data?.message || 'No se pudo obtener la lista de docentes activos'
            docentesActivos.value = []
            throw e
        } finally {
            cargandoActivos.value = false
        }
    }

    // Botón "Solo Activos": si aún no cargamos la lista de activos (o cambió
    // el año/periodo), la carga; luego activa el filtro. Si ya estaba activo,
    // lo desactiva (toggle).
    async function alternarSoloActivos({ anio, periodo } = {}) {
        if (soloActivos.value) {
            soloActivos.value = false
            return
        }
        if (anio !== undefined) anioActivos.value = anio
        if (periodo !== undefined) periodoActivos.value = periodo
        await cargarDocentesActivos({ anio: anioActivos.value, periodo: periodoActivos.value })
        soloActivos.value = true
    }

    // Set de códigos de docente activos, como string, para comparar sin
    // preocuparse si el backend devuelve number o string.
    const codigosActivos = computed(() => {
        return new Set(docentesActivos.value.map(d => String(d.codigo)))
    })

    // Lista que debe mostrarse en la tabla: si "Solo Activos" está apagado,
    // es el preview completo; si está prendido, solo las filas cuyo
    // COD_DOCENTE está en la lista de activos.
    const previewMostrado = computed(() => {
        if (!soloActivos.value) return preview.value
        return preview.value.filter(item => codigosActivos.value.has(String(item.COD_DOCENTE)))
    })

    // GET /api/reportes/carga-horaria-docentes?anio=X&periodo=Y
    // Trae, por docente, la carga horaria real (CH) de cada materia que dicta
    // en esa gestión/periodo (según HORARIOS2/GRUPOS/MATERIAS).
    async function obtenerCargaHorariaDocentes({ anio, periodo } = {}) {
        cargandoCargaHoraria.value = true
        errorCargaHoraria.value = null
        try {
            const { data } = await axios.get(
                `${API_BASE}/api/reportes/carga-horaria-docentes`,
                {
                    params: {
                        anio: anio ?? anioActivos.value,
                        periodo: periodo ?? periodoActivos.value,
                    },
                    headers: authHeaders(),
                }
            )
            return data.data || []
        } catch (e) {
            errorCargaHoraria.value = e?.response?.data?.message || 'No se pudo obtener la carga horaria de los docentes'
            throw e
        } finally {
            cargandoCargaHoraria.value = false
        }
    }

    // Función "experimental": compara cada fila del preview (docente + nombre de
    // materia) contra la carga horaria real obtenida de /carga-horaria-docentes.
    // Si el nombre de la materia coincide EXACTO (normalizado) para ese mismo
    // docente, asigna la CH a esa fila. Si no hay coincidencia, la deja como está
    // (vacía). No modifica filas sin materia real (placeholder "NO REGENTA...").
    async function asignarCargaHoraria({ anio, periodo } = {}) {
        const cargaDocentes = await obtenerCargaHorariaDocentes({ anio, periodo })

        // Mapa "codigoDocente|NOMBRE MATERIA NORMALIZADO" -> CH (sumada si se repite,
        // por ejemplo por grupos compartidos de la misma materia).
        const mapa = new Map()
        for (const docente of cargaDocentes) {
            for (const materia of (docente.materias || [])) {
                const clave = `${docente.codigo}|${normalizarNombreMateria(materia.nom_materia)}`
                mapa.set(clave, (mapa.get(clave) || 0) + Number(materia.carga_horaria || 0))
            }
        }

        let asignadas = 0
        let sinCoincidencia = 0

        preview.value = preview.value.map(item => {
            const nombreMateria = normalizarNombreMateria(item.NOMBRE_MATERIA)

            if (!item.COD_DOCENTE || !nombreMateria || nombreMateria === PLACEHOLDER_SIN_MATERIA) {
                return item
            }

            const clave = `${item.COD_DOCENTE}|${nombreMateria}`
            if (mapa.has(clave)) {
                asignadas++
                return { ...item, CH: mapa.get(clave) }
            }

            sinCoincidencia++
            return item
        })

        cargaHorariaAsignada.value = true
        return { asignadas, sinCoincidencia }
    }

    // Construye la URL de descarga real del Excel (mismo endpoint que ya existe,
    // vía GET). Se usa cuando NO se asignó carga horaria automática en el preview.
    // Si "Solo Activos" está activo, manda también anio/periodo y el flag
    // solo_activos=1 para que el backend aplique el mismo filtro al generar
    // el archivo (requiere el soporte correspondiente en el controller).
    function urlDescarga({ gestion_desde, gestion_hasta, periodo, version, categoria, tipo_titulo } = {}) {
        const params = new URLSearchParams()
        if (gestion_desde) params.set('gestion_desde', gestion_desde)
        if (gestion_hasta) params.set('gestion_hasta', gestion_hasta)
        if (periodo) params.set('periodo', periodo)
        if (version) params.set('version', version)

        const categoriaCsv = serializarLista(categoria)
        if (categoriaCsv) params.set('categoria', categoriaCsv)

        const tipoTituloCsv = serializarLista(tipo_titulo)
        if (tipoTituloCsv) params.set('tipo_titulo', tipoTituloCsv)

        if (soloActivos.value) {
            params.set('solo_activos', '1')
            params.set('anio_activos', anioActivos.value)
            params.set('periodo_activos', periodoActivos.value)
        }

        return `${API_BASE}/api/reportes/docentes-clasificados/excel?${params.toString()}`
    }

    // POST /api/reportes/docentes-clasificados/excel-personalizado
    // Se usa SOLO cuando ya se asignó carga horaria automática en el preview
    // (cargaHorariaAsignada = true): manda el arreglo `preview` tal cual está
    // en pantalla (con la CH ya asignada) para que el backend genere el Excel
    // exactamente con esos datos, en vez de reconstruirlos desde cero y perder
    // la asignación hecha en el navegador.
    async function descargarExcelPersonalizado({ gestion, version } = {}) {
        try {
            const response = await axios.post(
                `${API_BASE}/api/reportes/docentes-clasificados/excel-personalizado`,
                {
                    data: preview.value,
                    gestion: gestion || gestionEtiqueta.value,
                    version: version || versionEtiqueta.value,
                },
                {
                    headers: authHeaders(),
                    responseType: 'blob',
                }
            )

            const blob = new Blob([response.data], {
                type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            })
            const nombreGestion = (gestion || gestionEtiqueta.value || '').toString().replace(/\//g, '-')
            const nombreArchivo = `LISTA_DOCENTES_CLASIFICADOS_${nombreGestion}.xlsx`

            const url = window.URL.createObjectURL(blob)
            const link = document.createElement('a')
            link.href = url
            link.download = nombreArchivo
            document.body.appendChild(link)
            link.click()
            link.remove()
            window.URL.revokeObjectURL(url)
        } catch (e) {
            error.value = 'No se pudo descargar el Excel con la carga horaria asignada'
            throw e
        }
    }

    function reset() {
        error.value = null
        preview.value = []
        gestionEtiqueta.value = ''
        versionEtiqueta.value = ''
        totalFilas.value = 0
        soloActivos.value = false
        docentesActivos.value = []
        cargaHorariaAsignada.value = false
        errorCargaHoraria.value = null
    }

    return {
        loading,
        error,
        preview,
        gestionEtiqueta,
        versionEtiqueta,
        totalFilas,
        previsualizar,
        urlDescarga,
        reset,

        // Solo Activos
        cargandoActivos,
        errorActivos,
        docentesActivos,
        soloActivos,
        anioActivos,
        periodoActivos,
        cargarDocentesActivos,
        alternarSoloActivos,
        previewMostrado,

        // Asignar Carga Horaria (experimental)
        cargandoCargaHoraria,
        errorCargaHoraria,
        cargaHorariaAsignada,
        obtenerCargaHorariaDocentes,
        asignarCargaHoraria,
        descargarExcelPersonalizado,
    }
}