<template>
  <div class="flex flex-col items-center justify-center min-h-[60vh] text-center px-4">
    <div class="w-14 h-14 rounded-full bg-red-50 flex items-center justify-center mb-4">
      <Lock class="w-6 h-6 text-red-500" />
    </div>
    <h2 class="text-[16px] font-medium text-navy mb-2">Acceso restringido</h2>
    <p class="text-[12px] text-gray-400 mb-1">
      No tiene permisos para acceder a esta sección.
    </p>
    <p class="text-[11px] text-gray-300 mb-6">
      Rol actual: <span class="font-medium text-gray-400">{{ roleLabel }}</span>
    </p>
    <router-link
      to="/dashboard"
      class="px-5 py-2 rounded-lg bg-gold text-white text-[12px] font-medium hover:opacity-90 transition-opacity"
    >
      Volver al dashboard
    </router-link>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Lock } from 'lucide-vue-next'
import { useAuthStore } from '../store/authStore'
import { getRoleLabel } from '@/shared/utils/helpers'

const authStore = useAuthStore()
const roleLabel = computed(() => getRoleLabel(authStore.userRole))
</script>
