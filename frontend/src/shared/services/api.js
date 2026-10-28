import axios from 'axios'
import router from '@/router'

const api = axios.create({
  
  baseURL: import.meta.env.VITE_API_URL,
  withCredentials: false,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (import.meta.env.DEV) {
      console.error('[API Error]', error.response?.status, error.config?.url, error.message)
    }

    if (error.response?.status === 401) {
      const url = error.config?.url ?? ''
      // No redirigir en el propio login ni en la validación inicial de
      // sesión: esos 401 son esperables (credenciales malas / token
      // expirado) y el store ya los maneja sin necesidad de navegar.
      const isAuthProbe = url.includes('/api/auth/login') || url.includes('/api/auth/me')
      const alreadyOnLogin = router.currentRoute.value.path === '/login'
      localStorage.removeItem('token')
      if (!isAuthProbe && !alreadyOnLogin) {
        router.push('/login')
      }
    }

    return Promise.reject(error)
  }
)

export default api