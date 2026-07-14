import { ref } from 'vue'
import axios from 'axios'

const API_BASE = import.meta.env.VITE_API_URL || '/api'

const normalizeDocente = (doc) => {
    if (!doc || typeof doc !== 'object') return doc
    const out = { ...doc }

    out.nombre = doc.nombre ?? doc.NOMBRE ?? doc.Nombre ?? ''
    out.NOMBRE = doc.NOMBRE ?? doc.nombre ?? doc.Nombre ?? out.nombre

    out.codigo = doc.codigo ?? doc.CODIGO ?? doc.cod ?? ''
    out.CODIGO = doc.CODIGO ?? doc.codigo ?? doc.cod ?? out.codigo

    out.id = doc.id ?? doc.ID ?? doc.id_docente ?? doc.ID_DOCENTE ?? null
    out.ID = doc.ID ?? doc.id ?? doc.ID_DOCENTE ?? doc.id_docente ?? out.id

    return out
}

const normalizeMateria = (m) => {
    if (!m || typeof m !== 'object') return m
    const out = { ...m }

    out.nro = m.nro ?? m.NRO ?? m.numero ?? m.NUMERO ?? null
    out.gestion = m.gestion ?? m.GESTION ?? m.anio ?? m.ANIO ?? ''
    out.plan = m.plan ?? m.PLAN ?? ''
    out.materia = m.materia ?? m.MATERIA ?? ''
    out.compartido = m.compartido ?? m.COMPARTIDO ?? false
    out.grp = m.grp ?? m.GRP ?? m.grupo ?? m.GRUPO ?? ''
    out.resolucion = m.resolucion ?? m.RESOLUCION ?? m.resolucion_num ?? m.RESOLUCION_NUM ?? ''
    out.designacion = m.designacion ?? m.DESIGNACION ?? ''
    out.tipo_ingreso = m.tipo_ingreso ?? m.TIPO_INGRESO ?? ''
    return out
}

const normalizeReporteResponse = (data) => {
    if (!data || typeof data !== 'object') return data
    const out = { ...data }

    out.docente = normalizeDocente(data.docente ?? data.DOCENTE ?? data.Docente)
    out.anio_desde = data.anio_desde ?? data.ANIO_DESDE ?? data.anio ?? data.ANIO ?? null
    out.total = data.total ?? data.TOTAL ?? null

    // Info de restricción de periodos aún no concluidos (para el botón
    // de "habilitar" en ReporteHeader). Se pasa tal cual viene del backend.
    out.restriccion = data.restriccion ?? data.RESTRICCION ?? null

    const materiasRaw = data.materias ?? data.MATERIAS ?? []
    out.materias = Array.isArray(materiasRaw)
        ? materiasRaw.map(normalizeMateria)
        : materiasRaw

    return out
}

export function useReporte() {
    const reporte = ref(null)
    const loading = ref(false)
    const error = ref(null)

    /**
     * Genera el reporte de un docente.
     *
     * @param {number|string} codigoDocente
     * @param {Object} filtros
     * @param {number|null} filtros.anio               Año "desde" (o único año si no hay rango)
     * @param {string|null} filtros.periodo             Periodo del año desde: '1' | '2' | '3' | '4'
     * @param {number|null} filtros.anioHasta           Año "hasta" (para filtrar por rango)
     * @param {string|null} filtros.periodoHasta        Periodo del año hasta
     * @param {string|null} filtros.materia             Código o nombre de materia
     * @param {string|null} filtros.grupo
     * @param {boolean}     filtros.habilitarRestriccion  true = pide liberar un periodo puntual restringido
     * @param {number|null} filtros.anioHabilitado        Año del periodo a liberar (junto con habilitarRestriccion)
     * @param {string|null} filtros.periodoHabilitado     Periodo a liberar (junto con habilitarRestriccion)
     */
    const generarReporte = async (codigoDocente, filtros = {}) => {
        loading.value = true
        error.value = null
        reporte.value = null

        const {
            anio = null,
            periodo = null,
            anioHasta = null,
            periodoHasta = null,
            materia = null,
            grupo = null,
            habilitarRestriccion = false,
            anioHabilitado = null,
            periodoHabilitado = null,
        } = filtros


        try {
            const token = localStorage.getItem('token')
            const payload = { docente: Number(codigoDocente) }

            if (anio) payload.anio = Number(anio)
            if (periodo) payload.periodo = periodo
            if (anioHasta) payload.anio_hasta = Number(anioHasta)
            if (periodoHasta) payload.periodo_hasta = periodoHasta
            if (materia) payload.materia = materia
            if (grupo) payload.grupo = grupo

            // Solo se manda si el usuario clickeó "habilitar" en el frontend.
            // Si no, el backend sigue ocultando lo no concluido como siempre.
            if (habilitarRestriccion && anioHabilitado && periodoHabilitado) {
                payload.habilitar_restriccion = true
                payload.anio_habilitado = Number(anioHabilitado)
                payload.periodo_habilitado = periodoHabilitado
            }

            const response = await axios.post(
                `${API_BASE}/api/reporte-docente`,
                payload,
                { headers: { Authorization: `Bearer ${token}` } }
            )


            reporte.value = normalizeReporteResponse(response.data)


        } catch (err) {
            console.error('[useReporte] ERROR en la petición →', err.response?.data || err.message)
            error.value = err.response?.data?.message || 'Error al generar el reporte'
        } finally {
            loading.value = false
        }
    }
    // ─────────────────────────────────────────────────────
    // Abre o descarga el PDF de una resolución
    // Recibe el nro_resolucion (ej: "RR N 21/2007")
    // Primero busca el id, luego abre /resoluciones/{id}/pdf
    // ─────────────────────────────────────────────────────
    const verPdfResolucion = async (nroResolucion, descargar = false) => {
        const token = localStorage.getItem('token')

        const { data } = await axios.get(
            `${API_BASE}/api/resoluciones/por-numero`,
            {
                params: { nro: nroResolucion },
                headers: { Authorization: `Bearer ${token}` },
            }
        )

        if (!data.ok) {
            throw new Error('No encontrado en resoluciones')
        }

        const url = `${API_BASE}/api/resoluciones/${data.id_resolucion}/pdf`

        if (descargar) {
            const blob = await axios.get(url, {
                responseType: 'blob',
                headers: { Authorization: `Bearer ${token}` },
            })
            const blobUrl = URL.createObjectURL(new Blob([blob.data], { type: 'application/pdf' }))
            const link = document.createElement('a')
            link.href = blobUrl
            link.setAttribute('download', data.nombre_archivo || 'resolucion.pdf')
            document.body.appendChild(link)
            link.click()
            document.body.removeChild(link)
            URL.revokeObjectURL(blobUrl)
        } else {
            const blob = await axios.get(url, {
                responseType: 'blob',
                headers: { Authorization: `Bearer ${token}` },
            })
            const blobUrl = URL.createObjectURL(new Blob([blob.data], { type: 'application/pdf' }))
            window.open(blobUrl, '_blank')
        }
    }

    const limpiarReporte = () => {
        reporte.value = null
        error.value = null
    }

    return {
        reporte,
        loading,
        error,
        generarReporte,
        limpiarReporte,
        verPdfResolucion,
    }
}