export const dashboardRoutes = [
  {
    path: 'dashboard',
    name: 'Dashboard',
    component: () => import('./views/DashboardView.vue'),
    meta: { requiresAuth: true },
  },
]
