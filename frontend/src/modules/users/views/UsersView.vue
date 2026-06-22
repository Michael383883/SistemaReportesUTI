<template>
  <div>
    <!-- Header -->
    <div class="flex items-start justify-between mb-5">
     <div class="flex items-start justify-between mb-3">
      <h1 class="text-xl font-bold text-black-400 tracking-tight m-0 mb-0.5">
        Gestion de usuarios
        <p class="text-[12px] text-slate-500 mt-0.5">Control de accesos por rol</p>
      </h1>
      
      </div>
      <button
        @click="openCreate"
         class="
                inline-flex items-center gap-2 px-4 py-2 rounded-lg text-[14px] font-semibold
                bg-amber-500 hover:bg-amber-400 active:bg-amber-600
                text-slate-900 transition-all duration-150 cursor-pointer border-none
                shadow-lg shadow-amber-500/20
              "
      >
        <Plus class="w-3.5 h-3.5" /> Nuevo usuario
      </button>
    </div>

    <!-- Error -->
    <transition
      enter-active-class="transition-opacity duration-200"
      leave-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      leave-to-class="opacity-0"
    >
      <div
        v-if="usersStore.error"
        class="flex items-center gap-2 bg-red-500/10 border border-red-500/20 rounded-lg px-3 py-2 mb-4 text-[12px] text-red-400"
      >
        <AlertCircle class="w-3.5 h-3.5 shrink-0" />
        {{ usersStore.error }}
        <button class="ml-auto" @click="usersStore.clearError()">
          <X class="w-3 h-3" />
        </button>
      </div>
    </transition>

    <!-- Tabla -->
    <div class="rounded-xl border border-slate-700 bg-slate-800 overflow-hidden">
      <div class="overflow-x-auto">

        <!-- Loading -->
        <div v-if="usersStore.loading && !usersStore.users.length" class="py-12 text-center">
          <Loader2 class="w-5 h-5 animate-spin mx-auto mb-2 text-slate-600" />
          <p class="text-[12px] text-slate-500">Cargando usuarios...</p>
        </div>

        <table v-else class="w-full text-[13px] border-collapse">
          <thead>
            <tr class="border-b border-slate-700 bg-slate-900/60">
              <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400">Nombre</th>
              <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400">Correo</th>
              <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400">Rol</th>
              <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400">Estado</th>
              <th class="text-right px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(u, i) in usersStore.users"
              :key="u.id"
              class="border-b border-slate-700/60 transition-colors hover:bg-white/[0.025]"
              :class="i % 2 === 0 ? 'bg-transparent' : 'bg-slate-900/20'"
            >
              <td class="px-4 py-3 text-slate-200 font-medium">{{ u.name }}</td>
              <td class="px-4 py-3 text-slate-400">{{ u.email }}</td>
              
              <td class="px-4 py-3">
                <span
                  class="px-2 py-0.5 rounded text-[0.68rem] font-semibold"
                  :class="u.active
                    ? 'bg-emerald-500/15 text-emerald-400'
                    : 'bg-slate-700/60 text-slate-400'"
                >
                  {{ u.active ? 'Activo' : 'Inactivo' }}
                </span>
              </td>
              <td class="px-4 py-3 text-right">
                <button
                  @click="openEdit(u)"
                  class="inline-flex items-center gap-1 px-2 py-1 rounded text-[0.68rem] font-medium
                         bg-blue-500/10 text-blue-400 hover:bg-blue-500/20 transition-colors mr-1.5"
                  title="Editar"
                >
                  <Pencil class="w-3 h-3" />
                </button>
                <button
                  @click="openDelete(u)"
                  class="inline-flex items-center gap-1 px-2 py-1 rounded text-[0.68rem] font-medium
                         bg-red-500/10 text-red-400 hover:bg-red-500/20 transition-colors"
                  title="Eliminar"
                >
                  <Trash2 class="w-3 h-3" />
                </button>
              </td>
            </tr>

            <tr v-if="!usersStore.users.length && !usersStore.loading">
              <td colspan="5" class="px-4 py-10 text-center text-[12px] text-slate-500">
                No hay usuarios registrados.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Footer -->
      <div class="px-4 py-2.5 border-t border-slate-700 bg-slate-900/30 text-xs text-slate-500 text-right">
        {{ usersStore.users.length }} registro{{ usersStore.users.length !== 1 ? 's' : '' }}
      </div>
    </div>

    <!-- Modales -->
    <UserFormModal
      v-model="showForm"
      :user="editingUser"
      :loading="usersStore.loading"
      @submit="handleFormSubmit"
    />
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

function openCreate() { editingUser.value = null; showForm.value = true }
function openEdit(u)  { editingUser.value = u;    showForm.value = true }
function openDelete(u){ deletingUser.value = u;   showDelete.value = true }

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
  } catch { /* el error ya está en usersStore.error */ }
}

async function handleDelete() {
  if (!deletingUser.value) return
  try {
    await usersStore.deleteUser(deletingUser.value.id)
    notify.success('Usuario eliminado')
    showDelete.value   = false
    deletingUser.value = null
  } catch { notify.error('Error al eliminar el usuario') }
}
</script>