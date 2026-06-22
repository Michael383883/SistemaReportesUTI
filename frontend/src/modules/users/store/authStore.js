// authStore.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authService } from '../services/authService'
import router from '@/router'  // ← importar directamente, NO useRouter()

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

    // Actualiza los datos de perfil del usuario logueado (nombre, email, etc.)
    async function updateProfile(payload) {
        loading.value = true
        error.value = null
        try {
            const data = await authService.updateProfile(payload)
            user.value = data.user ?? user.value
            return true
        } catch (err) {
            error.value = err.response?.data?.message ?? 'Error al actualizar el perfil'
            throw err
        } finally {
            loading.value = false
        }
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
            error.value = err.response?.data?.message ?? 'Error al cambiar la contraseña'
            throw err
        } finally {
            loading.value = false
        }
    }

    function clearError() { error.value = null }

    return {
        user, token, loading, error,
        isAuthenticated, userRole, isAdmin, isSecretaria, isUTI,
        login, logout, fetchMe, updateProfile, changePassword, clearError,
    }
})