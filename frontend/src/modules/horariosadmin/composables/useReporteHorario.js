import { ref, computed } from 'vue'

/**
 * @param {{docentes, loading, error, cargarTodos, cargarDocente}} dataSource
 * @param {{anio, periodo}} filtros
 */
export function useReporteHorario(dataSource, { anio, periodo }) {
    const { docentes, loading, error, cargarTodos, cargarDocente } = dataSource

    const busqueda = ref('')
    const terminoBuscado = ref('')

    const fechaActual = computed(() =>
        new Date().toLocaleString('es-BO', {
            day: '2-digit', month: '2-digit', year: 'numeric',
            hour: '2-digit', minute: '2-digit',
        })
    )

    async function verTodos() {
        terminoBuscado.value = ''
        await cargarTodos(anio.value, periodo.value)
    }

    async function buscarDocente() {
        if (!busqueda.value.trim()) {
            terminoBuscado.value = ''
            await cargarTodos(anio.value, periodo.value)
            return
        }
        const input = busqueda.value.trim()
        terminoBuscado.value = input
        if (/^\d+$/.test(input)) {
            await cargarDocente(input, anio.value, periodo.value)
        } else {
            await cargarTodos(anio.value, periodo.value)
            docentes.value = docentes.value.filter(d =>
                `${d.apellidos} ${d.nombres}`.toLowerCase().includes(input.toLowerCase())
            )
        }
    }

    /** Decide internamente si buscar o ver todos según el texto escrito */
    async function ejecutarBusqueda() {
        if (!busqueda.value.trim()) {
            await verTodos()
        } else {
            await buscarDocente()
        }
    }

    return {
        docentes, loading, error,
        busqueda, terminoBuscado, fechaActual,
        verTodos, buscarDocente, ejecutarBusqueda,
    }
}