export const docentesRoutes = [
    {
        path: '/reportes/docentes',
        name: 'Materias Dictadas',
        component: () => import('./views/DocentesView.vue'),
        //meta: { requiresAuth: true, roles: ['Administrador', 'Secretaria'] },
    },
]
