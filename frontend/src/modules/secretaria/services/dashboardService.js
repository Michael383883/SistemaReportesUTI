// src/modules/secretaria/services/dashboardService.js

import { docentesService } from '../../../shared/services/docentesService.js'

export const dashboardService = {

    async getKPIs() {
        // getAll() → /api/secretaria/docentes  (tiene grado_academico, nombre_docente, unidad, horas_total)
        // getAllHorarios() → /api/horarios/docentes  (tiene materias con horarios para distribución)
        const [todosResp, horariosResp] = await Promise.allSettled([
            docentesService.getAll(),
            docentesService.getAllHorarios(),
        ])

        const todos = todosResp.status === 'fulfilled' && Array.isArray(todosResp.value) ? todosResp.value : []
        const horarios = horariosResp.status === 'fulfilled' && Array.isArray(horariosResp.value) ? horariosResp.value : []

        // Normalizar el array principal (viene de /api/secretaria/docentes)
        const docentes = todos.map(d => ({
            codigo: String(d.docente ?? ''),
            nombre: d.nombre_docente ?? '—',
            grado: d.grado_academico ?? null,
            unidad: d.unidad || null,
            horas: d.horas_total ?? 0,
        }))

        // Índice de materias/horarios por código para distribucionHoraria
        const horariosMap = new Map(
            horarios.map(d => [String(d.docente ?? ''), d.materias ?? []])
        )

        return buildKPIs(docentes, horariosMap)
    },
}

// ─── BUILDER ─────────────────────────────────────────────────────────────────

function buildKPIs(docentes, horariosMap) {
    const activos = docentes.filter(d => (d.horas ?? 0) > 0)
    const sinCarga = docentes.filter(d => (d.horas ?? 0) === 0)
    const totalHoras = activos.reduce((s, d) => s + (d.horas ?? 0), 0)
    const horasPromedio = activos.length
        ? Math.round((totalHoras / activos.length) * 10) / 10
        : 0
    console.log('DOCENTES SECRETARIA', docentes.slice(0, 5))

    return {
        totalDocentes: docentes.length,
        docentesActivos: activos.length,
        docentesSinCarga: sinCarga.length,
        horasPromedio,
        porUnidad: buildPorUnidad(docentes),
        porGrado: buildPorGrado(docentes),
        cargaHoraria: buildCargaHoraria(docentes),
        docentesRecientes: buildRecientes(docentes),
        alertas: buildAlertas(docentes, sinCarga, horasPromedio),
        distribucionHoraria: buildDistribucionHoraria(docentes, horariosMap),
        topCargaHoraria: buildTopCargaHoraria(docentes),
    }
}

// ─── POR UNIDAD ──────────────────────────────────────────────────────────────

function buildPorUnidad(docentes) {
    const map = new Map()

    docentes.forEach(d => {
        const unidad = d.unidad || 'Sin unidad'
        if (!map.has(unidad)) map.set(unidad, { cantidad: 0, totalHoras: 0 })
        const entry = map.get(unidad)
        entry.cantidad++
        entry.totalHoras += d.horas ?? 0
    })

    return [...map.entries()]
        .sort((a, b) => b[1].cantidad - a[1].cantidad)
        .map(([unidad, { cantidad, totalHoras }]) => ({
            unidad,
            cantidad,
            horasPromedio: cantidad > 0
                ? Math.round((totalHoras / cantidad) * 10) / 10
                : 0,
        }))
}

// ─── POR GRADO ───────────────────────────────────────────────────────────────

const GRADO_COLORS = {
    'Licenciado': '#14b8a6',
    'Magister': '#3b82f6',
    'PhD': '#8b5cf6',
    'Ingeniero': '#f59e0b',
    'Doctor': '#ef4444',
    'EST.': '#6366f1',
}
const FALLBACK_COLORS = ['#ec4899', '#0ea5e9', '#84cc16', '#f97316', '#a855f7']

function buildPorGrado(docentes) {
    const map = new Map()

    docentes.forEach(d => {
        const grado = d.grado || 'Sin grado'
        map.set(grado, (map.get(grado) ?? 0) + 1)
    })

    return [...map.entries()]
        .sort((a, b) => b[1] - a[1])
        .map(([grado, cantidad], i) => ({
            grado,
            cantidad,
            color: GRADO_COLORS[grado] ?? FALLBACK_COLORS[i % FALLBACK_COLORS.length],
        }))
}

function buildTopCargaHoraria(docentes) {
    return [...docentes]
        .filter(d => (d.horas ?? 0) > 0)
        .sort((a, b) => (b.horas ?? 0) - (a.horas ?? 0))
        .slice(0, 10)
        .map(d => ({
            nombre: d.nombre,
            horas: d.horas,
            grado: d.grado,
        }))
}
// ─── CARGA HORARIA ───────────────────────────────────────────────────────────

const RANGOS = [
    { rango: '0h', min: 0, max: 0 },
    { rango: '1-10h', min: 1, max: 10 },
    { rango: '11-20h', min: 11, max: 20 },
    { rango: '21-30h', min: 21, max: 30 },
    { rango: '31-40h', min: 31, max: 40 },
    { rango: '40h+', min: 41, max: Infinity },
]

function buildCargaHoraria(docentes) {
    return RANGOS.map(r => ({
        rango: r.rango,
        cantidad: docentes.filter(d => {
            const h = d.horas ?? 0
            return h >= r.min && h <= r.max
        }).length,
    })).filter(r => r.cantidad > 0)
}

// ─── RECIENTES ───────────────────────────────────────────────────────────────

function buildRecientes(docentes) {
    return docentes.slice(0, 5).map(d => ({
        codigo: d.codigo ?? '—',
        nombre: d.nombre ?? '—',
        grado: d.grado ?? '—',
        unidad: d.unidad ?? '—',
        horas: d.horas ?? 0,
        fecha: d.fecha ?? null,
    }))
}

// ─── ALERTAS ─────────────────────────────────────────────────────────────────

function buildAlertas(docentes, sinCarga, horasPromedio) {
    const alertas = []
    let id = 1

    if (sinCarga.length > 0) {
        alertas.push({
            id: id++,
            tipo: 'warning',
            mensaje: `${sinCarga.length} docente${sinCarga.length > 1 ? 's' : ''} sin carga horaria asignada este período`,
            accion: '/secretaria/docentes?estado=sin-carga',
        })
    }

    const excedidos = docentes.filter(d => (d.horas ?? 0) > 40)
    if (excedidos.length > 0) {
        alertas.push({
            id: id++,
            tipo: 'warning',
            mensaje: `${excedidos.length} docente${excedidos.length > 1 ? 's' : ''} con carga horaria excedida (>40h)`,
        })
    }

    if (horasPromedio > 0) {
        alertas.push({
            id: id++,
            tipo: 'info',
            mensaje: `Promedio de horas por docente: ${horasPromedio}h`,
        })
    }

    return alertas
}

// ─── DISTRIBUCIÓN HORARIA ────────────────────────────────────────────────────

const DIAS_LABEL = { 'Lunes': 'Lun', 'Martes': 'Mar', 'Miércoles': 'Mié', 'Jueves': 'Jue', 'Viernes': 'Vie', 'Sábado': 'Sáb' }
const DIAS_ORDER = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado']

function getTurno(horaInicio) {
    if (!horaInicio) return null
    const [h] = horaInicio.split(':').map(Number)
    if (h < 12) return 'Mañana'
    if (h < 18) return 'Tarde'
    return 'Noche'
}

function buildDistribucionHoraria(docentes, horariosMap) {
    const counts = {
        Mañana: Object.fromEntries(DIAS_ORDER.map(d => [d, 0])),
        Tarde: Object.fromEntries(DIAS_ORDER.map(d => [d, 0])),
        Noche: Object.fromEntries(DIAS_ORDER.map(d => [d, 0])),
    }

    docentes.forEach(d => {
        const materias = horariosMap.get(d.codigo) ?? []
        materias.forEach(m => {
            (m.horarios ?? []).forEach(h => {
                const turno = getTurno(h.hora_inicio)
                const dia = h.dia
                if (turno && dia in counts[turno]) {
                    counts[turno][dia]++
                }
            })
        })
    })

    return {
        labels: DIAS_ORDER.map(d => DIAS_LABEL[d]),
        datasets: [
            { label: 'Mañana', data: DIAS_ORDER.map(d => counts.Mañana[d]), color: '#3b82f6' },
            { label: 'Tarde', data: DIAS_ORDER.map(d => counts.Tarde[d]), color: '#10b981' },
            { label: 'Noche', data: DIAS_ORDER.map(d => counts.Noche[d]), color: '#8b5cf6' },
        ],
    }
}