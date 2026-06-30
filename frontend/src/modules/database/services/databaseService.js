import axios from 'axios'

const BASE = import.meta.env.VITE_API_URL ?? ''

export const databaseService = {

    async getStatus() {
        const { data } = await axios.get(`${BASE}/api/database/status`)
        return data
    },

    async migrateAll() {
        const { data } = await axios.post(`${BASE}/api/database/migrate-all`)
        return data
    },

    async syncGrupos(eliminarObsoletos = false) {
        const { data } = await axios.post(`${BASE}/api/database/sync-grupos`, {
            eliminar_obsoletos: eliminarObsoletos,
        })
        return data
    },

    async syncGestion(eliminarObsoletos = false) {
        const { data } = await axios.post(`${BASE}/api/database/sync-gestion`, {
            eliminar_obsoletos: eliminarObsoletos,
        })
        return data
    },

    async syncTable(tabla) {
        const { data } = await axios.post(`${BASE}/api/database/sync-table`, {
            tabla,
        })
        return data
    }

}