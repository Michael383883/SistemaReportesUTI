import { ref, computed } from 'vue'
import axios from 'axios'


//const API_BASE = import.meta.env.VITE_API_URL || '/api'

const API_BASE = import.meta.env.VITE_API_URL

export function useDocentes() {
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
            codDocente: d.cod_docente ?? d.COD_DOCENTE ?? d.codigo ?? d.CODIGO ?? '',
            departamento: d.departamento ?? d.DEPARTAMENTO ?? d.depart ?? '',
            categoria: d.categoria ?? d.CATEGORIA ?? '',
            email: d.email ?? d.EMAIL ?? '',
            telefono: d.telefono ?? d.TELEFONO ?? '',
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

    // Prioridad de relevancia: apellido primero, luego nombre, luego código.
    // 0 = apellido empieza con el texto buscado (lo más relevante)
    // 1 = nombre empieza con el texto buscado
    // 2 = apellido contiene el texto buscado
    // 3 = nombre contiene el texto buscado
    // 4 = código contiene el texto buscado
    const relevancia = (d, q) => {
        const apellido = d.apellidos.toLowerCase()
        const nombre = d.nombres.toLowerCase()
        const codigo = String(d.codigo).toLowerCase()

        if (apellido.startsWith(q)) return 0
        if (nombre.startsWith(q)) return 1
        if (apellido.includes(q)) return 2
        if (nombre.includes(q)) return 3
        if (codigo.includes(q)) return 4
        return 5
    }

    const filteredDocentes = computed(() => {
        const q = searchQuery.value.trim().toLowerCase()
        if (!q) return docentes.value

        const filtrados = docentes.value.filter((d) => {
            const fullName = `${d.nombres} ${d.apellidos}`.toLowerCase()
            return (
                d.nombres.toLowerCase().includes(q) ||
                d.apellidos.toLowerCase().includes(q) ||
                fullName.includes(q) ||
                String(d.codigo).includes(q)
            )
        })

        return filtrados.sort((a, b) => relevancia(a, q) - relevancia(b, q))
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