import DashboardPage from './views/DashboardPage.vue'
import EstudiantesPage from './views/EstudiantesPage.vue'
import DocentesPage from './views/DocentesPage.vue'

export const secretariaRoutes = [
    {
        path: 'secretaria/dashboard',
        name: 'SecretariaDashboard',
        component: DashboardPage,
        meta: { roles: ['secretaria', 'uti'], requiresAuth: true },
    },
    {
        path: 'secretaria/estudiantes',
        name: 'SecretariaEstudiantes',
        component: EstudiantesPage,
        meta: { roles: ['secretaria', 'uti'], requiresAuth: true },
    },
    {
        path: 'secretaria/docentes',
        name: 'SecretariaDocentes',
        component: DocentesPage,
        meta: { roles: ['secretaria', 'uti'], requiresAuth: true },
    },
]