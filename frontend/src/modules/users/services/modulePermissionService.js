import axios from 'axios'

const API_BASE = import.meta.env.VITE_API_URL

const headers = () => ({
    Authorization: `Bearer ${localStorage.getItem('token')}`,
})

export const modulePermissionService = {
    listar: () =>
        axios
            .get(`${API_BASE}/api/admin/module-permissions`, { headers: headers() })
            .then((r) => r.data?.modulos ?? []),

    actualizar: ({ module, role, enabled }) =>
        axios
            .put(`${API_BASE}/api/admin/module-permissions`, { module, role, enabled }, { headers: headers() })
            .then((r) => r.data),
}
