import { ref, computed } from 'vue'
import axios from 'axios'

//const API_BASE = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'
const API_BASE = import.meta.env.VITE_API_URL || '/api'

export function useDocentes() {
    const docentes = ref([])
    const loading = ref(false)
    const error = ref(null)
    const searchQuery = ref('')
    const dropdownOpen = ref(false)

    const fetchDocentes = async () => {
        loading.value = true
        error.value = null
        try {
            const token = localStorage.getItem('token')
            const response = await axios.get(`${API_BASE}/api/docentes`, {
                headers: { Authorization: `Bearer ${token}` },
            })
            // Soporta { data: [...] } o array directo
            docentes.value = response.data?.data ?? response.data
        } catch (err) {
            error.value = err.response?.data?.message || 'Error al cargar docentes'
        } finally {
            loading.value = false
        }
    }


    const filteredDocentes = computed(() => {
        const q = searchQuery.value.trim().toLowerCase()
        if (!q) return docentes.value
        return docentes.value.filter(d =>
            d.nombres?.toLowerCase().includes(q) ||
            d.apellidos?.toLowerCase().includes(q) ||
            `${d.nombres} ${d.apellidos}`.toLowerCase().includes(q) ||
            String(d.codigo).includes(q)   // ← sis es número, lo convertimos
        )
    })

    const selectedDocente = ref(null)

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