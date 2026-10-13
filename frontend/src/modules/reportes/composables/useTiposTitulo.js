import { ref, computed } from 'vue'

export function useTiposTitulo() {
    const tipos = ref([])                  // tipos de título que tiene el docente
    const tiposSeleccionados = ref([])
    const loadingTipos = ref(false)

    const tieneTipos = computed(() => tipos.value.length > 0)

    async function cargarTipos(codDocente) {
        loadingTipos.value = true
        try {
            // ⚠️ AJUSTAR: usá tu cliente HTTP / endpoint real
            const res = await fetch(`/api/docentes/${codDocente}/tipos-titulo`)
            const data = await res.json()
            // Se espera un array de strings, ej: ['Licenciatura', 'Maestría', 'Doctorado']
            tipos.value = data
        } catch (e) {
            console.error('[useTiposTitulo] Error cargando tipos de título', e)
            tipos.value = []
        } finally {
            loadingTipos.value = false
        }
    }

    function limpiar() {
        tipos.value = []
        tiposSeleccionados.value = []
    }

    return { tipos, tiposSeleccionados, tieneTipos, loadingTipos, cargarTipos, limpiar }
}