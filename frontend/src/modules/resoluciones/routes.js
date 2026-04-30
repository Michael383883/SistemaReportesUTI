// modules/resoluciones/routes.js  ← renombra a routes.js
export const resolucionesRoutes = [
    {
        path: '/resoluciones',
        name: 'resoluciones',
        component: () => import('./views/ResolucionView.vue'),
        meta: { requiresAuth: true }
    },
]