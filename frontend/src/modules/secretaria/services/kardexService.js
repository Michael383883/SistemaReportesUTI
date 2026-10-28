import axios from 'axios'

const API_BASE = import.meta.env.VITE_API_URL

const headers = () => ({
    Authorization: `Bearer ${localStorage.getItem('token')}`,
})

export const kardexService = {
    // ¿El módulo Kardex está habilitado para mi rol?
    estado: () =>
        axios
            .get(`${API_BASE}/api/kardex-secretaria/estado`, { headers: headers() })
            .then((r) => r.data),

    // Lista liviana para el buscador: [{ codigo, apellidos, nombres }]
    docentes: () =>
        axios
            .get(`${API_BASE}/api/kardex-secretaria/docentes`, { headers: headers() })
            .then((r) => r.data?.data ?? []),

    // Kardex del docente (sin filtros, sin resolución ni designación)
    reporte: (codigo) =>
        axios
            .get(`${API_BASE}/api/kardex-secretaria/docentes/${codigo}/reporte`, { headers: headers() })
            .then((r) => r.data),
}
