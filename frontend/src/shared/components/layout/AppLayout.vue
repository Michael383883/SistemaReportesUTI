<template>
  <div class="flex flex-col h-screen" style="background:var(--bg); color:var(--text);">

    <!-- ═══════════════ TOPBAR ═══════════════ -->
    <header
      class="flex items-center justify-between shrink-0 z-10"
      style="height:64px; background:#081F33; padding:0 20px;"
    >
      <!-- Logo + Título en el topbar -->
      <div style="display:flex; align-items:center; gap:12px;">

        <!-- Logo SVG real -->
        <img
          :src="logo"
          alt="SIA-UTI"
          style="height:48px; width:auto; flex-shrink:0;"
        />

        <!-- Texto al lado, imitando la tipografía del logo -->
        <div style="display:flex; flex-direction:column; line-height:1.15;">
          <span style="font-family:'Poppins','Segoe UI',sans-serif; font-weight:800; font-size:21px; letter-spacing:-0.02em; white-space:nowrap;">
            <span style="color:#ffffff;">SIA-</span><span style="color:#ef4444;">UTI</span>
          </span>
          <span style="font-size:11px; font-weight:600; letter-spacing:0.12em; color:rgba(255,255,255,0.55); text-transform:uppercase; white-space:nowrap; margin-top:2px;">
            Sistema de Información Académica
          </span>
        </div>
      </div>

      <!-- Perfil con dropdown -->
      <div class="relative" ref="profileRef">
        <button
          @click="profileOpen = !profileOpen"
          class="flex items-center gap-2 rounded-lg"
          style="
            padding:4px 8px 4px 4px;
            background:rgba(255,255,255,0.06);
            border:1px solid rgba(255,255,255,0.12);
            cursor:pointer; transition:background 0.15s;
          "
          @mouseover="e => e.currentTarget.style.background='rgba(255,255,255,0.12)'"
          @mouseleave="e => e.currentTarget.style.background='rgba(255,255,255,0.06)'"
        >
          <div
            class="flex items-center justify-center rounded-full shrink-0"
            style="width:28px; height:28px; background:#D28B45; color:#fff;"
          >
            <User style="width:15px; height:15px;" />
          </div>
          <span style="color:#ffffff; font-size:13px; font-weight:500; white-space:nowrap;">
            {{ authStore.user?.name }}
          </span>
          <ChevronDown
            style="width:13px; height:13px; color:rgba(255,255,255,0.5); transition:transform 0.2s;"
            :style="profileOpen ? 'transform:rotate(180deg)' : ''"
          />
        </button>

        <!-- Dropdown -->
        <div
          v-if="profileOpen"
          style="
            position:absolute; top:calc(100% + 8px); right:0;
            background:#ffffff; border-radius:10px;
            box-shadow:0 8px 24px rgba(0,0,0,0.15);
            border:1px solid rgba(0,0,0,0.08);
            min-width:190px; overflow:hidden; z-index:200;
          "
        >
          <div style="padding:12px 14px 10px; border-bottom:1px solid #f0f0f0;">
            <div style="font-size:13px; font-weight:600; color:#081F33;">{{ authStore.user?.name }}</div>
            <div style="font-size:12px; color:#D28B45; margin-top:1px;">{{ roleLabel }}</div>
          </div>

          <div style="padding:6px 0;">
            <button
              v-if="authStore.userRole === 'admin'"
              @click="goTo('/perfil')"
              class="flex items-center gap-3 w-full"
              style="padding:9px 14px; background:none; border:none; cursor:pointer; font-size:13.5px; color:#2d3748; text-align:left; transition:background 0.12s;"
              @mouseover="e => e.currentTarget.style.background='#f7f8fa'"
              @mouseleave="e => e.currentTarget.style.background='none'"
            >
              <User style="width:16px; height:16px; color:#6b7280; flex-shrink:0;" /> Perfil
            </button>

            <button
              @click="toggleDarkMode"
              class="flex items-center justify-between w-full"
              style="padding:9px 14px; background:none; border:none; cursor:pointer; font-size:13.5px; color:#2d3748; text-align:left; transition:background 0.12s;"
              @mouseover="e => e.currentTarget.style.background='#f7f8fa'"
              @mouseleave="e => e.currentTarget.style.background='none'"
            >
              <div class="flex items-center gap-3">
                <Moon style="width:16px; height:16px; color:#6b7280; flex-shrink:0;" /> Modo oscuro
              </div>
              <div
                style="width:32px; height:18px; border-radius:9px; transition:background 0.2s; flex-shrink:0; position:relative;"
                :style="darkMode ? 'background:#D28B45;' : 'background:#d1d5db;'"
              >
                <div
                  style="position:absolute; top:2px; width:14px; height:14px; border-radius:50%; background:#fff; transition:left 0.2s; box-shadow:0 1px 3px rgba(0,0,0,0.2);"
                  :style="darkMode ? 'left:16px;' : 'left:2px;'"
                />
              </div>
            </button>

            
          </div>

          <div style="border-top:1px solid #f0f0f0; padding:6px 0 4px;">
            <button
              @click="handleLogout"
              class="flex items-center gap-3 w-full"
              style="padding:9px 14px; background:none; border:none; cursor:pointer; font-size:13.5px; color:#ef4444; text-align:left; transition:background 0.12s;"
              @mouseover="e => e.currentTarget.style.background='#fff5f5'"
              @mouseleave="e => e.currentTarget.style.background='none'"
            >
              <LogOut style="width:16px; height:16px; flex-shrink:0;" /> Cerrar sesión
            </button>
          </div>
        </div>
      </div>
    </header>

    <!-- ═══════════════ BODY ═══════════════ -->
    <div class="flex flex-1 overflow-hidden">

      <!-- ═══════════════ SIDEBAR ═══════════════ -->
      <aside
        class="flex flex-col overflow-y-auto overflow-x-hidden shrink-0"
        :style="sidebarOpen ? 'width:200px;' : 'width:52px;'"
        style="background:#081F33; transition:width 0.2s ease; position:relative; z-index:100;"
      >

        <!-- BOTÓN ☰ -->
        <div
          style="height:64px; flex-shrink:0; border-bottom:1px solid rgba(255,255,255,0.07);"
          :style="sidebarOpen
            ? 'display:flex; align-items:center; justify-content:flex-start; padding-left:11px;'
            : 'display:flex; align-items:center; justify-content:center;'"
        >
          <button
            @click="sidebarOpen = !sidebarOpen"
            style="
              width:30px; height:30px;
              background:rgba(255,255,255,0.07);
              border:1px solid rgba(255,255,255,0.13);
              border-radius:7px; cursor:pointer;
              display:flex; align-items:center; justify-content:center;
              transition:background 0.15s;
            "
            @mouseover="e => e.currentTarget.style.background='rgba(255,255,255,0.15)'"
            @mouseleave="e => e.currentTarget.style.background='rgba(255,255,255,0.07)'"
            :title="sidebarOpen ? 'Colapsar menú' : 'Expandir menú'"
          >
            <Menu style="width:15px; height:15px; color:#ffffff;" />
          </button>
        </div>

        <!-- SECCIÓN ROL + MENÚ -->
        <div class="flex flex-col flex-1" style="padding-top:4px;">

          <template v-if="!authStore.user">
            <div style="padding:8px; display:flex; flex-direction:column; gap:6px;">
              <div v-for="n in 4" :key="n" style="height:32px; background:rgba(255,255,255,0.06); border-radius:6px;" />
            </div>
          </template>

          <template v-else>
            <template v-for="section in filteredMenu" :key="section.label">

              <!-- Etiqueta rol → click colapsa (solo expandido) -->
              <button
                v-if="sidebarOpen"
                @click="sidebarOpen = false"
                style="
                  display:flex; align-items:center; justify-content:space-between;
                  width:100%; padding:7px 14px 5px;
                  background:none; border:none; cursor:pointer;
                  transition:background 0.12s;
                "
                @mouseover="e => e.currentTarget.style.background='rgba(255,255,255,0.04)'"
                @mouseleave="e => e.currentTarget.style.background='none'"
              >
                <span style="
                  font-size:10.5px; font-weight:700;
                  color:rgba(255,255,255,0.3);
                  text-transform:uppercase; letter-spacing:0.1em;
                ">{{ section.label }}</span>
                <ChevronLeft style="width:12px; height:12px; color:rgba(255,255,255,0.25);" />
              </button>

              <!-- Divisor colapsado -->
              <div
                v-else
                style="height:1px; background:rgba(255,255,255,0.07); margin:4px 8px 6px;"
              />

              <!-- Items -->
              <template v-for="item in section.items" :key="item.label">

                <!-- ── Ítem sin hijos ── -->
                <router-link
                  v-if="!item.children"
                  :to="item.to"
                  class="flex items-center transition-all"
                  :class="sidebarOpen ? 'gap-2' : 'justify-center'"
                  :style="sidebarOpen
                    ? 'padding:8px 14px; font-size:13.5px; color:rgba(255,255,255,0.65); border-left:3px solid transparent; text-decoration:none; white-space:nowrap;'
                    : 'padding:10px 0; color:rgba(255,255,255,0.65); border-left:3px solid transparent; text-decoration:none;'"
                  active-class="sidebar-active"
                  :title="!sidebarOpen ? item.label : ''"
                >
                  <component :is="item.icon" style="width:17px; height:17px; flex-shrink:0;" />
                  <span v-if="sidebarOpen">{{ item.label }}</span>
                </router-link>

                <!-- ── Ítem con hijos ── -->
                <div v-else style="position:relative;">

                  <!-- ━━ MODO EXPANDIDO ━━ -->
                  <template v-if="sidebarOpen">
                    <button
                      @click="toggleSubmenu(item.label)"
                      class="flex items-center justify-between w-full"
                      style="padding:8px 14px; color:rgba(255,255,255,0.65); background:none; border:none; border-left:3px solid transparent; cursor:pointer; font-size:13.5px; white-space:nowrap;"
                    >
                      <div class="flex items-center gap-2">
                        <component :is="item.icon" style="width:17px; height:17px; flex-shrink:0;" />
                        <span>{{ item.label }}</span>
                      </div>
                      <ChevronDown
                        style="width:14px; height:14px; transition:transform 0.2s;"
                        :style="openSubmenus[item.label] ? 'transform:rotate(180deg)' : ''"
                      />
                    </button>

                    <div v-if="openSubmenus[item.label]">
                      <router-link
                        v-for="child in item.children"
                        :key="child.to"
                        :to="child.to"
                        class="flex items-center gap-2"
                        style="
                          padding:7px 14px 7px 38px;
                          font-size:13px; color:rgba(255,255,255,0.5);
                          text-decoration:none; border-left:3px solid transparent; white-space:nowrap;
                        "
                        active-class="sidebar-active"
                      >
                        <span style="width:4px; height:4px; border-radius:50%; background:rgba(255,255,255,0.3); flex-shrink:0;"></span>
                        {{ child.label }}
                      </router-link>
                    </div>
                  </template>

                  <!-- ━━ MODO COLAPSADO: ícono + flyout al hover ━━ -->
                  <template v-else>
                    <div
                      class="collapsed-item"
                      :data-flyout-anchor="item.label"
                      @mouseenter="openFlyout(item.label, $event)"
                      @mouseleave="closeFlyout(item.label)"
                    >
                      <!-- Botón ícono — se resalta si algún hijo está activo -->
                      <div
                        style="
                          padding:10px 0;
                          display:flex; align-items:center; justify-content:center;
                          border-left:3px solid transparent;
                          cursor:pointer;
                          transition: color 0.15s, border-color 0.15s, background 0.15s;
                        "
                        :style="isChildActive(item)
                          ? 'color:#D28B45; border-left-color:#D28B45; background:rgba(210,139,69,0.08);'
                          : 'color:rgba(255,255,255,0.65);'"
                        :title="item.label"
                      >
                        <component :is="item.icon" style="width:17px; height:17px; flex-shrink:0;" />
                      </div>

                      <!-- Flyout panel -->
                      <div
                        v-if="flyoutOpen[item.label]"
                        style="
                          position:fixed;
                          left:52px;
                          background:#0d2748;
                          border-radius:0 8px 8px 0;
                          box-shadow:4px 4px 16px rgba(0,0,0,0.4);
                          border:1px solid rgba(255,255,255,0.1);
                          border-left:2px solid #D28B45;
                          min-width:185px;
                          overflow:hidden;
                          z-index:9999;
                        "
                        :style="{ top: flyoutTopMap[item.label] + 'px' }"
                        @mouseenter="openFlyout(item.label)"
                        @mouseleave="closeFlyout(item.label)"
                      >
                        <!-- Encabezado del flyout -->
                        <div style="
                          padding:8px 14px 7px;
                          border-bottom:1px solid rgba(255,255,255,0.08);
                          font-size:10.5px; font-weight:700;
                          color:#D28B45;
                          text-transform:uppercase; letter-spacing:0.1em;
                        ">
                          {{ item.label }}
                        </div>

                        <!-- Links hijos -->
                        <div style="padding:4px 0;">
                          <router-link
                            v-for="child in item.children"
                            :key="child.to"
                            :to="child.to"
                            class="flex items-center gap-2"
                            style="
                              padding:9px 16px;
                              font-size:13px;
                              color:rgba(255,255,255,0.7);
                              text-decoration:none;
                              white-space:nowrap;
                              transition:background 0.12s, color 0.12s;
                            "
                            active-class="flyout-active"
                            @click="closeFlyout(item.label)"
                            @mouseover="e => e.currentTarget.style.background='rgba(210,139,69,0.12)'"
                            @mouseleave="e => e.currentTarget.style.background='transparent'"
                          >
                            <span style="
                              width:5px; height:5px; border-radius:50%;
                              background:rgba(255,255,255,0.25); flex-shrink:0;
                            "></span>
                            {{ child.label }}
                          </router-link>
                        </div>
                      </div>
                    </div>
                  </template>

                </div>

              </template>
            </template>
          </template>
        </div>

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
import { computed, reactive, ref, onMounted, onUnmounted, nextTick } from 'vue'
import { useRouter, useRoute } from 'vue-router'

import { useAuthStore } from '@/modules/auth/store/authStore'
import { getRoleLabel } from '@/shared/utils/helpers'
import { useNotify } from '@/shared/composables/useNotify'
import { useDarkMode } from '@/composables/useDarkMode'

// Logo SIA-UTI (guardado en src/assets/img/)
import logo from '@/assets/img/SIA-UTI-logo.svg'


import {
  LogOut, LayoutDashboard, FileText, Users, User,
  GraduationCap, BookOpen, CalendarDays, ClipboardCheck,
  Settings, UserCog, TrendingUp, Menu,
  ChevronDown, ChevronLeft, Moon, Save,
  BarChart2, Upload, FolderOpen, Download,
} from 'lucide-vue-next'

const authStore = useAuthStore()
const router    = useRouter()
const route     = useRoute()
const notify    = useNotify()

const sidebarOpen  = ref(true)
const openSubmenus = reactive({})
const profileOpen  = ref(false)
const profileRef   = ref(null)
const { darkMode, toggleDarkMode } = useDarkMode()

// ── Flyout state para modo colapsado ──
const flyoutOpen     = reactive({})
const flyoutTopMap   = reactive({})
const flyoutTimers   = {}

function openFlyout(label) {
  if (flyoutTimers[label]) {
    clearTimeout(flyoutTimers[label])
    flyoutTimers[label] = null
  }

  nextTick(() => {
    const el = document.querySelector(`[data-flyout-anchor="${CSS.escape(label)}"]`)
    if (el) {
      const rect = el.getBoundingClientRect()
      flyoutTopMap[label] = rect.top
    }
  })

  flyoutOpen[label] = true
}

function closeFlyout(label) {
  flyoutTimers[label] = setTimeout(() => {
    flyoutOpen[label] = false
  }, 80)
}

function getFlyoutTop(label) {
  return flyoutTopMap[label] ?? 0
}

function toggleSubmenu(label) { openSubmenus[label] = !openSubmenus[label] }
function goTo(path)           { profileOpen.value = false; router.push(path) }

function handleClickOutside(e) {
  if (profileRef.value && !profileRef.value.contains(e.target))
    profileOpen.value = false
}
onMounted(()  => document.addEventListener('mousedown', handleClickOutside))
onUnmounted(() => {
  document.removeEventListener('mousedown', handleClickOutside)
  Object.values(flyoutTimers).forEach(t => t && clearTimeout(t))
})

const roleLabel = computed(() => getRoleLabel(authStore.userRole))

const menuSections = [
  // ─────────────────────────────────────────
  // ROL: ADMIN
  // ─────────────────────────────────────────
  {
    label: 'Admin',
    roles: ['admin'],
    items: [
      { to: '/dashboard', label: 'Dashboard', icon: LayoutDashboard },
      

      {
        label: 'Reportes', icon: BarChart2,
        children: [
          { to: '/reportes/docentes',      label: 'Materias Dictadas'      },
          { to: '/reportes/edicion-tipo-ingreso', label: 'Gestión de Modalidad'          },
        ],
      },

      {
        label: 'Digitalización', icon: Upload,
        children: [
          { to: '/resoluciones/subir',         label: 'Subir Resolución'           },
          { to: '/resoluciones/listado', label: 'Lista de Resoluciones' },
          { to: '/resoluciones/asignar', label: 'Asignar Resolución' },
        ],
      },

      {
        label: 'Horarios', icon: CalendarDays,
        children: [
          { to: '/reporte-horario-completo', label: 'Horario Completo' },
          { to: '/reporte-horario-resumen',  label: 'Horario Resumen'  },
          { to: '/reporte-horario-resumen-dos', label: 'Horario Resumen Dos' },
        ],
      },

      { to: '/inscritos',  label: 'Inscritos',  icon: ClipboardCheck  },

      {
        label: 'Administración', icon: Settings,
        children: [
          { to: '/usuarios',  label: 'Usuarios'       },
          { to: '/config-bd', label: 'Base de datos'  },
        ],
      },
    ],
  },

  // ─────────────────────────────────────────
  // ROL: SECRETARIA
  // ─────────────────────────────────────────
  {
    label: 'Secretaria',
    roles: ['secretaria'],
    items: [
      { to: '/secretaria/dashboard',   label: 'Dashboard',   icon: LayoutDashboard },
      { to: '/secretaria/estudiantes', label: 'Estudiantes', icon: GraduationCap   },
      { to: '/secretaria/docentes',    label: 'Docentes',    icon: BookOpen        },
    ],
  },
   // ─────────────────────────────────────────
  // ROL: UTI
  // ─────────────────────────────────────────
  {
    label: 'Secretaria',
    roles: ['uti'],
    items: [
      { to: '/secretaria/dashboard',   label: 'Dashboard',   icon: LayoutDashboard },
      { to: '/secretaria/estudiantes', label: 'Estudiantes', icon: GraduationCap   },
      { to: '/secretaria/docentes',    label: 'Docentes',    icon: BookOpen        },
    ],
  },
  // ─────────────────────────────────────────
  // ROL: SECRETARIA TALLERES
  // ─────────────────────────────────────────
  {
    label: 'Sec. Talleres',
    roles: ['secretaria_talleres'],
    items: [
      { to: '/secretariaTalleres/dashboard',  label: 'Dashboard',  icon: LayoutDashboard },
      { to: '/secretariaTalleres/docentes',   label: 'Docentes',   icon: BookOpen        },
      { to: '/secretariaTalleres/estudiante', label: 'Estudiante', icon: GraduationCap   },
    ],
  },
]

const filteredMenu = computed(() => {
  if (!authStore.userRole) return []
  return menuSections.filter(s => s.roles.includes(authStore.userRole))
})

function isChildActive(item) {
  return item.children?.some(child => route.path.startsWith(child.to)) ?? false
}

async function handleLogout() {
  profileOpen.value = false
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

.flyout-active {
  color: #D28B45 !important;
  background: rgba(210, 139, 69, 0.12) !important;
}

.collapsed-item {
  position: relative;
}
</style>