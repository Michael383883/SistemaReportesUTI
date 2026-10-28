<template>
  <header class="flex items-center justify-between shrink-0 z-50 h-16 bg-[#081F33] px-5">
    <!-- Logo + Título en el topbar -->
    <div class="flex items-center gap-3">
      <img :src="logo" alt="SIA-UTI" class="h-12 w-auto shrink-0" />

      <div class="flex flex-col leading-[1.15]">
        <span class="[font-family:'Poppins','Segoe_UI',sans-serif] font-extrabold text-[21px] tracking-[-0.02em] whitespace-nowrap">
          <span class="text-white">SIA-</span><span class="text-red-500">UTI</span>
        </span>
        <span class="text-[11px] font-semibold tracking-[0.12em] text-white/55 uppercase whitespace-nowrap mt-0.5">
          Sistema de Información Académica
        </span>
      </div>
    </div>

    <!-- Perfil con dropdown -->
    <div class="relative" ref="profileRef">
      <button
        @click="profileOpen = !profileOpen"
        class="flex items-center gap-2 rounded-lg pt-1 pr-2 pb-1 pl-1 bg-white/[0.06] border border-white/[0.12] cursor-pointer transition-colors duration-150 hover:bg-white/[0.12]"
      >
        <div class="flex items-center justify-center rounded-full shrink-0 w-7 h-7 bg-[#D28B45] text-white">
          <User class="w-[15px] h-[15px]" />
        </div>
        <span class="text-white text-[13px] font-medium whitespace-nowrap">
          {{ authStore.user?.name }}
        </span>
        <ChevronDown
          class="w-[13px] h-[13px] text-white/50 transition-transform duration-200"
          :class="profileOpen ? 'rotate-180' : ''"
        />
      </button>

      <!-- Dropdown -->
      <div
        v-if="profileOpen"
        class="absolute top-[calc(100%+8px)] right-0 bg-white dark:bg-[#111a2e] rounded-[10px] shadow-[0_8px_24px_rgba(0,0,0,0.15)] dark:shadow-[0_8px_24px_rgba(0,0,0,0.5)] border border-black/[0.08] dark:border-white/[0.08] min-w-[190px] overflow-hidden z-[200]"
      >
        <div class="pt-3 px-3.5 pb-2.5 border-b border-[#f0f0f0] dark:border-white/[0.08]">
          <div class="text-[15px] font-semibold text-[#081F33] dark:text-white">{{ authStore.user?.name }}</div>
          <div class="text-sm text-[#D28B45] mt-px">{{ roleLabel }}</div>
        </div>

        <div class="py-1.5">
          <button
            v-if="authStore.userRole === 'admin'"
            @click="openPerfilModal"
            class="flex items-center gap-3 w-full py-[9px] px-3.5 bg-transparent border-none cursor-pointer text-[13.5px] text-[#2d3748] dark:text-slate-200 text-left transition-colors duration-[120ms] hover:bg-[#f7f8fa] dark:hover:bg-white/[0.06]"
          >
            <User class="w-4 h-4 text-[#6b7280] dark:text-slate-400 shrink-0" /> Perfil
          </button>

          <button
            @click="toggleDarkMode"
            class="flex items-center justify-between w-full py-[9px] px-3.5 bg-transparent border-none cursor-pointer text-[13.5px] text-[#2d3748] dark:text-slate-200 text-left transition-colors duration-[120ms] hover:bg-[#f7f8fa] dark:hover:bg-white/[0.06]"
          >
            <div class="flex items-center gap-3">
              <Moon class="w-4 h-4 text-[#6b7280] dark:text-slate-400 shrink-0" /> Modo oscuro
            </div>
            <div
              class="w-8 h-[18px] rounded-[9px] transition-colors duration-200 shrink-0 relative"
              :class="darkMode ? 'bg-[#D28B45]' : 'bg-gray-300 dark:bg-white/20'"
            >
              <div
                class="absolute top-0.5 w-3.5 h-3.5 rounded-full bg-white transition-[left] duration-200 shadow-[0_1px_3px_rgba(0,0,0,0.2)]"
                :class="darkMode ? 'left-4' : 'left-0.5'"
              />
            </div>
          </button>
        </div>

        <div class="border-t border-[#f0f0f0] dark:border-white/[0.08] pt-1.5 pb-1">
          <button
            @click="handleLogout"
            class="flex items-center gap-3 w-full py-[9px] px-3.5 bg-transparent border-none cursor-pointer text-[13.5px] text-red-500 dark:text-red-400 text-left transition-colors duration-[120ms] hover:bg-[#fff5f5] dark:hover:bg-red-500/10"
          >
            <LogOut class="w-4 h-4 shrink-0" /> Cerrar sesión
          </button>
        </div>
      </div>
    </div>
  </header>

  <!-- Modal de perfil, propiedad de este componente -->
  <ProfileModal v-model="showPerfilModal" />
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { User, ChevronDown, Moon, LogOut } from 'lucide-vue-next'

import { useAuthStore } from '@/modules/auth/store/authStore'
import { getRoleLabel } from '@/shared/utils/helpers'
import { useNotify } from '@/shared/composables/useNotify'
import { useDarkMode } from '@/composables/useDarkMode'
import logo from '@/assets/img/SIA-UTI-logo.svg'
import ProfileModal from './ProfileModal.vue'

const authStore = useAuthStore()
const router = useRouter()
const notify = useNotify()
const { darkMode, toggleDarkMode } = useDarkMode()

const profileOpen = ref(false)
const profileRef = ref(null)
const showPerfilModal = ref(false)

const roleLabel = computed(() => getRoleLabel(authStore.userRole))

function openPerfilModal() {
  profileOpen.value = false
  showPerfilModal.value = true
}

function handleClickOutside(e) {
  if (profileRef.value && !profileRef.value.contains(e.target)) {
    profileOpen.value = false
  }
}

async function handleLogout() {
  profileOpen.value = false
  await authStore.logout()
  notify.success('Sesión cerrada correctamente')
  router.push('/login')
}

onMounted(() => document.addEventListener('mousedown', handleClickOutside))
onUnmounted(() => document.removeEventListener('mousedown', handleClickOutside))
</script>