import axios from 'axios'

const api = axios.create({
    baseURL: `${import.meta.env.VITE_API_URL ?? ''}/api/database`,
})

const indicesApi = axios.create({
    baseURL: `${import.meta.env.VITE_API_URL ?? ''}/api/indices`,
})

export const databaseService = {
    async getStatus() {
        const { data } = await api.get('/status')
        return data
    },
    async getTables() {
        const { data } = await api.get('/tables')
        return data
    },
    async verificarIndices() {
        const { data } = await indicesApi.get('/verificar')
        return data
    },
    async crearIndices() {
        const { data } = await indicesApi.post('/crear')
        return data
    },
}

export default databaseService