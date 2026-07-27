<!-- src/modules/secretaria_talleres/views/DashboardPage.vue -->
<template>
  <div class="min-h-screen bg-[var(--bg)]">

    <!-- Header -->
    <div class="sticky top-0 z-20 bg-[var(--surface)]/90 backdrop-blur-sm border-b border-[var(--border)] px-6 py-5">
      <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
          <h1 class="text-2xl font-bold text-[var(--text)] tracking-tight">Dashboard de Talleres</h1>
          <p class="text-sm text-[var(--muted)] mt-1 flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-[rgb(8,31,51)]" />
            Gestión {{ PERIODOS[gestion.periodo] || gestion.periodo }}/{{ gestion.anio }}
          </p>
        </div>

        <div class="flex items-center gap-3">
          <!-- Tabs Estudiantes / Docentes -->
          <div class="flex bg-[var(--bg)] rounded-xl p-1 gap-1">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              @click="activeTab = tab.id"
              :class="[
                'flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition-all duration-200',
                activeTab === tab.id
                  ? 'bg-[var(--surface)] text-[var(--text)] shadow-sm'
                  : 'text-[var(--muted)] hover:text-[var(--text)]'
              ]"
            >
              <component :is="tab.icon" class="w-4 h-4" />
              {{ tab.label }}
            </button>
          </div>

          <button
            @click="recargarDatos"
            :disabled="loading"
            class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-[var(--muted)] bg-[var(--surface)] border border-[var(--border)] rounded-lg hover:bg-[var(--hover)] hover:border-[rgb(8,31,51)]/30 active:scale-95 transition-all duration-150 disabled:opacity-60 disabled:cursor-not-allowed"
          >
            <RefreshCw :class="['w-4 h-4', loading && 'animate-spin']" />
            {{ loading ? 'Actualizando…' : 'Actualizar' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ═══ Layout principal: cards a la izquierda + contenido a la derecha ═══ -->
    <div class="p-6 max-w-7xl mx-auto">
      <div class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 items-start">

        <!-- Columna izquierda: KPI Cards en columna -->
        <DashboardCards
          :kpis="kpis"
          :loading="loading"
          vertical
          @cardClick="handleCardClick"
        />

        <!-- Columna derecha: gráfico + listas -->
        <Transition
          mode="out-in"
          enter-active-class="transition-all duration-200 ease-out"
          enter-from-class="opacity-0 translate-y-1.5"
          enter-to-class="opacity-100 translate-y-0"
          leave-active-class="transition-all duration-150 ease-in"
          leave-from-class="opacity-100 translate-y-0"
          leave-to-class="opacity-0 -translate-y-1.5"
        >
          <!-- ════════════════════ TAB: ESTUDIANTES ════════════════════ -->
          <div v-if="activeTab === 'estudiantes'" key="estudiantes" class="space-y-6">

            <!-- Gráfico: Estudiantes por Taller -->
            <div class="bg-[var(--surface)] rounded-2xl border border-[var(--border)] p-6">
              <h2 class="text-lg font-semibold text-[var(--text)] mb-4">Estudiantes por Taller</h2>
              <div class="h-56 relative">
                <div v-if="loading" class="absolute inset-0 flex items-center justify-center">
                  <div class="w-8 h-8 border-2 border-[var(--border)] border-t-[rgb(8,31,51)] rounded-full animate-spin" />
                </div>
                <Bar v-else-if="hayDatosPorTaller" :data="chartEstTaller" :options="chartOptionsBar" />
                <EmptyState v-else icon="chart" texto="Sin inscripciones registradas en esta gestión" />
              </div>
            </div>

            <!-- Estudiantes Recientes -->
            <div class="bg-[var(--surface)] rounded-2xl border border-[var(--border)] p-6">
              <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-[var(--text)]">Estudiantes Recientes</h2>
                <router-link
                  to="/secretariaTalleres/estudiante"
                  class="text-sm font-medium text-[rgb(8,31,51)] hover:opacity-75 flex items-center gap-1 transition-opacity duration-150"
                >
                  Ver todos <span aria-hidden="true">→</span>
                </router-link>
              </div>

              <div v-if="loading" class="space-y-3">
                <div v-for="i in 4" :key="i" class="h-10 bg-[var(--border)] rounded-lg animate-pulse" />
              </div>

              <TransitionGroup
                v-else-if="kpis.estudiantes?.recientes?.length"
                tag="div"
                class="divide-y divide-[var(--border)]"
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="opacity-0 translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                move-class="transition-transform duration-300 ease-out"
              >
                <div
                  v-for="est in kpis.estudiantes.recientes.slice(0, 5)"
                  :key="est.codigo"
                  class="flex items-center justify-between gap-3 py-3 rounded-lg hover:bg-[var(--hover)] transition-colors duration-150 cursor-pointer px-2"
                  @click="irAEstudiante(est.codigo)"
                >
                  <div class="min-w-0">
                    <p class="text-sm font-medium text-[var(--text)] truncate">{{ formatNombre(est.nombre) }}</p>
                    <p class="text-xs text-[var(--muted)] truncate">{{ est.taller }}</p>
                  </div>
                  <span class="text-xs text-[var(--muted)] flex-shrink-0">{{ formatFecha(est.fecha) }}</span>
                </div>
              </TransitionGroup>

              <EmptyState v-else icon="users" texto="Aún no hay estudiantes inscritos en esta gestión" />
            </div>
          </div>

          <!-- ════════════════════ TAB: DOCENTES ════════════════════ -->
          <div v-else key="docentes" class="space-y-6">

            <!-- Docentes por Taller -->
            <div class="bg-[var(--surface)] rounded-2xl border border-[var(--border)] p-6">
              <h2 class="text-lg font-semibold text-[var(--text)] mb-4">Docentes por Taller</h2>

              <div v-if="loading" class="space-y-3">
                <div v-for="i in 4" :key="i" class="h-10 bg-[var(--border)] rounded-lg animate-pulse" />
              </div>

              <div v-else-if="kpis.docentes?.porTaller?.length" class="divide-y divide-[var(--border)]">
                <div
                  v-for="item in kpis.docentes.porTaller"
                  :key="item.taller"
                  class="flex items-center justify-between gap-3 py-3"
                >
                  <span class="text-sm text-[var(--text)] font-medium">{{ item.taller }}</span>
                  <span
                    :class="[
                      'text-sm',
                      esSinDesignar(item.docente) ? 'text-amber-600 italic' : 'text-[var(--muted)]'
                    ]"
                  >{{ formatNombre(item.docente) }}</span>
                </div>
              </div>

              <EmptyState v-else icon="book" texto="Sin talleres con docentes asignados" />
            </div>

            <!-- Docentes Recientes -->
            <div class="bg-[var(--surface)] rounded-2xl border border-[var(--border)] p-6">
              <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-[var(--text)]">Docentes Recientes</h2>
                <router-link
                  to="/secretariaTalleres/docentes"
                  class="text-sm font-medium text-[rgb(8,31,51)] hover:opacity-75 flex items-center gap-1 transition-opacity duration-150"
                >
                  Ver todos <span aria-hidden="true">→</span>
                </router-link>
              </div>

              <div v-if="loading" class="space-y-3">
                <div v-for="i in 4" :key="i" class="h-10 bg-[var(--border)] rounded-lg animate-pulse" />
              </div>

              <TransitionGroup
                v-else-if="docentesRecientes?.length"
                tag="div"
                class="divide-y divide-[var(--border)]"
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="opacity-0 translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                move-class="transition-transform duration-300 ease-out"
              >
                <div
                  v-for="docente in docentesRecientes.slice(0, 5)"
                  :key="docente.codigo"
                  class="flex items-center justify-between gap-3 py-3 rounded-lg hover:bg-[var(--hover)] transition-colors duration-150 cursor-pointer px-2"
                  @click="irADocente(docente.codigo)"
                >
                  <p class="text-sm font-medium text-[var(--text)] truncate">{{ formatNombre(docente.nombre) }}</p>
                  <p class="text-xs text-[var(--muted)] truncate flex-shrink-0">{{ docente.codigo || '—' }}</p>
                </div>
              </TransitionGroup>

              <EmptyState v-else icon="users" texto="Sin docentes recientes" />
            </div>
          </div>
        </Transition>

      </div>
    </div>
  </div>
</template>

<script setup>
defineOptions({ name: 'DashboardPage' })
import { ref, computed, onMounted, h } from 'vue'
import { useRouter } from 'vue-router'
import { RefreshCw, Users, GraduationCap, BarChart3, BookOpen } from 'lucide-vue-next'
import { Bar } from 'vue-chartjs'
import { useDocentesRecientes } from '@/shared/composables/useDocentesRecientes'
import {
  Chart as ChartJS, Title, Tooltip, Legend,
  BarElement, CategoryScale, LinearScale
} from 'chart.js'
import DashboardCards from '../components/DashboardCards.vue'
import { dashboardService } from '../services/dashboardService'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale)

// ── Estado vacío reutilizable ───────────────────────────────────────────────
const EMPTY_ICONS = { chart: BarChart3, users: Users, book: BookOpen }
const EmptyState = (props) => h(
  'div',
  { class: 'flex flex-col items-center justify-center text-[var(--muted)] py-10' },
  [
    h(EMPTY_ICONS[props.icon] || Users, { class: 'w-7 h-7 mb-2 opacity-40' }),
    h('p', { class: 'text-sm' }, props.texto)
  ]
)
EmptyState.props = ['icon', 'texto']

const router = useRouter()
const kpis = ref({})
const loading = ref(true)
const activeTab = ref('estudiantes')
const { recientes: docentesRecientes } = useDocentesRecientes()

const tabs = [
  { id: 'estudiantes', label: 'Estudiantes', icon: GraduationCap },
  { id: 'docentes', label: 'Docentes', icon: Users },
]

const PERIODOS = { '1': 'I', '2': 'II' }

// ── Gestión calculada por fecha ─────────────────────────────────────────────
function gestionPorFecha() {
  const hoy = new Date()
  const mes = hoy.getMonth() + 1
  return {
    anio: hoy.getFullYear(),
    periodo: mes >= 9 ? '2' : '1',
  }
}

const gestion = computed(() => kpis.value.gestion || gestionPorFecha())

// ── Computed ─────────────────────────────────────────────────────────────────
const hayDatosPorTaller = computed(() =>
  (kpis.value.estudiantes?.porTaller || []).some(t => t.cantidad > 0)
)

const coloresTaller = ['#6366f1', '#0d9488', '#f59e0b', '#ef4444', '#8b5cf6', '#3b82f6', '#10b981']

const chartEstTaller = computed(() => ({
  labels: kpis.value.estudiantes?.porTaller?.map(t => t.taller.replace('Taller de ', '')) || [],
  datasets: [{
    label: 'Estudiantes',
    data: kpis.value.estudiantes?.porTaller?.map(t => t.cantidad) || [],
    backgroundColor: coloresTaller,
    borderRadius: 8,
    borderSkipped: false,
    maxBarThickness: 48,
  }]
}))

const chartOptionsBar = {
  responsive: true,
  maintainAspectRatio: false,
  animation: { duration: 500, easing: 'easeOutQuart' },
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: 'rgb(8, 31, 51)',
      padding: 10,
      cornerRadius: 8,
      titleFont: { size: 12, weight: '600' },
      bodyFont: { size: 12 },
    }
  },
  scales: {
    y: { beginAtZero: true, grid: { color: 'rgba(15, 23, 42, 0.08)' }, ticks: { precision: 0 } },
    x: { grid: { display: false } }
  }
}

// ── Lifecycle ────────────────────────────────────────────────────────────────
onMounted(async () => await cargarDatos())

async function cargarDatos() {
  loading.value = true
  try {
    const g = gestionPorFecha()
    kpis.value = await dashboardService.getKPIs({ anio: g.anio, periodo: g.periodo })
  } catch (error) {
    console.error('Error cargando dashboard:', error)
  } finally {
    loading.value = false
  }
}

async function recargarDatos() {
  await cargarDatos()
}

function handleCardClick(id) {
  const rutas = {
    estudiantes: '/secretaria-talleres/estudiantes',
    docentes: '/secretaria-talleres/docentes',
    talleres: '/secretaria-talleres/talleres',
  }
  if (id === 'estudiantes' || id === 'docentes') activeTab.value = id
  if (rutas[id]) router.push(rutas[id])
}

function irAEstudiante(codigo) {
  router.push(`/secretaria-talleres/estudiantes?codigo=${codigo}`)
}

function irADocente(codigo) {
  router.push(`/secretaria-talleres/docentes?codigo=${codigo}`)
}

function formatNombre(nombre) {
  if (!nombre) return 'Sin nombre'
  return nombre.split(' ').map(p => p.charAt(0) + p.slice(1).toLowerCase()).join(' ')
}

function esSinDesignar(nombre) {
  if (!nombre) return true
  return /por designar/i.test(nombre)
}

function formatFecha(fecha) {
  if (!fecha) return ''
  return new Date(fecha).toLocaleDateString('es-BO', { day: '2-digit', month: 'short' })
}
</script>