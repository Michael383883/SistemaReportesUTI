import { ref } from 'vue'
import { kardexService } from '../services/kardexService'

// Estado compartido (a nivel de módulo): el menú lateral y la página
// leen el mismo valor, sin repetir la petición en cada componente.
const habilitado = ref(false)
const cargado = ref(false)
const cargando = ref(false)

export function useKardexPermiso() {
    /**
     * @param {boolean} forzar  true → vuelve a preguntar al backend aunque ya esté cargado
     */
    const cargar = async (forzar = false) => {
        if (cargando.value) return
        if (cargado.value && !forzar) return

        cargando.value = true
        try {
            const { habilitado: h } = await kardexService.estado()
            habilitado.value = !!h
        } catch {
            habilitado.value = false
        } finally {
            cargado.value = true
            cargando.value = false
        }
    }

    // Se llama cuando el backend responde 403 MODULE_DISABLED
    const marcarDeshabilitado = () => {
        habilitado.value = false
        cargado.value = true
    }

    // Para usar al cerrar sesión / cambiar de usuario
    const reset = () => {
        habilitado.value = false
        cargado.value = false
    }

    return { habilitado, cargado, cargando, cargar, marcarDeshabilitado, reset }
}
