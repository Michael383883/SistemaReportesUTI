<template>
  <div class="p-6 max-w-4xl">

    <!-- Header -->
    <div class="mb-6">
      <h1 class="text-xl font-bold text-black-400 tracking-tight m-0 mb-0.5">Mi perfil</h1>
      <p class="text-[12px] text-slate-500">Información de la cuenta y seguridad</p>
    </div>

    <!-- Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

      <!-- Card: Datos de la cuenta -->
      <div class="bg-slate-800 border border-slate-700 rounded-xl p-6">
        <h3 class="text-[0.68rem] font-semibold text-slate-500 uppercase tracking-widest mb-5">
          Datos de la cuenta
        </h3>
        <div class="flex flex-col">
          <div class="flex justify-between items-center py-3 border-b border-slate-900/70">
            <span class="text-[13px] text-slate-500">Nombre</span>
            <span class="text-[13px] text-slate-200 font-medium">{{ authStore.user?.name ?? '—' }}</span>
          </div>
          <div class="flex justify-between items-center py-3 border-b border-slate-900/70">
            <span class="text-[13px] text-slate-500">Correo</span>
            <span class="text-[13px] text-slate-200 font-medium">{{ authStore.user?.email ?? '—' }}</span>
          </div>
          <div class="flex justify-between items-center py-3">
            <span class="text-[13px] text-slate-500">Rol</span>
            <span class="bg-violet-500/15 text-violet-300 px-2.5 py-0.5 rounded-full text-[11px] font-semibold">
              {{ roleLabel }}
            </span>
          </div>
        </div>
      </div>

      <!-- Card: Cambiar contraseña -->
      <div class="bg-slate-800 border border-slate-700 rounded-xl p-6">
        <h3 class="text-[0.68rem] font-semibold text-slate-500 uppercase tracking-widest mb-5">
          Cambiar contraseña
        </h3>

        <form @submit.prevent="handleChangePassword">

          <!-- Campo con ojito -->
          <div class="mb-4">
            <label for="currentPassword" class="block text-[12px] text-slate-400 mb-1">
              Contraseña actual
            </label>
            <div class="relative">
              <input
                id="currentPassword"
                v-model="form.currentPassword"
                :type="show.current ? 'text' : 'password'"
                autocomplete="current-password"
                class="w-full px-3 py-2 pr-9 rounded-md bg-slate-900/60 border text-[13px] text-slate-200 placeholder-slate-600
                       focus:outline-none focus:ring-2 focus:ring-indigo-500/30 transition-colors"
                :class="errors.currentPassword ? 'border-red-500/60' : 'border-slate-700 focus:border-indigo-500'"
                @input="clearFieldError('currentPassword')"
              />
              <button
                v-if="form.currentPassword"
                type="button"
                @click="show.current = !show.current"
                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300 transition-colors"
                :aria-label="show.current ? 'Ocultar contraseña' : 'Ver contraseña'"
              >
                <EyeOff v-if="show.current" class="w-3.5 h-3.5" />
                <Eye v-else class="w-3.5 h-3.5" />
              </button>
            </div>
            <span v-if="errors.currentPassword" class="block text-[11px] text-red-400 mt-1">
              {{ errors.currentPassword }}
            </span>
          </div>

          <div class="mb-4">
            <label for="newPassword" class="block text-[12px] text-slate-400 mb-1">
              Nueva contraseña
            </label>
            <div class="relative">
              <input
                id="newPassword"
                v-model="form.newPassword"
                :type="show.new ? 'text' : 'password'"
                autocomplete="new-password"
                class="w-full px-3 py-2 pr-9 rounded-md bg-slate-900/60 border text-[13px] text-slate-200 placeholder-slate-600
                       focus:outline-none focus:ring-2 focus:ring-indigo-500/30 transition-colors"
                :class="errors.newPassword ? 'border-red-500/60' : 'border-slate-700 focus:border-indigo-500'"
                @input="clearFieldError('newPassword')"
              />
              <button
                v-if="form.newPassword"
                type="button"
                @click="show.new = !show.new"
                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300 transition-colors"
                :aria-label="show.new ? 'Ocultar contraseña' : 'Ver contraseña'"
              >
                <EyeOff v-if="show.new" class="w-3.5 h-3.5" />
                <Eye v-else class="w-3.5 h-3.5" />
              </button>
            </div>
            <span v-if="errors.newPassword" class="block text-[11px] text-red-400 mt-1">
              {{ errors.newPassword }}
            </span>
          </div>

          <div class="mb-2">
            <label for="confirmPassword" class="block text-[12px] text-slate-400 mb-1">
              Confirmar nueva contraseña
            </label>
            <div class="relative">
              <input
                id="confirmPassword"
                v-model="form.confirmPassword"
                :type="show.confirm ? 'text' : 'password'"
                autocomplete="new-password"
                class="w-full px-3 py-2 pr-9 rounded-md bg-slate-900/60 border text-[13px] text-slate-200 placeholder-slate-600
                       focus:outline-none focus:ring-2 focus:ring-indigo-500/30 transition-colors"
                :class="errors.confirmPassword ? 'border-red-500/60' : 'border-slate-700 focus:border-indigo-500'"
                @input="clearFieldError('confirmPassword')"
              />
              <button
                v-if="form.confirmPassword"
                type="button"
                @click="show.confirm = !show.confirm"
                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300 transition-colors"
                :aria-label="show.confirm ? 'Ocultar contraseña' : 'Ver contraseña'"
              >
                <EyeOff v-if="show.confirm" class="w-3.5 h-3.5" />
                <Eye v-else class="w-3.5 h-3.5" />
              </button>
            </div>
            <span v-if="errors.confirmPassword" class="block text-[11px] text-red-400 mt-1">
              {{ errors.confirmPassword }}
            </span>
          </div>

          <p v-if="authStore.error" class="text-[12px] text-red-400 mt-2">{{ authStore.error }}</p>
          <p v-if="successMessage" class="text-[12px] text-emerald-400 mt-2">{{ successMessage }}</p>

          <div class="flex justify-end mt-5">
            <button
              type="submit"
              :disabled="authStore.loading"
              class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-[12px] font-medium rounded-md
                     transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ authStore.loading ? 'Guardando...' : 'Actualizar contraseña' }}
            </button>
          </div>

        </form>
      </div>

    </div>
  </div>
</template>

<script setup>
import { reactive, ref, computed } from 'vue'
import { Eye, EyeOff } from 'lucide-vue-next'
import { useAuthStore } from '../store/authStore'

const authStore = useAuthStore()

const roleLabels = { admin: 'Administrador', secretaria: 'Secretaria', uti: 'UTI' }
const roleLabel = computed(() => roleLabels[authStore.userRole] ?? authStore.userRole ?? '—')

const form = reactive({ currentPassword: '', newPassword: '', confirmPassword: '' })
const errors = reactive({ currentPassword: '', newPassword: '', confirmPassword: '' })
const successMessage = ref('')

// Estado de visibilidad por campo
const show = reactive({ current: false, new: false, confirm: false })

function clearFieldError(field) {
  errors[field] = ''
  successMessage.value = ''
  authStore.clearError()
}

function validate() {
  let valid = true
  if (!form.currentPassword) { errors.currentPassword = 'Ingresa tu contraseña actual'; valid = false }
  if (!form.newPassword) { errors.newPassword = 'Ingresa una nueva contraseña'; valid = false }
  else if (form.newPassword.length < 6) { errors.newPassword = 'Debe tener al menos 6 caracteres'; valid = false }
  if (!form.confirmPassword) { errors.confirmPassword = 'Confirma la nueva contraseña'; valid = false }
  else if (form.confirmPassword !== form.newPassword) { errors.confirmPassword = 'Las contraseñas no coinciden'; valid = false }
  return valid
}

async function handleChangePassword() {
  successMessage.value = ''
  authStore.clearError()
  if (!validate()) return
  try {
    await authStore.changePassword({ currentPassword: form.currentPassword, newPassword: form.newPassword })
    successMessage.value = 'Contraseña actualizada correctamente'
    form.currentPassword = ''
    form.newPassword = ''
    form.confirmPassword = ''
    show.current = false
    show.new = false
    show.confirm = false
  } catch { /* authStore.error ya contiene el mensaje */ }
}
</script>