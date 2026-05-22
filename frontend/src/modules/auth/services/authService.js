import api from '@/shared/services/api'

export const authService = {
  async login(credentials) {
    const { data } = await api.post('/api/auth/login', credentials)

    console.log('Respuesta completa del login:', data)
    console.log('Token recibido:', data.token)

    if (data.token) {
      localStorage.setItem('token', data.token)
      localStorage.setItem('user', JSON.stringify(data.user))
    }

    return data
  },

  async logout() {
    await api.post('/api/auth/logout')
    localStorage.removeItem('token')
    localStorage.removeItem('user')
  },

  async me() {
    const { data } = await api.get('/api/auth/me')
    return data
  },
}