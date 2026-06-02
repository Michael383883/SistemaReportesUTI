import { useAuthStore } from '../store/authStore'
import { useRouter, useRoute } from 'vue-router'

export function useAuth() {
  const authStore = useAuthStore()
  const router = useRouter()
  const route = useRoute()

  async function login(credentials) {
    await authStore.login(credentials)   // ← aquí ya se guarda user + role

    // 1. Si venía con ?redirect= y es una ruta interna válida → respetarla
    const redirect = route.query?.redirect
    if (typeof redirect === 'string' && redirect.startsWith('/')) {
      return router.push(redirect)
    }

    // 2. Redirigir al home correcto según rol
    const roleHome = {
      secretaria: '/secretaria/dashboard',
      secretaria_talleres: '/secretariaTalleres/dashboard', 
      admin: '/dashboard',
      uti: '/dashboard',          // ajusta si tienes ruta propia
    }
    const home = roleHome[authStore.userRole] ?? '/dashboard'
    router.push(home)
  }

  async function logout() {
    await authStore.logout()
    router.push('/login')
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