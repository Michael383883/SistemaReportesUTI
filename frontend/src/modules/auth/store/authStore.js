import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useAuthStore = defineStore('auth', () => {
    const loading = ref(false)
    const error = ref(null)

    function clearError() {
        error.value = null
    }

    async function login({ email, password }) {
        loading.value = true
        error.value = null
        try {
            // TODO: conectar con tu API real
            await new Promise(r => setTimeout(r, 1000)) // simulación
            if (email !== 'admin@umss.edu' || password !== '12345678') {
                throw new Error('Credenciales incorrectas')
            }
        } catch (e) {
            error.value = e.message
            throw e
        } finally {
            loading.value = false
        }
    }

    return { loading, error, login, clearError }
})