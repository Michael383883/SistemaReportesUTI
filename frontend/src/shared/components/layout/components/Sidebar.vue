<template>
  <aside
    class="flex flex-col overflow-y-auto overflow-x-hidden shrink-0 bg-[#081F33] transition-[width] duration-200 ease-in-out relative z-[100]"
    :class="sidebarOpen ? 'w-[200px]' : 'w-[52px]'"
  >
    <!-- BOTÓN ☰ -->
    <div
      class="h-16 shrink-0 border-b border-white/[0.07] flex items-center"
      :class="sidebarOpen ? 'justify-start pl-[11px]' : 'justify-center'"
    >
      <button
        @click="sidebarOpen = !sidebarOpen"
        class="w-[30px] h-[30px] bg-white/[0.07] border border-white/[0.13] rounded-[7px] cursor-pointer flex items-center justify-center transition-colors duration-150 hover:bg-white/[0.15]"
        :title="sidebarOpen ? 'Colapsar menú' : 'Expandir menú'"
      >
        <Menu class="w-[15px] h-[15px] text-white" />
      </button>
    </div>

    <!-- SECCIÓN ROL + MENÚ -->
    <div class="flex flex-col flex-1 pt-1">
      <template v-if="!authStore.user">
        <div class="p-2 flex flex-col gap-1.5">
          <div v-for="n in 4" :key="n" class="h-8 bg-white/[0.06] rounded-md" />
        </div>
      </template>

      <template v-else>
        <template v-for="section in filteredMenu" :key="section.label">

          <!-- Etiqueta rol → click colapsa (solo expandido) -->
          <button
            v-if="sidebarOpen"
            @click="sidebarOpen = false"
            class="flex items-center justify-between w-full pt-[7px] px-3.5 pb-[5px] bg-transparent border-none cursor-pointer transition-colors duration-[120ms] hover:bg-white/[0.04]"
          >
            <span class="text-[10.5px] font-bold text-white/30 uppercase tracking-[0.1em]">{{ section.label }}</span>
            <ChevronLeft class="w-3 h-3 text-white/25" />
          </button>

          <!-- Divisor colapsado -->
          <div v-else class="h-px bg-white/[0.07] mt-1 mx-2 mb-1.5" />

          <!-- Items -->
          <SidebarMenuItem
            v-for="item in section.items"
            :key="item.label"
            :item="item"
            :sidebar-open="sidebarOpen"
          />
        </template>
      </template>
    </div>
  </aside>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Menu, ChevronLeft } from 'lucide-vue-next'

import { useAuthStore } from '@/modules/auth/store/authStore'
import { menuSections } from '../config/menuSections'
import SidebarMenuItem from './SidebarMenuItem.vue'

const authStore = useAuthStore()
const sidebarOpen = ref(true)

const filteredMenu = computed(() => {
  if (!authStore.userRole) return []
  return menuSections.filter(s => s.roles.includes(authStore.userRole))
})
</script>
