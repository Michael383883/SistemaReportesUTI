import api from '@/shared/services/api'
export const authService = {
    async login(credentials) {
        const { data } = await api.post('/api/auth/login', credentials)
        // El store es quien persiste el token — authService no debería duplicarlo.
        // Se mantiene solo como fallback por si algún interceptor lo necesita antes
        // de que el store haya terminado de procesar la respuesta.
        if (data.token) {
            localStorage.setItem('token', data.token)
        }
        return data
    },
    async logout() {
        // NO borrar el token aquí — axios lo necesita en el header para
        // que el backend acepte la request. El store limpia localStorage en _clearSession().
        await api.post('/api/auth/logout')
    },
    async me() {
        const { data } = await api.get('/api/auth/me')
        return data
    },

    // Actualiza los datos de perfil del usuario logueado (nombre, email, etc.)
    async updateProfile(payload) {
        const { data } = await api.put('/api/auth/profile', payload)
        return data
    },

    // Verifica la contraseña actual del usuario logueado SIN generar un token nuevo.
    // Se usa como paso previo obligatorio antes de mostrar el campo de nueva contraseña.
    async verifyPassword(password) {
        const { data } = await api.post('/api/auth/verify-password', { password })
        return data
    },

    // Cambia la contraseña del usuario logueado.
    // payload esperado: { currentPassword, newPassword }
    async changePassword(payload) {
        const { data } = await api.put('/api/auth/change-password', payload)
        return data
    },
}