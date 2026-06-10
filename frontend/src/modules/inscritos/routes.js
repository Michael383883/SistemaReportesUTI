export const inscritosRoutes = [
    {
        path: '/inscritos',
        name: 'inscritos',
        component: () => import('./views/InscritosView.vue'),
        // meta: { requiresAuth: true, roles: ['Administrador', 'Secretaria'] },
    },
]