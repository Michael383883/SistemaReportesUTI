// modules/periodos-academicos/routes.js
export const periodosAcademicosRoutes = [
    {
        path: '/periodos-academicos',
        name: 'periodos-academicos',
        component: () => import('./views/PeriodosAcademicosView.vue'),
        meta: { requiresAuth: true, roles: ['admin'] }
    }
]
