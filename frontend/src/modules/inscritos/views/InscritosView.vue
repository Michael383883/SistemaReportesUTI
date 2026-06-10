
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

        <!-- Filtros -->
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
          <button
            @click="cargar"
            :disabled="loading"
            class="h-9 px-5 bg-slate-800 hover:bg-slate-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-semibold rounded-lg transition-colors flex items-center gap-2"
          >
            <svg v-if="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
            </svg>
            <span>{{ loading ? 'Cargando…' : 'Buscar' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- ── Resumen rápido (ranking visual) ───────────────── -->
    <div v-if="data.length" class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-8">
      <div class="bg-white border border-slate-200 rounded-xl px-4 py-3 shadow-sm">
        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1">Docentes</p>
        <p class="text-2xl font-extrabold text-slate-800">{{ data.length }}</p>
      </div>
      <div class="bg-white border border-slate-200 rounded-xl px-4 py-3 shadow-sm">
        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1">Total Inscritos</p>
        <p class="text-2xl font-extrabold text-blue-600">{{ totalGlobal }}</p>
      </div>
      <div class="bg-white border border-slate-200 rounded-xl px-4 py-3 shadow-sm">
        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1">Mayor Carga</p>
        <p class="text-2xl font-extrabold text-emerald-600">{{ maxInscritos }}</p>
        <p class="text-[11px] text-slate-400 truncate">{{ docenteMax }}</p>
      </div>
      <div class="bg-white border border-slate-200 rounded-xl px-4 py-3 shadow-sm">
        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1">Promedio</p>
        <p class="text-2xl font-extrabold text-slate-700">{{ promedio }}</p>
        <p class="text-[11px] text-slate-400">insc. por docente</p>
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

const { data, loading, error, meta, fetchInscritos } = useInscritos()

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

onMounted(() => cargar())
</script>
