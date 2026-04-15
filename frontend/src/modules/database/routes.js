export const databaseRoutes = [
    {
        path: '/config-bd',
        name: 'config-bd',
        component: () => import('./views/DatabaseView.vue'),
    }
]