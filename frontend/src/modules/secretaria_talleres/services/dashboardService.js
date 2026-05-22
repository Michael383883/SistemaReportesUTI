// src/modules/secretaria_talleres/services/dashboardService.js

const API_BASE = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

async function apiFetch(path) {
    try {
        const res = await fetch(`${API_BASE}${path}`, {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            }
        })
        if (!res.ok) throw new Error(`Error ${res.status}`)
        const response = await res.json()
        return response.data || response
    } catch (error) {
        console.error('API Error:', error)
        throw error
    }
}

export const dashboardService = {
    /**
     * Obtener KPIs del dashboard de secretaría de talleres
     * Cuando tengas el endpoint real:
     * return apiFetch('/secretaria-talleres/dashboard/kpis')
     */
    async getKPIs() {
        await delay(500)

        return {
            // ─── ESTUDIANTES ─────────────────────────────────────────────
            estudiantes: {
                total: 487,
                inscritos: 487,
                porTaller: [
                    { taller: 'Taller de Emprendimiento', cantidad: 98 },
                    { taller: 'Taller de Investigación', cantidad: 87 },
                    { taller: 'Taller de Comunicación', cantidad: 76 },
                    { taller: 'Taller de Liderazgo', cantidad: 65 },
                    { taller: 'Taller de Estadística', cantidad: 60 },
                    { taller: 'Taller de Finanzas', cantidad: 55 },
                    { taller: 'Otros', cantidad: 46 },
                ],
                porNivel: [
                    { nivel: 'Nivel 1', cantidad: 145, color: '#6366f1' },
                    { nivel: 'Nivel 2', cantidad: 132, color: '#8b5cf6' },
                    { nivel: 'Nivel 3', cantidad: 110, color: '#a78bfa' },
                    { nivel: 'Nivel 4', cantidad: 100, color: '#c4b5fd' },
                ],
                recientes: [
                    { codigo: '20220001', nombre: 'QUISPE MAMANI ANDREA', taller: 'Taller de Emprendimiento', nivel: 'Nivel 2', fecha: '2026-05-12' },
                    { codigo: '20220002', nombre: 'FLORES GUTIERREZ CARLOS', taller: 'Taller de Investigación', nivel: 'Nivel 1', fecha: '2026-05-11' },
                    { codigo: '20210034', nombre: 'ROJAS VEGA DANIELA', taller: 'Taller de Comunicación', nivel: 'Nivel 3', fecha: '2026-05-10' },
                    { codigo: '20230012', nombre: 'CONDORI BLANCO LUIS', taller: 'Taller de Liderazgo', nivel: 'Nivel 2', fecha: '2026-05-09' },
                    { codigo: '20220089', nombre: 'MAMANI TICONA JESSICA', taller: 'Taller de Estadística', nivel: 'Nivel 1', fecha: '2026-05-08' },
                ]
            },

            // ─── DOCENTES ─────────────────────────────────────────────────
            docentes: {
                total: 18,
                activos: 16,
                sinCarga: 2,
                horasPromedio: 18.5,
                porTaller: [
                    { taller: 'Taller de Emprendimiento', docente: 'MAMANI COCA WALTER', horas: 16 },
                    { taller: 'Taller de Investigación', docente: 'GARCIA PEREZ ROSA', horas: 20 },
                    { taller: 'Taller de Comunicación', docente: 'TORREZ SILVA MARIO', horas: 16 },
                    { taller: 'Taller de Liderazgo', docente: 'FLORES RIOS ANA', horas: 18 },
                    { taller: 'Taller de Estadística', docente: 'VARGAS LUNA PEDRO', horas: 22 },
                ],
                cargaHoraria: [
                    { rango: '0h', cantidad: 2, color: '#ef4444' },
                    { rango: '1-10h', cantidad: 2, color: '#f59e0b' },
                    { rango: '11-20h', cantidad: 9, color: '#10b981' },
                    { rango: '21-30h', cantidad: 5, color: '#0d9488' },
                ],
                recientes: [
                    { codigo: '199800034', nombre: 'MAMANI COCA WALTER', grado: 'Magister', taller: 'Taller de Emprendimiento', horas: 16, fecha: '2026-05-10' },
                    { codigo: '197000002', nombre: 'GARCIA PEREZ ROSA', grado: 'PhD', taller: 'Taller de Investigación', horas: 20, fecha: '2026-05-08' },
                    { codigo: '196900002', nombre: 'TORREZ SILVA MARIO', grado: 'Licenciado', taller: 'Taller de Comunicación', horas: 0, fecha: '2026-05-05' },
                ]
            },

            // ─── TALLERES ─────────────────────────────────────────────────
            talleres: {
                total: 12,
                activos: 12,
                porPlan: [
                    { plan: 'ADM', cantidad: 4 },
                    { plan: 'ECO', cantidad: 3 },
                    { plan: 'FIN', cantidad: 2 },
                    { plan: 'CON', cantidad: 2 },
                    { plan: 'COM', cantidad: 1 },
                ],
            },

            // ─── ALERTAS ─────────────────────────────────────────────────
            alertas: [
                { id: 1, tipo: 'warning', mensaje: '2 docentes sin carga horaria asignada este período', accion: '/secretaria-talleres/docentes?estado=sin-carga' },
                { id: 2, tipo: 'info', mensaje: 'Período 2026-I · Fecha límite inscripciones: 30 de mayo' },
                { id: 3, tipo: 'warning', mensaje: '3 estudiantes con inscripción pendiente de validar', accion: '/secretaria-talleres/estudiantes?estado=pendiente' },
            ]
        }
    }
}

function delay(ms) { return new Promise(r => setTimeout(r, ms)) }