import DashboardPage from './views/DashboardPage.vue'
import DocentesPage from './views/DocentesPage.vue'
import EstudiantesPage from './views/EstudiantesPage.vue'
export const secretariaTalleresRoutes = [

    {
        path: 'secretariaTalleres/dashboard',
        name: 'SecretariaTalleresDashboard',
        component: DashboardPage,
        meta: { roles: ['secretaria_talleres'], requiresAuth: true },
    },
    {
        path: 'secretariaTalleres/docentes',
        name: 'SecretariaTalleresDocentes',
        component: DocentesPage,
        meta: { roles: ['secretaria_talleres'], requiresAuth: true },
    },
    {
        path: 'secretariaTalleres/estudiante',
        name: 'SecretariaTalleresEstudiante',
        component: EstudiantesPage,
        meta: { roles: ['secretaria_talleres'], requiresAuth: true },
    },
]