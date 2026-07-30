<template>
  <div class="max-w-3xl">

    <div class="flex flex-col gap-4">

      <!-- Card: Datos de la cuenta -->
      <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm">
        <div class="bg-[#0b1437] px-6 py-4">
          <h3 class="text-[14px] font-semibold text-white tracking-wide m-0">
            Datos de la cuenta
          </h3>
        </div>
        <div class="p-6 flex flex-col">
          <div class="flex justify-between items-center py-3 border-b border-gray-100">
            <span class="text-[13px] text-gray-800">Nombre</span>
            <span class="text-[13px] text-gray-900 font-medium">{{ authStore.user?.name ?? '—' }}</span>
          </div>
          <div class="flex justify-between items-center py-3 border-b border-gray-100">
            <span class="text-[13px] text-gray-800">Correo</span>
            <span class="text-[13px] text-gray-900 font-medium">{{ authStore.user?.email ?? '—' }}</span>
          </div>
          <div class="flex justify-between items-center py-3">
            <span class="text-[13px] text-gray-800">Rol</span>
            <span class="bg-orange-50 text-[#c97a12] px-2.5 py-0.5 rounded-full text-[11px] font-semibold border border-orange-100">
              {{ roleLabel }}
            </span>
          </div>
        </div>
      </div>

      <!-- Card: Seguridad / Contraseña -->
      <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm">
        <div class="bg-[#0b1437] px-6 py-4">
          <h3 class="text-[14px] font-semibold text-white tracking-wide m-0">
            Seguridad
          </h3>
        </div>

        <div class="p-6">

          <!-- Estado 1: idle -->
          <div v-if="step === 'idle'" class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-xl bg-gray-50 border border-gray-200 flex items-center justify-center">
                <Lock class="w-4 h-4 text-gray-800" />
              </div>
              <div>
                <p class="text-[13px] text-gray-900 font-medium m-0">Contraseña</p>
                <p class="text-[12px] text-gray-400 m-0">••••••••••</p>
              </div>
            </div>
            <button
              type="button"
              @click="openVerifyStep"
              class="flex items-center gap-1.5 px-3.5 py-2 bg-[#f5a623] hover:bg-[#e0951a]
                     text-white text-[12px] font-semibold rounded-xl transition-colors shadow-sm"
            >
              <KeyRound class="w-3.5 h-3.5" />
              Cambiar contraseña
            </button>
          </div>

          <!-- Estado 2: verificar contraseña actual -->
          <form v-else-if="step === 'verify'" @submit.prevent="handleVerify">
            <p class="text-[13px] text-gray-800 mb-4">Confirma tu contraseña actual para continuar</p>

            <label for="verifyPassword" class="block text-[12px] font-bold text-[#7a1f2b] mb-1.5">
              Contraseña actual
            </label>
            <div class="relative">
              <input
                id="verifyPassword"
                ref="verifyInput"
                v-model="verifyForm.password"
                :type="show.verify ? 'text' : 'password'"
                autocomplete="current-password"
                 class="w-full px-4 py-2.5 pr-10 rounded-xl bg-gray-50 border text-[13px] text-gray-900 placeholder-gray-400
                         outline-none focus:bg-white transition-colors"
                  :class="errors.newPassword ? 'border-red-400' : 'border-gray-200 focus:border-[#0b1437]'"
                @input="verifyError = ''"
              />
              <button
                v-if="verifyForm.password"
                type="button"
                @click="show.verify = !show.verify"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors"
                :aria-label="show.verify ? 'Ocultar contraseña' : 'Ver contraseña'"
              >
                <EyeOff v-if="show.verify" class="w-4 h-4" />
                <Eye v-else class="w-4 h-4" />
              </button>
            </div>
            <span v-if="verifyError" class="block text-[11px] text-red-500 mt-1">{{ verifyError }}</span>

            <div class="flex justify-end gap-2 mt-5">
              <button
                type="button"
                @click="reset"
                class="px-4 py-2.5 rounded-xl border border-gray-200 text-[12px] font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors"
              >
                Cancelar
              </button>
              <button
                type="submit"
                :disabled="verifying || !verifyForm.password"
                class="px-4 py-2.5 bg-[#f5a623] hover:bg-[#e0951a] text-white text-[12px] font-semibold rounded-xl
                       transition-colors disabled:opacity-50 disabled:cursor-not-allowed shadow-sm"
              >
                {{ verifying ? 'Verificando...' : 'Verificar' }}
              </button>
            </div>
          </form>

          <!-- Estado 3: definir nueva contraseña (ya verificado) — un solo campo, sin repetir -->
          <form v-else @submit.prevent="handleChangePassword">
            <p class="text-[13px] font-bold text-gray-800 mb-4">Ingresa tu nueva contraseña</p>

            <div class="mb-2">
              <label for="newPassword" class="block text-[12px] font-bold text-[#7a1f2b] mb-1.5">
                Nueva contraseña
              </label>
              <div class="relative">
                <input
                  id="newPassword"
                  ref="newInput"
                  v-model="form.newPassword"
                  :type="show.new ? 'text' : 'password'"
                  autocomplete="new-password"
                  placeholder="Mín. 8 caracteres"
                  class="w-full px-4 py-2.5 pr-10 rounded-xl bg-gray-50 border text-[13px] text-gray-900 placeholder-gray-400
                         outline-none focus:bg-white transition-colors"
                  :class="errors.newPassword ? 'border-red-400' : 'border-gray-200 focus:border-[#0b1437]'"
                  @input="clearFieldError('newPassword')"
                />
                <button
                  v-if="form.newPassword"
                  type="button"
                  @click="show.new = !show.new"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors"
                  :aria-label="show.new ? 'Ocultar contraseña' : 'Ver contraseña'"
                >
                  <EyeOff v-if="show.new" class="w-4 h-4" />
                  <Eye v-else class="w-4 h-4" />
                </button>
              </div>
              <span v-if="errors.newPassword" class="block text-[11px] text-red-500 mt-1">
                {{ errors.newPassword }}
              </span>
            </div>

            <p v-if="authStore.error" class="text-[12px] text-red-500 mt-2">{{ authStore.error }}</p>

            <div class="flex justify-end gap-2 mt-5">
              <button
                type="button"
                @click="reset"
                class="px-4 py-2.5 rounded-xl border border-gray-200 text-[12px] font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors"
              >
                Cancelar
              </button>
              <button
                type="submit"
                :disabled="authStore.loading"
                class="px-4 py-2.5 bg-[#f5a623] hover:bg-[#e0951a] text-white text-[12px] font-semibold rounded-xl
                       transition-colors disabled:opacity-50 disabled:cursor-not-allowed shadow-sm"
              >
                {{ authStore.loading ? 'Guardando...' : 'Guardar nueva contraseña' }}
              </button>
            </div>
          </form>

          <!-- Mensaje de éxito, se muestra tras volver a idle -->
          <p v-if="successMessage" class="text-[12px] text-emerald-600 mt-3">
            {{ successMessage }}
          </p>

        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { reactive, ref, computed, nextTick } from 'vue'
import { Eye, EyeOff, Lock, KeyRound } from 'lucide-vue-next'
import { useAuthStore } from '@/modules/auth/store/authStore'

const authStore = useAuthStore()

const roleLabels = { admin: 'Administrador', secretaria: 'Secretaria', uti: 'UTI' }
const roleLabel = computed(() => roleLabels[authStore.userRole] ?? authStore.userRole ?? '—')

// step: 'idle' -> 'verify' -> 'change'
const step = ref('idle')

const verifyForm = reactive({ password: '' })
const verifyError = ref('')
const verifying = ref(false)
// Contraseña ya verificada, se reutiliza para el cambio real sin pedirla dos veces
let verifiedPassword = ''

const form = reactive({ newPassword: '' })
const errors = reactive({ newPassword: '' })
const successMessage = ref('')

const show = reactive({ verify: false, new: false })

const verifyInput = ref(null)
const newInput = ref(null)

function clearFieldError(field) {
  errors[field] = ''
  authStore.clearError()
}

function reset() {
  step.value = 'idle'
  verifyForm.password = ''
  verifyError.value = ''
  form.newPassword = ''
  errors.newPassword = ''
  verifiedPassword = ''
  show.verify = false
  show.new = false
  authStore.clearError()
}

async function openVerifyStep() {
  successMessage.value = ''
  step.value = 'verify'
  await nextTick()
  verifyInput.value?.focus()
}

async function handleVerify() {
  if (!verifyForm.password) return
  verifying.value = true
  verifyError.value = ''
  try {
    // Solo verifica la contraseña contra el usuario logueado, no crea token nuevo
    await authStore.verifyPassword(verifyForm.password)
    verifiedPassword = verifyForm.password
    step.value = 'change'
    await nextTick()
    newInput.value?.focus()
  } catch (err) {
    verifyError.value = err.response?.data?.message ?? 'Contraseña incorrecta'
  } finally {
    verifying.value = false
  }
}

function validateNewPassword() {
  let valid = true
  if (!form.newPassword) { errors.newPassword = 'Ingresa una nueva contraseña'; valid = false }
  else if (form.newPassword.length < 6) { errors.newPassword = 'Debe tener al menos 6 caracteres'; valid = false }
  return valid
}

async function handleChangePassword() {
  authStore.clearError()
  if (!validateNewPassword()) return
  try {
    await authStore.changePassword({ currentPassword: verifiedPassword, newPassword: form.newPassword })
    successMessage.value = 'Contraseña actualizada correctamente'
    reset()
  } catch { /* authStore.error ya contiene el mensaje */ }
}
</script>