// composables/useResumenPorGrupo.js
// Fetch de INSCRITOS/APROBADOS/REPROBADOS por GRUPO (formato plano),
// consumiendo el endpoint resumenPorGrupo. Resuelve la paginación
// del backend automáticamente (per_page tope 500) para traer todo
// el listado completo, listo para el reporte "Resumido".

import { ref } from 'vue'

const BASE_URL = import.meta.env.VITE_API_URL 
const MAX_PAGINAS_SEGURIDAD = 50 // evita loops infinitossi algo falla en el backend

// /api/admin/horarios/inscritos/agrupados/aprobados-reprobados ahora está
// protegida con auth:sanctum. Antes este fetch no mandaba ningún header,
// así que devolvía 401 siempre.
function authHeaders(extra = {}) {
    const token = localStorage.getItem('token')
    return {
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
        ...extra,
    }
}

export function useResumenPorGrupo() {
    const data = ref([])
    const loading = ref(false)
    const error = ref(null)
    const meta = ref({ total: 0, total_pages: 0 })

    /**
     * Trae TODAS las páginas del resumen por grupo para año/periodo dados.
     * @param {number} anio
     * @param {number} periodo
     * @param {object} filtros opcionales: { plan, materia, grupo, nivel }
     */
    async function fetchResumenPorGrupo(anio, periodo, filtros = {}) {
        loading.value = true
        error.value = null
        data.value = []

        try {
            const perPage = 500
            let page = 1
            let totalPages = 1
            const acumulado = []

            do {
                const params = new URLSearchParams({
                    anio,
                    periodo,
                    page,
                    per_page: perPage,
                    ...filtros,
                })

                const res = await fetch(
                    `${BASE_URL}/api/admin/horarios/inscritos/agrupados/aprobados-reprobados?${params.toString()}`,
                    { headers: authHeaders() }
                )
                if (!res.ok) throw new Error(`Error ${res.status}: ${res.statusText}`)

                const json = await res.json()
                if (!json.success) throw new Error(json.message ?? 'Respuesta no exitosa del servidor.')

                acumulado.push(...(json.data ?? []))
                totalPages = json.total_pages ?? 1
                meta.value = { total: json.total ?? 0, total_pages: totalPages }

                page++
            } while (page <= totalPages && page <= MAX_PAGINAS_SEGURIDAD)

            data.value = acumulado
        } catch (e) {
            error.value = e.message
            data.value = []
        } finally {
            loading.value = false
        }
    }

    return { data, loading, error, meta, fetchResumenPorGrupo }
}