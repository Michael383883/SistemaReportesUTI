export const docentesRoutes = [
    {
        path: '/docentes',
        name: 'docentes',
        component: () => import('./views/DocentesView.vue'),
        //meta: { requiresAuth: true, roles: ['Administrador', 'Secretaria'] },
    },
]
