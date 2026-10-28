// modules/resoluciones/routes.js
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
        name: 'resolucion-asignar',
        component: () => import('./views/AsignarOrigenAMaterias.vue'),
        props: { tipoInicial: 'resolucion' },
    },
    {
        path: '/clasificaciones/asignar',
        name: 'documento-asignar',
        component: () => import('./views/AsignarOrigenAMaterias.vue'),
        props: { tipoInicial: 'documento' },
    },
]