import { useAuthStore } from '../store/authStore'
import { useRouter, useRoute } from 'vue-router'

export function useAuth() {
  const authStore = useAuthStore()
  const router = useRouter()
  const route = useRoute()

  async function login(credentials) {
    await authStore.login(credentials)
    const redirect = route.query?.redirect
    // Validación: solo permitir rutas internas seguras
    if (typeof redirect === 'string' && redirect.startsWith('/')) {
      router.push(redirect)
    } else {
      router.push('/dashboard')
    }
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
