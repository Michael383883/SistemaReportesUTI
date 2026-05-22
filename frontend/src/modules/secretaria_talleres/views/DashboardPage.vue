<!-- src/modules/secretaria_talleres/views/DashboardPage.vue -->
<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 to-white">

    <!-- Header -->
    <div class="bg-white border-b border-slate-200 px-6 py-5">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-slate-800">Dashboard de Talleres</h1>
          <p class="text-sm text-slate-500 mt-1">Secretaría de Talleres · {{ periodoActual }}</p>
        </div>
        <div class="flex items-center gap-3">
          <!-- Tabs Docentes / Estudiantes -->
          <div class="flex bg-slate-100 rounded-xl p-1 gap-1">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              @click="activeTab = tab.id"
              :class="[
                'flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition-all',
                activeTab === tab.id
                  ? 'bg-white text-slate-800 shadow-sm'
                  : 'text-slate-500 hover:text-slate-700'
              ]"
            >
              <component :is="tab.icon" class="w-4 h-4" />
              {{ tab.label }}
            </button>
          </div>
          <button
            @click="recargarDatos"
            class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors"
          >
            <RefreshCw :class="['w-4 h-4', loading && 'animate-spin']" />
            Actualizar
          </button>
        </div>
      </div>
    </div>

    <div class="p-6 space-y-6">

      <!-- KPI Cards -->
      <DashboardCards :kpis="kpis" :loading="loading" @cardClick="handleCardClick" />

      <!-- ════════════════════ TAB: ESTUDIANTES ════════════════════ -->
      <template v-if="activeTab === 'estudiantes'">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

          <!-- Gráfico: Estudiantes por Taller -->
          <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-6">
            <div class="flex items-center justify-between mb-6">
              <h2 class="text-lg font-semibold text-slate-800">Estudiantes por Taller</h2>
              <div class="flex items-center gap-2">
                <button
                  v-for="view in ['bar', 'pie']"
                  :key="view"
                  @click="chartViewEst = view"
                  :class="[
                    'px-3 py-1.5 text-xs font-medium rounded-lg transition-colors',
                    chartViewEst === view
                      ? 'bg-indigo-50 text-indigo-700 border border-indigo-200'
                      : 'text-slate-500 hover:bg-slate-50'
                  ]"
                >{{ view === 'bar' ? 'Barras' : 'Circular' }}</button>
              </div>
            </div>
            <div class="h-72">
              <Bar v-if="chartViewEst === 'bar'" :data="chartEstTaller" :options="chartOptionsBar" />
              <Pie v-else :data="chartEstTallerPie" :options="chartOptionsPie" />
            </div>
          </div>

          <!-- Estudiantes por Nivel -->
          <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <h2 class="text-lg font-semibold text-slate-800 mb-6">Por Nivel</h2>
            <div class="space-y-4">
              <div v-for="item in kpis.estudiantes?.porNivel" :key="item.nivel" class="group cursor-pointer">
                <div class="flex items-center justify-between mb-1.5">
                  <span class="text-sm font-medium text-slate-700">{{ item.nivel }}</span>
                  <span class="text-sm font-semibold text-slate-800">{{ item.cantidad }}</span>
                </div>
                <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                  <div
                    class="h-full rounded-full transition-all duration-500 group-hover:opacity-80"
                    :style="{
                      width: (item.cantidad / maxNivel) * 100 + '%',
                      backgroundColor: item.color
                    }"
                  />
                </div>
              </div>
            </div>
            <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between text-sm">
              <span class="text-slate-500">Total</span>
              <span class="font-bold text-slate-800">{{ kpis.estudiantes?.total ?? 0 }}</span>
            </div>
          </div>
        </div>

        <!-- Estudiantes Recientes -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-semibold text-slate-800">Estudiantes Recientes</h2>
            <router-link
              to="/secretaria-talleres/estudiantes"
              class="text-sm font-medium text-indigo-600 hover:text-indigo-700 transition-colors"
            >
              Ver todos →
            </router-link>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            <div
              v-for="est in kpis.estudiantes?.recientes?.slice(0, 6)"
              :key="est.codigo"
              class="flex items-center gap-3 p-3 rounded-xl hover:bg-slate-50 transition-colors cursor-pointer group"
              @click="irAEstudiante(est.codigo)"
            >
              <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-600 flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
                {{ iniciales(est.nombre) }}
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-slate-800 truncate group-hover:text-indigo-700 transition-colors">
                  {{ formatNombre(est.nombre) }}
                </p>
                <p class="text-xs text-slate-400">{{ est.taller }} · {{ est.nivel }}</p>
              </div>
              <span class="text-xs text-slate-400 flex-shrink-0">{{ formatFecha(est.fecha) }}</span>
            </div>
          </div>
        </div>
      </template>

      <!-- ════════════════════ TAB: DOCENTES ════════════════════ -->
      <template v-if="activeTab === 'docentes'">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

          <!-- Docentes por Taller (tabla) -->
          <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-6">
            <h2 class="text-lg font-semibold text-slate-800 mb-6">Docentes por Taller</h2>
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead>
                  <tr class="border-b border-slate-100">
                    <th class="text-left pb-3 text-xs font-semibold text-slate-400 uppercase tracking-wide">Taller</th>
                    <th class="text-left pb-3 text-xs font-semibold text-slate-400 uppercase tracking-wide">Docente</th>
                    <th class="text-right pb-3 text-xs font-semibold text-slate-400 uppercase tracking-wide">Horas</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                  <tr
                    v-for="item in kpis.docentes?.porTaller"
                    :key="item.taller"
                    class="hover:bg-slate-50 transition-colors group"
                  >
                    <td class="py-3">
                      <span class="text-slate-700 font-medium">{{ item.taller }}</span>
                    </td>
                    <td class="py-3">
                      <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-teal-100 flex items-center justify-center text-teal-700 text-xs font-bold">
                          {{ iniciales(item.docente) }}
                        </div>
                        <span class="text-slate-600">{{ formatNombre(item.docente) }}</span>
                      </div>
                    </td>
                    <td class="py-3 text-right">
                      <span
                        :class="[
                          'text-sm font-bold px-2.5 py-1 rounded-lg',
                          item.horas === 0
                            ? 'bg-red-50 text-red-600'
                            : item.horas >= 20
                              ? 'bg-teal-50 text-teal-700'
                              : 'bg-slate-100 text-slate-700'
                        ]"
                      >{{ item.horas }}h</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Distribución carga horaria docentes -->
          <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <h2 class="text-lg font-semibold text-slate-800 mb-6">Carga Horaria</h2>
            <div class="space-y-4">
              <div v-for="item in kpis.docentes?.cargaHoraria" :key="item.rango" class="group">
                <div class="flex items-center justify-between mb-1.5">
                  <span class="text-sm font-medium text-slate-600">{{ item.rango }}</span>
                  <span class="text-sm font-semibold text-slate-800">{{ item.cantidad }} doc.</span>
                </div>
                <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                  <div
                    class="h-full rounded-full transition-all duration-500"
                    :style="{
                      width: (item.cantidad / maxCargaDoc) * 100 + '%',
                      backgroundColor: item.color
                    }"
                  />
                </div>
              </div>
            </div>
            <div class="mt-6 pt-4 border-t border-slate-100 space-y-2">
              <div class="flex items-center justify-between text-sm">
                <span class="text-slate-500">Total Docentes</span>
                <span class="font-bold text-slate-800">{{ kpis.docentes?.total ?? 0 }}</span>
              </div>
              <div class="flex items-center justify-between text-sm">
                <span class="text-slate-500">Horas Promedio</span>
                <span class="font-bold text-teal-700">{{ kpis.docentes?.horasPromedio ?? 0 }}h</span>
              </div>
              <div class="flex items-center justify-between text-sm">
                <span class="text-slate-500">Sin Carga</span>
                <span class="font-bold text-red-600">{{ kpis.docentes?.sinCarga ?? 0 }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Docentes Recientes -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-semibold text-slate-800">Docentes Recientes</h2>
            <router-link
              to="/secretaria-talleres/docentes"
              class="text-sm font-medium text-teal-600 hover:text-teal-700 transition-colors"
            >
              Ver todos →
            </router-link>
          </div>
          <div class="space-y-3">
            <div
              v-for="doc in kpis.docentes?.recientes?.slice(0, 5)"
              :key="doc.codigo"
              class="flex items-center gap-4 p-3 rounded-xl hover:bg-slate-50 transition-colors cursor-pointer group"
              @click="irADocente(doc.codigo)"
            >
              <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
                {{ iniciales(doc.nombre) }}
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-slate-800 truncate group-hover:text-teal-700 transition-colors">
                  {{ formatNombre(doc.nombre) }}
                </p>
                <p class="text-xs text-slate-400">{{ doc.taller }} · {{ doc.grado }}</p>
              </div>
              <div class="text-right flex-shrink-0">
                <p :class="['text-sm font-semibold', doc.horas === 0 ? 'text-red-500' : 'text-slate-800']">
                  {{ doc.horas }}h
                </p>
                <p class="text-xs text-slate-400">{{ formatFecha(doc.fecha) }}</p>
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- Alertas (siempre visibles) -->
      <div v-if="kpis.alertas?.length" class="bg-white rounded-2xl border border-slate-200 p-6">
        <h2 class="text-lg font-semibold text-slate-800 mb-4">Alertas y Notificaciones</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
          <div
            v-for="alerta in kpis.alertas"
            :key="alerta.id"
            :class="[
              'flex items-start gap-3 p-4 rounded-xl border transition-all hover:shadow-md',
              alertaStyles[alerta.tipo]?.bg || 'bg-slate-50 border-slate-200'
            ]"
          >
            <div :class="['w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0', alertaStyles[alerta.tipo]?.iconBg]">
              <component :is="alertaStyles[alerta.tipo]?.icon" :class="['w-5 h-5', alertaStyles[alerta.tipo]?.iconColor]" />
            </div>
            <div class="flex-1">
              <p :class="['text-sm font-medium', alertaStyles[alerta.tipo]?.text]">{{ alerta.mensaje }}</p>
              <router-link
                v-if="alerta.accion"
                :to="alerta.accion"
                class="text-xs font-medium text-indigo-600 hover:text-indigo-700 mt-1 inline-block"
              >Ver detalles →</router-link>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { RefreshCw, AlertTriangle, Info, AlertCircle, Users, GraduationCap } from 'lucide-vue-next'
import { Bar, Pie } from 'vue-chartjs'
import {
  Chart as ChartJS, Title, Tooltip, Legend,
  BarElement, CategoryScale, LinearScale, ArcElement
} from 'chart.js'
import DashboardCards from '../components/DashboardCards.vue'
import { dashboardService } from '../services/dashboardService'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement)

const router = useRouter()
const kpis = ref({})
const loading = ref(true)
const periodoActual = ref('Mayo 2026')
const activeTab = ref('estudiantes')
const chartViewEst = ref('bar')

const tabs = [
  { id: 'estudiantes', label: 'Estudiantes', icon: GraduationCap },
  { id: 'docentes', label: 'Docentes', icon: Users },
]

const alertaStyles = {
  warning: {
    bg: 'bg-amber-50 border-amber-200',
    iconBg: 'bg-amber-100',
    icon: AlertTriangle,
    iconColor: 'text-amber-600',
    text: 'text-amber-800'
  },
  info: {
    bg: 'bg-blue-50 border-blue-200',
    iconBg: 'bg-blue-100',
    icon: Info,
    iconColor: 'text-blue-600',
    text: 'text-blue-800'
  },
  error: {
    bg: 'bg-red-50 border-red-200',
    iconBg: 'bg-red-100',
    icon: AlertCircle,
    iconColor: 'text-red-600',
    text: 'text-red-800'
  }
}

// ── Computed ─────────────────────────────────────────────────────────────────
const maxNivel = computed(() => {
  if (!kpis.value.estudiantes?.porNivel?.length) return 1
  return Math.max(...kpis.value.estudiantes.porNivel.map(n => n.cantidad))
})

const maxCargaDoc = computed(() => {
  if (!kpis.value.docentes?.cargaHoraria?.length) return 1
  return Math.max(...kpis.value.docentes.cargaHoraria.map(c => c.cantidad))
})

const coloresTaller = ['#6366f1', '#0d9488', '#f59e0b', '#ef4444', '#8b5cf6', '#3b82f6', '#10b981']

const chartEstTaller = computed(() => ({
  labels: kpis.value.estudiantes?.porTaller?.map(t => t.taller.replace('Taller de ', '')) || [],
  datasets: [{
    label: 'Estudiantes',
    data: kpis.value.estudiantes?.porTaller?.map(t => t.cantidad) || [],
    backgroundColor: coloresTaller,
    borderRadius: 8,
    borderSkipped: false,
  }]
}))

const chartEstTallerPie = computed(() => ({
  labels: kpis.value.estudiantes?.porTaller?.map(t => t.taller.replace('Taller de ', '')) || [],
  datasets: [{
    data: kpis.value.estudiantes?.porTaller?.map(t => t.cantidad) || [],
    backgroundColor: coloresTaller,
    borderWidth: 2,
    borderColor: '#fff',
  }]
}))

const chartOptionsBar = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: {
    y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
    x: { grid: { display: false } }
  }
}

const chartOptionsPie = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom',
      labels: { padding: 16, usePointStyle: true, font: { size: 11 } }
    }
  }
}

// ── Lifecycle ────────────────────────────────────────────────────────────────
onMounted(async () => await cargarDatos())

async function cargarDatos() {
  loading.value = true
  try {
    kpis.value = await dashboardService.getKPIs()
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
  if (id === 'estudiantes') {
    activeTab.value = 'estudiantes'
    router.push('/secretaria-talleres/estudiantes')
  } else if (id === 'docentes') {
    activeTab.value = 'docentes'
    router.push('/secretaria-talleres/docentes')
  }
}

function irAEstudiante(codigo) {
  router.push(`/secretaria-talleres/estudiantes?codigo=${codigo}`)
}

function irADocente(codigo) {
  router.push(`/secretaria-talleres/docentes?codigo=${codigo}`)
}

function iniciales(nombre) {
  if (!nombre) return '?'
  const partes = nombre.trim().split(' ').filter(Boolean)
  return partes.length >= 2 ? partes[0][0] + partes[1][0] : partes[0]?.[0] || '?'
}

function formatNombre(nombre) {
  if (!nombre) return 'Sin nombre'
  return nombre.split(' ').map(p => p.charAt(0) + p.slice(1).toLowerCase()).join(' ')
}

function formatFecha(fecha) {
  if (!fecha) return ''
  return new Date(fecha).toLocaleDateString('es-BO', { day: '2-digit', month: 'short' })
}
</script>