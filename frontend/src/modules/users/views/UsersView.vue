<template>
  <div>
    <!-- Header -->
    <div class="flex items-start justify-between mb-5">
      <div>
        <h1 class="text-[16px] font-medium text-navy">Usuarios del sistema</h1>
        <p class="text-[13px] text-gray-400 mt-0.5">Gestión de accesos por rol</p>
      </div>
      <button
        @click="openCreate"
        class="flex items-center gap-1.5 px-3 py-2 bg-navy text-white text-[13px] rounded-lg hover:bg-[#051828] transition-colors"
      >
        <Plus class="w-3.5 h-3.5" /> Nuevo usuario
      </button>
    </div>

    <!-- Error global del store -->
    <transition name="fade">
      <div
        v-if="usersStore.error"
        class="flex items-center gap-2 bg-red-50 border border-red-200 rounded-lg px-3 py-2 mb-4 text-[13px] text-red-700"
      >
        <AlertCircle class="w-3.5 h-3.5 shrink-0" />
        {{ usersStore.error }}
        <button class="ml-auto" @click="usersStore.clearError()">
          <X class="w-3 h-3" />
        </button>
      </div>
    </transition>

    <!-- Tabla -->
    <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
      <!-- Loading inicial -->
      <div v-if="usersStore.loading && !usersStore.users.length" class="py-12 text-center">
        <Loader2 class="w-5 h-5 animate-spin mx-auto mb-2 text-gray-300" />
        <p class="text-[13px] text-gray-400">Cargando usuarios...</p>
      </div>

      <table v-else class="w-full text-[13px]">
        <thead>
          <tr class="border-b border-gray-50">
            <th class="px-4 py-3 text-left text-[11px] text-gray-400 font-semibold tracking-widest uppercase">Nombre</th>
            <th class="py-3 text-left text-[11px] text-gray-400 font-semibold tracking-widest uppercase">Correo</th>
            <th class="py-3 text-left text-[11px] text-gray-400 font-semibold tracking-widest uppercase">Rol</th>
            <th class="py-3 text-left text-[11px] text-gray-400 font-semibold tracking-widest uppercase">Estado</th>
            <th class="py-3 pr-4 text-right text-[11px] text-gray-400 font-semibold tracking-widest uppercase">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="u in usersStore.users"
            :key="u.id"
            class="border-b border-gray-50 last:border-0 hover:bg-gray-50/50 transition-colors"
          >
            <td class="px-4 py-3 text-gray-700 font-medium">{{ u.name }}</td>
            <td class="py-3 text-gray-500">{{ u.email }}</td>
            <td class="py-3">
              <span
                class="px-2 py-0.5 rounded-full text-[12px] font-medium"
                :class="getRoleBadgeClass(u.role)"
              >
                {{ getRoleLabel(u.role) }}
              </span>
            </td>
            <td class="py-3">
              <span
                class="px-2 py-0.5 rounded-full text-[12px] font-medium"
                :class="u.active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500'"
              >
                {{ u.active ? 'Activo' : 'Inactivo' }}
              </span>
            </td>
            <td class="py-3 pr-4 text-right">
              <button
                @click="openEdit(u)"
                class="text-gray-400 hover:text-navy mr-3 transition-colors"
                title="Editar"
              >
                <Pencil class="w-3.5 h-3.5 inline" />
              </button>
              <button
                @click="openDelete(u)"
                class="text-gray-400 hover:text-red-600 transition-colors"
                title="Eliminar"
              >
                <Trash2 class="w-3.5 h-3.5 inline" />
              </button>
            </td>
          </tr>

          <tr v-if="!usersStore.users.length && !usersStore.loading">
            <td colspan="5" class="px-4 py-10 text-center text-[13px] text-gray-400">
              No hay usuarios registrados.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal crear / editar -->
    <UserFormModal
      v-model="showForm"
      :user="editingUser"
      :loading="usersStore.loading"
      @submit="handleFormSubmit"
    />

    <!-- Modal confirmar eliminar -->
    <DeleteConfirmModal
      v-model="showDelete"
      :user-name="deletingUser?.name ?? ''"
      :loading="usersStore.loading"
      @confirm="handleDelete"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Plus, AlertCircle, X, Pencil, Trash2, Loader2 } from 'lucide-vue-next'
import { useUsersStore } from '../store/usersStore'
import { getRoleLabel, getRoleBadgeClass } from '@/shared/utils/helpers'
import { useNotify } from '@/shared/composables/useNotify'
import UserFormModal      from '../components/UserFormModal.vue'
import DeleteConfirmModal from '../components/DeleteConfirmModal.vue'

const usersStore   = useUsersStore()
const notify       = useNotify()

const showForm     = ref(false)
const showDelete   = ref(false)
const editingUser  = ref(null)
const deletingUser = ref(null)

onMounted(() => usersStore.fetchUsers())

function openCreate() {
  editingUser.value = null
  showForm.value    = true
}

function openEdit(user) {
  editingUser.value = user
  showForm.value    = true
}

function openDelete(user) {
  deletingUser.value = user
  showDelete.value   = true
}

async function handleFormSubmit(payload) {
  try {
    if (editingUser.value) {
      await usersStore.updateUser(editingUser.value.id, payload)
      notify.success('Usuario actualizado correctamente')
    } else {
      await usersStore.createUser(payload)
      notify.success('Usuario registrado correctamente')
    }
    showForm.value = false
  } catch {
    // El error ya está en usersStore.error
  }
}

async function handleDelete() {
  if (!deletingUser.value) return
  try {
    await usersStore.deleteUser(deletingUser.value.id)
    notify.success('Usuario eliminado')
    showDelete.value   = false
    deletingUser.value = null
  } catch {
    notify.error('Error al eliminar el usuario')
  }
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to       { opacity: 0; }
</style>