// src/modules/horariosadmin/composables/useHorarioAdmin.js

import { ref } from 'vue'
import axios from 'axios'

const API_BASE = import.meta.env.VITE_API_URL || '/api'

export function useHorarioAdmin() {
    const docentes = ref([])
    const docenteSeleccionado = ref(null)
    const loading = ref(false)
    const error = ref(null)

    // ← Ya NO hay anio ni periodo aquí

    async function cargarTodos(anio, periodo) {   // ← recibe como parámetro
        loading.value = true
        error.value = null

        try {
            const response = await axios.get(
                `${API_BASE}/api/admin/horarios`,
                {
                    params: { anio, periodo },
                }
            )

            const datos = response?.data?.data
            docentes.value = Array.isArray(datos) ? datos : []

            if (docentes.value.length === 0) {
                error.value = `Horarios no disponibles para la gestión ${anio} período ${periodo}`
            }

        } catch (e) {
            console.error('Error cargarTodos:', e)
            docentes.value = []
            error.value = e?.response?.data?.message || 'Error al cargar horarios'
        } finally {
            loading.value = false
        }
    }

    async function cargarDocente(codigoDocente, anio, periodo) {  // ← recibe como parámetro
        loading.value = true
        error.value = null
        docenteSeleccionado.value = null

        try {
            const response = await axios.get(
                `${API_BASE}/api/admin/horarios/${codigoDocente}`,
                {
                    params: { anio, periodo },
                }
            )

            const datos = response?.data?.data

            if (Array.isArray(datos) && datos.length > 0) {
                docenteSeleccionado.value = datos[0]
                docentes.value = datos
            } else {
                docenteSeleccionado.value = null
                docentes.value = []
                error.value = `No se encontraron horarios para el docente en la gestión ${anio} período ${periodo}`
            }

        } catch (e) {
            console.error('Error cargarDocente:', e)
            docentes.value = []
            docenteSeleccionado.value = null
            error.value = e?.response?.data?.message || 'Docente no encontrado'
        } finally {
            loading.value = false
        }
    }

    const COLORES_CARRERA = {
        ADM: {
            bg: '#dbeafe',
            text: '#1e40af',
            border: '#93c5fd',
        },
        ECO: {
            bg: '#dcfce7',
            text: '#166534',
            border: '#86efac',
        },
        CCP: {
            bg: '#fef9c3',
            text: '#854d0e',
            border: '#fde047',
        },
        COM: {
            bg: '#fce7f3',
            text: '#9d174d',
            border: '#f9a8d4',
        },
        FIN: {
            bg: '#ede9fe',
            text: '#5b21b6',
            border: '#c4b5fd',
        },
        NN: {
            bg: '#f3f4f6',
            text: '#374151',
            border: '#d1d5db',
        },
    }

    function colorCarrera(carrera) {
        return COLORES_CARRERA[carrera] || COLORES_CARRERA.NN
    }

    // ── Días de la semana (códigos de 2 letras) ──────────────────────────────
    const DIAS_ORDEN = ['LU', 'MA', 'MI', 'JU', 'VI', 'SA']

    const DIAS_LABEL = {
        LU: 'Lunes',
        MA: 'Martes',
        MI: 'Miércoles',
        JU: 'Jueves',
        VI: 'Viernes',
        SA: 'Sábado',
    }

    // 🔥 MAPA DE ABREVIATURAS - Para normalizar cualquier formato de día
    const ABREV_DIA_MAP = {
        // Nombres completos en español
        'lunes': 'LU',
        'martes': 'MA',
        'miercoles': 'MI',
        'jueves': 'JU',
        'viernes': 'VI',
        'sabado': 'SA',
        'domingo': 'DO',
        // Con mayúscula inicial
        'Lunes': 'LU',
        'Martes': 'MA',
        'Miercoles': 'MI',
        'Jueves': 'JU',
        'Viernes': 'VI',
        'Sabado': 'SA',
        'Domingo': 'DO',
        // Códigos de 2 letras (ya vienen así)
        'LU': 'LU',
        'MA': 'MA',
        'MI': 'MI',
        'JU': 'JU',
        'VI': 'VI',
        'SA': 'SA',
        'DO': 'DO',
        // Códigos de 3 letras (posible formato alternativo)
        'LUN': 'LU',
        'MAR': 'MA',
        'MIE': 'MI',
        'JUE': 'JU',
        'VIE': 'VI',
        'SAB': 'SA',
        'DOM': 'DO',
        // En inglés
        'monday': 'LU',
        'tuesday': 'MA',
        'wednesday': 'MI',
        'thursday': 'JU',
        'friday': 'VI',
        'saturday': 'SA',
        'sunday': 'DO',
        'Monday': 'LU',
        'Tuesday': 'MA',
        'Wednesday': 'MI',
        'Thursday': 'JU',
        'Friday': 'VI',
        'Saturday': 'SA',
        'Sunday': 'DO',
        // Códigos de 3 letras en inglés
        'MON': 'LU',
        'TUE': 'MA',
        'WED': 'MI',
        'THU': 'JU',
        'FRI': 'VI',
        'SAT': 'SA',
        'SUN': 'DO',
    }

    // 🔥 Función para normalizar/abreviar un día a código de 2 letras
    function abreviaturaDia(dia) {
        if (!dia) return '??'
        const key = String(dia).trim()
        // Buscar en el mapa (case insensitive)
        const lowerKey = key.toLowerCase()
        for (const [k, v] of Object.entries(ABREV_DIA_MAP)) {
            if (k.toLowerCase() === lowerKey) {
                return v
            }
        }
        // Fallback: tomar primeras 2 letras en mayúscula
        return key.slice(0, 2).toUpperCase()
    }

    function agruparPorMateriaGrupo(horarios = []) {
        const mapa = new Map()

        for (const h of horarios) {
            const key = `${h.MATERIA}-${h.GRUPO}-${h.PLAN}`

            if (!mapa.has(key)) {
                mapa.set(key, {
                    materia: h.MATERIA,
                    nombre: h.NOMBRE,
                    grupo: h.GRUPO,
                    carrera: h.CARRERA,
                    plan: h.PLAN,
                    nivel: h.NIVEL,
                    compartido: h.COMPARTIDO,
                    comp: h.COMP,
                    sesiones: [],
                    inscritos: h.TOTAL_NORMAL ?? 0,
                    carga: 0,
                })
            }

            const item = mapa.get(key)

            // 🔥 Normalizar el día antes de guardarlo
            const diaNormalizado = abreviaturaDia(h.DIA)

            item.sesiones.push({
                dia: diaNormalizado,  // 🔥 Guardar el día normalizado (LU, MA, MI, etc.)
                horario: h.HORARIO,
                ambiente: h.AMBIENTE,
                ch: Number(h.CARGA_HORARIA || 0),
            })

            item.carga += Number(h.CARGA_HORARIA || 0)
        }

        return Array.from(mapa.values())
    }

    return {
        docentes,
        docenteSeleccionado,
        loading,
        error,
        cargarTodos,
        cargarDocente,
        colorCarrera,
        agruparPorMateriaGrupo,
        DIAS_LABEL,
        DIAS_ORDEN,
        abreviaturaDia,  // 🔥 Exportar la función para usarla en otros archivos
    }
}