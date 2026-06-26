<template>
  <div class="min-h-screen flex items-center justify-center relative overflow-hidden bg-[#0a1a2e]">

    <!-- Decorativos de fondo -->
    <div
      class="absolute w-80 h-80 bg-[#112844] -top-20 -right-20 rounded-full pointer-events-none"
      aria-hidden="true"
    />
    <div
      class="absolute w-56 h-56 bg-[#0d2035] -bottom-16 -left-10 rounded-full pointer-events-none"
      aria-hidden="true"
    />
    <div
      class="absolute w-32 h-32 bg-[#1a3a5c]/50 top-10 left-8 rounded-full pointer-events-none"
      aria-hidden="true"
    />

    <!-- Línea de acento inferior -->
    <div
      class="absolute bottom-0 left-0 right-0 h-[3px] bg-[#D28B45]"
      aria-hidden="true"
    />

    <!-- Card principal -->
    <div class="relative z-10 bg-white rounded-2xl px-8 py-9 w-full max-w-xs shadow-lg">

      <!-- Encabezado / Logo -->
      <header class="flex flex-col items-center mb-7">
        <img
          :src="logo"
          alt="Logo SIA-UTI — Sistema de Información Académica"
          class="h-32 w-auto mb-3"
        />

        <h1 class="text-center text-[28px] font-extrabold leading-none tracking-tight">
          <span class="text-[#081F33]">SIA-</span>
          <span class="text-red-500">UTI</span>
        </h1>

        <!-- Separador con puntos -->
        <div class="w-full my-2 flex items-center gap-2" aria-hidden="true">
          <span class="w-1.5 h-1.5 rounded-full bg-[#081F33] flex-shrink-0" />
          <span class="flex-1 border-t border-[#081F33]/60" />
          <span class="w-1.5 h-1.5 rounded-full bg-[#081F33] flex-shrink-0" />
        </div>

        <p class="text-[10.5px] text-gray-400 font-semibold tracking-widest uppercase">
          Sistema de Información Académica
        </p>
      </header>

      <!-- Alerta de error del servidor -->
      <transition
        enter-active-class="transition-opacity duration-200"
        enter-from-class="opacity-0"
        leave-active-class="transition-opacity duration-200"
        leave-to-class="opacity-0"
      >
        <div
          v-if="authStore.error"
          role="alert"
          aria-live="assertive"
          class="flex items-center gap-2 rounded-lg mb-4 bg-red-50 border border-red-300 px-3 py-2 text-[11px] text-red-700"
        >
          <AlertCircle class="w-4 h-4 flex-shrink-0" aria-hidden="true" />
          <span>{{ authStore.error }}</span>
        </div>
      </transition>

      <!-- Formulario -->
      <form
        @submit.prevent="handleSubmit"
        novalidate
        aria-label="Iniciar sesión en SIA-UTI"
      >

        <!-- Correo electrónico -->
        <div class="mb-4">
          <label
            for="email"
            class="block text-[11px] text-gray-500 font-medium mb-1"
          >
            Correo electrónico
          </label>

          <input
            id="email"
            v-model="form.email"
            type="email"
            placeholder="usuario@umss.edu"
            autocomplete="username"
            :aria-invalid="!!errors.email"
            :aria-describedby="errors.email ? 'email-error' : undefined"
            @input="clearFieldError('email')"
            class="w-full px-3 py-2 rounded-lg border text-sm bg-gray-50 outline-none
                   transition-colors focus:bg-white focus:border-[#081F33]
                   focus-visible:ring-2 focus-visible:ring-[#081F33]/20"
            :class="errors.email ? 'border-red-400' : 'border-gray-200'"
          />

          <transition
            enter-active-class="transition-all duration-150 overflow-hidden"
            enter-from-class="opacity-0 max-h-0"
            enter-to-class="opacity-100 max-h-10"
            leave-active-class="transition-all duration-150 overflow-hidden"
            leave-from-class="opacity-100 max-h-10"
            leave-to-class="opacity-0 max-h-0"
          >
            <p
              v-if="errors.email"
              id="email-error"
              role="alert"
              class="text-[10px] text-red-500 mt-1"
            >
              {{ errors.email }}
            </p>
          </transition>
        </div>

        <!-- Contraseña -->
        <div class="mb-5">
          <label
            for="password"
            class="block text-[11px] text-gray-500 font-medium mb-1"
          >
            Contraseña
          </label>

          <div class="relative">
            <input
              id="password"
              v-model="form.password"
              :type="showPassword ? 'text' : 'password'"
              placeholder="••••••••"
              autocomplete="current-password"
              :aria-invalid="!!errors.password"
              :aria-describedby="errors.password ? 'password-error' : undefined"
              @input="clearFieldError('password')"
              class="w-full px-3 py-2 pr-10 rounded-lg border text-sm bg-gray-50 outline-none
                     transition-colors focus:bg-white focus:border-[#081F33]
                     focus-visible:ring-2 focus-visible:ring-[#081F33]/20"
              :class="errors.password ? 'border-red-400' : 'border-gray-200'"
            />

            <!-- Toggle visibilidad -->
            <button
              type="button"
              :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
              :aria-pressed="showPassword"
              @click="showPassword = !showPassword"
              class="absolute inset-y-0 right-2 flex items-center text-gray-400
                     hover:text-gray-700 transition-colors outline-none
                     focus-visible:ring-2 focus-visible:ring-[#081F33]/40 rounded"
            >
              <!-- Ojo abierto -->
              <svg
                v-if="!showPassword"
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                aria-hidden="true"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M2.458 12C3.732 7.943 7.523 5 12 5
                     c4.477 0 8.268 2.943 9.542 7
                     -1.274 4.057-5.065 7-9.542 7
                     -4.477 0-8.268-2.943-9.542-7z"/>
              </svg>

              <!-- Ojo cerrado -->
              <svg
                v-else
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                aria-hidden="true"
              >
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

          <transition
            enter-active-class="transition-all duration-150 overflow-hidden"
            enter-from-class="opacity-0 max-h-0"
            enter-to-class="opacity-100 max-h-10"
            leave-active-class="transition-all duration-150 overflow-hidden"
            leave-from-class="opacity-100 max-h-10"
            leave-to-class="opacity-0 max-h-0"
          >
            <p
              v-if="errors.password"
              id="password-error"
              role="alert"
              class="text-[10px] text-red-500 mt-1"
            >
              {{ errors.password }}
            </p>
          </transition>
        </div>

        <!-- Botón submit -->
        <button
          type="submit"
          :disabled="authStore.loading"
          :aria-busy="authStore.loading"
          class="w-full py-2.5 rounded-lg bg-[#081F33] text-white text-sm font-medium
                 hover:bg-[#051828] active:scale-[0.98] transition-all
                 flex items-center justify-center gap-2 outline-none
                 focus-visible:ring-2 focus-visible:ring-[#081F33]/50
                 disabled:opacity-60 disabled:cursor-not-allowed disabled:active:scale-100"
        >
          <span v-if="!authStore.loading">Iniciar sesión</span>

          <span v-else class="flex items-center gap-2">
            <Loader2 class="w-4 h-4 animate-spin" aria-hidden="true" />
            <span>Verificando...</span>
          </span>
        </button>
      </form>

      <!-- Roles disponibles -->
      <ul
        class="flex gap-2 justify-center flex-wrap mt-4 list-none p-0"
        aria-label="Roles del sistema"
      >
        <li class="text-[10px] px-3 py-1 rounded-full bg-indigo-50 text-[#081F33] font-medium">
          Administrador
        </li>
        <li class="text-[10px] px-3 py-1 rounded-full bg-orange-50 text-[#8B5E20] font-medium">
          Secretaría
        </li>
        <li class="text-[10px] px-3 py-1 rounded-full bg-red-50 text-red-700 font-medium">
          UTI
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useAuthStore } from '../store/authStore'
import { useAuth } from '../composables/useAuth'
import { AlertCircle, Loader2 } from 'lucide-vue-next'
import logo from '@/assets/img/SIA-UTI-logo.svg'

const authStore = useAuthStore()
const { login } = useAuth()
const showPassword = ref(false)

const form = reactive({ email: '', password: '' })
const errors = reactive({ email: '', password: '' })

/** Limpia el error de campo + error del store al escribir */
function clearFieldError(field) {
  errors[field] = ''
  authStore.clearError()
}

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
  if (authStore.loading) return   // previene doble envío

  try {
    await login({ email: form.email, password: form.password })
  } catch {
    // los errores se gestionan en el store
  }
}
</script>