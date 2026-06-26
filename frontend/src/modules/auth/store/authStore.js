import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authService } from '../services/authService'
import router from '@/router'

function classifyAuthError(err) {
    if (!err.response) {
        if (err.code === 'ERR_NETWORK' || err.message === 'Network Error') {
            return 'No se puede conectar al servidor. Verifica que el servicio esté activo.'
        }
        if (err.code === 'ECONNABORTED') {
            return 'La solicitud tardó demasiado. Intenta de nuevo.'
        }
        return 'Error de conexión inesperado. Intenta más tarde.'
    }

    const { status, data } = err.response

    if (status === 401) return data?.message ?? 'Credenciales incorrectas.'
    if (status === 403) return data?.message ?? 'No tienes permiso para acceder.'
    if (status === 422 || status === 400) return data?.message ?? 'Datos de inicio de sesión inválidos.'
    if (status === 429) return 'Demasiados intentos. Espera unos minutos.'
    if (status >= 500) return 'El servidor encontró un problema. Intenta más tarde.'

    return data?.message ?? 'Ocurrió un error inesperado.'
}

export const useAuthStore = defineStore('auth', () => {
    const user = ref(null)
    const token = ref(localStorage.getItem('token') ?? null)
    const loading = ref(false)
    const error = ref(null)

    const isAuthenticated = computed(() => !!token.value)
    const userRole = computed(() => user.value?.role ?? null)
    const isAdmin = computed(() => userRole.value === 'admin')
    const isSecretaria = computed(() => userRole.value === 'secretaria')
    const isUTI = computed(() => userRole.value === 'uti')

    async function login(credentials) {
        loading.value = true
        error.value = null
        try {
            const data = await authService.login(credentials)
            token.value = data.token
            user.value = data.user
            localStorage.setItem('token', data.token)
        } catch (err) {
            error.value = classifyAuthError(err)
            throw err
        } finally {
            loading.value = false
        }
    }

    async function logout() {
        // FIX: primero llamar al backend (el token aún está vigente),
        // luego limpiar sesión y redirigir.
        // Si el backend falla (401, red caída, etc.) igual se limpia localmente.
        try {
            await authService.logout()
        } catch {
            // silencioso — el 401 significa que el token ya no era válido,
            // pero la sesión local se limpia igual.
        } finally {
            _clearSession()
            // FIX: usar path en lugar de name — la ruta no tiene name definido
            router.push('/login')
        }
    }

    async function fetchMe() {
        if (!token.value) return
        try {
            const data = await authService.me()
            user.value = data.user
        } catch {
            _clearSession()
        }
    }

    function clearError() {
        error.value = null
    }

    function _clearSession() {
        user.value = null
        token.value = null
        localStorage.removeItem('token')
        localStorage.removeItem('user')
    }

    return {
        user, token, loading, error,
        isAuthenticated, userRole, isAdmin, isSecretaria, isUTI,
        login, logout, fetchMe, clearError,
    }
})