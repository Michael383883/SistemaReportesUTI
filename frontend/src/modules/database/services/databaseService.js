// Antes: este servicio creaba DOS instancias propias de axios
// (una con baseURL `${VITE_API_URL}/api/database` y otra con
// `${VITE_API_URL}/api/indices`), ninguna con interceptor de token.
// Al proteger /api/database/* y /api/indices/* con auth:sanctum,
// esas llamadas dejaban de mandar el header Authorization y
// devolvían 401 siempre, con o sin sesión iniciada.
//
// Ahora se usa la instancia compartida `api` (@/shared/services/api),
// que ya tiene:
//   - request interceptor: agrega Authorization: Bearer ${token}
//   - response interceptor: si hay 401, limpia el token y redirige a /login
import api from '@/shared/services/api'

export const databaseService = {
    async getStatus() {
        const { data } = await api.get('/api/database/status')
        return data
    },
    async getTables() {
        const { data } = await api.get('/api/database/tables')
        return data
    },
    async verificarIndices() {
        const { data } = await api.get('/api/indices/verificar')
        return data
    },
    async crearIndices() {
        const { data } = await api.post('/api/indices/crear')
        return data
    },
}

export default databaseService