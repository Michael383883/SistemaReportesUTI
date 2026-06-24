export const reporteHorarioRoutesadmin = [
    {
        path: 'reporte-horario-completo',
        name: 'reporte-horario-completo',
        component: () => import('./views/HorarioCompletoView.vue'),
        meta: {
            requiresAuth: true,
            roles: ['admin'],
        },
    },

    {
        path: 'reporte-horario-resumen',
        name: 'reporte-horario-resumen',
        component: () => import('./views/HorarioResumenView.vue'),
        meta: {
            requiresAuth: true,
            roles: ['admin'],
        },
    },

    {
        path: 'reporte-horario-resumen-dos',
        name: 'reporte-horario-resumen-dos',
        component: () => import('./views/Horarioresumendosview.vue'),
        meta: {
            requiresAuth: true,
            roles: ['admin'],
        },
    },


]