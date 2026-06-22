<template>
  <div class="min-h-screen bg-slate-100 pb-12">
    <!-- HEADER -->
     <div class="flex items-start justify-between mb-3">
      <h1 class="text-xl font-bold text-black-400 tracking-tight m-0 mb-0.5">
        Reportes de horarios resumen
      </h1>
      <span class="text-xs text-black/70">
        Reporte completo · Gestión {{ anio }}/{{ periodo }}
      </span>
    </div>

    <!-- FILTROS -->
    <div class="border-b border-slate-700 px-8 py-2.5 flex flex-wrap gap-3 items-end">

      <!-- Año -->
      <div class="flex flex-col gap-1.5">
        <label class="text-[0.68rem] font-semibold tracking-widest uppercase text-slate-800">Año</label>
        <input
          v-model.number="anio"
          type="number"
          min="2020"
          max="2030"
          class="w-24 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 text-sm px-3 py-2 outline-none
                 placeholder-slate-500 transition-all duration-150
                 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20
                 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
        />
      </div>

      <!-- Período -->
      <div class="flex flex-col gap-1.5">
        <label class="text-[0.68rem] font-semibold tracking-widest uppercase text-slate-800">Período</label>
        <select
          v-model.number="periodo"
          class="w-24 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 text-sm px-3 py-2 outline-none
                 transition-all duration-150
                 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20"
        >
          <option :value="1">1</option>
          <option :value="2">2</option>
          <option :value="3">3</option>
          <option :value="4">4</option>
        </select>
      </div>

      <!-- Buscar Docente -->
      <div class="flex-1 min-w-[260px] flex flex-col gap-1.5">
        <label class="text-[0.68rem] font-semibold tracking-widest uppercase text-slate-800">Buscar Docente</label>
        <div class="flex gap-2">
          <input
            v-model="busqueda"
            type="text"
            placeholder="Código o apellidos..."
            @keyup.enter="buscarDocente"
            class="flex-1 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 text-sm px-3 py-2 outline-none
                   placeholder-slate-500 transition-all duration-150
                   focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20"
          />
          <button
            @click="buscarDocente"
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

      <!-- PDF -->
      <div class="flex flex-col">
        <label class="text-[0.68rem] invisible">PDF</label>
        <button
          type="button"
          @click="generarPDF"
          :disabled="loading || docentes.length === 0"
          class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold
                 bg-red-700 hover:bg-red-600 active:bg-red-800 text-white
                 transition-all duration-150 disabled:opacity-40 disabled:cursor-not-allowed
                 border border-red-700/40 shadow-lg shadow-red-900/20"
        >
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
          </svg>
          Generar PDF
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
      <ResumenDocenteCardDos
        v-for="doc in docentes"
        :key="doc.docente"
        :docente="doc"
      />

      <!-- FOOTER -->
      <div class="text-center text-slate-400 text-xs mt-8 pt-4 border-t border-slate-200">
        Procesado UTI – Facultad de Ciencias Económicas · Los inscritos incluyen ambos grupos de materias compartidas.
      </div>
    </div>
  </div>
</template>


<script setup>
import { ref, computed } from 'vue'
import ResumenDocenteCardDos from '../components/Resumendocentecarddos.vue'
import { useHorarioResumen } from '../composables/useHorarioResumen'

import { generarPDFResumenDos  } from '../composables/usepdf2prueba'
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
  console.log('1. Click detectado, docentes:', docentes.value.length)

  try {
    const resultado = generarPDFResumenDos(
      docentes.value,
      { anio: anio.value, periodo: periodo.value }
    )
    console.log('2. Resultado de generarPDFResumenDos:', resultado)

    const { url, filename } = resultado

    if (!url) {
      console.error('❌ No se generó URL del blob')
      return
    }

    const a = document.createElement('a')
    a.href = url
    a.download = filename
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    console.log('3. Click de descarga disparado')

    setTimeout(() => URL.revokeObjectURL(url), 1000)
  } catch (err) {
    console.error('❌ ERROR generando PDF:', err)
  }
}
</script>