import { ref, computed } from 'vue'
import axios from 'axios'

const API = `${import.meta.env.VITE_API_URL}/api/reportes/prelacion-materias`

export const CARRERAS = [
    { sigla: 'CP', nombre: 'Contaduría Pública' },
    { sigla: 'ECO', nombre: 'Economía' },
    { sigla: 'ADM', nombre: 'Administración' },
    { sigla: 'IF', nombre: 'Ing. Financiera' },
    { sigla: 'IC', nombre: 'Ing. Comercial' },
]
export const PERIODOS = [1, 2, 3, 4]

const ROMANOS = { 1: 'I', 2: 'II', 3: 'III', 4: 'IV' }
const XLSX_MIME = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'

const filtrosIniciales = () => ({
    anio: new Date().getFullYear(),
    periodo: 1,
    materia: '',
    carreras: [],               // vacío = todas
    alcance: 'docente',         // 'docente' | 'materia'
    incluirSinDocumento: true,
    mostrarReferencias: false,
    soloListado: true,
})

const headers = () => {
    const token = localStorage.getItem('token')
    return token ? { Authorization: `Bearer ${token}` } : {}
}

// Si se pidió un blob y el backend respondió JSON de error, hay que leerlo como texto.
async function mensajeDeError(e, porDefecto) {
    const d = e?.response?.data
    try {
        if (d instanceof Blob) {
            const j = JSON.parse(await d.text())
            return j.error || j.message || porDefecto
        }
    } catch (_) { /* usa el mensaje por defecto */ }
    return d?.error || d?.message || porDefecto
}

export function useReportePrelacionMateria() {
    const filtros = ref(filtrosIniciales())

    const loading = ref(false)
    const descargando = ref(false)
    const error = ref(null)
    const data = ref([])
    const totalMaterias = ref(0)
    const totalFilas = ref(0)
    const filasSinDocumento = ref(0)
    const materiasFueraDelListado = ref([])
    const consultado = ref({ anio: null, periodo: null })

    let controladorActual = null

    // Params enviados al backend. Papel/orientación/ajuste: formato compacto para hoja oficio.
    const construirParams = () => {
        const f = filtros.value
        return {
            anio: f.anio || undefined,
            periodo: f.periodo || undefined,
            materia: f.materia?.trim() || undefined,
            carrera: f.carreras.length ? f.carreras.join(',') : undefined,
            alcance: f.alcance,
            incluir_sin_documento: +f.incluirSinDocumento,
            mostrar_referencias: +f.mostrarReferencias,
            solo_listado: +f.soloListado,
            papel: 'oficio',
            orientacion: 'horizontal',
            ajustar_ancho: 1,
        }
    }

    function limpiarResultado() {
        data.value = []
        totalMaterias.value = 0
        totalFilas.value = 0
        filasSinDocumento.value = 0
        materiasFueraDelListado.value = []
    }

    async function previsualizar() {
        controladorActual?.abort()
        const controlador = (controladorActual = new AbortController())
        loading.value = true
        error.value = null

        try {
            const { data: r } = await axios.get(`${API}/preview`, {
                params: construirParams(),
                headers: headers(),
                signal: controlador.signal,
            })

            if (!r.ok) {
                error.value = r.error || 'No se pudo obtener la vista previa'
                return limpiarResultado()
            }

            data.value = r.data || []
            totalMaterias.value = r.total_materias ?? 0
            totalFilas.value = r.total_filas ?? data.value.length
            filasSinDocumento.value = r.filas_sin_documento ?? 0
            materiasFueraDelListado.value = r.materias_fuera_del_listado || []
            consultado.value = { anio: r.anio, periodo: r.periodo }
        } catch (e) {
            if (axios.isCancel(e) || e?.code === 'ERR_CANCELED') return
            error.value = await mensajeDeError(e, 'No se pudo obtener la vista previa')
            limpiarResultado()
        } finally {
            if (controladorActual === controlador) {
                loading.value = false
                controladorActual = null
            }
        }
    }

    async function descargarExcel() {
        descargando.value = true
        try {
            const { data: blobData } = await axios.get(`${API}/excel`, {
                params: construirParams(),
                headers: headers(),
                responseType: 'blob',
            })
            const url = URL.createObjectURL(new Blob([blobData], { type: XLSX_MIME }))
            const link = Object.assign(document.createElement('a'), {
                href: url,
                download: `ORDEN_PRELACION_${filtros.value.periodo}-${filtros.value.anio}.xlsx`,
            })
            document.body.appendChild(link)
            link.click()
            link.remove()
            URL.revokeObjectURL(url)
        } catch (e) {
            const mensaje = await mensajeDeError(e, 'No se pudo descargar el Excel')
            error.value = mensaje
            throw new Error(mensaje)
        } finally {
            descargando.value = false
        }
    }

    const alternarCarrera = (sigla) => {
        const { carreras } = filtros.value
        filtros.value.carreras = carreras.includes(sigla)
            ? carreras.filter(c => c !== sigla)
            : [...carreras, sigla]
    }

    const restablecerFiltros = () => { filtros.value = filtrosIniciales() }

    function reset() {
        controladorActual?.abort()
        error.value = null
        loading.value = false
        limpiarResultado()
        restablecerFiltros()
    }

    const filasConDocumento = computed(() => totalFilas.value - filasSinDocumento.value)
    const hayDatos = computed(() => data.value.length > 0)
    const etiquetaPeriodo = computed(() => {
        const { anio, periodo } = consultado.value
        return anio ? `${ROMANOS[periodo] || periodo}-${anio}` : ''
    })

    return {
        filtros, loading, descargando, error, data,
        totalMaterias, totalFilas, filasSinDocumento, filasConDocumento,
        materiasFueraDelListado, consultado, etiquetaPeriodo, hayDatos,
        previsualizar, descargarExcel, alternarCarrera, restablecerFiltros, reset,
    }
}