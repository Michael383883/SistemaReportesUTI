import { createRouter, createWebHistory } from 'vue-router'
import { authRoutes } from '@/modules/auth/routes'
import { usersRoutes } from '@/modules/users/routes'
import { dashboardRoutes } from '@/modules/dashboard/routes'

const routes = [
  // Públicas
  ...authRoutes,

  // Protegidas (layout)
  {
    path: '/',
    component: () => import('@/shared/components/layout/AppLayout.vue'),
    children: [
      { path: '', redirect: '/dashboard' },
      ...dashboardRoutes,
      ...usersRoutes,
    ],
  },

  // Fallback
  { path: '/:pathMatch(.*)*', redirect: '/dashboard' },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

// Guard temporal (todo permitido)
router.beforeEach((_to, _from, next) => next())

export default router