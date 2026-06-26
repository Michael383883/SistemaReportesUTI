import { useAuthStore } from '../store/authStore'
import { useRouter, useRoute } from 'vue-router'

export function useAuth() {
  const authStore = useAuthStore()
  const router = useRouter()
  const route = useRoute()

  async function login(credentials) {
    await authStore.login(credentials)

    const redirect = route.query?.redirect
    if (typeof redirect === 'string' && redirect.startsWith('/')) {
      return router.push(redirect)
    }

    const roleHome = {
      secretaria: '/secretaria/dashboard',
      secretaria_talleres: '/secretariaTalleres/dashboard',
      admin: '/dashboard',
      uti:  '/secretaria/dashboard',
    }
    router.push(roleHome[authStore.userRole] ?? '/dashboard')
  }

  // FIX: delegar en authStore.logout() — él ya maneja la request, limpieza
  // y redirección. No duplicar router.push aquí.
  async function logout() {
    await authStore.logout()
  }

  return {
    user: authStore.user,
    isAuthenticated: authStore.isAuthenticated,
    isAdmin: authStore.isAdmin,
    isSecretaria: authStore.isSecretaria,
    isUTI: authStore.isUTI,
    userRole: authStore.userRole,
    loading: authStore.loading,
    error: authStore.error,
    clearError: authStore.clearError,
    login,
    logout,
  }
}