import { ref } from 'vue'
import axios from 'axios'

const API_BASE = import.meta.env.VITE_API_URL

export function useClasificacion() {
    const loading = ref(false)
    const error = ref(null)
    const errorDetalle = ref(null)
    const listado = ref([])
    const actual = ref(null)

    function authHeaders(extra = {}) {
        const token = localStorage.getItem('token')
        return {
            ...(token ? { Authorization: `Bearer ${token}` } : {}),
            ...extra,
        }
    }

    function parseErrorDetalle(respData) {
        if (!respData) return null
        return {
            tipoError: respData.tipo_error ?? null,
            sqlstate: respData.sqlstate ?? null,
            codigoDriver: respData.codigo_driver ?? null,
            mensajeDriver: respData.mensaje_driver ?? null,
            mensaje: respData.error ?? null,
            sql: respData.sql ?? null,
            bindings: respData.bindings ?? null,
            archivo: respData.archivo ?? null,
            linea: respData.linea ?? null,
            paso: respData.paso ?? null,
            pasosCompletados: respData.pasos_completados ?? null,
            revertido: respData.revertido ?? null,
        }
    }

    // Arma un mensaje legible: error + paso donde falló + estado de los datos.
    function armarMensajeError(data, fallback) {
        const base = data?.error || fallback
        if (!data?.paso) return base
        const estado = data.revertido
            ? 'No se guardó NINGÚN cambio: todo se revirtió y los datos quedaron como estaban.'
            : 'Atención: algunos cambios pudieron haberse aplicado. Revisa el documento.'
        return `${base}\n\nFalló en el paso: ${data.paso}\n${estado}`
    }

    const MSG_RED =
        'No hay conexión con el servidor. No se pudo confirmar si los cambios se guardaron; recarga el listado y verifica.'

    function buildFormData({
        cod_docente, categoria, nivel, tipo_documento, gestion, periodo,
        detalle_general, observacion, observacion2, materias, referencias,
        titulo, archivo, solo_este_docente,
        id_documento_origen, // reutilizar el PDF de otro documento (no se vuelve a subir)
    }) {
        const fd = new FormData()
        if (cod_docente) fd.append('cod_docente', cod_docente)
        fd.append('categoria', categoria)
        if (nivel) fd.append('nivel', nivel)
        if (tipo_documento) fd.append('tipo_documento', tipo_documento)
        if (gestion) fd.append('gestion', gestion)
        if (periodo) fd.append('periodo', periodo)
        if (detalle_general) fd.append('detalle_general', detalle_general)
        if (observacion) fd.append('observacion', observacion)
        if (observacion2) fd.append('observacion2', observacion2)
        fd.append('materias', JSON.stringify(materias || []))
        fd.append('referencias', JSON.stringify(referencias || []))
        if (titulo) fd.append('titulo', JSON.stringify(titulo))
        if (archivo) fd.append('archivo_pdf', archivo)

        // Sin archivo nuevo pero con documento origen: el backend reutiliza
        // esa RUTA_ARCHIVO/NOMBRE_ARCHIVO en vez de pedir un PDF nuevo.
        if (!archivo && id_documento_origen) {
            fd.append('id_documento_origen', id_documento_origen)
        }

        if (solo_este_docente) fd.append('solo_este_docente', '1')
        return fd
    }

    // POST /api/clasificaciones
    async function guardarClasificacion(payload) {
        loading.value = true
        error.value = null
        errorDetalle.value = null
        try {
            const fd = buildFormData(payload)

            console.log('===== FORMDATA (guardarClasificacion) =====')
            for (const [key, value] of fd.entries()) {
                console.log(key, value)
            }
            console.log('=============================================')

            const { data } = await axios.post(`${API_BASE}/api/clasificaciones`, fd, {
                headers: authHeaders({ 'Content-Type': 'multipart/form-data' }),
            })

            console.log('✅ Respuesta de guardarClasificacion:', data)

            if (!data.ok) {
                error.value = data.error || 'No se pudo guardar la clasificación'
                errorDetalle.value = parseErrorDetalle(data)
                throw new Error(error.value)
            }

            return {
                idDocumento: data.id_documento,
                idsClasificacionDocente: data.ids_clasificacion_docente ?? [],
                materiasInsertadas: data.materias_insertadas,
                referenciasInsertadas: data.referencias_insertadas,
            }
        } catch (e) {
            if (e?.response?.data?.tipo === 'validacion') {
                const primerError = Object.values(e.response.data.errores)[0]?.[0]
                error.value = primerError || 'Error de validación'
                errorDetalle.value = { tipoError: 'validacion', mensaje: error.value }
            } else if (!error.value) {
                if (e?.response?.data) {
                    error.value = e.response.data.error || e.message || 'No se pudo guardar la clasificación'
                    errorDetalle.value = parseErrorDetalle(e.response.data)
                } else if (e.message === 'Network Error') {
                    error.value = MSG_RED
                } else {
                    error.value = e.message || 'No se pudo guardar la clasificación'
                }
            }
            throw e
        } finally {
            loading.value = false
        }
    }

    // GET /api/clasificaciones
    async function listar(filtros = {}) {
        loading.value = true
        error.value = null
        errorDetalle.value = null
        try {
            const { data } = await axios.get(`${API_BASE}/api/clasificaciones`, {
                params: filtros,
                headers: authHeaders(),
            })
            listado.value = data
            return data
        } catch (e) {
            error.value = e?.response?.data?.error || 'No se pudo obtener el listado'
            errorDetalle.value = parseErrorDetalle(e?.response?.data)
            throw e
        } finally {
            loading.value = false
        }
    }

    // GET /api/clasificaciones/{id}
    async function obtener(id) {
        loading.value = true
        error.value = null
        errorDetalle.value = null
        try {
            const { data } = await axios.get(`${API_BASE}/api/clasificaciones/${id}`, {
                headers: authHeaders(),
            })
            actual.value = data
            return data
        } catch (e) {
            error.value = e?.response?.data?.error || 'Clasificación no encontrada'
            errorDetalle.value = parseErrorDetalle(e?.response?.data)
            throw e
        } finally {
            loading.value = false
        }
    }

    // DELETE /api/clasificaciones/{id}
    async function eliminar(id) {
        loading.value = true
        error.value = null
        errorDetalle.value = null
        try {
            const { data } = await axios.delete(`${API_BASE}/api/clasificaciones/${id}`, {
                headers: authHeaders(),
            })
            return data
        } catch (e) {
            error.value = e?.response?.data?.error || 'No se pudo eliminar la clasificación'
            errorDetalle.value = parseErrorDetalle(e?.response?.data)
            throw e
        } finally {
            loading.value = false
        }
    }

    // GET /api/clasificaciones/{id}/pdf
    // OJO: esta URL NO debe usarse directo en un <a href>, porque esa ruta
    // está protegida con auth:sanctum y un <a> normal no manda el token.
    function urlPdf(id, modo = 'inline') {
        return `${API_BASE}/api/clasificaciones/${id}/pdf?modo=${modo}`
    }

    // Descarga el PDF protegido con axios (sí manda el token) y lo abre
    // en una pestaña nueva como blob.
    async function verPdf(id, modo = 'inline') {
        loading.value = true
        error.value = null
        try {
            const response = await axios.get(`${API_BASE}/api/clasificaciones/${id}/pdf`, {
                params: { modo },
                headers: authHeaders(),
                responseType: 'blob',
            })

            const blobUrl = window.URL.createObjectURL(
                new Blob([response.data], { type: 'application/pdf' })
            )
            window.open(blobUrl, '_blank')

            setTimeout(() => window.URL.revokeObjectURL(blobUrl), 60_000)
        } catch (e) {
            error.value = e?.response?.data?.error || 'No se pudo abrir el PDF'
            errorDetalle.value = parseErrorDetalle(e?.response?.data)
            console.error('❌ Error en verPdf:', e)
            throw e
        } finally {
            loading.value = false
        }
    }

    function reset() {
        error.value = null
        errorDetalle.value = null
        actual.value = null
    }

    // DELETE /api/clasificaciones/docente/{idClasificacionDocente}
    async function eliminarDocente(idClasificacionDocente) {
        loading.value = true
        error.value = null
        errorDetalle.value = null
        try {
            const { data } = await axios.delete(
                `${API_BASE}/api/clasificaciones/docente/${idClasificacionDocente}`,
                { headers: authHeaders() }
            )
            return data
        } catch (e) {
            error.value = e?.response?.data?.error || 'No se pudo eliminar el docente de la clasificación'
            errorDetalle.value = parseErrorDetalle(e?.response?.data)
            throw e
        } finally {
            loading.value = false
        }
    }

    // PUT /api/clasificaciones/{id}/aplicar
    async function aplicarEnGrupos(id, idsMateria = []) {
        loading.value = true
        error.value = null
        errorDetalle.value = null

        console.log('===== ENVIANDO A /aplicar =====')
        console.log('URL:', `${API_BASE}/api/clasificaciones/${id}/aplicar`)
        console.log('id_documento:', id)
        console.log('ids_materia:', idsMateria)
        console.log('================================')

        try {
            const { data } = await axios.put(
                `${API_BASE}/api/clasificaciones/${id}/aplicar`,
                { ids_materia: idsMateria },
                { headers: authHeaders() }
            )

            console.log('✅ Respuesta completa de /aplicar:', data)
            console.log('   filas_afectadas:', data.filas_afectadas)
            console.log('   grupos:', data.grupos)
            console.log('   mensaje:', data.mensaje)

            if (!data.ok) {
                error.value = data.error || 'No se pudo aplicar la clasificación en grupos'
                errorDetalle.value = parseErrorDetalle(data)
                throw new Error(error.value)
            }

            return data // { ok, filas_afectadas, grupos, mensaje? }
        } catch (e) {
            error.value = e?.response?.data?.error
                || (e.message === 'Network Error' ? MSG_RED : null)
                || e.message
                || 'No se pudo aplicar la clasificación en grupos'
            errorDetalle.value = parseErrorDetalle(e?.response?.data)
            console.error('❌ Error en aplicarEnGrupos:', errorDetalle.value)
            console.error('❌ Respuesta cruda del error:', e?.response?.data)
            throw e
        } finally {
            loading.value = false
        }
    }

    // PUT /api/clasificaciones/{id}/quitar
    async function quitarDeGrupos(id, idsMateria = []) {
        loading.value = true
        error.value = null
        errorDetalle.value = null

        console.log('===== ENVIANDO A /quitar =====')
        console.log('URL:', `${API_BASE}/api/clasificaciones/${id}/quitar`)
        console.log('id_documento:', id)
        console.log('ids_materia:', idsMateria)
        console.log('===============================')

        try {
            const { data } = await axios.put(
                `${API_BASE}/api/clasificaciones/${id}/quitar`,
                { ids_materia: idsMateria },
                { headers: authHeaders() }
            )

            console.log('✅ Respuesta completa de /quitar:', data)

            if (!data.ok) {
                error.value = data.error || 'No se pudo quitar la clasificación de grupos'
                errorDetalle.value = parseErrorDetalle(data)
                throw new Error(error.value)
            }

            return data // { ok, filas_afectadas, grupos, mensaje? }
        } catch (e) {
            error.value = e?.response?.data?.error
                || (e.message === 'Network Error' ? MSG_RED : null)
                || e.message
                || 'No se pudo quitar la clasificación de grupos'
            errorDetalle.value = parseErrorDetalle(e?.response?.data)
            console.error('❌ Error en quitarDeGrupos:', errorDetalle.value)
            console.error('❌ Respuesta cruda del error:', e?.response?.data)
            throw e
        } finally {
            loading.value = false
        }
    }

    // POST (con _method=PUT) /api/clasificaciones/{id}
    async function actualizarClasificacion(id, payload, confirmarDesvinculacion = false) {
        loading.value = true
        error.value = null
        errorDetalle.value = null
        try {
            const fd = buildFormData(payload)
            fd.append('_method', 'PUT')
            if (confirmarDesvinculacion) fd.append('confirmar_desvinculacion', '1')

            const { data } = await axios.post(`${API_BASE}/api/clasificaciones/${id}`, fd, {
                headers: authHeaders({ 'Content-Type': 'multipart/form-data' }),
            })

            // Caso especial ANTES del error genérico, por si axios resuelve el 409 sin lanzar.
            if (!data.ok && data.tipo === 'confirmar_desvinculacion') {
                const err = new Error('confirmar_desvinculacion')
                err.docentesADesvincular = data.docentes_a_desvincular
                err.mensaje = data.mensaje
                throw err
            }

            if (!data.ok) {
                error.value = armarMensajeError(data, 'No se pudo actualizar la clasificación')
                errorDetalle.value = parseErrorDetalle(data)
                throw new Error(error.value)
            }
            return data
        } catch (e) {
            // Ya viene armado desde arriba
            if (e.message === 'confirmar_desvinculacion' && e.docentesADesvincular) {
                throw e
            }
            // axios sí rechazó con status 409
            if (e?.response?.status === 409 && e?.response?.data?.tipo === 'confirmar_desvinculacion') {
                const err = new Error('confirmar_desvinculacion')
                err.docentesADesvincular = e.response.data.docentes_a_desvincular
                err.mensaje = e.response.data.mensaje
                throw err
            }
            if (e?.response?.data?.tipo === 'validacion') {
                const primerError = Object.values(e.response.data.errores)[0]?.[0]
                error.value = primerError || 'Error de validación'
                errorDetalle.value = { tipoError: 'validacion', mensaje: error.value }
            } else if (e?.response?.data) {
                // Error del servidor (500 con paso, etc.)
                error.value = armarMensajeError(e.response.data, e.message || 'No se pudo actualizar la clasificación')
                errorDetalle.value = parseErrorDetalle(e.response.data)
            } else if (!error.value) {
                error.value = e.message === 'Network Error'
                    ? MSG_RED
                    : (e.message || 'No se pudo actualizar la clasificación')
            }
            throw e
        } finally {
            loading.value = false
        }
    }

    // POST /api/clasificaciones/{idDocumento}/materias/bulk
    async function guardarMateriasAdicionales(idDocumento, detalles) {
        loading.value = true
        error.value = null
        errorDetalle.value = null
        try {
            const { data } = await axios.post(
                `${API_BASE}/api/clasificaciones/${idDocumento}/materias/bulk`,
                { detalles },
                { headers: authHeaders() }
            )
            if (!data.ok) {
                error.value = data.error || 'No se pudieron agregar las materias'
                throw new Error(error.value)
            }
            return data // { ok, total, ids_materia }
        } catch (e) {
            error.value = e?.response?.data?.error || e.message || 'No se pudieron agregar las materias'
            errorDetalle.value = parseErrorDetalle(e?.response?.data)
            throw e
        } finally {
            loading.value = false
        }
    }

    return {
        loading,
        error,
        errorDetalle,
        listado,
        actual,
        listar,
        obtener,
        guardarClasificacion,
        actualizarClasificacion,
        eliminar,
        eliminarDocente,
        aplicarEnGrupos,
        quitarDeGrupos,
        urlPdf,
        verPdf,
        reset,
        guardarMateriasAdicionales,
    }
}