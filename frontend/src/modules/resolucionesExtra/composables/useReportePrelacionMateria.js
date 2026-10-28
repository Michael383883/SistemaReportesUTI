import { ref, computed } from 'vue'
import axios from 'axios'

const API_BASE = import.meta.env.VITE_API_URL

// Siglas de carrera que devuelve el backend (PLANES en ReportePrelacionMateriaController)
export const CARRERAS = [
    { sigla: 'CP', nombre: 'Contaduría Pública' },
    { sigla: 'ECO', nombre: 'Economía' },
    { sigla: 'ADM', nombre: 'Administración' },
    { sigla: 'IF', nombre: 'Ing. Financiera' },
    { sigla: 'IC', nombre: 'Ing. Comercial' },
]

export const PERIODOS = [1, 2, 3, 4]

function authHeaders(extra = {}) {
    const token = localStorage.getItem('token')
    return {
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
        ...extra,
    }
}

// Cuando se pide un blob y el backend responde un error JSON, hay que leer el blob como texto.
async function mensajeDeError(e, porDefecto) {
    const data = e?.response?.data
    try {
        if (data instanceof Blob) {
            const texto = await data.text()
            const json = JSON.parse(texto)
            return json.error || json.message || porDefecto
        }
    } catch (_) { /* se ignora y se usa el mensaje por defecto */ }
    return data?.error || data?.message || porDefecto
}

export function useReportePrelacionMateria() {
    // ── Filtros (se envían tal cual al backend) ──
    const filtros = ref({
        anio: new Date().getFullYear(),
        periodo: 1,
        materia: '',
        carreras: [],              // ['CP', 'ECO', ...] (vacío = todas)
        alcance: 'docente',        // 'docente' | 'materia'
        incluirSinDocumento: true,
        mostrarReferencias: false,
        soloListado: true,
    })

    // ── Resultado ──
    const loading = ref(false)
    const descargando = ref(false)
    const error = ref(null)
    const data = ref([])
    const totalMaterias = ref(0)
    const totalFilas = ref(0)
    const filasSinDocumento = ref(0)
    const materiasFueraDelListado = ref([])
    const consultado = ref({ anio: null, periodo: null })

    let controladorActual = null // para cancelar consultas viejas si el usuario cambia filtros rápido

    function construirParams() {
        const f = filtros.value
        const materia = (f.materia || '').trim()
        return {
            anio: f.anio || undefined,
            periodo: f.periodo || undefined,
            materia: materia || undefined,
            carrera: f.carreras.length ? f.carreras.join(',') : undefined,
            alcance: f.alcance,
            incluir_sin_documento: f.incluirSinDocumento ? 1 : 0,
            mostrar_referencias: f.mostrarReferencias ? 1 : 0,
            solo_listado: f.soloListado ? 1 : 0,
        }
    }

    // GET /api/reportes/prelacion-materias/preview
    async function previsualizar() {
        if (controladorActual) controladorActual.abort()
        const controlador = new AbortController()
        controladorActual = controlador

        loading.value = true
        error.value = null

        try {
            const { data: resp } = await axios.get(
                `${API_BASE}/api/reportes/prelacion-materias/preview`,
                {
                    params: construirParams(),
                    headers: authHeaders(),
                    signal: controlador.signal,
                }
            )

            if (!resp.ok) {
                error.value = resp.error || 'No se pudo obtener la vista previa'
                limpiarResultado()
                return
            }

            data.value = resp.data || []
            totalMaterias.value = resp.total_materias ?? 0
            totalFilas.value = resp.total_filas ?? data.value.length
            filasSinDocumento.value = resp.filas_sin_documento ?? 0
            materiasFueraDelListado.value = resp.materias_fuera_del_listado || []
            consultado.value = { anio: resp.anio, periodo: resp.periodo }
        } catch (e) {
            if (axios.isCancel(e) || e?.code === 'ERR_CANCELED') return // consulta reemplazada por otra
            error.value = e?.response?.data?.error || e?.response?.data?.message || 'No se pudo obtener la vista previa'
            limpiarResultado()
        } finally {
            if (controladorActual === controlador) {
                loading.value = false
                controladorActual = null
            }
        }
    }

    // GET /api/reportes/prelacion-materias/excel (con token, vía blob)
    async function descargarExcel() {
        descargando.value = true
        try {
            const response = await axios.get(
                `${API_BASE}/api/reportes/prelacion-materias/excel`,
                {
                    params: construirParams(),
                    headers: authHeaders(),
                    responseType: 'blob',
                }
            )
            const blob = new Blob([response.data], {
                type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            })
            const nombre = `ORDEN_PRELACION_${filtros.value.periodo}-${filtros.value.anio}.xlsx`
            const url = window.URL.createObjectURL(blob)
            const link = document.createElement('a')
            link.href = url
            link.download = nombre
            document.body.appendChild(link)
            link.click()
            link.remove()
            window.URL.revokeObjectURL(url)
        } catch (e) {
            const mensaje = await mensajeDeError(e, 'No se pudo descargar el Excel')
            error.value = mensaje
            throw new Error(mensaje)
        } finally {
            descargando.value = false
        }
    }

    function limpiarResultado() {
        data.value = []
        totalMaterias.value = 0
        totalFilas.value = 0
        filasSinDocumento.value = 0
        materiasFueraDelListado.value = []
    }

    function alternarCarrera(sigla) {
        const lista = filtros.value.carreras
        filtros.value.carreras = lista.includes(sigla)
            ? lista.filter(c => c !== sigla)
            : [...lista, sigla]
    }

    function restablecerFiltros() {
        filtros.value = {
            anio: new Date().getFullYear(),
            periodo: 1,
            materia: '',
            carreras: [],
            alcance: 'docente',
            incluirSinDocumento: true,
            mostrarReferencias: false,
            soloListado: true,
        }
    }

    function reset() {
        if (controladorActual) controladorActual.abort()
        error.value = null
        loading.value = false
        limpiarResultado()
        restablecerFiltros()
    }

    // ── Derivados para la franja de resumen ──
    const filasConDocumento = computed(() => totalFilas.value - filasSinDocumento.value)

    const hayDatos = computed(() => data.value.length > 0)

    // Etiqueta tipo "I-2026" (igual que el título del Excel)
    const etiquetaPeriodo = computed(() => {
        const romano = { 1: 'I', 2: 'II', 3: 'III', 4: 'IV' }[consultado.value.periodo]
        return consultado.value.anio ? `${romano || consultado.value.periodo}-${consultado.value.anio}` : ''
    })

    return {
        // estado
        filtros,
        loading,
        descargando,
        error,
        data,
        totalMaterias,
        totalFilas,
        filasSinDocumento,
        filasConDocumento,
        materiasFueraDelListado,
        consultado,
        etiquetaPeriodo,
        hayDatos,

        // acciones
        previsualizar,
        descargarExcel,
        alternarCarrera,
        restablecerFiltros,
        reset,
    }
}