// src/modules/secretaria/services/dashboardService.js

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
     * Obtener KPIs del dashboard de secretaría
     */
    async getKPIs() {
        // Por ahora retornamos datos mock, cuando tengas el endpoint:
        // return apiFetch('/secretaria/dashboard/kpis')

        await delay(500)
        return {
            totalDocentes: 87,
            docentesActivos: 72,
            docentesSinCarga: 3,
            horasPromedio: 24.5,
            porUnidad: [
                { unidad: 'Economía', cantidad: 18, horasPromedio: 28.3 },
                { unidad: 'Administración', cantidad: 22, horasPromedio: 25.1 },
                { unidad: 'Contaduría', cantidad: 15, horasPromedio: 22.8 },
                { unidad: 'Informática', cantidad: 12, horasPromedio: 30.2 },
                { unidad: 'Marketing', cantidad: 10, horasPromedio: 20.5 },
                { unidad: 'Derecho', cantidad: 10, horasPromedio: 18.9 },
            ],
            porGrado: [
                { grado: 'Licenciado', cantidad: 35, color: '#14b8a6' },
                { grado: 'Magister', cantidad: 28, color: '#3b82f6' },
                { grado: 'PhD', cantidad: 15, color: '#8b5cf6' },
                { grado: 'Ingeniero', cantidad: 9, color: '#f59e0b' },
            ],
            cargaHoraria: [
                { rango: '0h', cantidad: 3 },
                { rango: '1-10h', cantidad: 8 },
                { rango: '11-20h', cantidad: 15 },
                { rango: '21-30h', cantidad: 32 },
                { rango: '31-40h', cantidad: 24 },
                { rango: '40h+', cantidad: 5 },
            ],
            docentesRecientes: [
                { codigo: '199800034', nombre: 'ABASTO CASTRO MONICA SHIRLEY', grado: 'Licenciado', unidad: 'Administración', horas: 24, fecha: '2026-05-10' },
                { codigo: '197000002', nombre: 'POZO MURIEL JAIME', grado: 'Licenciado', unidad: 'Economía', horas: 32, fecha: '2026-05-08' },
                { codigo: '196900002', nombre: 'OLIVARES ARANA JORGE', grado: 'Licenciado', unidad: 'Informática', horas: 0, fecha: '2026-05-05' },
                { codigo: '196500004', nombre: 'CABALLERO ESPINOZA JAVIER', grado: 'Licenciado', unidad: 'Contaduría', horas: 28, fecha: '2026-05-03' },
            ],
            alertas: [
                { id: 1, tipo: 'warning', mensaje: '3 docentes sin carga horaria asignada este período', accion: '/secretaria/docentes?estado=sin-carga' },
                { id: 2, tipo: 'info', mensaje: 'Promedio de horas por docente: 24.5h - dentro del rango esperado' },
                { id: 3, tipo: 'warning', mensaje: '2 docentes con carga horaria excedida (>40h)' },
                { id: 4, tipo: 'info', mensaje: 'Período 2026-I - Fecha límite de asignación: 30 de mayo' },
            ],
            distribucionHoraria: {
                labels: ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'],
                datasets: [
                    { label: 'Mañana', data: [45, 42, 48, 40, 44, 15], color: '#3b82f6' },
                    { label: 'Tarde', data: [30, 28, 32, 35, 30, 8], color: '#10b981' },
                    { label: 'Noche', data: [20, 22, 18, 25, 20, 5], color: '#8b5cf6' },
                ]
            }
        }
    }
}

function delay(ms) { return new Promise(r => setTimeout(r, ms)) }