import api from '@/shared/services/api'

export const usersService = {
  async getAll(params = {}) {
    const { data } = await api.get('api/users', { params })
    return data
  },

  async getById(id) {
    const { data } = await api.get(`api/users/${id}`)
    return data
  },

  async create(payload) {
    const { data } = await api.post('api/users', payload)
    return data
  },

  async update(id, payload) {
    const { data } = await api.put(`api/users/${id}`, payload)
    return data
  },

  async remove(id) {
    await api.delete(`api/users/${id}`)
  },
}
