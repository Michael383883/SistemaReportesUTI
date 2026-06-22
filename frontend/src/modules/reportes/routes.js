export const reportesRoutes = [
    {
        path: '/reporte',
        name: 'reporte',
        component: () => import('./views/ReporteView.vue'),
        //meta: { requiresAuth: true, roles: ['Administrador', 'Secretaria'] },
    },
    {
        path: '/reportes/exportaciones',
        name: 'reportes-exportacion',
        component: () => import('./views/ExportacionesView.vue')
    },
]
