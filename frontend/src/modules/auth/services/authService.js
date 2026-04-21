import api from '@/shared/services/api'

export const authService = {
  async getCsrfCookie() {
    await api.get('/sanctum/csrf-cookie')
  },

  async login(credentials) {
    await this.getCsrfCookie()
    const { data } = await api.post('/api/auth/login', credentials)
    return data
  },

  async logout() {
    await api.post('/api/auth/logout')
    localStorage.removeItem('token')
  },

  async me() {
    const { data } = await api.get('/api/auth/me')
    return data
  },
}
