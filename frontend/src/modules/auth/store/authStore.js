import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authService } from '../services/authService'


export const useAuthStore = defineStore('auth', () => {
    const user = ref(null)
    const token = ref(localStorage.getItem('token') ?? null)
    const loading = ref(false)
    const error = ref(null)

    // const isAuthenticated = computed(() => !!token.value && !!user.value)
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
            error.value = err.response?.data?.message ?? 'Credenciales incorrectas'
            throw err
        } finally {
            loading.value = false
        }
    }

    async function logout() {
        try {
            await authService.logout()
        } finally {
            user.value = null
            token.value = null
            localStorage.removeItem('token')

        }
    }

    async function fetchMe() {
        if (!token.value) return
        try {
            const data = await authService.me()
            user.value = data.user
        } catch {
            user.value = null
            token.value = null
            localStorage.removeItem('token')

        }
    }

    function clearError() { error.value = null }

    return {
        user, token, loading, error,
        isAuthenticated, userRole, isAdmin, isSecretaria, isUTI,
        login, logout, fetchMe, clearError,
    }
})
