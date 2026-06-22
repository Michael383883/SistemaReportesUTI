// modules/resoluciones/routes.js  ← renombra a routes.js
export const resolucionesRoutes = [
    {
        path: '/resoluciones/subir',
        name: 'resoluciones',
        component: () => import('./views/ResolucionView.vue'),
        meta: { requiresAuth: true }
    },
    {
        path: '/resoluciones/listado',
        name: 'resoluciones-listado',
        component: () => import('./views/ResolucionListadoView.vue')
    },
    {
        path: '/resoluciones/asignar',
        name: 'resoluciones-asignar',
        component: () => import('./views/AsignarResolucionView.vue'),
        meta: { requiresAuth: true }
    },
]