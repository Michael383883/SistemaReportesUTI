// src/modules/secretaria/services/docentesService.js

const API_BASE = 'http://localhost:8000/api'

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
        return apiFetch(`/secretaria/docentes${query ? '?' + query : ''}`)
    },

    /**
     * Obtener un docente por código
     * GET /api/secretaria/docentes/{codigo}
     */
    async getById(codigo) {
        return apiFetch(`/secretaria/docentes/${codigo}`)
    },

    /**
     * Obtener horario/materias de un docente
     * GET /api/horarios/docentes/{codigo_docente}
     */
    async getHorario(codigo) {
        const response = await apiFetch(`/horarios/docentes/${codigo}`)

        // Transformar la respuesta al formato que espera el frontend
        if (response && response.horarios) {
            return {
                docente: response.docente,
                nombre_completo: response.nombre_completo,
                carga_horaria_total: response.carga_horaria_total,
                total_horarios: response.total_horarios,
                materias: transformarHorarios(response.horarios)
            }
        }

        // Si la respuesta ya es un array o tiene otra estructura
        if (Array.isArray(response)) {
            return {
                materias: transformarHorarios(response)
            }
        }

        return response
    },

    /**
     * Obtener horarios de todos los docentes
     * GET /api/horarios/docentes
     */
    async getAllHorarios() {
        const response = await apiFetch(`/horarios/docentes`)

        // Si es un array, transformar cada docente
        if (Array.isArray(response)) {
            return response.map(docente => ({
                ...docente,
                materias: transformarHorarios(docente.horarios || [])
            }))
        }

        return response
    }
}

/**
 * Transforma los horarios del backend al formato del frontend
 */
function transformarHorarios(horarios) {
    if (!Array.isArray(horarios)) return []

    // Agrupar por materia única (materia + grupo + tipo)
    const materiasMap = new Map()

    horarios.forEach(h => {
        const key = `${h.materia}_${h.grupo}_${h.tipo}`

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