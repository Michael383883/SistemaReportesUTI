import axios from 'axios'

const api = axios.create({
    baseURL: `${import.meta.env.VITE_API_URL ?? ''}/api/database`,
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
}

export default databaseService