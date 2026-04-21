export const authRoutes = [
    {
        path: '/login',
        name: 'Login',
        component: () => import('./views/LoginView.vue'),
        meta: { requiresGuest: true },
    },
    {
        path: '/403',
        name: 'Forbidden',
        component: () => import('./views/ForbiddenView.vue'),
    },
]
