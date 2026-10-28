import { ref, computed } from 'vue'
import { useAsignacionResolucion } from './useAsignacionResolucion' // ajustá la ruta real
import { useAsignacionDocumento } from './useAsignacionDocumento' // ajustá la ruta real

/**
 * Adaptador que unifica "Asignar resolución" y "Asignar documento (clasificación)"
 * bajo una sola API, para que la vista no tenga que saber cuál de los dos
 * composables está usando por debajo.
 *
 * No reemplaza la lógica de useAsignacionResolucion / useAsignacionDocumento
 * (que siguen guardando cada una a su manera: la primera pega directo contra
 * la resolución elegida, la segunda genera/reutiliza una fila puente en
 * RESOLUCIONES_PDF antes de guardar). Solo expone un frente común:
 *
 *   tipo               -> 'resolucion' | 'documento'
 *   origenActivo       -> resolucionActiva.value  o  documentoActivo.value
 *   origenBloqueado    -> resolucionBloqueada.value  o  documentoBloqueado.value
 *   materiasMarcadas, guardando, errorGuardado
 *   seleccionarOrigen(o), limpiarOrigen(), toggleMateria(docente, materia),
 *   actualizarTipoIngreso(docente, materia), quitarMateria(key), limpiarTodo(),
 *   confirmarAsignacion(), aplicarEnGrupos(idResolucion, idsDetalle)
 *
 * IMPORTANTE: no se puede cambiar de "tipo" (resolucion <-> documento) una vez
 * que hay materias marcadas en cualquiera de los dos, para no mezclar estados.
 */
export function useAsignacionOrigen(tipoInicial = 'resolucion') {
    const tipo = ref(tipoInicial) // 'resolucion' | 'documento'

    const resol = useAsignacionResolucion()
    const docu = useAsignacionDocumento()

    const bloqueadoCambioTipo = computed(() =>
        resol.materiasMarcadas.value.length > 0 || docu.materiasMarcadas.value.length > 0
    )

    function setTipo(nuevo) {
        if (bloqueadoCambioTipo.value) return false
        if (nuevo !== 'resolucion' && nuevo !== 'documento') return false
        tipo.value = nuevo
        return true
    }

    const esResolucion = computed(() => tipo.value === 'resolucion')

    // ─── Estado delegado ───────────────────────────────────────────
    const origenActivo = computed(() =>
        esResolucion.value ? resol.resolucionActiva.value : docu.documentoActivo.value
    )
    const origenBloqueado = computed(() =>
        esResolucion.value ? resol.resolucionBloqueada.value : docu.documentoBloqueado.value
    )
    const materiasMarcadas = computed(() =>
        esResolucion.value ? resol.materiasMarcadas.value : docu.materiasMarcadas.value
    )
    const guardando = computed(() =>
        esResolucion.value ? resol.guardando.value : docu.guardando.value
    )
    const errorGuardado = computed(() =>
        esResolucion.value ? resol.errorGuardado.value : docu.errorGuardado.value
    )

    // ─── Acciones delegadas ────────────────────────────────────────
    function seleccionarOrigen(o) {
        return esResolucion.value ? resol.seleccionarResolucion(o) : docu.seleccionarDocumento(o)
    }

    function limpiarOrigen() {
        return esResolucion.value ? resol.limpiarResolucion() : docu.limpiarDocumento()
    }

    function toggleMateria(docente, materia) {
        return esResolucion.value
            ? resol.toggleMateria(docente, materia)
            : docu.toggleMateria(docente, materia)
    }

    function actualizarTipoIngreso(docente, materia) {
        return esResolucion.value
            ? resol.actualizarTipoIngreso(docente, materia)
            : docu.actualizarTipoIngreso(docente, materia)
    }

    function quitarMateria(key) {
        return esResolucion.value ? resol.quitarMateria(key) : docu.quitarMateria(key)
    }

    // Limpia los dos por las dudas (no importa cuál estaba activo).
    function limpiarTodo() {
        resol.limpiarTodo()
        docu.limpiarTodo()
    }

    async function confirmarAsignacion() {
        return esResolucion.value ? resol.confirmarAsignacion() : docu.confirmarAsignacion()
    }

    async function aplicarEnGrupos(idResolucion, idsDetalle = null) {
        return esResolucion.value
            ? resol.aplicarEnGrupos(idResolucion, idsDetalle)
            : docu.aplicarEnGrupos(idResolucion, idsDetalle)
    }

    return {
        tipo,
        setTipo,
        bloqueadoCambioTipo,

        origenActivo,
        origenBloqueado,
        materiasMarcadas,
        guardando,
        errorGuardado,

        seleccionarOrigen,
        limpiarOrigen,
        toggleMateria,
        actualizarTipoIngreso,
        quitarMateria,
        limpiarTodo,
        confirmarAsignacion,
        aplicarEnGrupos,
    }
}