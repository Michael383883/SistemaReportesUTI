import { ref, computed } from 'vue'
import axios from 'axios'
import { abrevPlan } from '../../resoluciones/utils/planes'

const API_BASE = import.meta.env.VITE_API_URL

export function useAsignacionDocumento(idDocumento, datosDocumento) {
    const loading = ref(false)
    const error = ref(null)
    const errorDetalle = ref(null)

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

    // El "documento" ya viene fijo — no hay buscador, a diferencia de resolución
    const documentoActivo = ref(datosDocumento) // { idDocumento, gestion, periodo, detalle_general, tipo_documento }

    const materiasMarcadas = ref([])

    function generarKey(docenteCod, materia) {
        return `${docenteCod}__${materia.plan}__${materia.materia}__${materia.grp}__${materia.gestion}`
    }

    function extraerCodMateria(materiaRaw) {
        if (!materiaRaw) return ''
        return String(materiaRaw).trim().split(/\s+/)[0]
    }

    function toggleMateria(docente, materia) {
        const docenteCod = docente.cod_docente ?? docente.CODIGO ?? docente.codigo
        const key = generarKey(docenteCod, materia)
        const idx = materiasMarcadas.value.findIndex(m => m.key === key)
        if (idx !== -1) { materiasMarcadas.value.splice(idx, 1); return }

        materiasMarcadas.value.push({
            key,
            docente,
            cod_docente: Number(docenteCod),
            cod_plan: materia.plan,
            planLabel: abrevPlan(materia.plan),
            cod_materia: extraerCodMateria(materia.materia),
            nombre_materia: materia.materia,
            grupo: materia.grp || null,
            tipo: materia.tipo || 'N',
            tipo_ingreso: materia.tipo_ingreso || null,
            gestion: materia.gestion,
            observacion: materia.compartido ? 'COMPARTIDO' : null,
            materiaLabel: materia.materia,
        })
    }

    function actualizarTipoIngreso(docente, materia) {
        const docenteCod = docente.cod_docente ?? docente.CODIGO ?? docente.codigo
        const item = materiasMarcadas.value.find(m => m.key === generarKey(docenteCod, materia))
        if (item) item.tipo_ingreso = materia.tipo_ingreso || null
    }

    function quitarMateria(key) {
        materiasMarcadas.value = materiasMarcadas.value.filter(m => m.key !== key)
    }

    function limpiarTodo() {
        materiasMarcadas.value = []
    }

    const idResolucion = ref(null)
    const ultimosIdsDetalle = ref([])

    // POST /api/clasificaciones/{idDocumento}/generar-resolucion
    // Puente: crea (o reutiliza) la fila en RESOLUCIONES_PDF a partir de
    // CLASIFICACION_DOCUMENTO (tipo_documento, detalle_general, gestion,
    // periodo) sin volver a subir el PDF.
    async function generarResolucionBase() {
        try {
            const { data } = await axios.post(
                `${API_BASE}/api/clasificaciones/${idDocumento}/generar-resolucion`,
                {},
                { headers: authHeaders() }
            )

            console.log("✅ Respuesta de generarResolucionBase:", data)

            if (!data.ok) {
                error.value = data.error || 'No se pudo generar la resolución base'
                errorDetalle.value = parseErrorDetalle(data)
                throw new Error(error.value)
            }

            idResolucion.value = data.id_resolucion
            return data
        } catch (e) {
            if (!error.value) {
                error.value = e?.response?.data?.error || e.message || 'No se pudo generar la resolución base'
                errorDetalle.value = parseErrorDetalle(e?.response?.data)
            }
            throw e
        }
    }

    // POST /api/resoluciones/{id}/detalles/bulk
    async function confirmarAsignacion() {
        if (materiasMarcadas.value.length === 0) {
            throw new Error('Marca al menos una materia para asignar.')
        }

        loading.value = true
        error.value = null
        errorDetalle.value = null

        try {
            if (!idResolucion.value) {
                await generarResolucionBase()
            }

            const detalles = materiasMarcadas.value.map(m => ({
                cod_docente: m.cod_docente,
                cod_plan: m.cod_plan,
                cod_materia: m.cod_materia,
                grupo: m.grupo,
                tipo: m.tipo || 'N',
                tipo_ingreso: m.tipo_ingreso,
                observacion: m.observacion,
            }))

            console.log("===== FORMDATA (confirmarAsignacion / detalles bulk) =====")
            console.log("id_resolucion:", idResolucion.value)
            console.log("detalles:", detalles)
            console.log("============================================================")

            const { data } = await axios.post(
                `${API_BASE}/api/resoluciones/${idResolucion.value}/detalles/bulk`,
                { detalles },
                { headers: authHeaders() }
            )

            console.log("✅ Respuesta de confirmarAsignacion (detalles/bulk):", data)

            ultimosIdsDetalle.value = data?.ids_detalle ?? []

            return { idResolucion: idResolucion.value, idsDetalle: ultimosIdsDetalle.value }
        } catch (e) {
            if (e?.response?.data?.tipo === 'validacion') {
                const primerError = Object.values(e.response.data.errores)[0]?.[0]
                error.value = primerError || 'Error de validación'
                errorDetalle.value = { tipoError: 'validacion', mensaje: error.value }
            } else if (!error.value) {
                error.value = e?.response?.data?.error || e.message || 'Error al guardar la asignación.'
                errorDetalle.value = parseErrorDetalle(e?.response?.data)
            }
            throw e
        } finally {
            loading.value = false
        }
    }

    // POST /api/resoluciones/{id}/aplicar-grupos
    async function aplicarEnGrupos(idsDetalle = null) {
        const ids = idsDetalle ?? ultimosIdsDetalle.value

        loading.value = true
        error.value = null
        errorDetalle.value = null

        console.log("===== ENVIANDO A /aplicar-grupos =====")
        console.log("URL:", `${API_BASE}/api/resoluciones/${idResolucion.value}/aplicar-grupos`)
        console.log("id_resolucion:", idResolucion.value)
        console.log("ids_detalle:", ids)
        console.log("=======================================")

        try {
            const { data } = await axios.post(
                `${API_BASE}/api/resoluciones/${idResolucion.value}/aplicar-grupos`,
                { ids_detalle: ids },
                { headers: authHeaders() }
            )

            console.log("✅ Respuesta completa de /aplicar-grupos:", data)
            console.log("   filas_afectadas:", data.filas_afectadas)
            console.log("   grupos:", data.grupos)

            if (!data.ok) {
                error.value = data.error || 'No se pudo aplicar la asignación en grupos'
                errorDetalle.value = parseErrorDetalle(data)
                throw new Error(error.value)
            }

            return data // { ok, filas_afectadas, grupos }
        } catch (e) {
            error.value = e?.response?.data?.error || 'No se pudo aplicar la asignación en grupos'
            errorDetalle.value = parseErrorDetalle(e?.response?.data)
            console.error('❌ Error en aplicarEnGrupos:', errorDetalle.value)
            console.error('❌ Respuesta cruda del error:', e?.response?.data)
            throw e
        } finally {
            loading.value = false
        }
    }

    // PUT /api/resoluciones/{id}/quitar
    async function quitarDeGrupos(idsDetalle = null) {
        const ids = idsDetalle ?? ultimosIdsDetalle.value

        loading.value = true
        error.value = null
        errorDetalle.value = null

        console.log("===== ENVIANDO A /quitar =====")
        console.log("URL:", `${API_BASE}/api/resoluciones/${idResolucion.value}/quitar`)
        console.log("id_resolucion:", idResolucion.value)
        console.log("ids_detalle:", ids)
        console.log("===============================")

        try {
            const { data } = await axios.put(
                `${API_BASE}/api/resoluciones/${idResolucion.value}/quitar`,
                { ids_detalle: ids },
                { headers: authHeaders() }
            )

            console.log("✅ Respuesta completa de /quitar:", data)

            if (!data.ok) {
                error.value = data.error || 'No se pudo quitar la asignación de grupos'
                errorDetalle.value = parseErrorDetalle(data)
                throw new Error(error.value)
            }

            return data
        } catch (e) {
            error.value = e?.response?.data?.error || 'No se pudo quitar la asignación de grupos'
            errorDetalle.value = parseErrorDetalle(e?.response?.data)
            console.error('❌ Error en quitarDeGrupos:', errorDetalle.value)
            throw e
        } finally {
            loading.value = false
        }
    }

    return {
        documentoActivo,
        materiasMarcadas,
        loading,
        guardando: loading,
        error,
        errorGuardado: error,
        errorDetalle,
        toggleMateria,
        actualizarTipoIngreso,
        quitarMateria,
        limpiarTodo,
        generarResolucionBase,
        confirmarAsignacion,
        aplicarEnGrupos,
        quitarDeGrupos,
        idResolucion: computed(() => idResolucion.value),
    }
}