<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 pb-16 font-sans">

    <!-- ── Encabezado ────────────────────────────────────── -->
    <div class="mb-8">
      <span class="text-xs font-bold tracking-widest uppercase text-blue-500 block mb-1">
        Gestión Académica
      </span>
      <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
          <h1 class="text-3xl font-extrabold text-slate-900 leading-tight">
            Lista de Inscritos
          </h1>
          <p v-if="meta.anio" class="mt-1.5 flex flex-wrap items-center gap-2 text-sm text-slate-500">
            Gestión <strong class="text-slate-700">{{ meta.anio }}-{{ meta.periodo }}</strong>
            <span class="bg-slate-100 text-slate-600 font-semibold px-2.5 py-0.5 rounded-full text-xs">
              {{ meta.total_docentes }} docentes
            </span>
            <span class="bg-blue-100 text-blue-700 font-bold px-2.5 py-0.5 rounded-full text-xs">
              {{ totalGlobal }} inscritos
            </span>
          </p>
        </div>

        <!-- Filtros + Botones -->
        <div class="flex flex-wrap items-end gap-3">
          <div class="flex flex-col gap-1">
            <label class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Año</label>
            <input
              v-model.number="filtros.anio"
              type="number"
              min="2000" max="2099"
              placeholder="2025"
              class="h-9 w-24 px-3 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent"
            />
          </div>
          <div class="flex flex-col gap-1">
            <label class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Período</label>
            <select
              v-model.number="filtros.periodo"
              class="h-9 px-3 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent"
            >
              <option :value="1">1° Semestre</option>
              <option :value="2">2° Semestre</option>
            </select>
          </div>

          <!-- Buscar -->
          <button
            @click="cargar"
            :disabled="loading"
            class="h-9 px-5 bg-slate-800 hover:bg-slate-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-semibold rounded-lg transition-colors flex items-center gap-2"
          >
            <svg v-if="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
            </svg>
            <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
            </svg>
            <span>{{ loading ? 'Cargando…' : 'Buscar' }}</span>
          </button>

          <!-- Separador vertical -->
          <div v-if="data.length" class="h-9 w-px bg-slate-200 hidden sm:block"></div>

          <!-- Botones PDF (solo visibles cuando hay datos) -->
          <template v-if="data.length">
            <!-- PDF Lista completa -->
            <button
              @click="handleListaCompleta"
              :disabled="generandoLista"
              title="PDF con la lista completa de estudiantes por docente"
              class="h-9 px-4 bg-red-600 hover:bg-red-500 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-semibold rounded-lg transition-colors flex items-center gap-2 shadow-sm"
            >
              <svg v-if="generandoLista" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
              </svg>
              <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 13h6M9 17h4"/>
              </svg>
              <span class="hidden sm:inline">{{ generandoLista ? 'Generando…' : 'PDF Lista' }}</span>
              <span class="sm:hidden">Lista</span>
            </button>

            <!-- PDF Solo totales -->
            <button
              @click="handleResumenTotales"
              :disabled="generandoResumen"
              title="PDF con solo totales y cantidades por docente"
              class="h-9 px-4 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-semibold rounded-lg transition-colors flex items-center gap-2 shadow-sm"
            >
              <svg v-if="generandoResumen" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
              </svg>
              <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M9 17v-2m3 2v-4m3 4v-6M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
              <span class="hidden sm:inline">{{ generandoResumen ? 'Generando…' : 'PDF Totales' }}</span>
              <span class="sm:hidden">Totales</span>
            </button>
          </template>
        </div>
      </div>
    </div>


    <!-- ── Estado vacío ───────────────────────────────────── -->
    <div
      v-if="!loading && !error && !data.length"
      class="flex flex-col items-center justify-center py-24 text-slate-400 gap-3"
    >
      <svg class="w-12 h-12 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>
      </svg>
      <p class="text-sm font-medium">Seleccioná año y período para ver los inscritos.</p>
    </div>

    <!-- ── Error ──────────────────────────────────────────── -->
    <div
      v-if="error"
      class="flex items-center gap-3 bg-red-50 border border-red-200 text-red-700 rounded-xl px-5 py-4 mb-6"
    >
      <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
      </svg>
      <span class="text-sm font-medium flex-1">{{ error }}</span>
      <button @click="cargar" class="text-xs font-bold underline">Reintentar</button>
    </div>

    <!-- ── Lista de docentes ──────────────────────────────── -->
    <div class="flex flex-col gap-4">
      <DocenteInscritosCard
        v-for="docente in data"
        :key="docente.cod_docente"
        :docente="docente"
      />
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import DocenteInscritosCard from '../components/DocenteInscritosCard.vue'
import { useInscritos } from '../composables/useInscritos'
import { useReporteInscritos } from '../composables/useReporteInscritos'

const { data, loading, error, meta, fetchInscritos } = useInscritos()
const {
  generandoLista,
  generandoResumen,
  exportarListaCompleta,
  exportarResumenTotales,
} = useReporteInscritos()

const filtros = ref({
  anio: new Date().getFullYear(),
  periodo: 1,
})

const totalGlobal = computed(() =>
  data.value.reduce((s, d) => s + (d.total_inscritos ?? 0), 0)
)
const maxInscritos = computed(() =>
  data.value.length ? Math.max(...data.value.map(d => d.total_inscritos ?? 0)) : 0
)
const docenteMax = computed(() => {
  const d = data.value.find(d => d.total_inscritos === maxInscritos.value)
  return d ? `${d.apellidos}, ${d.nombres}` : ''
})
const promedio = computed(() =>
  data.value.length ? Math.round(totalGlobal.value / data.value.length) : 0
)

async function cargar() {
  await fetchInscritos(filtros.value.anio, filtros.value.periodo)
}

function handleListaCompleta() {
  exportarListaCompleta(data.value, filtros.value.anio, filtros.value.periodo)
}

function handleResumenTotales() {
  exportarResumenTotales(data.value, filtros.value.anio, filtros.value.periodo)
}

onMounted(() => cargar())
</script>