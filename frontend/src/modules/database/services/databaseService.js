import axios from 'axios'

const BASE = import.meta.env.VITE_API_URL || '/api'

export const databaseService = {

    async getStatus() {
        const { data } = await axios.get(`${BASE}/api/database/status`)
        return data
    },


    async migrate() {
        const payload = {
            migraciones: [
                {
                    origen: "DOCENTES_2",
                    destino: "docentes"
                }
            ]
        }

        const { data } = await axios.post(`${BASE}/api/database/migrate`, payload)
        return data
    }
}