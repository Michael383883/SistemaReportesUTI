<!-- src/modules/secretaria/views/DashboardPage.vue -->
<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 to-white">
    <!-- Header -->
    <div class="bg-white border-b border-slate-200 px-6 py-5">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-slate-800">{{ tituloDashboard }}</h1>
          <p class="text-sm text-slate-500 mt-1">Panel de control docente</p>
        </div>
        <div class="flex items-center gap-3">
          
          <button
            @click="recargarDatos"
            class="flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 shadow-sm shadow-blue-600/20 transition-colors disabled:opacity-60 disabled:cursor-not-allowed"
            :disabled="loading"
          >
            <RefreshCw :class="['w-4 h-4', loading && 'animate-spin']" />
            Actualizar
          </button>
        </div>
      </div>
    </div>

    <div class="p-6 space-y-6">
      <!-- KPI -->
      <DashboardCards :kpis="kpis" :loading="loading" @cardClick="handleCardClick" />

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Distribución por Grado -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 flex flex-col" style="height: 480px;">

          <!-- Header -->
          <div class="flex items-center justify-between mb-6 flex-shrink-0">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600 flex-shrink-0">
                <BarChart3 class="w-5 h-5" />
              </div>
              <h2 class="text-lg font-semibold text-slate-800">Por Grado Académico</h2>
            </div>
            <span
              v-if="!loading"
              class="text-xs font-semibold text-blue-700 bg-blue-50 px-2.5 py-1 rounded-full flex-shrink-0"
            >
              {{ kpis.porGrado?.length || 0 }} grados
            </span>
            <span
              v-else
              class="h-5 w-16 rounded-full bg-slate-100 animate-pulse flex-shrink-0"
            />
          </div>

          <!-- Lista scrolleable -->
          <div class="flex-1 overflow-y-auto space-y-5 pr-2 min-h-0">

            <!-- Skeleton -->
            <template v-if="loading">
              <div v-for="n in 6" :key="'sk-grado-' + n" class="animate-pulse">
                <div class="flex items-center justify-between mb-2">
                  <div class="h-3.5 rounded bg-slate-100" :style="{ width: 60 + (n % 3) * 15 + 'px' }" />
                  <div class="flex items-center gap-3">
                    <div class="h-3.5 w-6 rounded bg-slate-100" />
                    <div class="h-3.5 w-8 rounded bg-slate-100" />
                  </div>
                </div>
                <div class="h-2 bg-slate-100 rounded-full" />
              </div>
            </template>

            <template v-else>
              <div
                v-for="(item, i) in kpis.porGrado"
                :key="item.grado"
                class="group cursor-pointer"
              >
                <div class="flex items-center justify-between mb-2">
                  <span class="text-sm font-semibold text-slate-700 truncate max-w-[120px]">{{ item.grado }}</span>
                  <div class="flex items-center gap-3 flex-shrink-0">
                    <span class="text-sm font-bold text-slate-800 w-10 text-right">{{ item.cantidad }}</span>
                    <span
                      class="text-sm font-bold w-12 text-right"
                      :style="{ color: paletaGrado[i % paletaGrado.length] }"
                    >
                      {{ Math.round((item.cantidad / totalPorGrado) * 100) }}%
                    </span>
                  </div>
                </div>
                <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                  <div
                    class="h-full rounded-full transition-all duration-700 group-hover:opacity-80"
                    :style="{
                      width: (item.cantidad / maxGrado) * 100 + '%',
                      backgroundColor: paletaGrado[i % paletaGrado.length]
                    }"
                  />
                </div>
              </div>

              <div v-if="!kpis.porGrado?.length" class="h-full flex items-center justify-center text-sm text-slate-400">
                Sin datos por grado
              </div>
            </template>
          </div>

          <!-- Footer fijo -->
          <div class="mt-5 pt-5 border-t border-slate-100 flex-shrink-0">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 flex-shrink-0">
                <Users class="w-4 h-4" />
              </div>
              <span class="text-sm text-slate-500">Total registrado</span>
              <span v-if="!loading" class="text-base font-bold text-slate-800 ml-auto">
                {{ totalPorGrado.toLocaleString() }}
                <span class="text-sm font-medium text-slate-400">docentes</span>
              </span>
              <span v-else class="h-4 w-24 rounded bg-slate-100 animate-pulse ml-auto" />
            </div>
          </div>
        </div>

        <!-- Últimos docentes agregados -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-6">
          <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-teal-50 flex items-center justify-center text-teal-600 flex-shrink-0">
                <Users class="w-5 h-5" />
              </div>
              <h2 class="text-lg font-semibold text-slate-800">Docentes Recientes</h2>
            </div>
            <router-link
              to="/secretaria/docentes"
              class="text-sm font-semibold text-blue-600 hover:text-blue-700 transition-colors flex items-center gap-1"
            >
              Ver todos <ArrowRight class="w-3.5 h-3.5" />
            </router-link>
          </div>

          <!-- Skeleton -->
          <div v-if="loading" class="divide-y divide-slate-100">
            <div
              v-for="n in 5"
              :key="'sk-doc-' + n"
              class="flex items-center gap-4 py-4 first:pt-0 last:pb-0 animate-pulse"
            >
              <div class="w-10 h-10 rounded-xl bg-slate-100 flex-shrink-0" />
              <div class="flex-1 min-w-0 space-y-2">
                <div class="h-3.5 rounded bg-slate-100" :style="{ width: 55 - (n % 3) * 8 + '%' }" />
                <div class="h-3 w-24 rounded bg-slate-100" />
              </div>
              <div class="h-6 w-16 rounded-full bg-slate-100 flex-shrink-0" />
            </div>
          </div>

          <div v-else class="divide-y divide-slate-100">
            <div
              v-for="docente in docentesRecientes?.slice(0, 5)"
              :key="docente.codigo"
              class="flex items-center gap-4 py-4 first:pt-0 last:pb-0 hover:bg-slate-50 -mx-3 px-3 rounded-xl transition-colors cursor-pointer group"
              @click="irADocente(docente.codigo)"
            >
              <div class="w-10 h-10 rounded-xl bg-teal-50 flex items-center justify-center text-teal-600 flex-shrink-0">
                <User class="w-5 h-5" />
              </div>

              <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-slate-800 truncate group-hover:text-teal-700 transition-colors">
                  {{ formatNombre(docente.nombre) }}
                </p>
                <p class="text-xs text-slate-400 truncate mt-0.5">
                  Cod. {{ docente.codigo }}
                </p>
              </div>

              <span class="text-xs font-semibold text-teal-700 bg-teal-50 px-2.5 py-1 rounded-full flex-shrink-0">
                {{ docente.grado }}
              </span>

              <ChevronRight class="w-4 h-4 text-slate-300 group-hover:text-teal-500 transition-colors flex-shrink-0" />
            </div>

            <div v-if="!docentesRecientes?.length" class="text-sm text-slate-400 py-6 text-center">
              No hay docentes recientes para mostrar
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
import { RefreshCw, Calendar, BarChart3, Users, User, ArrowRight, ChevronRight } from 'lucide-vue-next'
import { useDocentesRecientes } from '@/shared/composables/useDocentesRecientes'
import { useAuth } from '../../auth/composables/useAuth'
import DashboardCards from '../components/DashboardCards.vue'
import { dashboardService } from '../services/dashboardService'

const router = useRouter()
const kpis = ref({})
const loading = ref(true)

const { recientes: docentesRecientes } = useDocentesRecientes()

// Rol del usuario logueado, para distinguir secretaría / UTI
const { userRole } = useAuth()

const tituloDashboard = computed(() =>
  userRole === 'uti' ? 'Dashboard Secretaría UTI' : 'Dashboard de Secretaría'
)

// Paleta fija para las barras de "Por Grado", igual estilo que la referencia
const paletaGrado = ['#3b82f6', '#8b5cf6', '#22c55e', '#f97316', '#ef4444', '#06b6d4']

const maxGrado = computed(() => {
  if (!kpis.value.porGrado?.length) return 1
  return Math.max(...kpis.value.porGrado.map(g => g.cantidad))
})

const totalPorGrado = computed(() =>
  kpis.value.porGrado?.reduce((acc, g) => acc + g.cantidad, 0) || 0
)

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

function formatNombre(nombre) {
  if (!nombre) return 'Sin nombre'
  return nombre.split(' ').map(p => p.charAt(0) + p.slice(1).toLowerCase()).join(' ')
}

function formatFecha(fecha) {
  if (!fecha) return ''
  return new Date(fecha).toLocaleDateString('es-BO', { day: '2-digit', month: 'short' })
}
</script>