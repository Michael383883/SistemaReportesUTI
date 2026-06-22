export const usersRoutes = [
  {
    path: 'usuarios',
    name: 'Usuarios',
    component: () => import('./views/UsersView.vue'),
    meta: { requiresAuth: true, roles: ['admin'] },
  },
  {
    path: 'perfil',
    name: 'Perfil',
    component: () => import('./views/AdminProfileView.vue'),
    meta: { requiresAuth: true, roles: ['admin'] },
  },
]