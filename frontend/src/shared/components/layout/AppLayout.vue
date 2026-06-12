<template>
  <div class="flex flex-col h-screen" style="background:#f4f6f9;">

    <!-- Topbar -->
    <header
      class="flex items-center justify-between shrink-0 z-10"
      style="height:52px; background:#081F33; padding:0 20px;"
    >
      <div class="flex items-center gap-3">
        <!-- Toggle sidebar -->
        <button
          @click="sidebarOpen = !sidebarOpen"
          class="flex items-center justify-center rounded-lg transition-colors"
          style="
            width:32px; height:32px;
            background:rgba(255,255,255,0.08);
            border:1px solid rgba(255,255,255,0.15);
            cursor:pointer; flex-shrink:0;
          "
          @mouseover="e => e.currentTarget.style.background='rgba(255,255,255,0.15)'"
          @mouseleave="e => e.currentTarget.style.background='rgba(255,255,255,0.08)'"
          :title="sidebarOpen ? 'Colapsar menú' : 'Expandir menú'"
        >
          <Menu style="width:16px; height:16px; color:#ffffff;" />
        </button>

        <div
          class="flex flex-col items-center justify-center rounded-full shrink-0"
          style="width:36px; height:36px; background:#0d2e4a; border:2px solid #D28B45;"
        >
          <span style="color:#fff; font-weight:700; font-size:12px; line-height:1.1;">U</span>
          <span style="color:#ef4444; font-weight:700; font-size:11px; letter-spacing:0.08em; line-height:1;">FCE</span>
        </div>

        <span
          v-if="sidebarOpen"
          style="color:#ffffff; font-size:18px; font-weight:500; letter-spacing:-0.01em; white-space:nowrap;"
        >
          UTI-FCE · Sistema de Reportes
        </span>
      </div>

      <div class="flex items-center gap-3">
        <!-- Perfil -->
        <div class="flex items-center gap-2">
          <div
            class="flex items-center justify-center rounded-full shrink-0"
            style="width:34px; height:34px; background:#D28B45; color:#fff;"
          >
            <User style="width:18px; height:18px;" />
          </div>
          <div v-if="sidebarOpen" style="line-height:1.25;">
            <div style="color:#ffffff; font-size:13px; font-weight:500;">{{ authStore.user?.name }}</div>
            <div style="color:#D28B45; font-size:12px;">{{ roleLabel }}</div>
          </div>
        </div>

        <!-- Logout -->
        <button
          @click="handleLogout"
          title="Cerrar sesión"
          class="flex items-center justify-center rounded-lg transition-colors"
          style="
            width:32px; height:32px;
            background:rgba(255,255,255,0.08);
            border:1px solid rgba(255,255,255,0.15);
            cursor:pointer;
          "
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
        class="flex flex-col overflow-y-auto overflow-x-hidden shrink-0 transition-all duration-200"
        :style="sidebarOpen ? 'width:200px;' : 'width:52px;'"
        style="background:#081F33; padding:12px 0;"
      >
        <template v-if="!authStore.user">
          <div class="flex flex-col gap-2 px-2 mt-4">
            <div v-for="n in 4" :key="n" style="height:32px; background:rgba(255,255,255,0.06); border-radius:6px;" />
          </div>
        </template>

        <template v-else>
          <template v-for="section in filteredMenu" :key="section.label">

            <!-- Label de sección (solo visible expandido) -->
            <p
              v-if="sidebarOpen"
              style="
                padding:0 14px;
                font-size:11px;
                color:rgba(255,255,255,0.3);
                text-transform:uppercase;
                letter-spacing:0.08em;
                margin:12px 0 2px;
                white-space:nowrap;
              "
            >{{ section.label }}</p>

            <!-- Divisor cuando está colapsado -->
            <div
              v-else
              style="height:1px; background:rgba(255,255,255,0.08); margin:8px 8px 4px;"
            />

            <template v-for="item in section.items" :key="item.label">

              <!-- Item normal -->
              <router-link
                v-if="!item.children"
                :to="item.to"
                class="flex items-center transition-all"
                :class="sidebarOpen ? 'gap-2' : 'justify-center'"
                :style="sidebarOpen
                  ? 'padding:8px 14px; font-size:13.5px; color:rgba(255,255,255,0.65); border-left:3px solid transparent; text-decoration:none; white-space:nowrap;'
                  : 'padding:10px 0; font-size:13.5px; color:rgba(255,255,255,0.65); border-left:3px solid transparent; text-decoration:none;'"
                active-class="sidebar-active"
                :title="!sidebarOpen ? item.label : ''"
              >
                <component :is="item.icon" style="width:17px; height:17px; flex-shrink:0;" />
                <span v-if="sidebarOpen">{{ item.label }}</span>
              </router-link>

              <!-- Menú desplegable -->
              <div v-else>
                <button
                  @click="toggleSubmenu(item.label)"
                  class="flex items-center w-full transition-all"
                  :class="sidebarOpen ? 'justify-between' : 'justify-center'"
                  :style="sidebarOpen
                    ? 'padding:8px 14px; color:rgba(255,255,255,0.65); background:none; border:none; border-left:3px solid transparent; cursor:pointer; font-size:13.5px; white-space:nowrap;'
                    : 'padding:10px 0; color:rgba(255,255,255,0.65); background:none; border:none; cursor:pointer;'"
                  :title="!sidebarOpen ? item.label : ''"
                >
                  <div class="flex items-center" :class="sidebarOpen ? 'gap-2' : ''">
                    <component :is="item.icon" style="width:17px; height:17px; flex-shrink:0;" />
                    <span v-if="sidebarOpen">{{ item.label }}</span>
                  </div>
                  <ChevronDown
                    v-if="sidebarOpen"
                    style="width:14px; height:14px; transition:transform 0.2s;"
                    :style="openSubmenus[item.label] ? 'transform:rotate(180deg)' : ''"
                  />
                </button>

                <!-- Hijos -->
                <div v-if="openSubmenus[item.label] && sidebarOpen">
                  <router-link
                    v-for="child in item.children"
                    :key="child.to"
                    :to="child.to"
                    class="flex items-center gap-2"
                    style="
                      padding:7px 14px 7px 38px;
                      font-size:13px;
                      color:rgba(255,255,255,0.5);
                      text-decoration:none;
                      border-left:3px solid transparent;
                      white-space:nowrap;
                    "
                    active-class="sidebar-active"
                  >
                    <span style="width:4px; height:4px; border-radius:50%; background:rgba(255,255,255,0.3); flex-shrink:0;"></span>
                    {{ child.label }}
                  </router-link>
                </div>
              </div>

            </template>
          </template>
        </template>
      </aside>

      <!-- Contenido principal -->
      <main class="flex-1 overflow-y-auto" style="padding:20px;">
        <router-view v-slot="{ Component }">
          <keep-alive :include="['DocentesPage', 'EstudiantesPage', 'SecretariaDashboard', 'DashboardPage']">
            <component :is="Component" />
          </keep-alive>
        </router-view>
      </main>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, reactive } from 'vue'
import { useRouter } from 'vue-router'

import { useAuthStore } from '@/modules/auth/store/authStore'
import { getRoleLabel } from '@/shared/utils/helpers'
import { useNotify } from '@/shared/composables/useNotify'

import {
  LogOut,
  LayoutDashboard,
  FileText,
  Users,
  User,
  BarChart2,
  Database,
  GraduationCap,
  BookOpen,
  CalendarDays,
  ClipboardCheck,
  Settings,
  UserCog,
  TrendingUp,
  Menu,
  ChevronDown,
} from 'lucide-vue-next'

const authStore = useAuthStore()
const router = useRouter()
const notify = useNotify()

const sidebarOpen = ref(true)
const openSubmenus = reactive({})

function toggleSubmenu(label) {
  openSubmenus[label] = !openSubmenus[label]
}

const roleLabel = computed(() => getRoleLabel(authStore.userRole))

const menuSections = [
  {
    label: 'Admin',
    roles: ['admin'],
    items: [
      { to: '/dashboard',    label: 'Dashboard',   icon: LayoutDashboard },
      { to: '/resoluciones', label: 'Resoluciones', icon: FileText        },
      { to: '/docentes',     label: 'Docentes',     icon: Users           },
      { to: '/reportes',     label: 'Reportes',     icon: TrendingUp      },
      { to: '/inscritos',    label: 'Inscritos',    icon: ClipboardCheck  },
      {
        label: 'Horarios',
        icon: CalendarDays,
        children: [
          { to: '/reporte-horario-completo', label: 'Horario Completo' },
          { to: '/reporte-horario-resumen',  label: 'Horario Resumen'  },
        ],
      },
      { to: '/usuarios',  label: 'Usuarios',   icon: UserCog  },
      { to: '/config-bd', label: 'Config. BD', icon: Settings },
    ],
  },
  {
    label: 'Secretaria',
    roles: ['secretaria'],
    items: [
      { to: '/secretaria/dashboard',   label: 'Dashboard',   icon: LayoutDashboard },
      { to: '/secretaria/estudiantes', label: 'Estudiantes', icon: GraduationCap   },
      { to: '/secretaria/docentes',    label: 'Docentes',    icon: BookOpen        },
    ],
  },
  {
    label: 'Sec. Talleres',
    roles: ['secretaria_talleres'],
    items: [
      { to: '/secretariaTalleres/dashboard',  label: 'Dashboard',   icon: LayoutDashboard },
      { to: '/secretariaTalleres/docentes',   label: 'Docentes',    icon: BookOpen        },
      { to: '/secretariaTalleres/estudiante', label: 'Estudiante',  icon: GraduationCap   },
    ],
  },
]

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