const API_BASE = import.meta.env.VITE_API_URL || ''

export const PLANES = {
    '109401': 'Lic. en Administración de Empresas',
    '125091': 'Licenciatura en Ingeniería Comercial',
    '089801': 'Licenciatura en Contaduría Pública',
    '126091': 'Licenciatura en Ingeniería Financiera',
    '059801': 'Licenciatura en Economía',
}

async function apiFetch(path, params = {}) {
    const url = new URL(`${API_BASE}${path}`, window.location.origin)

    // Solo agrega a la query los params que realmente tienen valor
    // (undefined/null/'' se omiten para no mandar ?anio=&periodo=)
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

function normalizarEstudiante(row) {
    return {
        codigo: row.CODIGO_ESTUDIANTE,
        cod_estudiante: row.CODIGO_ESTUDIANTE,

        nom_estudiante: row.ESTUDIANTE,

        plan: row.PLAN,

        materia: row.MATERIA,

        nom_materia: row.MATERIA_NOMBRE,

        grupo: row.GRUPO,

        docente: row.DOCENTE,

        cod_docente: row.CODIGO_DOCENTE,

        nota_final: row.NOTA_FINAL,

        anio: row.ANIO,

        periodo: row.PERIODO,

        celular: row.CELULAR,
        correo: row.CORREO,

    }
}

export const estudiantesService = {

    async getInscritos(filtros = {}) {

        const { materia = null, plan = null, anio = null, periodo = null } = filtros

        let endpoint = '/api/talleres'

        if (materia) {
            endpoint = `/api/talleres/${materia}`
        }

        // anio/periodo solo se mandan si el usuario los eligió a mano
        // (override). Si van null, el backend calcula la gestión actual
        // automáticamente con PeriodoAcademicoService.
        const respuesta = await apiFetch(endpoint, { plan, anio, periodo })

        return {
            data: (respuesta.data ?? []).map(normalizarEstudiante),
            total: respuesta.total ?? 0,
            // Estos vienen calculados (o confirmados) por el backend en
            // cada respuesta — nunca hardcodeados en el frontend.
            anio: respuesta.anio ?? null,
            periodo: respuesta.periodo != null ? String(respuesta.periodo) : null,
            automatico: respuesta.automatico ?? true,
        }
    },

    // temporal
    async getContactoEstudiante() {
        return {
            email: null,
            celular: null,
        }
    }
}

export default estudiantesService