import estudiantesService, { PLANES } from './estudiantesService.js'
import { docentesService } from '../../../shared/services/docentesService.js'

export const dashboardService = {

    async getKPIs(filtros = {}) {
        const [estudiantesResp, docentesResp] = await Promise.allSettled([
            estudiantesService.getInscritos({
                anio: filtros.anio || null,
                periodo: filtros.periodo || null,
            }),
            docentesService.getAllHorarios({
                anio: filtros.anio || null,
                periodo: filtros.periodo || null,
            }),
        ])

        const estudiantes = estudiantesResp.status === 'fulfilled'
            ? estudiantesResp.value
            : { data: [], total: 0 }

        // getAllHorarios ahora retorna { data, anio, periodo, automatico }
        const docentesResult = docentesResp.status === 'fulfilled'
            ? docentesResp.value
            : { data: [], anio: null, periodo: null, automatico: true }

        const docentesRaw = docentesResult.data || []

        // La gestión "real" usada por el backend (útil si el usuario no mandó nada
        // y queremos reflejar en el header qué periodo se está mostrando)
        const gestion = {
            anio: docentesResult.anio ?? estudiantes.anio ?? null,
            periodo: docentesResult.periodo ?? estudiantes.periodo ?? null,
            automatico: docentesResult.automatico ?? true,
        }

        return {
            gestion,
            estudiantes: buildEstudiantesKPIs(estudiantes),
            docentes: buildDocentesKPIs(docentesRaw),
            talleres: buildTalleresKPIs(estudiantesResp, docentesRaw),
        }
    },
}

// ─── ESTUDIANTES ────────────────────────────────────────────────────────────

function buildEstudiantesKPIs({ data = [], total = 0 }) {
    // Agrupar por taller (nom_materia)
    const porTallerMap = new Map()
    data.forEach(e => {
        const taller = e.nom_materia || 'Sin taller'
        porTallerMap.set(taller, (porTallerMap.get(taller) ?? 0) + 1)
    })
    const porTaller = [...porTallerMap.entries()]
        .sort((a, b) => b[1] - a[1])
        .map(([taller, cantidad]) => ({ taller, cantidad }))

    // Agrupar por plan → nivel
    const NIVEL_COLORS = ['#6366f1', '#8b5cf6', '#a78bfa', '#c4b5fd', '#ddd6fe']
    const porPlanMap = new Map()
    data.forEach(e => {
        const nombre = PLANES[e.plan] ?? e.plan ?? 'Sin plan'
        porPlanMap.set(nombre, (porPlanMap.get(nombre) ?? 0) + 1)
    })
    const porNivel = [...porPlanMap.entries()]
        .sort((a, b) => b[1] - a[1])
        .map(([nivel, cantidad], i) => ({
            nivel,
            cantidad,
            color: NIVEL_COLORS[i % NIVEL_COLORS.length],
        }))

    // Últimos 5 inscritos (los últimos del array, sin orden garantizado por fecha)
    const recientes = [...data]
        .reverse()
        .slice(0, 5)
        .map(e => ({
            codigo: e.codigo,
            nombre: e.nom_estudiante,
            taller: e.nom_materia,
            nivel: PLANES[e.plan] ?? e.plan ?? '—',
            fecha: e.fecha ?? null,
            celular: e.celular ?? null,
            correo: e.correo ?? null,
        }))

    return {
        total: total || data.length,
        inscritos: total || data.length,
        porTaller,
        porNivel,
        recientes,
    }
}

// ─── DOCENTES ────────────────────────────────────────────────────────────────

function buildDocentesKPIs(docentes = []) {
    if (!Array.isArray(docentes)) docentes = []

    const activos = docentes.filter(d => (d.carga_horaria_total ?? 0) > 0)
    const sinCarga = docentes.filter(d => (d.carga_horaria_total ?? 0) === 0)
    const totalHoras = activos.reduce((sum, d) => sum + (d.carga_horaria_total ?? 0), 0)
    const horasPromedio = activos.length > 0
        ? Math.round((totalHoras / activos.length) * 10) / 10
        : 0

    // Un docente → un taller (primera materia del primer horario)
    const porTaller = docentes
        .filter(d => d.materias?.length > 0)
        .map(d => ({
            taller: d.materias[0].nombre ?? d.materias[0].materia,
            docente: d.nombre_completo ?? d.docente ?? '—',
            horas: d.carga_horaria_total ?? 0,
        }))
        .sort((a, b) => b.horas - a.horas)
        .slice(0, 5)

    // Distribución de carga horaria
    const rangos = [
        { rango: '0h', min: 0, max: 0, color: '#ef4444' },
        { rango: '1-10h', min: 1, max: 10, color: '#f59e0b' },
        { rango: '11-20h', min: 11, max: 20, color: '#10b981' },
        { rango: '21-30h', min: 21, max: 30, color: '#0d9488' },
        { rango: '31h+', min: 31, max: Infinity, color: '#6366f1' },
    ]
    const cargaHoraria = rangos.map(r => ({
        rango: r.rango,
        color: r.color,
        cantidad: docentes.filter(d => {
            const h = d.carga_horaria_total ?? 0
            return h >= r.min && h <= r.max
        }).length,
    })).filter(r => r.cantidad > 0)

    // Últimos 3 registros
    const recientes = docentes.slice(0, 3).map(d => ({
        codigo: d.docente ?? '—',
        nombre: d.nombre_completo ?? '—',
        grado: d.grado ?? '—',
        taller: d.materias?.[0]?.nombre ?? d.materias?.[0]?.materia ?? '—',
        horas: d.carga_horaria_total ?? 0,
    }))

    return {
        total: docentes.length,
        activos: activos.length,
        sinCarga: sinCarga.length,
        horasPromedio,
        porTaller,
        cargaHoraria,
        recientes,
    }
}

// ─── TALLERES ────────────────────────────────────────────────────────────────

function buildTalleresKPIs(estudiantesResp, docentes = []) {
    const data = estudiantesResp.status === 'fulfilled'
        ? (estudiantesResp.value.data ?? [])
        : []

    // Talleres únicos de los estudiantes
    const talleresSet = new Set(data.map(e => e.materia).filter(Boolean))
    const total = talleresSet.size
    const activos = total  // todos los que tienen inscritos se consideran activos

    // Por plan de estudios
    const porPlanMap = new Map()
    data.forEach(e => {
        if (!e.plan) return
        const abrev = e.plan  // ej. '109401' o 'ADM'
        porPlanMap.set(abrev, (porPlanMap.get(abrev) ?? 0) + 1)
    })
    const porPlan = [...porPlanMap.entries()]
        .sort((a, b) => b[1] - a[1])
        .map(([plan, cantidad]) => ({ plan, cantidad }))

    return { total, activos, porPlan }
}