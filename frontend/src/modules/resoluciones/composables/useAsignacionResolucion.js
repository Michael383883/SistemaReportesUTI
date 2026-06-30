import { ref, computed } from 'vue'
import { useResolucion } from './useResolucion'

/**
 * Orquesta el flujo de "Asignar resolución a materias":
 *  1. Se elige una resolución (queda fija una vez hay materias marcadas).
 *  2. Se marcan materias del reporte de uno o varios docentes (click en check).
 *  3. Se guardan todas como detalles de esa resolución (guardarDetalles bulk).
 *  4. Se aplica en grupos SOLO lo recién guardado (usando los ids_detalle
 *     devueltos por guardarDetalles), no todo el historial de la resolución.
 *
 * Reutiliza useResolucion.js para el guardado real contra el backend.
 */
export function useAsignacionResolucion() {
    const {
        loading: guardando,
        error: errorGuardado,
        guardarDetalles,
        aplicarEnGrupos: aplicarEnGruposBase,
    } = useResolucion()

    // ─── Resolución activa ──────────────────────────────────────────
    const resolucionActiva = ref(null) // { idResolucion, nroResolucion, anio, periodo, ... }

    // ─── Materias marcadas (acumula entre distintos docentes) ───────
    // Cada item: { key, docente: {...}, cod_docente, cod_plan, cod_materia, grupo, tipo, gestion, materiaLabel }
    const materiasMarcadas = ref([])

    const resolucionBloqueada = computed(() => materiasMarcadas.value.length > 0)

    function generarKey(docenteCod, materia) {
        return `${docenteCod}__${materia.plan}__${materia.materia}__${materia.grp}__${materia.gestion}`
    }

    // El reporte trae "materia" como "1301134 CALCULO I" (código + nombre).
    // El backend solo acepta el código (máx. 10 caracteres), así que extraemos
    // únicamente la parte numérica/alfanumérica antes del primer espacio.
    function extraerCodMateria(materiaRaw) {
        if (!materiaRaw) return ''
        return String(materiaRaw).trim().split(/\s+/)[0]
    }

    function seleccionarResolucion(resolucion) {
        if (resolucionBloqueada.value) return false
        resolucionActiva.value = resolucion
        return true
    }

    function limpiarResolucion() {
        if (resolucionBloqueada.value) return false
        resolucionActiva.value = null
        return true
    }

    function estaMarcada(docenteCod, materia) {
        const key = generarKey(docenteCod, materia)
        return materiasMarcadas.value.some(m => m.key === key)
    }

    function toggleMateria(docente, materia) {
        if (!resolucionActiva.value) return

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
            cod_materia: extraerCodMateria(materia.materia),
            grupo: materia.grp || null,
            tipo: 'N',
            tipo_ingreso: materia.tipo_ingreso || null,   // ← nuevo
            gestion: materia.gestion,
            observacion: materia.compartido ? 'COMPARTIDO' : null,
            materiaLabel: materia.materia,
        })
    }
    // Sincroniza el tipo_ingreso de una materia YA marcada cuando el usuario
    // cambia el <select> en la tabla después de haber hecho click en el check.
    // Si la materia todavía no está marcada, no hace nada (toggleMateria ya
    // la copiará con el valor correcto cuando se marque).
    function actualizarTipoIngreso(docente, materia) {
        const docenteCod = docente.cod_docente ?? docente.CODIGO ?? docente.codigo
        const key = generarKey(docenteCod, materia)
        const item = materiasMarcadas.value.find(m => m.key === key)
        if (item) {
            item.tipo_ingreso = materia.tipo_ingreso || null
        }
    }
    function quitarMateria(key) {
        materiasMarcadas.value = materiasMarcadas.value.filter(m => m.key !== key)
    }

    function limpiarTodo() {
        materiasMarcadas.value = []
        resolucionActiva.value = null
    }

    // Guarda los ID_DETALLE devueltos por el último guardarDetalles(),
    // para que aplicarEnGrupos() sepa filtrar solo por lo recién insertado.
    const ultimosIdsDetalle = ref([])

    async function confirmarAsignacion() {
        if (!resolucionActiva.value) throw new Error('Selecciona una resolución antes de continuar.')
        if (materiasMarcadas.value.length === 0) throw new Error('Marca al menos una materia para asignar.')

        const idResolucion = resolucionActiva.value.idResolucion ?? resolucionActiva.value.id_resolucion

        const detalles = materiasMarcadas.value.map(m => ({
            cod_docente: m.cod_docente,
            cod_plan: m.cod_plan,
            cod_materia: m.cod_materia,
            grupo: m.grupo,
            tipo: m.tipo,
            tipo_ingreso: m.tipo_ingreso,   // ← nuevo
            observacion: m.observacion,
        }))

        const resultado = await guardarDetalles(idResolucion, detalles)

        // El backend devuelve "ids_detalle": los IDs recién insertados.
        // Los guardamos para que aplicarEnGrupos() filtre solo por ellos.
        ultimosIdsDetalle.value = resultado?.ids_detalle ?? []

        return { idResolucion, resultado, idsDetalle: ultimosIdsDetalle.value }
    }

    // Wrapper: siempre pasa los IDs de la última asignación, así el
    // componente que llama no tiene que acordarse de hacerlo manualmente.
    async function aplicarEnGrupos(idResolucion, idsDetalle = null) {
        const ids = idsDetalle ?? ultimosIdsDetalle.value
        return aplicarEnGruposBase(idResolucion, ids)
    }

    return {
        // estado
        resolucionActiva,
        materiasMarcadas,
        resolucionBloqueada,
        guardando,
        errorGuardado,

        // acciones
        seleccionarResolucion,
        limpiarResolucion,
        estaMarcada,
        toggleMateria,
        actualizarTipoIngreso,
        quitarMateria,
        limpiarTodo,
        confirmarAsignacion,
        aplicarEnGrupos,
    }
}