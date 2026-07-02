
export const resolucionesExtraRoutes = [
    {
        path: '/clasificaciones',
        name: 'clasificaciones-listado',
        component: () => import('./views/ClasificacionListadoView.vue'),
    },
    {
        path: '/clasificaciones/nueva',
        name: 'clasificaciones-nueva',
        component: () => import('./views/ClasificacionView.vue'),
    },
    {
        path: '/clasificaciones/docente/:cod_docente',
        name: 'clasificaciones-docente',
        component: () => import('./views/ClasificacionDocenteView.vue'),
    },
]