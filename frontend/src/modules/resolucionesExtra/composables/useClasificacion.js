import { ref } from 'vue'
import axios from 'axios'

const API_BASE = import.meta.env.VITE_API_URL ?? ''

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
        }
    }

    function buildFormData({ cod_docente, categoria, nivel, tipo_documento, gestion, periodo, detalle_general, observacion, observacion2, materias, referencias, titulo, archivo }) {
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
        if (titulo) fd.append('titulo', JSON.stringify(titulo))   // 👈 esto faltaba
        if (archivo) fd.append('archivo_pdf', archivo)
        return fd
    }
    async function guardarClasificacion(payload) {
        loading.value = true
        error.value = null
        errorDetalle.value = null
        try {
            const fd = buildFormData(payload)

            console.log("===== FORMDATA (guardarClasificacion) =====")
            for (const [key, value] of fd.entries()) {
                console.log(key, value)
            }
            console.log("=============================================")

            const { data } = await axios.post(`${API_BASE}/api/clasificaciones`, fd, {
                headers: authHeaders({ 'Content-Type': 'multipart/form-data' }),
            })

            // 👇 nuevo: qué contestó el backend al guardar
            console.log("✅ Respuesta de guardarClasificacion:", data)

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
                error.value = e?.response?.data?.error || e.message || 'No se pudo guardar la clasificación'
                errorDetalle.value = parseErrorDetalle(e?.response?.data)
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
    function urlPdf(id, modo = 'inline') {
        return `${API_BASE}/api/clasificaciones/${id}/pdf?modo=${modo}`
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

        // 👇 NUEVO: mostrar exactamente qué se va a mandar
        console.log("===== ENVIANDO A /aplicar =====")
        console.log("URL:", `${API_BASE}/api/clasificaciones/${id}/aplicar`)
        console.log("id_documento:", id)
        console.log("ids_materia:", idsMateria)
        console.log("================================")

        try {
            const { data } = await axios.put(
                `${API_BASE}/api/clasificaciones/${id}/aplicar`,
                { ids_materia: idsMateria },
                { headers: authHeaders() }
            )

            // 👇 NUEVO: mostrar la respuesta completa, salga bien o mal
            console.log("✅ Respuesta completa de /aplicar:", data)
            console.log("   filas_afectadas:", data.filas_afectadas)
            console.log("   grupos:", data.grupos)
            console.log("   mensaje:", data.mensaje)

            if (!data.ok) {
                error.value = data.error || 'No se pudo aplicar la clasificación en grupos'
                errorDetalle.value = parseErrorDetalle(data)
                throw new Error(error.value)
            }

            return data // { ok, filas_afectadas, grupos, mensaje? }
        } catch (e) {
            error.value = e?.response?.data?.error || 'No se pudo aplicar la clasificación en grupos'
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

        // 👇 NUEVO
        console.log("===== ENVIANDO A /quitar =====")
        console.log("URL:", `${API_BASE}/api/clasificaciones/${id}/quitar`)
        console.log("id_documento:", id)
        console.log("ids_materia:", idsMateria)
        console.log("===============================")

        try {
            const { data } = await axios.put(
                `${API_BASE}/api/clasificaciones/${id}/quitar`,
                { ids_materia: idsMateria },
                { headers: authHeaders() }
            )

            // 👇 NUEVO
            console.log("✅ Respuesta completa de /quitar:", data)

            if (!data.ok) {
                error.value = data.error || 'No se pudo quitar la clasificación de grupos'
                errorDetalle.value = parseErrorDetalle(data)
                throw new Error(error.value)
            }

            return data // { ok, filas_afectadas, grupos, mensaje? }
        } catch (e) {
            error.value = e?.response?.data?.error || 'No se pudo quitar la clasificación de grupos'
            errorDetalle.value = parseErrorDetalle(e?.response?.data)
            console.error('❌ Error en quitarDeGrupos:', errorDetalle.value)
            console.error('❌ Respuesta cruda del error:', e?.response?.data)
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
        eliminar,
        eliminarDocente,
        aplicarEnGrupos,
        quitarDeGrupos,
        urlPdf,
        reset,
    }
}