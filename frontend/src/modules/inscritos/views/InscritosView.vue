<template>
  <div class="min-h-screen bg-slate-100 pb-12">

    <!-- HEADER -->
    <div class="flex items-start justify-between mb-3 px-8 pt-4">
      <h1 class="text-xl font-bold text-black tracking-tight m-0 mb-0.5">
        Lista de Inscritos
      </h1>
      <span class="text-xs text-black/70">
        Reporte completo · Gestión {{ filtros.anio }}/{{ filtros.periodo }}
      </span>
    </div>

    <!-- FILTROS -->
    <div class="border-b border-slate-700 px-8 py-2.5 flex flex-wrap gap-3 items-end">

      <!-- Año -->
      <div class="flex flex-col gap-1.5">
        <label class="text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400">Año</label>
        <input
          v-model.number="filtros.anio"
          type="number"
          min="2000"
          max="2099"
          class="w-24 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 text-sm px-3 py-2 outline-none
                 placeholder-slate-500 transition-all duration-150
                 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20
                 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
        />
      </div>

      <!-- Período -->
      <div class="flex flex-col gap-1.5">
        <label class="text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400">Período</label>
        <select
          v-model.number="filtros.periodo"
          class="w-36 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 text-sm px-3 py-2 outline-none
                 transition-all duration-150
                 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20"
        >
          <option :value="1">1° Semestre</option>
          <option :value="2">2° Semestre</option>
        </select>
      </div>

      <!-- Buscar Docente -->
      <div class="flex-1 min-w-[260px] flex flex-col gap-1.5">
        <label class="text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400">Buscar Docente</label>
        <div class="flex gap-2">
          <input
            v-model="busqueda"
            type="text"
            placeholder="Código o apellidos..."
            @keyup.enter="handleBuscar"
            class="flex-1 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 text-sm px-3 py-2 outline-none
                   placeholder-slate-500 transition-all duration-150
                   focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20"
          />
          <button
            @click="handleBuscar"
            :disabled="loading"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold
                   bg-amber-500 hover:bg-amber-400 active:bg-amber-600 text-slate-900
                   transition-all duration-150 disabled:opacity-50 disabled:cursor-not-allowed
                   shadow-lg shadow-amber-500/20"
          >
            <svg :class="loading ? 'animate-spin' : ''" width="15" height="15" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2.5">
              <template v-if="loading">
                <circle cx="12" cy="12" r="9"/>
                <path d="M12 3a9 9 0 0 1 9 9" stroke-linecap="round"/>
              </template>
              <template v-else>
                <circle cx="11" cy="11" r="8"/>
                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
              </template>
            </svg>
            {{ loading ? 'Buscando...' : 'Buscar' }}
          </button>
          <button
            @click="verTodos"
            :disabled="loading"
            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium
                   border border-slate-700 text-slate-400 bg-transparent
                   hover:bg-white/5 hover:text-slate-200
                   transition-all duration-150 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Ver todos
          </button>
        </div>
      </div>

      <!-- Botones PDF (solo visibles cuando hay datos) -->
      <template v-if="data.length">
        <div class="flex flex-col">
          <label class="text-[0.68rem] invisible">PDF</label>
          <div class="flex gap-2">

            <!-- PDF Lista completa -->
            <button
              @click="handleListaCompleta"
              :disabled="generandoLista"
              title="PDF con la lista completa de estudiantes por docente"
              class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold
                     bg-red-700 hover:bg-red-600 active:bg-red-800 text-white
                     transition-all duration-150 disabled:opacity-40 disabled:cursor-not-allowed
                     border border-red-700/40 shadow-lg shadow-red-900/20"
            >
              <svg :class="generandoLista ? 'animate-spin' : ''" width="15" height="15" viewBox="0 0 24 24"
                   fill="none" stroke="currentColor" stroke-width="2">
                <template v-if="generandoLista">
                  <circle cx="12" cy="12" r="9"/>
                  <path d="M12 3a9 9 0 0 1 9 9" stroke-linecap="round"/>
                </template>
                <template v-else>
                  <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                  <polyline points="14 2 14 8 20 8"/>
                </template>
              </svg>
              {{ generandoLista ? 'Generando...' : 'PDF Lista' }}
            </button>

            <!-- PDF Solo totales -->
            <button
              @click="handleResumenTotales"
              :disabled="generandoResumen"
              title="PDF con solo totales y cantidades por docente"
              class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold
                     bg-emerald-800 hover:bg-emerald-700 active:bg-emerald-900 text-white
                     transition-all duration-150 disabled:opacity-40 disabled:cursor-not-allowed
                     border border-emerald-800/40 shadow-lg shadow-emerald-900/20"
            >
              <svg :class="generandoResumen ? 'animate-spin' : ''" width="15" height="15" viewBox="0 0 24 24"
                   fill="none" stroke="currentColor" stroke-width="2">
                <template v-if="generandoResumen">
                  <circle cx="12" cy="12" r="9"/>
                  <path d="M12 3a9 9 0 0 1 9 9" stroke-linecap="round"/>
                </template>
                <template v-else>
                  <path d="M9 17v-2m3 2v-4m3 4v-6M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </template>
              </svg>
              {{ generandoResumen ? 'Generando...' : 'PDF Totales' }}
            </button>

          </div>
        </div>
      </template>
    </div>

    <!-- ERROR -->
    <div
      v-if="error"
      class="mx-8 mt-4 bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg flex items-center gap-3"
    >
      <span>⚠️ {{ error }}</span>
      <button @click="handleBuscar" class="text-xs font-bold underline ml-auto">Reintentar</button>
    </div>

    <!-- LOADING -->
    <div
      v-if="loading"
      class="flex flex-col items-center gap-3 py-20 text-slate-500"
    >
      <div class="w-10 h-10 border-4 border-slate-200 border-t-amber-500 rounded-full animate-spin" />
      <span>Cargando inscritos...</span>
    </div>

    <!-- EMPTY STATE INICIAL -->
    <div
      v-else-if="!loading && data.length === 0 && !error"
      class="flex flex-col items-center gap-3 py-24 text-slate-400"
    >
      <span class="text-6xl">📋</span>
      <p>Seleccioná año y período, luego hacé clic en <strong class="text-slate-600">Buscar</strong> o <strong class="text-slate-600">Ver todos</strong>.</p>
    </div>

    <!-- CONTENIDO -->
    <div v-else-if="!loading && data.length" id="reporte-imprimible" class="px-8 py-4">

      <!-- CABECERA REPORTE -->
      <div class="bg-white border border-slate-200 rounded-lg px-5 py-2.5 flex flex-wrap justify-between items-center mb-3 text-sm text-slate-500 gap-2">
        <span>Generado: <strong class="text-slate-700">{{ fechaActual }}</strong></span>
        <span class="text-slate-300 hidden sm:block">|</span>
        <span>Total docentes: <strong class="text-slate-700">{{ data.length }}</strong></span>
        <span class="text-slate-300 hidden sm:block">|</span>
        <span>Total inscritos:
          <strong class="text-slate-700">{{ totalGlobal }}</strong>
        </span>
      </div>

      <!-- CARDS -->
      <DocenteInscritosCard
        v-for="docente in data"
        :key="docente.cod_docente"
        :docente="docente"
      />

      <!-- FOOTER -->
      <div class="text-center text-slate-400 text-xs mt-8 pt-4 border-t border-slate-200">
        Procesado UTI – Facultad de Ciencias Económicas · La carga incluye todos los grupos asignados.
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import DocenteInscritosCard from '../components/DocenteInscritosCard.vue'
import { useInscritos } from '../composables/useInscritos'
import { useReporteInscritos } from '../composables/useReporteInscritos'

// ─── Sin onMounted: no carga automática al entrar ───────────────────────────
const { data, loading, error, fetchInscritos } = useInscritos()
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
const busqueda = ref('')

const totalGlobal = computed(() =>
  data.value.reduce((s, d) => s + (d.total_inscritos ?? 0), 0)
)

const fechaActual = computed(() =>
  new Date().toLocaleString('es-BO', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
)

async function verTodos() {
  busqueda.value = ''
  await fetchInscritos(filtros.value.anio, filtros.value.periodo)
}

async function handleBuscar() {
  const q = busqueda.value.trim()
  if (!q) {
    await fetchInscritos(filtros.value.anio, filtros.value.periodo)
    return
  }
  if (/^\d+$/.test(q)) {
    // Búsqueda por código: carga todos y filtra, o puedes tener un endpoint específico
    await fetchInscritos(filtros.value.anio, filtros.value.periodo)
    data.value = data.value.filter(d => d.cod_docente === q)
  } else {
    await fetchInscritos(filtros.value.anio, filtros.value.periodo)
    data.value = data.value.filter(d =>
      `${d.apellidos} ${d.nombres}`.toLowerCase().includes(q.toLowerCase())
    )
  }
}

function handleListaCompleta() {
  exportarListaCompleta(data.value, filtros.value.anio, filtros.value.periodo)
}

function handleResumenTotales() {
  exportarResumenTotales(data.value, filtros.value.anio, filtros.value.periodo)
}
</script>