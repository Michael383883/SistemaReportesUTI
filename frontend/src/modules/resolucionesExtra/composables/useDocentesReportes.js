// useDocentesReportes.js
import { ref, computed } from 'vue'
import axios from 'axios'

const API_BASE = import.meta.env.VITE_API_URL ?? ''

export function useDocentesReportes() {
    const docentes = ref([])
    const loading = ref(false)
    const error = ref(null)
    const searchQuery = ref('')
    const dropdownOpen = ref(false)
    const selectedDocente = ref(null)

    const normalize = (d) => {
        if (!d || typeof d !== 'object') return d
        return {
            id: d.id ?? d.ID ?? d.id_docente ?? d.ID_DOCENTE ?? null,
            nombres: d.nombres ?? d.NOMBRES ?? d.Nombres ?? '',
            apellidos: d.apellidos ?? d.APELLIDOS ?? d.Apellidos ?? '',
            codigo: d.codigo ?? d.CODIGO ?? d.cod ?? '',
        }
    }

    const fetchDocentes = async () => {
        loading.value = true
        error.value = null
        try {
            const token = localStorage.getItem('token')

            const response = await axios.get(`${API_BASE}/api/docentes`, {
                headers: { Authorization: `Bearer ${token}` },
            })

            // Soporta { data: [...] } o array directo
            const raw = response.data?.data ?? response.data
            docentes.value = Array.isArray(raw) ? raw.map(normalize) : []
        } catch (err) {
            error.value = err.response?.data?.message ?? 'Error al cargar docentes'
        } finally {
            loading.value = false
        }
    }

    const filteredDocentes = computed(() => {
        const q = searchQuery.value.trim().toLowerCase()
        if (!q) return docentes.value

        return docentes.value.filter((d) => {
            const fullName = `${d.nombres} ${d.apellidos}`.toLowerCase()
            return (
                d.nombres.toLowerCase().includes(q) ||
                d.apellidos.toLowerCase().includes(q) ||
                fullName.includes(q) ||
                String(d.codigo).includes(q)
            )
        })
    })

    const selectDocente = (docente) => {
        selectedDocente.value = docente
        searchQuery.value = `${docente.nombres} ${docente.apellidos}`
        dropdownOpen.value = false
    }

    const clearSelection = () => {
        selectedDocente.value = null
        searchQuery.value = ''
    }

    return {
        docentes,
        loading,
        error,
        searchQuery,
        dropdownOpen,
        filteredDocentes,
        selectedDocente,
        fetchDocentes,
        selectDocente,
        clearSelection,
    }
}