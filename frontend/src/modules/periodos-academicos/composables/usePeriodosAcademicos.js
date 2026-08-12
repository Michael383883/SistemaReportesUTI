// modules/periodos-academicos/composables/usePeriodosAcademicos.js
import { ref } from 'vue'
import api from '@/shared/services/api'

// Estado a nivel de módulo => se comporta como un mini-store compartido
// entre todos los componentes que usen este composable, sin depender de Pinia.
const periodos = ref([])
const loading = ref(false)
const error = ref(null)

const ORDEN_ACADEMICO = { '1': 1, '4': 2, '2': 3, '3': 4 }

function ordenarPeriodos(lista) {
    return [...lista].sort(
        (a, b) => (ORDEN_ACADEMICO[a.periodo] ?? 99) - (ORDEN_ACADEMICO[b.periodo] ?? 99)
    )
}

export function usePeriodosAcademicos() {
    async function fetchPeriodos() {
        loading.value = true
        error.value = null
        try {
            const { data } = await api.get('/api/periodos-academicos')
            periodos.value = ordenarPeriodos(data.periodos)
        } catch (e) {
            error.value = e.response?.data?.message ?? 'No se pudieron cargar los periodos académicos.'
        } finally {
            loading.value = false
        }
    }

    async function guardarCambios(periodosEditados) {
        loading.value = true
        error.value = null
        try {
            const { data } = await api.put('/api/periodos-academicos', {
                periodos: periodosEditados.map((p) => ({
                    id: p.id,
                    inicio: p.inicio,
                    fin: p.fin,
                    nombre: p.nombre
                }))
            })
            periodos.value = ordenarPeriodos(data.periodos)
            return { success: true, message: data.message }
        } catch (e) {
            const message = e.response?.data?.message ?? 'No se pudieron guardar los cambios.'
            error.value = message
            return { success: false, message }
        } finally {
            loading.value = false
        }
    }

    async function restaurarPredeterminados() {
        loading.value = true
        error.value = null
        try {
            const { data } = await api.post('/api/periodos-academicos/restaurar')
            periodos.value = ordenarPeriodos(data.periodos)
            return { success: true, message: data.message }
        } catch (e) {
            const message = e.response?.data?.message ?? 'No se pudo restaurar los valores.'
            error.value = message
            return { success: false, message }
        } finally {
            loading.value = false
        }
    }

    function clearError() {
        error.value = null
    }

    return {
        periodos,
        loading,
        error,
        fetchPeriodos,
        guardarCambios,
        restaurarPredeterminados,
        clearError
    }
}