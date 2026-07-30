import { createRouter, createWebHistory } from 'vue-router'
import { authRoutes } from '@/modules/auth/routes'
import { usersRoutes } from '@/modules/users/routes'
import { dashboardRoutes } from '@/modules/dashboard/routes'
import { databaseRoutes } from '@/modules/database/routes'
import { useAuthStore } from '@/modules/auth/store/authStore'
import { docentesRoutes } from '@/modules/docentes/routes'
import { reportesRoutes } from '@/modules/reportes/routes'
import { resolucionesRoutes } from '@/modules/resoluciones/routes'
import { reporteHorarioRoutes } from '@/modules/reporte-horario/routes'
import { secretariaRoutes } from '@/modules/secretaria/routes'
import { secretariaTalleresRoutes } from '@/modules/secretaria_talleres/routes'
import { reporteHorarioRoutesadmin } from '@/modules/horariosadmin/routes'
import { inscritosRoutes } from '@/modules/inscritos/routes'
import { resolucionesExtraRoutes } from '@/modules/resolucionesExtra/routes'

const routes = [
  ...authRoutes,
  {
    path: '/',
    component: () => import('@/shared/components/layout/MainLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        redirect: () => {
          const authStore = useAuthStore()
          const role = authStore.userRole
          if (role === 'secretaria' || role === 'uti') return '/secretaria/dashboard'
          if (role === 'secretaria_talleres') return '/secretariaTalleres/dashboard'
          return '/dashboard'
        },
      },
      ...dashboardRoutes,
      ...usersRoutes,
      ...databaseRoutes,
      ...docentesRoutes,
      ...reportesRoutes,
      ...resolucionesRoutes,
      ...reporteHorarioRoutes,
      ...secretariaRoutes,
      ...secretariaTalleresRoutes,
      ...reporteHorarioRoutesadmin,
      ...inscritosRoutes,
      ...resolucionesExtraRoutes,
    ],
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/dashboard',
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

router.beforeEach(async (to, _from) => {
  const token = localStorage.getItem('token')
  const requiresAuth = to.matched.some(r => r.meta.requiresAuth)
  const isGuest = to.matched.some(r => r.meta.isGuest)
  const requiredRoles = to.meta.roles

  if (requiresAuth && !token) {
    return { path: '/login', query: { redirect: to.fullPath } }
  }

  if (token) {
    const authStore = useAuthStore()
    if (!authStore.user) {
      await authStore.fetchMe()
    }
  }

  const authStore = useAuthStore()

  if (to.path === '/') {
    const role = authStore.userRole
    if (role === 'secretaria' || role === 'uti') return { path: '/secretaria/dashboard' }
    if (role === 'secretaria_talleres') return { path: '/secretariaTalleres/dashboard' }
    return { path: '/dashboard' }
  }

  if (isGuest && token) {
    const role = authStore.userRole
    if (role === 'secretaria' || role === 'uti') return { path: '/secretaria/dashboard' }
    if (role === 'secretaria_talleres') return { path: '/secretariaTalleres/dashboard' }
    return { path: '/dashboard' }
  }

  if (requiredRoles?.length > 0) {
    const userRole = authStore.userRole
    if (!userRole || !requiredRoles.includes(userRole)) {
      if (userRole === 'secretaria' || userRole === 'uti') return { path: '/secretaria/dashboard' }
      if (userRole === 'secretaria_talleres') return { path: '/secretariaTalleres/dashboard' }
      return { path: '/dashboard' }
    }
  }

  return true
})

export default router