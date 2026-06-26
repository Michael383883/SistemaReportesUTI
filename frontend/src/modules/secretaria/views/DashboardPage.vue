<!-- src/modules/secretaria/views/DashboardPage.vue -->
<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 to-white">
    <!-- Header -->
    <div class="bg-white border-b border-slate-200 px-6 py-5">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-slate-800">{{ tituloDashboard }}</h1>
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
       

        <!-- Distribución por Grado -->
          <div class="bg-white rounded-2xl border border-slate-200 p-6 flex flex-col" style="height: 480px;">
            
            <!-- Header -->
            <div class="flex items-center justify-between mb-5 flex-shrink-0">
              <h2 class="text-lg font-semibold text-slate-800">Por Grado Académico</h2>
              <span class="text-xs text-slate-400 bg-slate-100 px-2 py-1 rounded-full">
                {{ kpis.porGrado?.length || 0 }} grados
              </span>
            </div>

            <!-- Lista scrolleable -->
            <div class="flex-1 overflow-y-auto space-y-3 pr-2 min-h-0">
              <div 
                v-for="item in kpis.porGrado" 
                :key="item.grado"
                class="group cursor-pointer py-1"
              >
                <div class="flex items-center justify-between mb-1.5">
                  <span class="text-sm font-medium text-slate-700 truncate max-w-[140px]">{{ item.grado }}</span>
                  <div class="flex items-center gap-2 flex-shrink-0">
                    <span class="text-xs text-slate-400">
                      {{ Math.round((item.cantidad / (kpis.porGrado?.reduce((a,g) => a + g.cantidad, 0) || 1)) * 100) }}%
                    </span>
                    <span class="text-sm font-bold text-slate-800 w-10 text-right">{{ item.cantidad }}</span>
                  </div>
                </div>
                <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                  <div 
                    class="h-full rounded-full transition-all duration-700 group-hover:opacity-75"
                    :style="{ 
                      width: (item.cantidad / maxGrado) * 100 + '%',
                      backgroundColor: item.color 
                    }"
                  />
                </div>
              </div>
            </div>

            <!-- Footer fijo -->
            <div class="mt-4 pt-4 border-t border-slate-100 flex-shrink-0">
              <div class="flex items-center justify-between">
                <span class="text-sm text-slate-500">Total docentes</span>
                <span class="text-base font-bold text-slate-800">
                  {{ kpis.porGrado?.reduce((acc, g) => acc + g.cantidad, 0) || 0 }}
                </span>
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
              v-for="docente in docentesRecientes?.slice(0, 5)" 
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

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { RefreshCw, AlertTriangle, Info, AlertCircle, BellRing } from 'lucide-vue-next'
import { Bar, Pie } from 'vue-chartjs'
import { useDocentesRecientes } from '@/shared/composables/useDocentesRecientes'
import { useAuth } from '../../auth/composables/useAuth'
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
const { recientes: docentesRecientes } = useDocentesRecientes()

// Rol del usuario logueado, para distinguir secretaría / UTI
const { userRole } = useAuth()

const tituloDashboard = computed(() =>
  userRole === 'uti' ? 'Dashboard Secretaría UTI' : 'Dashboard de Secretaría'
)

const chartDataCarga = computed(() => ({
  labels: kpis.value.cargaHoraria?.map(c => c.rango) || [],
  datasets: [
    {
      label: 'Docentes',
      data: kpis.value.cargaHoraria?.map(c => c.cantidad) || [],
      backgroundColor: [
        '#f59e0b',
        '#10b981',
        '#14b8a6',
        '#6366f1',
        '#ef4444'
      ],
      borderRadius: 8
    }
  ]
}))

const chartDataGrados = computed(() => ({
  labels: kpis.value.cargaHoraria?.map(c => c.rango) || [],
  datasets: [
    {
      data: kpis.value.cargaHoraria?.map(c => c.cantidad) || [],
      backgroundColor: [
        '#f59e0b',
        '#10b981',
        '#14b8a6',
        '#6366f1',
        '#ef4444'
      ],
      borderWidth: 2,
      borderColor: '#fff'
    }
  ]
}))

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

    console.log('KPIS', kpis.value)
    console.log('TOP CARGA', kpis.value.topCargaHoraria)

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