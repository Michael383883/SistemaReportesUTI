<template>
  <div class="flex flex-col h-screen" style="background:#f4f6f9;">

    <!-- Topbar -->
    <header
      class="flex items-center justify-between shrink-0 z-10"
      style="height:52px; background:#081F33; padding:0 20px;"
    >
      <div class="flex items-center gap-3">
        <div
          class="flex flex-col items-center justify-center rounded-full shrink-0"
          style="width:32px; height:32px; background:#0d2e4a; border:2px solid #D28B45;"
        >
          <span style="color:#fff; font-weight:700; font-size:9px; line-height:1.1;">U</span>
          <span style="color:#ef4444; font-weight:700; font-size:6px; letter-spacing:0.08em; line-height:1;">FCE</span>
        </div>
        <span style="color:#ffffff; font-size:13px; font-weight:500; letter-spacing:-0.01em;">
          UTI-FCE · Sistema de Reportes
        </span>
      </div>

      <div class="flex items-center gap-3">
        <div class="flex items-center gap-2">
          <div
            class="flex items-center justify-center rounded-full shrink-0"
            style="width:30px; height:30px; background:#D28B45; color:#fff; font-size:11px; font-weight:500;"
          >
            {{ initials }}
          </div>
          <div style="line-height:1.25;">
            <div style="color:#ffffff; font-size:12px; font-weight:500;">{{ authStore.user?.name }}</div>
            <div style="color:#D28B45; font-size:10px;">{{ roleLabel }}</div>
          </div>
        </div>

        <button
          @click="handleLogout"
          title="Cerrar sesión"
          class="flex items-center justify-center rounded-lg transition-colors"
          style="width:32px; height:32px; background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.15); cursor:pointer;"
          @mouseover="e => e.currentTarget.style.background='rgba(220,38,38,0.3)'"
          @mouseleave="e => e.currentTarget.style.background='rgba(255,255,255,0.08)'"
        >
          <LogOut style="width:15px; height:15px; color:#ffffff;" />
        </button>
      </div>
    </header>

    <!-- Body -->
    <div class="flex flex-1 overflow-hidden">

      <!-- Sidebar -->
      <aside
        class="flex flex-col overflow-y-auto shrink-0"
        style="width:160px; background:#081F33; padding:16px 0;"
      >
        <!-- Mientras carga el usuario -->
        <template v-if="!authStore.user">
          <div class="flex flex-col gap-2 px-4 mt-4">
            <div v-for="n in 4" :key="n"
              style="height:32px; background:rgba(255,255,255,0.06); border-radius:6px;"
            />
          </div>
        </template>

        <!-- Menú filtrado por rol -->
        <template v-else>
          <template v-for="section in filteredMenu" :key="section.label">
            <p style="padding:0 16px; font-size:9px; color:rgba(255,255,255,0.3); text-transform:uppercase; letter-spacing:0.08em; margin-bottom:4px; margin-top:12px;">
              {{ section.label }}
            </p>
            <router-link
              v-for="item in section.items"
              :key="item.to"
              :to="item.to"
              class="flex items-center gap-2 transition-all"
              style="padding:9px 16px; font-size:12px; color:rgba(255,255,255,0.6); border-left:3px solid transparent; text-decoration:none;"
              active-class="sidebar-active"
              @mouseover="e => { if (!e.currentTarget.classList.contains('router-link-active')) e.currentTarget.style.color='rgba(255,255,255,1)' }"
              @mouseleave="e => { if (!e.currentTarget.classList.contains('router-link-active')) e.currentTarget.style.color='rgba(255,255,255,0.6)' }"
            >
              <component :is="item.icon" style="width:14px; height:14px; flex-shrink:0;" />
              {{ item.label }}
            </router-link>
          </template>
        </template>
      </aside>

      <!-- Contenido principal -->
      <main class="flex-1 overflow-y-auto" style="padding:20px;">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'

import { useAuthStore } from '@/modules/auth/store/authStore'
import { getRoleLabel, getInitials } from '@/shared/utils/helpers'
import { useNotify } from '@/shared/composables/useNotify'
import {
  LogOut, LayoutDashboard, FileText,
  Users, BarChart2, Database,
} from 'lucide-vue-next'

const authStore = useAuthStore()
const router    = useRouter()
const notify    = useNotify()

const initials  = computed(() => getInitials(authStore.user?.name ?? ''))
const roleLabel = computed(() => getRoleLabel(authStore.userRole))

// ✅ Cada sección declara qué roles pueden verla
const menuSections = [
  {
    label: 'Principal',
    roles: ['admin', 'secretaria', 'uti'],
    items: [
      { to: '/dashboard',  label: 'Dashboard',  icon: LayoutDashboard },
      { to: '/documentos', label: 'Documentos', icon: FileText },
      { to: '/docentes',   label: 'Docentes',   icon: Users },
      { to: '/reportes',   label: 'Reportes',   icon: BarChart2 },
    ],
  },
  {
    label: 'Admin',
    roles: ['admin'], // Solo admin ve esta sección
    items: [
      { to: '/usuarios',  label: 'Usuarios',   icon: Users },
      { to: '/config-bd', label: 'Config. BD', icon: Database },
    ],
  },
]

// Filtra secciones según el rol actual
const filteredMenu = computed(() => {
  if (!authStore.userRole) return []
  return menuSections.filter(s => s.roles.includes(authStore.userRole))
})

async function handleLogout() {
  await authStore.logout()
  notify.success('Sesión cerrada correctamente')
  router.push('/login')
}
</script>

<style>
.sidebar-active {
  color: #D28B45 !important;
  border-left-color: #D28B45 !important;
  background: rgba(210, 139, 69, 0.08) !important;
}
</style>