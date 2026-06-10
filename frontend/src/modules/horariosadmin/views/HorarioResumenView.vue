<template>
  <div class="min-h-screen bg-slate-100 pb-12">
    <!-- HEADER -->
    <div class="bg-gradient-to-r from-slate-800 to-blue-700 px-8 py-3 flex justify-between items-center shadow-lg">
      <h1 class="text-white text-xl font-extrabold tracking-tight">
        Resumen Carga Horaria
      </h1>
      <span class="text-xs text-white/70">
        Resumen por materia · Gestión {{ anio }}/{{ periodo }}
      </span>
    </div>

    <!-- FILTROS -->
    <div class="bg-white border-b border-slate-200 px-8 py-2.5 flex flex-wrap gap-3 items-end">
      <!-- Año -->
      <div class="flex flex-col gap-1">
        <label class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">
          Año
        </label>
        <input
          v-model.number="anio"
          type="number"
          min="2020"
          max="2030"
          class="w-24 border border-slate-200 rounded-lg px-3 py-1.5 text-sm focus:border-blue-600 focus:outline-none"
        />
      </div>

      <!-- Periodo -->
      <div class="flex flex-col gap-1">
        <label class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">
          Período
        </label>
        <select
          v-model.number="periodo"
          class="w-24 border border-slate-200 rounded-lg px-3 py-1.5 text-sm focus:border-blue-600 focus:outline-none"
        >
          <option :value="1">1</option>
          <option :value="2">2</option>
          <option :value="3">3</option>
          <option :value="4">4</option>
        </select>
      </div>

      <!-- Buscar -->
      <div class="flex-1 min-w-[260px] flex flex-col gap-1">
        <label class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">
          Buscar Docente
        </label>
        <div class="flex gap-2">
          <input
            v-model="busqueda"
            type="text"
            placeholder="Código o apellidos..."
            @keyup.enter="buscarDocente"
            class="flex-1 border border-slate-200 rounded-lg px-3 py-1.5 text-sm focus:border-blue-600 focus:outline-none"
          />
          <button
            @click="buscarDocente"
            :disabled="loading"
            class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white px-4 py-1.5 rounded-lg font-semibold text-sm"
          >
            {{ loading ? '⏳' : '🔍 Buscar' }}
          </button>
          <button
            @click="verTodos"
            :disabled="loading"
            class="bg-slate-100 border border-slate-200 hover:bg-slate-200 text-slate-700 px-4 py-1.5 rounded-lg font-semibold text-sm"
          >
            Ver todos
          </button>
        </div>
      </div>

      <!-- PDF -->
      <div class="flex flex-col">
        <label class="text-[11px] invisible">PDF</label>
        <button
          @click="generarPDF"
          :disabled="loading || docentes.length === 0"
          class="bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white px-5 py-1.5 rounded-lg font-semibold text-sm"
        >
          📄 Generar PDF
        </button>
      </div>
    </div>

    <!-- ERROR -->
    <div
      v-if="error"
      class="mx-8 mt-4 bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg"
    >
      ⚠️ {{ error }}
    </div>

    <!-- LOADING -->
    <div
      v-if="loading"
      class="flex flex-col items-center gap-3 py-20 text-slate-500"
    >
      <div class="w-10 h-10 border-4 border-slate-200 border-t-blue-600 rounded-full animate-spin" />
      <span>Cargando resumen...</span>
    </div>

    <!-- EMPTY -->
    <div
      v-else-if="!loading && docentes.length === 0 && !error"
      class="flex flex-col items-center gap-3 py-24 text-slate-400"
    >
      <span class="text-6xl">📋</span>
      <p>Busca un docente o carga todos para ver el resumen.</p>
    </div>

    <!-- CONTENIDO -->
    <div v-else id="reporte-imprimible" class="px-8 py-4">

      <!-- CABECERA REPORTE -->
      <div class="bg-white border border-slate-200 rounded-lg px-5 py-2.5 flex justify-between items-center mb-3 text-sm text-slate-500">
        <span>Generado: <strong class="text-slate-700">{{ fechaActual }}</strong></span>
        <span class="text-slate-300 mx-2">|</span>
        <span>Total docentes: <strong class="text-slate-700">{{ docentes.length }}</strong></span>
      </div>

      

      <!-- CARDS -->
      <ResumenDocenteCard
        v-for="doc in docentes"
        :key="doc.docente"
        :docente="doc"
      />

      <!-- FOOTER -->
      <div class="text-center text-slate-400 text-xs mt-8 pt-4 border-t border-slate-200">
        Procesado UTI – Facultad de Ciencias Económicas · La carga horaria incluye Grupos Compartidos.
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import ResumenDocenteCard from '../components/ResumenDocenteCard.vue'
import { useHorarioResumen } from '../composables/useHorarioResumen'
import { generarPDFResumen } from '../composables/useGenerarPDFResumen'
import { usePeriodoActual } from '../composables/usePeriodoActual'

const { anio, periodo } = usePeriodoActual()

const {
  docentes,
  loading,
  error,
  cargarTodos,
  cargarDocente,
} = useHorarioResumen()

const busqueda = ref('')

const COLORES = {
  ADM: { bg: '#dbeafe', text: '#1e40af', border: '#93c5fd' },
  ECO: { bg: '#dcfce7', text: '#166534', border: '#86efac' },
  CCP: { bg: '#fef9c3', text: '#854d0e', border: '#fde047' },
  COM: { bg: '#fce7f3', text: '#9d174d', border: '#f9a8d4' },
  FIN: { bg: '#ede9fe', text: '#5b21b6', border: '#c4b5fd' },
}

const fechaActual = computed(() =>
  new Date().toLocaleString('es-BO', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
)

async function verTodos() {
  await cargarTodos(anio.value, periodo.value)
}

async function buscarDocente() {
  if (!busqueda.value.trim()) {
    await cargarTodos(anio.value, periodo.value)
    return
  }
  const input = busqueda.value.trim()
  if (/^\d+$/.test(input)) {
    await cargarDocente(input, anio.value, periodo.value)
  } else {
    await cargarTodos(anio.value, periodo.value)
    docentes.value = docentes.value.filter(d =>
      `${d.apellidos} ${d.nombres}`.toLowerCase().includes(input.toLowerCase())
    )
  }
}

async function generarPDF() {
  generarPDFResumen(docentes.value, { anio: anio.value, periodo: periodo.value })
}
</script>