import axios from 'axios'

const BASE = import.meta.env.VITE_API_URL || '/api'

export const databaseService = {
    /**
     * GET /api/database/status
     * Retorna el estado de conexión de PostgreSQL y SQL Server.
     */
    async getStatus() {
        const { data } = await axios.get(`${BASE}/api/database/status`)
        return data
    },

    /**
     * POST /api/database/migrate
     * Inicia la migración de SQL Server → PostgreSQL.
     */
    async migrate() {
        const { data } = await axios.post(`${BASE}/api/database/migrate`)
        return data
    }
}