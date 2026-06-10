import axios from 'axios'

const BASE = import.meta.env.VITE_API_URL || '/api'

export const databaseService = {

    async getStatus() {
        const { data } = await axios.get(`${BASE}/api/database/status`)
        return data
    },

    async migrateAll() {
        const { data } = await axios.post(`${BASE}/api/database/migrate-all`)
        return data
    }
}