import { defineStore } from 'pinia'
import { ref } from 'vue'
import { usersService } from '../services/usersService'

export const useUsersStore = defineStore('users', () => {
  const users   = ref([])
  const loading = ref(false)
  const error   = ref(null)

  async function fetchUsers() {
    loading.value = true
    error.value   = null
    try {
      const data    = await usersService.getAll()
      users.value   = data.data ?? data
    } catch (err) {
      error.value = err.response?.data?.message ?? 'Error al cargar usuarios'
    } finally {
      loading.value = false
    }
  }

  async function createUser(payload) {
    loading.value = true
    error.value   = null
    try {
      const data = await usersService.create(payload)
      users.value.unshift(data.data ?? data)
      return true
    } catch (err) {
      error.value = err.response?.data?.message ?? 'Error al crear usuario'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function updateUser(id, payload) {
    loading.value = true
    error.value   = null
    try {
      const data    = await usersService.update(id, payload)
      const updated = data.data ?? data
      const idx     = users.value.findIndex((u) => u.id === id)
      if (idx !== -1) users.value[idx] = updated
      return true
    } catch (err) {
      error.value = err.response?.data?.message ?? 'Error al actualizar usuario'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function deleteUser(id) {
    loading.value = true
    error.value   = null
    try {
      await usersService.remove(id)
      users.value = users.value.filter((u) => u.id !== id)
      return true
    } catch (err) {
      error.value = err.response?.data?.message ?? 'Error al eliminar usuario'
      throw err
    } finally {
      loading.value = false
    }
  }

  function clearError() { error.value = null }

  return {
    users, loading, error,
    fetchUsers, createUser, updateUser, deleteUser, clearError,
  }
})
