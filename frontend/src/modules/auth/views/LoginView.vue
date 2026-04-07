<template>
  <div class="min-h-screen bg-[#0a1a2e] flex items-center justify-center relative overflow-hidden">

    <!-- Fondo decorativo -->
    <div class="absolute w-[320px] h-[320px] rounded-full bg-[#112844] -top-20 -right-20 pointer-events-none" />
    <div class="absolute w-[220px] h-[220px] rounded-full bg-[#0d2035] -bottom-16 -left-10 pointer-events-none" />
    <div class="absolute w-[130px] h-[130px] rounded-full bg-[#1a3a5c]/50 top-10 left-8 pointer-events-none" />
    <div class="absolute bottom-0 left-0 right-0 h-[3px] bg-gold pointer-events-none" />

    <!-- Card -->
    <div class="relative z-10 bg-white rounded-2xl px-8 py-9 w-[320px] shadow-none">

      <!-- Logo -->
      <div class="flex flex-col items-center mb-7">
        <div class="w-[72px] h-[72px] rounded-full bg-navy border-[3px] border-gold flex flex-col items-center justify-center leading-none mb-3">
          <span class="text-white font-bold text-xl">U</span>
          <span class="text-red-500 font-bold text-[10px] tracking-widest">FCE</span>
          <span class="text-gold text-[7px] tracking-wide">UMSS</span>
        </div>
        <h1 class="text-[15px] font-medium text-navy text-center">UTI-FCE · Sistema de Reportes</h1>
        <p class="text-[11px] text-gray-400 mt-0.5">Unidad de Tecnologías de Información</p>
      </div>

      <!-- Error -->
      <transition name="fade">
        <div
          v-if="authStore.error"
          class="flex items-center gap-2 bg-red-50 border border-red-300 rounded-lg px-3 py-2 mb-4 text-[11px] text-red-700"
        >
          <AlertCircle class="w-3.5 h-3.5 shrink-0" />
          {{ authStore.error }}
        </div>
      </transition>

      <!-- Form -->
      <form @submit.prevent="handleSubmit" novalidate>
        <!-- Email -->
        <div class="mb-4">
          <label class="block text-[11px] text-gray-500 font-medium mb-1.5">Correo electrónico</label>
          <input
            v-model="form.email"
            type="email"
            placeholder="usuario@umss.edu"
            autocomplete="username"
            class="w-full px-3 py-2.5 rounded-lg border text-[13px] bg-gray-50 focus:outline-none focus:border-navy focus:bg-white transition-colors"
            :class="errors.email ? 'border-red-400' : 'border-gray-200'"
            @input="clearFieldError('email')"
          />
          <p v-if="errors.email" class="text-[10px] text-red-500 mt-1">{{ errors.email }}</p>
        </div>

        <!-- Password -->
        <div class="mb-5">
          <label class="block text-[11px] text-gray-500 font-medium mb-1.5">Contraseña</label>
          <input
            v-model="form.password"
            type="password"
            placeholder="••••••••"
            autocomplete="current-password"
            class="w-full px-3 py-2.5 rounded-lg border text-[13px] bg-gray-50 focus:outline-none focus:border-navy focus:bg-white transition-colors"
            :class="errors.password ? 'border-red-400' : 'border-gray-200'"
            @input="clearFieldError('password')"
          />
          <p v-if="errors.password" class="text-[10px] text-red-500 mt-1">{{ errors.password }}</p>
        </div>

        <!-- Submit -->
        <button
          type="submit"
          :disabled="authStore.loading"
          class="w-full py-2.5 rounded-lg bg-navy text-white text-[13px] font-medium hover:bg-navy-dark transition-colors disabled:opacity-60 disabled:cursor-not-allowed"
        >
          <span v-if="!authStore.loading">Iniciar sesión</span>
          <span v-else class="flex items-center justify-center gap-2">
            <Loader2 class="w-4 h-4 animate-spin" /> Verificando...
          </span>
        </button>
      </form>

      <!-- Role badges -->
      <div class="flex gap-2 justify-center mt-5">
        <span class="text-[10px] px-3 py-1 rounded-full bg-[#eef2ff] text-navy font-medium">Administrador</span>
        <span class="text-[10px] px-3 py-1 rounded-full bg-[#fff8ee] text-[#8B5E20] font-medium">Secretaría</span>
        <span class="text-[10px] px-3 py-1 rounded-full bg-red-50 text-red-700 font-medium">UTI</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/modules/auth/store/authStore'
import { AlertCircle, Loader2 } from 'lucide-vue-next'

const authStore = useAuthStore()
const router    = useRouter()

const form = reactive({ email: '', password: '' })
const errors = reactive({ email: '', password: '' })

function validate() {
  errors.email    = ''
  errors.password = ''
  let ok = true

  if (!form.email) {
    errors.email = 'El correo es requerido'
    ok = false
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
    errors.email = 'Formato de correo inválido'
    ok = false
  }

  if (!form.password) {
    errors.password = 'La contraseña es requerida'
    ok = false
  } else if (form.password.length < 8) {
    errors.password = 'Mínimo 8 caracteres'
    ok = false
  }

  return ok
}

function clearFieldError(field) {
  errors[field] = ''
  authStore.clearError()
}

async function handleSubmit() {
  if (!validate()) return
  try {
    await authStore.login({ email: form.email, password: form.password })
    const redirect = router.currentRoute.value.query?.redirect ?? '/dashboard'
    router.push(redirect)
  } catch {
    // error ya manejado en el store
  }
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>