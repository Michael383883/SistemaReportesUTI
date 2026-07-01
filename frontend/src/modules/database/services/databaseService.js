import axios from 'axios'

// ──────────────────────────────────────────────────────────────────────────
// Si tu proyecto ya tiene una instancia de axios centralizada (con baseURL,
// interceptores de auth, etc. — por ejemplo la que probablemente usa el
// módulo "docentes"), BÓRRALA esta instancia y usá la tuya:
//
//   import api from '@/services/api'
//
// Dejo esta acá para que el archivo funcione standalone.
// ──────────────────────────────────────────────────────────────────────────
const api = axios.create({
    baseURL: `${import.meta.env.VITE_API_URL ?? ''}/api/database`,
})

// Tablas catálogo (mismo orden que el backend, según la doc)
export const TABLAS_CATALOGO = [
    'BIOGRAFICOS',
    'BIOGRAFICOS_EXT',
    'DOCENTES',
    'DOCENTES_2',
    'DOCENTES_TELEFONO',
    'MATERIAS',
    'PLANES',
    'GRUPOS_COMPARTIDOS',
    'NROINSMATGRPNE',
]

// Tablas elegibles para carga inicial
export const TABLAS_CARGA_INICIAL = ['GRUPOS', 'HORARIOS2', 'KARDEX_EXT']

export const databaseService = {
    // 1. Estado de conexión ---------------------------------------------------
    async getStatus() {
        const { data } = await api.get('/status')
        return data
    },

    // 2. Catálogos -------------------------------------------------------------
    async migrarCatalogos() {
        const { data } = await api.post('/migrar-catalogos')
        return data
    },

    async migrarCatalogo(tabla) {
        const { data } = await api.post(`/migrar-catalogo/${tabla}`)
        return data
    },

    // 3. Carga inicial -----------------------------------------------------------
    // tablas: array opcional. Sin body -> el backend usa las 3 tablas por default.
    async cargaInicial(tablas) {
        const body = tablas && tablas.length ? { tablas } : {}
        const { data } = await api.post('/carga-inicial', body)
        return data
    },

    // 4a. Sincronizar GRUPOS por semestre (MERGE real: INSERT+UPDATE+DELETE) --
    async migrarGrupos(anio, periodo) {
        const { data } = await api.post('/migrar-grupos', { anio, periodo })
        return data
    },

    // 4b. Sincronizar HORARIOS2 / KARDEX_EXT por semestre (DELETE + INSERT) ---
    // tablas: opcional, default ["HORARIOS2", "KARDEX_EXT"] en el backend.
    async migrarSemestre(anio, periodo, tablas) {
        const body = { anio, periodo }
        if (tablas && tablas.length) body.tablas = tablas
        const { data } = await api.post('/migrar-semestre', body)
        return data
    },
}

export default databaseService