// Agrega estas rutas a tu router/index.js (o routes.js)
// dentro del array de children de tu layout principal.

// Ejemplo:
// import ReporteView from '@/modules/reportes/views/ReporteView.vue'
//
// {
//   path: 'reporte-docente',
//   name: 'reporte-docente',
//   component: ReporteView,
//   meta: { title: 'Reporte de Docente' }
// }

// -------------------------------------------------------
// Fragmento listo para copiar al array de rutas:
// -------------------------------------------------------

// {
//     path: 'reporte-docente',
//         name: 'reporte-docente',
//             component: () => import('@/modules/reportes/views/ReporteView.vue'),
//                 meta: { title: 'Reporte de Docente' }
// }

export const reportesRoutes = [
    {
        path: '/reporte',
        name: 'reporte',
        component: () => import('./views/ReporteView.vue'),
        //meta: { requiresAuth: true, roles: ['Administrador', 'Secretaria'] },
    },
]
