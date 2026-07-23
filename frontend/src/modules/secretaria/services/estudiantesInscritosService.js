const API_BASE = import.meta.env.VITE_API_URL || ''

export const ANIO_ACTUAL = '2026'
export const PERIODO_ACTUAL = '1'

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

export const SIGLAS_PLAN = {
    '089801': 'CON',
    '109401': 'ADM',
    '125091': 'COM',
    '126091': 'FIN',
    '059801': 'ECO',
}

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

// El backend ahora devuelve cada fila de "data" ya agrupada:
// { anio, periodo, plan, sigla_plan, nivel, materia, nombre_materia, grupo,
//   docente: { cod_docente, docente }, estudiantes: [{ cod_estudiante, estudiante }] }
function normalizarGrupo(g) {
    return {
        clave: `${g.plan}-${g.materia}-${g.grupo}-${g.nivel}`,

        anio: g.anio,
        periodo: g.periodo,

        plan: g.plan,
        siglaPlan: g.sigla_plan,
        nombrePlan: PLANES[g.plan] || g.plan,

        nivel: g.nivel,

        materia: g.materia,
        nombreMateria: g.nombre_materia,

        grupo: g.grupo,

        docente: g.docente
            ? {
                  codDocente: g.docente.cod_docente,
                  docente: g.docente.docente,
              }
            : null,

        estudiantes: (g.estudiantes ?? []).map((e) => ({
            codEstudiante: e.cod_estudiante,
            estudiante: e.estudiante,
        })),
    }
}

export const estudiantesInscritosService = {
    /**
     * Lista paginada de grupos (materia+grupo+nivel) con su docente
     * y la lista de estudiantes inscritos en ese grupo.
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
            data: (respuesta.data ?? []).map(normalizarGrupo),
            total: respuesta.total ?? 0,
            page: respuesta.page ?? page,
            perPage: respuesta.per_page ?? perPage,
            totalPages: respuesta.total_pages ?? 0,
            anio,
            periodo,
        }
    },

    /**
     * Trae TODAS las paginas y fusiona los grupos.
     * Importante: como el backend pagina por fila de estudiante (no por
     * grupo), un mismo grupo puede llegar "partido" entre dos paginas.
     * Aca se fusionan por clave (plan-materia-grupo-nivel) para que quede
     * un solo grupo con todos sus estudiantes. Util para exportar o para
     * la vista previa completa.
     */
    async getInscritosCompleto(filtros = {}) {
        const perPage = 500
        let page = 1
        let totalPages = 1
        let total = 0

        const mapaGrupos = new Map()

        do {
            const resp = await this.getInscritos({ ...filtros, page, perPage })

            for (const g of resp.data) {
                if (!mapaGrupos.has(g.clave)) {
                    mapaGrupos.set(g.clave, { ...g, estudiantes: [...g.estudiantes] })
                } else {
                    mapaGrupos.get(g.clave).estudiantes.push(...g.estudiantes)
                }
            }

            totalPages = resp.totalPages || 1
            total = resp.total
            page += 1
        } while (page <= totalPages)

        return {
            data: Array.from(mapaGrupos.values()),
            total,
            anio: filtros.anio ?? ANIO_ACTUAL,
            periodo: filtros.periodo ?? PERIODO_ACTUAL,
        }
    },
}

export default estudiantesInscritosService