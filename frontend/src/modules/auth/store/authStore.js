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
        // primero llamar al backend (el token aún está vigente),
        // luego limpiar sesión y redirigir.
        // Si el backend falla (401, red caída, etc.) igual se limpia localmente.
        try {
            await authService.logout()
        } catch {
            // silencioso — el 401 significa que el token ya no era válido,
            // pero la sesión local se limpia igual.
        } finally {
            _clearSession()
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

    // Actualiza los datos de perfil del usuario logueado (nombre, email, etc.)
    async function updateProfile(payload) {
        loading.value = true
        error.value = null
        try {
            const data = await authService.updateProfile(payload)
            user.value = data.user ?? user.value
            return true
        } catch (err) {
            error.value = classifyAuthError(err)
            throw err
        } finally {
            loading.value = false
        }
    }

    // Verifica la contraseña actual sin generar un token nuevo.
    // No toca `loading`/`error` globales para no interferir con el resto de la UI
    // mientras el usuario está en el paso de verificación.
    async function verifyPassword(password) {
        await authService.verifyPassword(password)
        return true
    }

    // Cambia la contraseña del usuario logueado.
    // payload esperado: { currentPassword, newPassword }
    async function changePassword(payload) {
        loading.value = true
        error.value = null
        try {
            await authService.changePassword(payload)
            return true
        } catch (err) {
            error.value = classifyAuthError(err)
            throw err
        } finally {
            loading.value = false
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
        login, logout, fetchMe, updateProfile, verifyPassword, changePassword, clearError,
    }
})