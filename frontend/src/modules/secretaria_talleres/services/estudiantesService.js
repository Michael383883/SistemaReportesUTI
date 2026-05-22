// services/estudiantesService.js
const API_BASE = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

// ─── Catálogo de planes ───────────────────────────────────────────────────────
export const PLANES = {
    '109401': 'Lic. en Administración de Empresas',
    '125091': 'Licenciatura en Ingeniería Comercial',
    '089801': 'Licenciatura en Contaduría Pública',
    '126091': 'Licenciatura en Ingeniería Financiera',
    '059801': 'Licenciatura en Economía',
}

// ─── Helper interno ───────────────────────────────────────────────────────────
async function apiFetch(path, params = {}) {
    const url = new URL(`${API_BASE}${path}`, window.location.origin)

    Object.entries(params).forEach(([key, val]) => {
        if (val !== null && val !== undefined && val !== '') {
            url.searchParams.append(key, val)
        }
    })

    const res = await fetch(url.toString(), {
        headers: {
            Accept: 'application/json',
        },
    })

    if (!res.ok) {
        const body = await res.json().catch(() => ({}))

        throw new Error(
            body.message || `Error ${res.status} en ${path}`
        )
    }

    return res.json()
}

// ─── Servicio principal ──────────────────────────────────────────────────────
export const estudiantesService = {
    /**
     * Lista de estudiantes inscritos en materias de TALLER.
     */
    async getEstudiantesEnTalleres({
        anio,
        periodo,
        plan,
        materia,
        grupo,
    } = {}) {
        return apiFetch('/api/talleres/estudiantes', {
            anio,
            periodo,
            plan,
            materia,
            grupo,
        })
    },

    /**
     * Materias disponibles.
     */
    async getMateriasDisponibles({
        anio,
        periodo,
        plan,
    } = {}) {
        return apiFetch('/api/talleres/materias', {
            anio,
            periodo,
            plan,
        })
    },

    /**
     * Grupos disponibles.
     */
    async getGrupos({
        anio,
        periodo,
        plan,
        materia,
    } = {}) {
        return apiFetch('/api/talleres/grupos', {
            anio,
            periodo,
            plan,
            materia,
        })
    },

    /**
     * Contacto de estudiante.
     */
    async getContactoEstudiante(codigoEstudiante) {
        return apiFetch(
            `/api/estudiantes/${encodeURIComponent(codigoEstudiante)}/contacto`
        )
    },
}

// ─── Export principal ────────────────────────────────────────────────────────
export default estudiantesService