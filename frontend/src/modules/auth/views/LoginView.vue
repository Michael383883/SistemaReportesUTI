<template>
  <div class="min-h-screen flex items-center justify-center relative overflow-hidden bg-[#0a1a2e]">

    <!-- Fondos -->
    <div class="absolute w-[320px] h-[320px] bg-[#112844] -top-20 -right-20 rounded-full pointer-events-none"></div>
    <div class="absolute w-[220px] h-[220px] bg-[#0d2035] -bottom-16 -left-10 rounded-full pointer-events-none"></div>
    <div class="absolute w-[130px] h-[130px] bg-[#1a3a5c80] top-10 left-8 rounded-full pointer-events-none"></div>

    <!-- Línea  -->
    <div class="absolute bottom-0 left-0 right-0 h-[3px] bg-[#D28B45]"></div>

    <!-- Card -->
    <div class="relative z-10 bg-white rounded-2xl px-8 py-9 w-full max-w-[320px] shadow-lg">

      <!-- Logo -->
      <div class="flex flex-col items-center mb-7">
        <div class="w-[72px] h-[72px] bg-[#081F33] border-[3px] border-[#D28B45] rounded-full flex flex-col items-center justify-center mb-3">
          <span class="text-white font-bold text-[20px] leading-none">U</span>
          <span class="text-red-500 font-bold text-[10px] tracking-widest leading-none">FCE</span>
          <span class="text-[#D28B45] text-[7px] leading-none">UMSS</span>
        </div>

        <h1 class="text-center text-[15px] font-medium text-[#081F33] leading-tight">
          UTI-FCE · Sistema de Reportes
        </h1>

        <p class="text-[11px] text-gray-400 mt-1">
          Unidad de Tecnologías de Información
        </p>
      </div>

      <!-- Error -->
      <transition name="fade">
        <div
          v-if="authStore.error"
          class="flex items-center gap-2 rounded-lg mb-4 bg-red-50 border border-red-300 px-3 py-2 text-[11px] text-red-700"
        >
          <AlertCircle class="w-4 h-4 flex-shrink-0" />
          {{ authStore.error }}
        </div>
      </transition>

      <!-- Form -->
      <form @submit.prevent="handleSubmit">

        <!-- Email -->
        <div class="mb-4">
          <label class="block text-[11px] text-gray-500 font-medium mb-1">
            Correo electrónico
          </label>

          <input
            v-model="form.email"
            type="email"
            placeholder="usuario@umss.edu"
            autocomplete="username"
            @input="authStore.clearError()"
            class="w-full px-3 py-2 rounded-lg border text-sm bg-gray-50 outline-none transition
              focus:bg-white focus:border-[#081F33]"
            :class="errors.email ? 'border-red-400' : 'border-gray-200'"
          />

          <p v-if="errors.email" class="text-[10px] text-red-500 mt-1">
            {{ errors.email }}
          </p>
        </div>

        <!-- Password -->
        <div class="mb-5">
          <label class="block text-[11px] text-gray-500 font-medium mb-1">
            Contraseña
          </label>

          <input
            v-model="form.password"
            type="password"
            placeholder="••••••••"
            autocomplete="current-password"
            @input="authStore.clearError()"
            class="w-full px-3 py-2 rounded-lg border text-sm bg-gray-50 outline-none transition
              focus:bg-white focus:border-[#081F33]"
            :class="errors.password ? 'border-red-400' : 'border-gray-200'"
          />

          <p v-if="errors.password" class="text-[10px] text-red-500 mt-1">
            {{ errors.password }}
          </p>
        </div>

        <!-- Botón -->
        <button
          type="submit"
          :disabled="authStore.loading"
          class="w-full py-2.5 rounded-lg bg-[#081F33] text-white text-sm font-medium
                 hover:bg-[#051828] transition flex items-center justify-center gap-2"
        >
          <span v-if="!authStore.loading">Iniciar sesión</span>

          <span v-else class="flex items-center gap-2">
            <Loader2 class="w-4 h-4 animate-spin" />
            Verificando...
          </span>
        </button>
      </form>

      <!-- Roles -->
      <div class="flex gap-2 justify-center flex-wrap mt-4">
        <span class="text-[10px] px-3 py-1 rounded-full bg-indigo-50 text-[#081F33] font-medium">
          Administrador
        </span>
        <span class="text-[10px] px-3 py-1 rounded-full bg-orange-50 text-[#8B5E20] font-medium">
          Secretaría
        </span>
        <span class="text-[10px] px-3 py-1 rounded-full bg-red-50 text-red-700 font-medium">
          UTI
        </span>
      </div>

    </div>
  </div>
</template>

<script setup>
import { reactive } from 'vue'
import { useAuthStore } from '../store/authStore'
import { useAuth } from '../composables/useAuth'
import { AlertCircle, Loader2 } from 'lucide-vue-next'

const authStore = useAuthStore()
const { login } = useAuth()

const form = reactive({
  email: '',
  password: ''
})

const errors = reactive({
  email: '',
  password: ''
})

function validate() {
  errors.email = ''
  errors.password = ''
  let valid = true

  if (!form.email) {
    errors.email = 'El correo es requerido'
    valid = false
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
    errors.email = 'Formato de correo inválido'
    valid = false
  }

  if (!form.password) {
    errors.password = 'La contraseña es requerida'
    valid = false
  } else if (form.password.length < 8) {
    errors.password = 'Mínimo 8 caracteres'
    valid = false
  }

  return valid
}

async function handleSubmit() {
  if (!validate()) return

  try {
    await login({
      email: form.email,
      password: form.password
    })
  } catch (error) {
    // manejado en el store
  }
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>