export const reportesRoutes = [
    {
        path: '/reporte',
        name: 'reporte',
        component: () => import('./views/ReporteView.vue'),
        //meta: { requiresAuth: true, roles: ['Administrador', 'Secretaria'] },
    },
    {
        path: '/reportes/edicion-tipo-ingreso',
        name: 'reportes-ediciontipoingreso',
        component: () => import('./views/EdicionTipoIngreso.vue')
    },

    {
        path: '/reportes/docentes-titulo',
        name: 'reportes-docentes-titulo',
        component: () => import('./views/ReporteDocente.vue')
    },
    {
        path: '/reportes/docente-titulos/:codigo',
        name: 'DocenteTitulos',
        component: () => import('./views/DocenteTitulosView.vue'),
        // meta: { requiresAuth: true }  ← igual que tus otras rutas
    },
]
