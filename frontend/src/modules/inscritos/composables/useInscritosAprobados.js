// composables/useInscritosAprobados.js
// Fetch de datos de INSCRITOS / APROBADOS / REPROBADOS agrupados por
// docente y carrera. Misma forma que useInscritos.js, pero cada carrera
// trae ademas subtotal_aprobados y subtotal_reprobados.
//
// Forma esperada de la respuesta del backend:
// {
//   success: true,
//   anio, periodo, total_docentes,
//   data: [
//     {
//       cod_docente: '1234',
//       apellidos: 'PEREZ',
//       nombres: 'JUAN',
//       carreras: [
//         { carrera: 'ECO', subtotal_inscritos: 57, subtotal_aprobados: 7, subtotal_reprobados: 18 },
//         ...
//       ],
//       total_inscritos: 100,
//       total_aprobados: 40,
//       total_reprobados: 30,
//     },
//     ...
//   ]
// }
//
// Ajusta la URL de fetchAprobadosReprobados si tu ruta real es distinta.

import { ref } from 'vue'

const BASE_URL = import.meta.env.VITE_API_URL ?? ''

export function useInscritosAprobados() {
    const data = ref([])
    const loading = ref(false)
    const error = ref(null)
    const meta = ref({ anio: null, periodo: null, total_docentes: 0 })

    /**
     * Carga todos los docentes con sus totales de aprobados/reprobados.
     * @param {number} anio
     * @param {number} periodo
     */
    async function fetchAprobadosReprobados(anio, periodo) {
        loading.value = true
        error.value = null
        try {
            const res = await fetch(
                `${BASE_URL}/api/admin/horarios/inscritos/aprobados-reprobados?anio=${anio}&periodo=${periodo}`
            )
            if (!res.ok) throw new Error(`Error ${res.status}: ${res.statusText}`)
            const json = await res.json()
            data.value = json.data ?? []
            meta.value = {
                anio: json.anio,
                periodo: json.periodo,
                total_docentes: json.total_docentes,
            }
        } catch (e) {
            error.value = e.message
            data.value = []
        } finally {
            loading.value = false
        }
    }

    return { data, loading, error, meta, fetchAprobadosReprobados }
}