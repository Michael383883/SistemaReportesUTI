import { ref, computed } from 'vue'
import axios from 'axios'
import { useResolucion } from '../../resoluciones/composables/useResolucion' // ajustá la ruta real
import { abrevPlan } from '../../resoluciones/utils/planes'

const API_BASE = import.meta.env.VITE_API_URL

/**
 * Orquesta "Asignar documento (clasificación) a materias de otros docentes".
 * Calcado de useAsignacionResolucion.js, con un paso previo:
 *
 *  1. Se elige un DOCUMENTO de CLASIFICACION_DOCUMENTO (queda fijo una vez
 *     hay materias marcadas, igual que la resolución en el otro flujo).
 *  2. Al confirmar la PRIMERA asignación, se genera (o reutiliza) la fila
 *     puente en RESOLUCIONES_PDF a partir de ese documento — mismo PDF,
 *     no se vuelve a subir, solo se copia RUTA_ARCHIVO/NOMBRE_ARCHIVO.
 *  3. De ahí en más TODO pasa por el mismo circuito que "Asignar
 *     resolución" (RESOLUCION_DETALLE + aplicarEnGrupos de useResolucion),
 *     así el tipo_ingreso que el usuario elige por materia se respeta
 *     y no se pisa con la CATEGORIA del documento.
 */
export function useAsignacionDocumento() {
    const {
        loading: guardando,
        error: errorGuardado,
        guardarDetalles,
        aplicarEnGrupos: aplicarEnGruposBase,
    } = useResolucion()

    const generandoPuente = ref(false)
    const errorPuente = ref(null)

    function authHeaders(extra = {}) {
        const token = localStorage.getItem('token')
        return {
            ...(token ? { Authorization: `Bearer ${token}` } : {}),
            ...extra,
        }
    }

    // { idDocumento, nroResolucion(=tipo_documento), descripcion(=detalle_general),
    //   anio(=gestion), periodo, nombreArchivo, idResolucion (null hasta confirmar) }
    const documentoActivo = ref(null)

    const materiasMarcadas = ref([])
    const documentoBloqueado = computed(() => materiasMarcadas.value.length > 0)

    function generarKey(docenteCod, materia) {
        return `${docenteCod}__${materia.plan}__${materia.materia}__${materia.grp}__${materia.gestion}`
    }

    function extraerCodMateria(materiaRaw) {
        if (!materiaRaw) return ''
        return String(materiaRaw).trim().split(/\s+/)[0]
    }

    function seleccionarDocumento(doc) {
        if (documentoBloqueado.value) return false
        documentoActivo.value = {
            idDocumento: doc.idDocumento,
            nroResolucion: doc.tipoDocumento,
            descripcion: doc.detalleGeneral ?? doc.descripcion ?? '',
            anio: String(doc.gestion ?? '').trim(),
            periodo: String(doc.periodo ?? '').trim(),
            nombreArchivo: doc.nombreArchivo,
            idResolucion: null,
        }
        return true
    }

    function limpiarDocumento() {
        if (documentoBloqueado.value) return false
        documentoActivo.value = null
        return true
    }

    function toggleMateria(docente, materia) {
        if (!documentoActivo.value) return

        const docenteCod = docente.cod_docente ?? docente.CODIGO ?? docente.codigo
        const key = generarKey(docenteCod, materia)
        const idx = materiasMarcadas.value.findIndex(m => m.key === key)

        if (idx !== -1) {
            materiasMarcadas.value.splice(idx, 1)
            return
        }

        materiasMarcadas.value.push({
            key,
            docente,
            cod_docente: Number(docenteCod),
            cod_plan: materia.plan,
            planLabel: abrevPlan(materia.plan),
            cod_materia: extraerCodMateria(materia.materia),
            grupo: materia.grp || null,
            tipo: 'N',
            tipo_ingreso: materia.tipo_ingreso || null,
            gestion: materia.gestion,
            observacion: materia.compartido ? 'COMPARTIDO' : null,
            materiaLabel: materia.materia,
        })
    }

    function actualizarTipoIngreso(docente, materia) {
        const docenteCod = docente.cod_docente ?? docente.CODIGO ?? docente.codigo
        const key = generarKey(docenteCod, materia)
        const item = materiasMarcadas.value.find(m => m.key === key)
        if (item) item.tipo_ingreso = materia.tipo_ingreso || null
    }

    function quitarMateria(key) {
        materiasMarcadas.value = materiasMarcadas.value.filter(m => m.key !== key)
    }

    function limpiarTodo() {
        materiasMarcadas.value = []
        documentoActivo.value = null
    }

    // POST /api/clasificaciones/{idDocumento}/generar-resolucion
    // Se llama solo una vez por documento (se cachea en documentoActivo.idResolucion).
    // Si ya existe una fila puente para ese tipo_documento+anio+periodo, el
    // backend la reutiliza y devuelve el mismo id_resolucion.
    async function asegurarResolucionPuente() {
        if (documentoActivo.value.idResolucion) return documentoActivo.value.idResolucion

        generandoPuente.value = true
        errorPuente.value = null
        try {
            const { data } = await axios.post(
                `${API_BASE}/api/clasificaciones/${documentoActivo.value.idDocumento}/generar-resolucion`,
                {},
                { headers: authHeaders() }
            )

            console.log('✅ Respuesta generar-resolucion (puente):', data)

            if (!data.ok) {
                errorPuente.value = data.error || 'No se pudo generar la resolución puente'
                throw new Error(errorPuente.value)
            }

            documentoActivo.value.idResolucion = data.id_resolucion
            return data.id_resolucion
        } catch (e) {
            errorPuente.value = e?.response?.data?.error || e.message || 'No se pudo generar la resolución puente'
            throw e
        } finally {
            generandoPuente.value = false
        }
    }

    const ultimosIdsDetalle = ref([])

    async function confirmarAsignacion() {
        if (!documentoActivo.value) throw new Error('Selecciona un documento antes de continuar.')
        if (materiasMarcadas.value.length === 0) throw new Error('Marca al menos una materia para asignar.')

        const idResolucion = await asegurarResolucionPuente()

        const detalles = materiasMarcadas.value.map(m => ({
            cod_docente: m.cod_docente,
            cod_plan: m.cod_plan,
            cod_materia: m.cod_materia,
            grupo: m.grupo,
            tipo: m.tipo,
            tipo_ingreso: m.tipo_ingreso,
            observacion: m.observacion,
        }))

        const resultado = await guardarDetalles(idResolucion, detalles)
        ultimosIdsDetalle.value = resultado?.ids_detalle ?? []

        return { idResolucion, resultado, idsDetalle: ultimosIdsDetalle.value }
    }

    async function aplicarEnGrupos(idResolucion, idsDetalle = null) {
        const ids = idsDetalle ?? ultimosIdsDetalle.value
        return aplicarEnGruposBase(idResolucion, ids)
    }

    return {
        documentoActivo,
        materiasMarcadas,
        documentoBloqueado,
        guardando: computed(() => guardando.value || generandoPuente.value),
        errorGuardado: computed(() => errorGuardado.value || errorPuente.value),

        seleccionarDocumento,
        limpiarDocumento,
        toggleMateria,
        actualizarTipoIngreso,
        quitarMateria,
        limpiarTodo,
        confirmarAsignacion,
        aplicarEnGrupos,
    }
}