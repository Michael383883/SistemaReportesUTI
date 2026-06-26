import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authService } from '../services/authService'
import router from '@/router'

// ─── Clasificador de errores ──────────────────────────────────────────────────
//
// Jerarquía de diagnóstico:
//   1. Sin respuesta      → servidor caído / CORS / sin red
//   2. 401 / 403          → credenciales o sesión inválidas
//   3. 422 / 400          → datos mal formados
//   4. 5xx                → fallo interno del servidor
//   5. Cualquier otro     → mensaje genérico o del backend

function classifyAuthError(err) {
    // Sin respuesta HTTP → el servidor no llegó a contestar
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

// ─── Store ────────────────────────────────────────────────────────────────────

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

    // ── Login ──────────────────────────────────────────────────────────────────
    async function login(credentials) {
        loading.value = true
        error.value = null

        try {
            const data = await authService.login(credentials)
            token.value = data.token
            user.value = data.user
            localStorage.setItem('token', data.token)
        } catch (err) {
            error.value = classifyAuthError(err)   // ← mensaje preciso según el tipo de fallo
            throw err
        } finally {
            loading.value = false
        }
    }

    // ── Logout ─────────────────────────────────────────────────────────────────
    async function logout() {
        try {
            await authService.logout()
        } finally {
            _clearSession()
            router.push({ name: 'login' })
        }
    }

    // ── Fetch usuario autenticado ──────────────────────────────────────────────
    async function fetchMe() {
        if (!token.value) return
        try {
            const data = await authService.me()
            user.value = data.user
        } catch {
            _clearSession()
        }
    }

    // ── Utilidades ─────────────────────────────────────────────────────────────
    function clearError() {
        error.value = null
    }

    function _clearSession() {
        user.value = null
        token.value = null
        localStorage.removeItem('token')
    }

    return {
        user, token, loading, error,
        isAuthenticated, userRole, isAdmin, isSecretaria, isUTI,
        login, logout, fetchMe, clearError,
    }
})