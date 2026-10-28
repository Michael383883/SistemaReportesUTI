import { ref, computed } from 'vue'
import axios from 'axios'

const API_BASE = import.meta.env.VITE_API_URL

function authHeaders() {
    const token = localStorage.getItem('token')
    return token ? { Authorization: `Bearer ${token}` } : {}
}

/**
 * Orquesta el flujo de "Edición de modo de ingreso":
 *  1. Se busca un docente (reutiliza useDocentes + DocenteSearch).
 *  2. Se genera el reporte de materias de ese docente (reutiliza useReporte).
 *  3. Se elige un nuevo tipo_ingreso por fila — queda como "cambio pendiente"
 *     (no se guarda todavía, solo se acumula en memoria).
 *  4. Al hacer click en "Aplicar asignación de modo de ingreso", se guardan
 *     TODOS los cambios pendientes de una sola vez (bulk), actualizando
 *     tanto GRUPOS como, si existe, el detalle correspondiente en
 *     RESOLUCION_DETALLE.
 *
 * Además expone la lista de tipos de ingreso que tiene un docente
 * (cargarTipos). OPCIONAL: ReporteView.vue ya no la llama porque el botón
 * "Modalidad de Ingreso" se arma con los tipo_ingreso de las materias del
 * reporte. Si se vuelve a usar, el backend debe exponer
 * GET /api/docentes/{codigo}/tipos-ingreso devolviendo un array de strings.
 */
export function useTipoIngreso() {
    const guardando = ref(false)
    const errorGuardado = ref('')
    const resultadoGuardado = ref(null)

    // Mapa: key (docente__plan__materia__grp__gestion) -> nuevo tipo_ingreso elegido
    const cambiosPendientes = ref({})

    const cantidadCambios = computed(() => Object.keys(cambiosPendientes.value).length)
    const hayCambiosPendientes = computed(() => cantidadCambios.value > 0)

    // ── Lista de tipos de ingreso del docente (para el botón del reporte) ──
    const tiposDisponibles = ref([])
    const loadingTipos = ref(false)
    const tieneTipos = computed(() => tiposDisponibles.value.length > 0)

    async function cargarTipos(codDocente) {
        loadingTipos.value = true
        try {
            const { data } = await axios.get(
                `${API_BASE}/api/docentes/${codDocente}/tipos-ingreso`,
                { headers: { ...authHeaders() } }
            )
            // Se espera: ['EXAMEN SUFICIENCIA', 'CONCURSO', ...]
            tiposDisponibles.value = Array.isArray(data) ? data : (data?.data ?? [])
        } catch (e) {
            // 404 = el endpoint aún no existe en el backend: no es un error real,
            // se ignora en silencio. Cualquier otro error sí se registra.
            if (e.response?.status !== 404) {
                console.error('❌ cargarTipos (tipo_ingreso):', e.response?.data ?? e.message)
            }
            tiposDisponibles.value = []
        } finally {
            loadingTipos.value = false
        }
    }

    function limpiarTipos() {
        tiposDisponibles.value = []
    }

    function generarKey(docenteCod, materia) {
        return `${docenteCod}__${materia.plan}__${materia.materia}__${materia.grp}__${materia.gestion}`
    }

    function registrarCambio({ key, materia, valor }) {
        // Si el valor elegido es igual al original, se considera "sin cambio"
        // y se quita del mapa de pendientes (evita mandar cambios vacíos).
        if (valor === (materia.tipo_ingreso ?? '')) {
            const copia = { ...cambiosPendientes.value }
            delete copia[key]
            cambiosPendientes.value = copia
            return
        }

        cambiosPendientes.value = {
            ...cambiosPendientes.value,
            [key]: valor,
        }
    }

    function limpiarCambios() {
        cambiosPendientes.value = {}
        resultadoGuardado.value = null
        errorGuardado.value = ''
    }

    /**
     * Aplica todos los cambios pendientes de una sola vez.
     * itemsParaGuardar: array de items ya armados por la vista
     * (cod_docente, cod_plan, cod_materia, grupo, gestion, tipo_ingreso).
     */
    async function aplicarCambios(itemsParaGuardar) {
        if (itemsParaGuardar.length === 0) {
            throw new Error('No hay cambios pendientes para aplicar.')
        }

        guardando.value = true
        errorGuardado.value = ''

        try {
            const payload = {
                cambios: itemsParaGuardar.map(item => ({
                    cod_docente: Number(item.cod_docente),
                    cod_plan: item.cod_plan,
                    cod_materia: item.cod_materia,
                    grupo: item.grupo,
                    gestion: item.gestion,
                    tipo_ingreso: item.tipo_ingreso,
                })),
            }

            const { data } = await axios.post(
                `${API_BASE}/api/grupos/tipo-ingreso/bulk`,
                payload,
                { headers: { ...authHeaders() } }
            )

            if (!data.ok) throw new Error(data.error ?? 'Error al aplicar los cambios.')

            resultadoGuardado.value = data
            limpiarCambios()

            return data // { ok, total_grupos_actualizados, total_detalles_actualizados, filas }

        } catch (e) {
            const laravelErrors = e.response?.data?.errors ?? e.response?.data?.errores
            if (laravelErrors) {
                errorGuardado.value = Object.values(laravelErrors).flat().join(' | ')
            } else {
                errorGuardado.value = e.response?.data?.error
                    ?? e.response?.data?.message
                    ?? e.message
                    ?? 'Error al aplicar los cambios.'
            }
            console.error('❌ aplicarCambios (tipo_ingreso):', e.response?.data ?? e.message)
            throw e
        } finally {
            guardando.value = false
        }
    }

    return {
        cambiosPendientes,
        cantidadCambios,
        hayCambiosPendientes,
        guardando,
        errorGuardado,
        resultadoGuardado,

        // lista de tipos del docente
        tiposDisponibles,
        loadingTipos,
        tieneTipos,
        cargarTipos,
        limpiarTipos,

        generarKey,
        registrarCambio,
        limpiarCambios,
        aplicarCambios,
    }
}