const API_BASE = import.meta.env.VITE_API_URL || ''

export const ANIO_ACTUAL = '2026'
export const PERIODO_ACTUAL = '1'

// Periodo solo puede ser 1 o 2
export const PERIODOS = [
    { value: '1', label: 'Periodo 1' },
    { value: '2', label: 'Periodo 2' },
]

export const PLANES = {
    '109401': 'Lic. en Administración de Empresas',
    '125091': 'Licenciatura en Ingeniería Comercial',
    '089801': 'Licenciatura en Contaduría Pública',
    '126091': 'Licenciatura en Ingeniería Financiera',
    '059801': 'Licenciatura en Economía',
}

// Igual a la sigla que devuelve el backend (CASE K.PLAN ...)
export const SIGLAS_PLAN = {
    '089801': 'CON',
    '109401': 'ADM',
    '125091': 'COM',
    '126091': 'FIN',
    '059801': 'ECO',
}

// Mismos niveles validados en el backend (M.NIVEL)
export const NIVELES = [
    'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J',
    'L', 'M', 'N', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'X',
]

async function apiFetch(path, params = {}) {
    const url = new URL(`${API_BASE}${path}`, window.location.origin)

    Object.entries(params).forEach(([key, value]) => {
        if (value !== null && value !== undefined && value !== '') {
            url.searchParams.set(key, value)
        }
    })

    const token = localStorage.getItem('token')

    const res = await fetch(url.toString(), {
        headers: {
            Accept: 'application/json',
            ...(token ? { Authorization: `Bearer ${token}` } : {}),
        },
    })

    if (!res.ok) {
        const body = await res.json().catch(() => ({}))
        throw new Error(body.message || `Error ${res.status}`)
    }

    return await res.json()
}

function normalizarInscrito(row) {
    return {
        anio: row.ANIO,
        periodo: row.PERIODO,

        plan: row.PLAN,
        siglaPlan: row.SIGLA_PLAN,
        nombrePlan: PLANES[row.PLAN] || row.PLAN,

        nivel: row.NIVEL,

        materia: row.MATERIA,
        nombreMateria: row.NOMBRE_MATERIA,

        grupo: row.GRUPO,

        codEstudiante: row.COD_ESTUDIANTE,
        estudiante: row.ESTUDIANTE,
    }
}

export const estudiantesInscritosService = {
    /**
     * Lista paginada de estudiantes inscritos.
     *
     * filtros: { anio, periodo, plan, materia, grupo, nivel, page, perPage }
     */
    async getInscritos(filtros = {}) {
        const {
            anio = ANIO_ACTUAL,
            periodo = PERIODO_ACTUAL,
            plan = null,
            materia = null,
            grupo = null,
            nivel = null,
            page = 1,
            perPage = 100,
        } = filtros

        const respuesta = await apiFetch('/api/estudiantes-inscritos', {
            anio,
            periodo,
            plan,
            materia,
            grupo,
            nivel,
            page,
            per_page: perPage,
        })

        return {
            data: (respuesta.data ?? []).map(normalizarInscrito),
            total: respuesta.total ?? 0,
            page: respuesta.page ?? page,
            perPage: respuesta.per_page ?? perPage,
            totalPages: respuesta.total_pages ?? 0,
            anio,
            periodo,
        }
    },

    /**
     * Trae TODAS las paginas y las une en un solo arreglo.
     * Util para exportar a Excel o para agrupar localmente sin
     * preocuparse por la paginacion del backend.
     * Cuidado: puede ser una respuesta grande si hay muchos inscritos.
     */
    async getInscritosCompleto(filtros = {}) {
        const perPage = 500
        let page = 1
        let totalPages = 1
        let acumulado = []
        let total = 0

        do {
            const resp = await this.getInscritos({ ...filtros, page, perPage })
            acumulado = acumulado.concat(resp.data)
            totalPages = resp.totalPages || 1
            total = resp.total
            page += 1
        } while (page <= totalPages)

        return {
            data: acumulado,
            total,
            anio: filtros.anio ?? ANIO_ACTUAL,
            periodo: filtros.periodo ?? PERIODO_ACTUAL,
        }
    },
}

export default estudiantesInscritosService