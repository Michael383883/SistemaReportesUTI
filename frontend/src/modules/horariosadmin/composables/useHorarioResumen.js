// src/modules/horariosadmin/composables/useHorarioResumen.js

import { ref } from 'vue'
import axios from 'axios'

const API_BASE = import.meta.env.VITE_API_URL || '/api'

// /api/admin/horarios/resumen/* ahora está protegido con auth:sanctum.
// Antes estas llamadas no mandaban ningún header y devolvían 401 siempre.
function authHeaders(extra = {}) {
    const token = localStorage.getItem('token')
    return {
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
        ...extra,
    }
}

export function useHorarioResumen() {
    const docentes = ref([])
    const docenteSeleccionado = ref(null)
    const loading = ref(false)
    const error = ref(null)

    async function cargarTodos(anio, periodo) {
        loading.value = true
        error.value = null

        try {
            const response = await axios.get(
                `${API_BASE}/api/admin/horarios/resumen/listado`,
                {
                    params: {
                        anio,
                        periodo,
                    },
                    headers: authHeaders(),
                }
            )

            const datos = response?.data?.data

            docentes.value = Array.isArray(datos)
                ? datos
                : []

            if (docentes.value.length === 0) {
                error.value = `Horarios no disponibles para la gestión ${anio} período ${periodo}`
            }

        } catch (e) {
            docentes.value = []

            error.value =
                e?.response?.data?.message ||
                'Error al cargar resumen'
        } finally {
            loading.value = false
        }
    }

    async function cargarDocente(codigoDocente, anio, periodo) {
        loading.value = true
        error.value = null
        docenteSeleccionado.value = null

        try {
            const response = await axios.get(
                `${API_BASE}/api/admin/horarios/resumen/docente/${codigoDocente}`,
                {
                    params: {
                        anio,
                        periodo,
                    },
                    headers: authHeaders(),
                }
            )

            const datos = response?.data?.data

            if (Array.isArray(datos) && datos.length > 0) {
                docenteSeleccionado.value = datos[0]
                docentes.value = datos
            } else {
                docenteSeleccionado.value = null
                docentes.value = []
                error.value = `No se encontraron horariospara el docente en la gestión ${anio} período ${periodo}`
            }

        } catch (e) {
            docentes.value = []
            docenteSeleccionado.value = null

            error.value =
                e?.response?.data?.message ||
                'Docente no encontrado'
        } finally {
            loading.value = false
        }
    }

    const COLORES_CARRERA = {
        ADM: { bg: '#dbeafe', text: '#1e40af', border: '#93c5fd' },
        ECO: { bg: '#dcfce7', text: '#166534', border: '#86efac' },
        CCP: { bg: '#fef9c3', text: '#854d0e', border: '#fde047' },
        COM: { bg: '#fce7f3', text: '#9d174d', border: '#f9a8d4' },
        FIN: { bg: '#ede9fe', text: '#5b21b6', border: '#c4b5fd' },
        NN: { bg: '#f3f4f6', text: '#374151', border: '#d1d5db' },
    }

    function colorCarrera(carrera) {
        return COLORES_CARRERA[carrera] || COLORES_CARRERA.NN
    }

    return {
        docentes,
        docenteSeleccionado,
        loading,
        error,
        cargarTodos,
        cargarDocente,
        colorCarrera,
    }
}