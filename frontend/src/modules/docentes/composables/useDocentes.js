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
            const raw = response.data?.data ?? response.data

            const normalize = (d) => {
                if (!d || typeof d !== 'object') return d
                const out = { ...d }

                // nombres
                out.nombres = d.nombres ?? d.NOMBRES ?? d.Nombres ?? ''
                out.NOMBRES = d.NOMBRES ?? d.nombres ?? d.Nombres ?? out.nombres

                // apellidos
                out.apellidos = d.apellidos ?? d.APELLIDOS ?? d.Apellidos ?? ''
                out.APELLIDOS = d.APELLIDOS ?? d.apellidos ?? d.Apellidos ?? out.apellidos

                // codigo / CODIGO
                out.codigo = d.codigo ?? d.CODIGO ?? d.cod ?? ''
                out.CODIGO = d.CODIGO ?? d.codigo ?? out.codigo

                // id variants
                out.id = d.id ?? d.ID ?? d.id_docente ?? d.ID_DOCENTE ?? null
                out.ID = d.ID ?? d.id ?? d.ID_DOCENTE ?? d.id_docente ?? out.id

                // cod_docente
                out.cod_docente = d.cod_docente ?? d.COD_DOCENTE ?? out.codigo ?? out.id
                out.COD_DOCENTE = d.COD_DOCENTE ?? d.cod_docente ?? out.cod_docente

                // other common fields
                out.departamento = d.departamento ?? d.DEPARTAMENTO ?? d.depart ?? ''
                out.DEPARTAMENTO = d.DEPARTAMENTO ?? d.departamento ?? out.departamento

                out.categoria = d.categoria ?? d.CATEGORIA ?? ''
                out.CATEGORIA = d.CATEGORIA ?? d.categoria ?? out.categoria

                out.email = d.email ?? d.EMAIL ?? ''
                out.EMAIL = d.EMAIL ?? d.email ?? out.email

                out.telefono = d.telefono ?? d.TELEFONO ?? ''
                out.TELEFONO = d.TELEFONO ?? d.telefono ?? out.telefono

                return out
            }

            docentes.value = Array.isArray(raw) ? raw.map(normalize) : raw
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