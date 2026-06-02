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
    return out
}

const normalizeReporteResponse = (data) => {
    if (!data || typeof data !== 'object') return data
    const out = { ...data }

    out.docente = normalizeDocente(data.docente ?? data.DOCENTE ?? data.Docente)
    out.anio_desde = data.anio_desde ?? data.ANIO_DESDE ?? data.anio ?? data.ANIO ?? null
    out.total = data.total ?? data.TOTAL ?? null

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

    // const generarReporte = async (codigoDocente, anio = null) => {
    //     loading.value = true
    //     error.value = null
    //     reporte.value = null

    //     try {
    //         const token = localStorage.getItem('token')
    //         const payload = { docente: Number(codigoDocente) }
    //         if (anio) payload.anio = Number(anio)

    //         const response = await axios.post(
    //             `${API_BASE}/api/reporte-docente`,
    //             payload,
    //             { headers: { Authorization: `Bearer ${token}` } }
    //         )
    //         reporte.value = response.data
    //     } catch (err) {
    //         error.value = err.response?.data?.message || 'Error al generar el reporte'
    //     } finally {
    //         loading.value = false
    //     }
    // }

    const generarReporte = async (codigoDocente, anio = null, materia = null, grupo = null) => {
        loading.value = true
        error.value = null
        reporte.value = null

        try {
            const token = localStorage.getItem('token')
            const payload = { docente: Number(codigoDocente) }

            if (anio) payload.anio = Number(anio)
            if (materia) payload.materia = materia   // AÑADIR
            if (grupo) payload.grupo = grupo     // AÑADIR

            const response = await axios.post(
                `${API_BASE}/api/reporte-docente`,
                payload,
                { headers: { Authorization: `Bearer ${token}` } }
            )
            reporte.value = normalizeReporteResponse(response.data)
        } catch (err) {
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
        try {
            const token = localStorage.getItem('token')

            // 1. Buscar el id_resolucion por nro_resolucion
            const { data } = await axios.get(
                `${API_BASE}/api/resoluciones/por-numero`,
                {
                    params: { nro: nroResolucion },
                    headers: { Authorization: `Bearer ${token}` },
                }
            )

            if (!data.ok) {
                alert('No se encontró el PDF para esta resolución.')
                return
            }

            // 2. Construir la URL del PDF
            const url = `${API_BASE}/api/resoluciones/${data.id_resolucion}/pdf`

            if (descargar) {
                // Descarga forzada
                const link = document.createElement('a')
                link.href = url
                link.setAttribute('download', data.nombre_archivo || 'resolucion.pdf')
                // Agregar token en header no funciona con <a>, 
                // entonces pedimos el blob y lo descargamos
                const blob = await axios.get(url, {
                    responseType: 'blob',
                    headers: { Authorization: `Bearer ${token}` },
                })
                const blobUrl = URL.createObjectURL(new Blob([blob.data], { type: 'application/pdf' }))
                link.href = blobUrl
                document.body.appendChild(link)
                link.click()
                document.body.removeChild(link)
                URL.revokeObjectURL(blobUrl)
            } else {
                // Ver en nueva pestaña — igual usando blob para enviar el token
                const blob = await axios.get(url, {
                    responseType: 'blob',
                    headers: { Authorization: `Bearer ${token}` },
                })
                const blobUrl = URL.createObjectURL(new Blob([blob.data], { type: 'application/pdf' }))
                window.open(blobUrl, '_blank')
            }

        } catch (err) {
            console.error('❌ verPdfResolucion:', err)
            alert('Error al obtener el PDF.')
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
        verPdfResolucion,  // 👈 nueva
    }
}