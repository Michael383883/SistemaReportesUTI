// src/modules/secretaria/services/docentesService.js

const API_BASE = import.meta.env.VITE_API_URL || ''

async function apiFetch(path, options = {}) {
    try {
        const url = `${API_BASE}${path}`

        const res = await fetch(url, {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                ...options.headers,
            },
            ...options,
        })

        if (!res.ok) {
            const error = await res.json().catch(() => ({ message: res.statusText }))
            throw new Error(error.message || `Error ${res.status}`)
        }

        const response = await res.json()
        return response.data || response
    } catch (error) {
        console.error('API Error:', error)
        throw error
    }
}

export const docentesService = {
    /**
     * Obtener todos los docentes con sus datos básicos
     * GET /api/secretaria/docentes
     */
    async getAll(filtros = {}) {
        const params = new URLSearchParams()
        if (filtros.busqueda) params.append('busqueda', filtros.busqueda)
        if (filtros.unidad) params.append('unidad', filtros.unidad)

        const query = params.toString()
        return apiFetch(`/api/secretaria/docentes${query ? '?' + query : ''}`)
    },

    /**
     * Obtener un docente por código
     * GET /api/secretaria/docentes/{codigo}
     */
    async getById(codigo) {
        return apiFetch(`/api/secretaria/docentes/${codigo}`)
    },

    /**
     * Obtener horario/materias de un docente
     * GET /api/horarios/docentes/{codigo_docente}
     *
     * Acepta anio/periodo opcionales para pedir una gestión específica.
     * Si no se envían, el backend calcula la gestión actual automáticamente.
     */
    async getHorario(codigo, filtros = {}) {
        const params = new URLSearchParams()
        if (filtros.anio) params.append('anio', filtros.anio)
        if (filtros.periodo) params.append('periodo', filtros.periodo)

        const query = params.toString()
        const url = `${API_BASE}/api/horarios/docentes/${codigo}${query ? '?' + query : ''}`

        const res = await fetch(url, {
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        })

        if (!res.ok) {
            const error = await res.json().catch(() => ({ message: res.statusText }))
            throw new Error(error.message || `Error ${res.status}`)
        }

        const response = await res.json()
        const item = Array.isArray(response.data) ? response.data[0] : response.data

        const resultado = item
            ? {
                  docente: item.docente,
                  nombre_completo: item.nombre_completo,
                  carga_horaria_total: item.carga_horaria_total,
                  total_horarios: item.total_horarios,
                  materias: transformarHorarios(item.horarios || [])
              }
            : { materias: [] }

        return {
            ...resultado,
            anio: response.filtros?.anio ?? null,
            periodo: response.filtros?.periodo ?? null,
            automatico: response.filtros?.automatico ?? true,
        }
    },

    /**
     * Obtener horarios de todos los docentes
     * GET /api/horarios/docentes
     *
     * Acepta anio/periodo opcionales. Si no se envían, el backend
     * calcula la gestión actual (semestral) automáticamente y la
     * informa de vuelta en `filtros.anio` / `filtros.periodo`.
     */
    async getAllHorarios(filtros = {}) {
        const params = new URLSearchParams()
        if (filtros.anio) params.append('anio', filtros.anio)
        if (filtros.periodo) params.append('periodo', filtros.periodo)

        const query = params.toString()
        const url = `${API_BASE}/api/horarios/docentes${query ? '?' + query : ''}`

        const res = await fetch(url, {
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        })

        if (!res.ok) {
            const error = await res.json().catch(() => ({ message: res.statusText }))
            throw new Error(error.message || `Error ${res.status}`)
        }

        const response = await res.json()

        const data = Array.isArray(response.data)
            ? response.data.map(docente => ({
                  ...docente,
                  materias: transformarHorarios(docente.horarios || [])
              }))
            : []

        return {
            data,
            anio: response.filtros?.anio ?? null,
            periodo: response.filtros?.periodo ?? null,
            automatico: response.filtros?.automatico ?? true,
        }
    }
}

/**
 * Transforma los horarios del backend al formato del frontend
 */
function transformarHorarios(horarios) {
    if (!Array.isArray(horarios)) return []

    // Agrupar por SECCIÓN única (materia + grupo + tipo + carrera + nivel)
    const materiasMap = new Map()

    horarios.forEach(h => {
        // La clave debe identificar una SECCIÓN única, no solo una materia.
        // Dos secciones distintas (ej. una clase "matricial"/compartida entre
        // carreras) pueden tener el mismo código de materia, el mismo grupo y
        // el mismo tipo, pero pertenecer a carrera/nivel distintos — en ese
        // caso son entidades separadas y NO deben fusionarse, aunque su
        // horario coincida exactamente (es justamente lo que indica el campo
        // "compartido" del backend).
        //
        // Antes: `${h.materia}_${h.grupo}_${h.tipo}`
        // Esto fusionaba, por ejemplo, "CONTABILIDAD II" (ECO-H, grupo 21)
        // con "CONTABILIDAD II" (CCP-B, grupo 21) en un solo objeto materia,
        // porque coincidían en materia+grupo+tipo. Sus horarios (idénticos,
        // por ser clase compartida) se mezclaban en un mismo arreglo y en el
        // grid del horario se perdía una de las dos secciones.
        const key = `${h.materia}_${h.grupo}_${h.tipo}_${h.carrera}_${h.nivel}`

        if (!materiasMap.has(key)) {
            materiasMap.set(key, {
                id: key,
                materia: h.materia,
                nombre: h.nombre_materia,
                grupo: h.grupo,
                tipo: h.tipo,
                carrera: h.carrera,
                nivel: h.nivel,
                carga_horaria: h.carga_horaria,
                compartido: h.compartido || null,
                total_inscritos: h.total_inscritos,
                horarios: []
            })
        }

        const materia = materiasMap.get(key)
        materia.horarios.push({
            dia: h.dia,
            hora_inicio: h.horario ? h.horario.split(' - ')[0] : '',
            hora_fin: h.horario ? h.horario.split(' - ')[1] : '',
            ambiente: h.ambiente,
            carga_horaria: h.carga_horaria
        })
    })

    return Array.from(materiasMap.values())
}