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
    // FIX: NO borrar el token aquí — axios lo necesita en el header para
    // que el backend acepte la request. El store limpia localStorage en _clearSession().
    await api.post('/api/auth/logout')
  },

  async me() {
    const { data } = await api.get('/api/auth/me')
    return data
  },
}