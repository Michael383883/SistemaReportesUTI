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

const routes = [
  ...authRoutes,
  {
    path: '/',
    component: () => import('@/shared/components/layout/AppLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        redirect: () => {
          const authStore = useAuthStore()
          const role = authStore.userRole
          if (role === 'secretaria') return '/secretaria/dashboard'
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
      ...secretariaTalleresRoutes,  // ← agregado
    ],
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: () => {
      const authStore = useAuthStore()
      const role = authStore.userRole
      if (role === 'secretaria') return '/secretaria/dashboard'
      if (role === 'secretaria_talleres') return '/secretariaTalleres/dashboard'
      return '/dashboard'
    },
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

router.beforeEach(async (to, _from, next) => {
  const token = localStorage.getItem('token')
  const requiresAuth = to.matched.some(r => r.meta.requiresAuth)
  const isGuest = to.matched.some(r => r.meta.isGuest)
  const requiredRoles = to.meta.roles

  if (requiresAuth && !token) {
    return next({ path: '/login', query: { redirect: to.fullPath } })
  }

  if (token) {
    const authStore = useAuthStore()
    if (!authStore.user) {
      await authStore.fetchMe()
    }
  }

  const authStore = useAuthStore()

  if (isGuest && token) {
    const role = authStore.userRole
    if (role === 'secretaria') return next('/secretaria/dashboard')
    if (role === 'secretaria_talleres') return next('/secretariaTalleres/dashboard')
    return next('/dashboard')
  }

  if (requiredRoles?.length > 0) {
    const userRole = authStore.userRole
    if (!userRole || !requiredRoles.includes(userRole)) {
      if (userRole === 'secretaria') return next('/secretaria/dashboard')
      if (userRole === 'secretaria_talleres') return next('/secretariaTalleres/dashboard')
      return next('/dashboard')
    }
  }

  next()
})

export default router