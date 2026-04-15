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

  <div class="relative">
    <input
      v-model="form.password"
      :type="showPassword ? 'text' : 'password'"
      placeholder="••••••••"
      autocomplete="current-password"
      @input="authStore.clearError()"
      class="w-full px-3 py-2 pr-10 rounded-lg border text-sm bg-gray-50 outline-none transition
        focus:bg-white focus:border-[#081F33]"
      :class="errors.password ? 'border-red-400' : 'border-gray-200'"
    />

    <!-- Botón ojo -->
    <button
      type="button"
      @click="showPassword = !showPassword"
      class="absolute inset-y-0 right-2 flex items-center text-gray-500 hover:text-gray-700"
    >
      <!-- Ojo abierto -->
      <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg"
        class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M2.458 12C3.732 7.943 7.523 5 12 5
             c4.477 0 8.268 2.943 9.542 7
             -1.274 4.057-5.065 7-9.542 7
             -4.477 0-8.268-2.943-9.542-7z"/>
      </svg>

      <!-- Ojo cerrado -->
      <svg v-else xmlns="http://www.w3.org/2000/svg"
        class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M13.875 18.825A10.05 10.05 0 0112 19
             c-4.478 0-8.27-2.943-9.543-7
             a9.956 9.956 0 012.042-3.368M6.18 6.18
             A9.956 9.956 0 0112 5c4.478 0 8.27 2.943
             9.543 7a9.956 9.956 0 01-4.132 5.411M15 12
             a3 3 0 11-6 0 3 3 0 016 0zm6 6L3 3"/>
      </svg>
    </button>
  </div>

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
          uti
        </span>
      </div>

    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useAuthStore } from '../store/authStore'
import { useAuth } from '../composables/useAuth'
import { AlertCircle, Loader2 } from 'lucide-vue-next'

const authStore = useAuthStore()
const { login } = useAuth()
const showPassword = ref(false)

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