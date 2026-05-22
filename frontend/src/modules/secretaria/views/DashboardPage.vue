<!-- src/modules/secretaria/views/DashboardPage.vue -->
<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 to-white">
    <!-- Header -->
    <div class="bg-white border-b border-slate-200 px-6 py-5">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-slate-800">Dashboard de Secretaría</h1>
          <p class="text-sm text-slate-500 mt-1">Panel de control docente · {{ periodoActual }}</p>
        </div>
        <div class="flex items-center gap-3">
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
      <!-- KPIs -->
      <DashboardCards :kpis="kpis" :loading="loading" @cardClick="handleCardClick" />

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Gráfico principal: Docentes por Unidad -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-6">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-semibold text-slate-800">Docentes por Unidad Académica</h2>
            <div class="flex items-center gap-2">
              <button 
                v-for="view in ['bar', 'pie']" 
                :key="view"
                @click="chartView = view"
                :class="[
                  'px-3 py-1.5 text-xs font-medium rounded-lg transition-colors',
                  chartView === view ? 'bg-teal-50 text-teal-700 border border-teal-200' : 'text-slate-500 hover:bg-slate-50'
                ]"
              >
                {{ view === 'bar' ? 'Barras' : 'Circular' }}
              </button>
            </div>
          </div>
          <div class="h-80">
            <Bar v-if="chartView === 'bar'" :data="chartDataUnidades" :options="chartOptions" />
            <Pie v-else :data="chartDataUnidadesPie" :options="chartOptionsPie" />
          </div>
        </div>

        <!-- Distribución por Grado -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
          <h2 class="text-lg font-semibold text-slate-800 mb-6">Por Grado Académico</h2>
          <div class="space-y-4">
            <div 
              v-for="item in kpis.porGrado" 
              :key="item.grado"
              class="group cursor-pointer"
            >
              <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-slate-700">{{ item.grado }}</span>
                <span class="text-sm font-semibold text-slate-800">{{ item.cantidad }}</span>
              </div>
              <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                <div 
                  class="h-full rounded-full transition-all duration-500 group-hover:opacity-80"
                  :style="{ 
                    width: (item.cantidad / maxGrado) * 100 + '%',
                    backgroundColor: item.color 
                  }"
                />
              </div>
            </div>
          </div>
          <div class="mt-6 pt-4 border-t border-slate-100">
            <div class="flex items-center justify-between text-sm">
              <span class="text-slate-500">Total</span>
              <span class="font-bold text-slate-800">{{ kpis.porGrado?.reduce((acc, g) => acc + g.cantidad, 0) || 0 }}</span>
            </div>
          </div>
        </div>

        <!-- Carga Horaria -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
          <h2 class="text-lg font-semibold text-slate-800 mb-6">Distribución de Carga Horaria</h2>
          <div class="space-y-3">
            <div 
              v-for="item in kpis.cargaHoraria" 
              :key="item.rango"
              class="group"
            >
              <div class="flex items-center justify-between mb-1.5">
                <span class="text-xs font-medium text-slate-600">{{ item.rango }}</span>
                <span class="text-xs font-semibold text-slate-800">{{ item.cantidad }} doc.</span>
              </div>
              <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                <div 
                  class="h-full rounded-full bg-gradient-to-r from-teal-400 to-teal-600 transition-all duration-500 group-hover:from-teal-500 group-hover:to-teal-700"
                  :style="{ width: (item.cantidad / maxCarga) * 100 + '%' }"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Últimos docentes agregados -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-6">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-semibold text-slate-800">Docentes Recientes</h2>
            <router-link 
              to="/secretaria/docentes"
              class="text-sm font-medium text-teal-600 hover:text-teal-700 transition-colors"
            >
              Ver todos →
            </router-link>
          </div>
          <div class="space-y-3">
            <div 
              v-for="docente in kpis.docentesRecientes?.slice(0, 5)" 
              :key="docente.codigo"
              class="flex items-center gap-4 p-3 rounded-xl hover:bg-slate-50 transition-colors cursor-pointer group"
              @click="irADocente(docente.codigo)"
            >
              <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
                {{ iniciales(docente.nombre) }}
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-slate-800 truncate group-hover:text-teal-700 transition-colors">
                  {{ formatNombre(docente.nombre) }}
                </p>
                <p class="text-xs text-slate-400">{{ docente.unidad }} · {{ docente.grado }}</p>
              </div>
              <div class="text-right">
                <p class="text-sm font-semibold text-slate-800">{{ docente.horas }}h</p>
                <p class="text-xs text-slate-400">{{ formatFecha(docente.fecha) }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Alertas -->
      <div v-if="kpis.alertas?.length" class="bg-white rounded-2xl border border-slate-200 p-6">
        <h2 class="text-lg font-semibold text-slate-800 mb-4">Alertas y Notificaciones</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div
            v-for="alerta in kpis.alertas"
            :key="alerta.id"
            :class="[
              'flex items-start gap-3 p-4 rounded-xl border transition-all hover:shadow-md',
              alertaStyles[alerta.tipo]?.bg || 'bg-slate-50 border-slate-200'
            ]"
          >
            <div :class="[
              'w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0',
              alertaStyles[alerta.tipo]?.iconBg || 'bg-slate-200'
            ]">
              <component 
                :is="alertaStyles[alerta.tipo]?.icon" 
                :class="['w-5 h-5', alertaStyles[alerta.tipo]?.iconColor || 'text-slate-500']"
              />
            </div>
            <div class="flex-1">
              <p :class="['text-sm font-medium', alertaStyles[alerta.tipo]?.text || 'text-slate-700']">
                {{ alerta.mensaje }}
              </p>
              <router-link
                v-if="alerta.accion"
                :to="alerta.accion"
                class="text-xs font-medium text-teal-600 hover:text-teal-700 mt-1 inline-block"
              >
                Ver detalles →
              </router-link>
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
import { RefreshCw, AlertTriangle, Info, AlertCircle, BellRing } from 'lucide-vue-next'
import { Bar, Pie } from 'vue-chartjs'
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale,
  ArcElement
} from 'chart.js'
import DashboardCards from '../components/DashboardCards.vue'
import { dashboardService } from '../services/dashboardService'

// Registrar componentes de Chart.js
ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement)

const router = useRouter()
const kpis = ref({})
const loading = ref(true)
const chartView = ref('bar')
const periodoActual = ref('Mayo 2026')

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

const maxGrado = computed(() => {
  if (!kpis.value.porGrado?.length) return 1
  return Math.max(...kpis.value.porGrado.map(g => g.cantidad))
})

const maxCarga = computed(() => {
  if (!kpis.value.cargaHoraria?.length) return 1
  return Math.max(...kpis.value.cargaHoraria.map(c => c.cantidad))
})

const chartDataUnidades = computed(() => ({
  labels: kpis.value.porUnidad?.map(u => u.unidad) || [],
  datasets: [
    {
      label: 'Cantidad de docentes',
      data: kpis.value.porUnidad?.map(u => u.cantidad) || [],
      backgroundColor: ['#3b82f6', '#10b981', '#8b5cf6', '#f59e0b', '#ef4444', '#06b6d4'],
      borderRadius: 8,
      borderSkipped: false,
    }
  ]
}))

const chartDataUnidadesPie = computed(() => ({
  labels: kpis.value.porUnidad?.map(u => u.unidad) || [],
  datasets: [
    {
      data: kpis.value.porUnidad?.map(u => u.cantidad) || [],
      backgroundColor: ['#3b82f6', '#10b981', '#8b5cf6', '#f59e0b', '#ef4444', '#06b6d4'],
      borderWidth: 2,
      borderColor: '#fff',
    }
  ]
}))

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false }
  },
  scales: {
    y: {
      beginAtZero: true,
      grid: { color: '#f1f5f9' }
    },
    x: {
      grid: { display: false }
    }
  }
}

const chartOptionsPie = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom',
      labels: {
        padding: 20,
        usePointStyle: true,
        font: { size: 12 }
      }
    }
  }
}

onMounted(async () => {
  await cargarDatos()
})

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
  if (id === 'total' || id === 'activos') {
    router.push('/secretaria/docentes')
  }
}

function irADocente(codigo) {
  router.push(`/secretaria/docentes?codigo=${codigo}`)
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