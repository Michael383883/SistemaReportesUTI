export const reporteHorarioRoutes = [
    {
        path: '/reporte-horario',
        name: 'reporte-horario',
        component: () => import('./views/ReporteHorarioView.vue'),
        //meta: { requiresAuth: true, roles: ['Administrador', 'Secretaria'] },
    },
]
