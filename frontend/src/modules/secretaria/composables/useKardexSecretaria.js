import { ref, computed } from 'vue'
import { kardexService } from '../services/kardexService'
import { useKardexPermiso } from './useKardexPermiso'
import { generarPDF } from './useKardexSecretariaPDF'

export function useKardexSecretaria() {
    const { marcarDeshabilitado } = useKardexPermiso()

    const docentes = ref([])
    const loadingDocentes = ref(false)
    const errorDocentes = ref(null)

    const searchQuery = ref('')
    const selectedDocente = ref(null)

    const reporte = ref(null)
    const loadingReporte = ref(false)
    const errorReporte = ref(null)

    // Si el admin quitó el permiso mientras la secretaria tenía la vista abierta
    const manejarError = (err, porDefecto) => {
        if (err?.response?.status === 403 && err.response?.data?.code === 'MODULE_DISABLED') {
            marcarDeshabilitado()
            return 'Este módulo fue deshabilitado por el administrador.'
        }
        return err?.response?.data?.message ?? porDefecto
    }

    const fetchDocentes = async () => {
        loadingDocentes.value = true
        errorDocentes.value = null
        try {
            docentes.value = await kardexService.docentes()
        } catch (err) {
            errorDocentes.value = manejarError(err, 'Error al cargar docentes')
        } finally {
            loadingDocentes.value = false
        }
    }

    // Relevancia: apellido empieza > nombre empieza > apellido contiene > nombre contiene > código
    const relevancia = (d, q) => {
        const ap = d.apellidos.toLowerCase()
        const no = d.nombres.toLowerCase()
        if (ap.startsWith(q)) return 0
        if (no.startsWith(q)) return 1
        if (ap.includes(q)) return 2
        if (no.includes(q)) return 3
        if (String(d.codigo).includes(q)) return 4
        return 5
    }

    const filteredDocentes = computed(() => {
        const q = searchQuery.value.trim().toLowerCase()
        if (!q) return docentes.value

        return docentes.value
            .filter((d) => {
                const completo = `${d.apellidos} ${d.nombres}`.toLowerCase()
                return (
                    completo.includes(q) ||
                    `${d.nombres} ${d.apellidos}`.toLowerCase().includes(q) ||
                    String(d.codigo).includes(q)
                )
            })
            .sort((a, b) => relevancia(a, q) - relevancia(b, q))
    })

    const cargarReporte = async (codigo) => {
        loadingReporte.value = true
        errorReporte.value = null
        reporte.value = null
        try {
            reporte.value = await kardexService.reporte(codigo)
        } catch (err) {
            errorReporte.value = manejarError(err, 'Error al generar el kardex')
        } finally {
            loadingReporte.value = false
        }
    }

    // Seleccionar un docente = ver su kardex de inmediato (sin botón "Generar")
    const seleccionarDocente = async (docente) => {
        selectedDocente.value = docente
        searchQuery.value = `${docente.apellidos} ${docente.nombres}`
        await cargarReporte(docente.codigo)
    }

    const limpiar = () => {
        selectedDocente.value = null
        searchQuery.value = ''
        reporte.value = null
        errorReporte.value = null
    }

    // ÚNICA acción disponible: imprimir (sin resolución ni designación)
    const imprimir = () => {
        if (!reporte.value) return
        generarPDF(reporte.value, { action: 'print' })
    }

    return {
        docentes,
        loadingDocentes,
        errorDocentes,
        searchQuery,
        filteredDocentes,
        selectedDocente,
        reporte,
        loadingReporte,
        errorReporte,
        fetchDocentes,
        seleccionarDocente,
        limpiar,
        imprimir,
    }
}