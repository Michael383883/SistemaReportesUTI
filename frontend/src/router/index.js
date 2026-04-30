// import { createRouter, createWebHistory } from 'vue-router'
// import { authRoutes } from '@/modules/auth/routes'
// import { usersRoutes } from '@/modules/users/routes'
// import { dashboardRoutes } from '@/modules/dashboard/routes'
// import { databaseRoutes } from '@/modules/database/routes'
// const routes = [
//   // Públicas
//   ...authRoutes,

//   // Protegidas (layout)
//   {
//     path: '/',
//     component: () => import('@/shared/components/layout/AppLayout.vue'),
//     children: [
//       { path: '', redirect: '/dashboard' },
//       ...dashboardRoutes,
//       ...usersRoutes,
//       ...databaseRoutes,
//     ],
//   },

//   // Fallback
//   { path: '/:pathMatch(.*)*', redirect: '/dashboard' },
// ]

// const router = createRouter({
//   history: createWebHistory(import.meta.env.BASE_URL),
//   routes,
// })

// // Guarda principal para redirecciones
// router.beforeEach((to, _from, next) => {
//   const token = localStorage.getItem('token')

//   const requiresAuth = to.matched.some(r => r.meta.requiresAuth)
//   const isGuest = to.matched.some(r => r.meta.isGuest)
//   const requiredRoles = to.meta.roles

//   // Sin sesión → login
//   if (requiresAuth && !token) {
//     return next({ path: '/login', query: { redirect: to.fullPath } })
//   }

//   // Ya logueado va a login → dashboard
//   if (isGuest && token) {
//     return next('/dashboard')
//   }

//   // ✅ Importar el store AQUÍ dentro (Pinia ya está lista en este punto)
//   if (requiredRoles?.length > 0) {
//     const { useAuthStore } = require('@/modules/auth/store/authStore')
//     const authStore = useAuthStore()
//     const userRole = authStore.userRole

//     if (!userRole || !requiredRoles.includes(userRole)) {
//       return next('/dashboard')
//     }
//   }

//   next()
// })


// export default router



import { createRouter, createWebHistory } from 'vue-router'
import { authRoutes } from '@/modules/auth/routes'
import { usersRoutes } from '@/modules/users/routes'
import { dashboardRoutes } from '@/modules/dashboard/routes'
import { databaseRoutes } from '@/modules/database/routes'
import { useAuthStore } from '@/modules/auth/store/authStore'
import { docentesRoutes } from '@/modules/docentes/routes'
//import { reportesRoutes } from '../modules/reportes/routes'
import { reportesRoutes } from '@/modules/reportes/routes'
import { resolucionesRoutes } from '@/modules/resoluciones/routes'

const routes = [
  ...authRoutes,
  {
    path: '/',
    component: () => import('@/shared/components/layout/AppLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      { path: '', redirect: '/dashboard' },
      ...dashboardRoutes,
      ...usersRoutes,
      ...databaseRoutes,
      ...docentesRoutes,
      ...reportesRoutes,
      ...resolucionesRoutes,
    ],
  },
  { path: '/:pathMatch(.*)*', redirect: '/dashboard' },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

router.beforeEach((to, _from, next) => {
  const token = localStorage.getItem('token')

  const requiresAuth = to.matched.some(r => r.meta.requiresAuth)
  const isGuest = to.matched.some(r => r.meta.isGuest)
  const requiredRoles = to.meta.roles

  if (requiresAuth && !token) {
    return next({ path: '/login', query: { redirect: to.fullPath } })
  }

  if (isGuest && token) {
    return next('/dashboard')
  }

  if (requiredRoles?.length > 0) {
    //  Se llama aquí dentro, cuando Pinia ya está activa
    const authStore = useAuthStore()
    const userRole = authStore.userRole

    if (!userRole || !requiredRoles.includes(userRole)) {
      return next('/dashboard')
    }
  }

  next()
})

export default router