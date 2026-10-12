<template>
  <div class="min-h-screen bg-gray-50 dark:bg-slate-900 p-6 transition-colors">
    <div class="mb-6">
      <h1 class="text-[20px] font-bold text-gray-1000 dark:text-white">Reporte de Docentes con Título</h1>
      <p class="text-[14px] text-gray-600 dark:text-slate-400 mt-1">Docentes del periodo seleccionado y sus títulos registrados</p>
    </div>

    <!-- Filtros -->
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-300 dark:border-slate-700 overflow-hidden mb-6">
      <div class="flex items-center justify-between px-4 py-2.5 bg-slate-800 dark:bg-slate-950">
        <span class="text-[13px] font-bold text-white">Filtros</span>
      </div>

      <div class="p-4 grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="text-[12px] font-semibold text-gray-700 dark:text-slate-300">Gestión (año) *</label>
          <input v-model.number="filtros.anio" type="number"
            class="mt-1 w-full border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-900 rounded-lg px-3 py-2 text-[13px] text-gray-900 dark:text-slate-100 focus:outline-none focus:border-blue-600 dark:focus:border-blue-400" />
        </div>

        <div>
          <label class="text-[12px] font-semibold text-gray-700 dark:text-slate-300">Periodo *</label>
          <select v-model="filtros.periodo"
            class="mt-1 w-full border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-900 rounded-lg px-3 py-2 text-[13px] text-gray-900 dark:text-slate-100 focus:outline-none focus:border-blue-600 dark:focus:border-blue-400">
            <option value="1">1</option>
            <option value="4">Invierno</option>
            <option value="2">2</option>
            <option value="3">Verano</option>
          </select>
        </div>

        <div class="md:col-span-2">
          <label class="text-[12px] font-semibold text-gray-700 dark:text-slate-300">Tipo de título</label>
          <select v-model="filtros.tipo_titulo"
            class="mt-1 w-full border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-900 rounded-lg px-3 py-2 text-[13px] text-gray-900 dark:text-slate-100 focus:outline-none focus:border-blue-600 dark:focus:border-blue-400">
            <option value="">Todos</option>
            <option v-for="t in tiposTitulo" :key="t" :value="t">{{ t }}</option>
          </select>
        </div>
      </div>

      <div class="px-4 pb-4 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <p class="text-[12px] font-semibold text-gray-700 dark:text-slate-300 mb-2">Datos adicionales del docente</p>
          <div class="flex flex-wrap gap-2">
            <label v-for="c in camposDocenteDisponibles" :key="c.value"
              class="inline-flex items-center gap-1.5 px-2.5 py-1 border rounded-lg text-[12px] font-medium cursor-pointer transition-colors"
              :class="filtros.campos.includes(c.value)
                ? 'bg-amber-50 border-amber-400 text-amber-700 dark:bg-amber-500/15 dark:border-amber-500/60 dark:text-amber-300'
                : 'border-gray-300 text-gray-800 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700/50'">
              <input type="checkbox" class="hidden" :value="c.value" v-model="filtros.campos" />
              {{ c.label }}
            </label>
          </div>
        </div>

        <div>
          <p class="text-[12px] font-semibold text-gray-700 dark:text-slate-300 mb-2">Datos adicionales del título</p>
          <div class="flex flex-wrap gap-2">
            <label v-for="c in camposTituloDisponibles" :key="c.value"
              class="inline-flex items-center gap-1.5 px-2.5 py-1 border rounded-lg text-[12px] font-medium cursor-pointer transition-colors"
              :class="filtros.campos_titulo.includes(c.value)
                ? 'bg-amber-50 border-amber-400 text-amber-700 dark:bg-amber-500/15 dark:border-amber-500/60 dark:text-amber-300'
                : 'border-gray-300 text-gray-800 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700/50'">
              <input type="checkbox" class="hidden" :value="c.value" v-model="filtros.campos_titulo" />
              {{ c.label }}
            </label>
          </div>
        </div>
      </div>

      <div class="flex items-center justify-end gap-3 px-4 py-3 bg-gray-50 dark:bg-slate-800/60 border-t border-gray-200 dark:border-slate-700">
        <button @click="onBuscar" :disabled="loading || !filtros.anio || !filtros.periodo"
          class="inline-flex items-center gap-2 px-4 py-2 bg-blue-800 hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-500 disabled:opacity-50 disabled:cursor-not-allowed text-white text-[13px] font-semibold rounded-lg transition-colors">
          <svg v-if="loading" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
          Generar lista
        </button>

        <button @click="onGenerarExcel" :disabled="!filtros.anio || !filtros.periodo || generandoExcel"
          class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-400 disabled:opacity-50 disabled:cursor-not-allowed text-white text-[13px] font-bold rounded-lg transition-colors">
          <svg v-if="generandoExcel" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
          <svg v-else class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
          </svg>
          Generar Excel
        </button>
      </div>
    </div>

    <div v-if="error || errorExcel"
      class="mb-4 flex items-center gap-2 p-3 bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/40 rounded-lg text-red-600 dark:text-red-300 text-[12px]">
      {{ error || errorExcel }}
    </div>

    <!-- Resultados -->
    <div v-if="datos.length">
      <TituloResumen
        :items="resumenTitulos"
        :sin-titulo="sinTituloCount"
        :seleccionado="tituloSeleccionado"
        @select="tituloSeleccionado = ($event === tituloSeleccionado ? null : $event)"
      />

      <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
        <p class="text-[13px] font-semibold text-gray-700 dark:text-slate-300">
          Resultados ({{ datosFiltrados.length }}<span v-if="datosFiltrados.length !== datos.length"> de {{ datos.length }}</span>)
        </p>

        <div class="relative w-full sm:w-72">
          <svg class="w-4 h-4 text-gray-400 dark:text-slate-500 absolute left-2.5 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
          </svg>
          <input v-model="busqueda" type="text" placeholder="Buscar código, nombre o título…"
            class="w-full border border-gray-300 dark:border-slate-600 rounded-lg pl-8 pr-8 py-1.5 text-[12px] text-gray-900 dark:text-slate-100 bg-white dark:bg-slate-900 placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none focus:border-blue-700 dark:focus:border-blue-400" />
          <button v-if="busqueda" @click="busqueda = ''"
            class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700 dark:text-slate-500 dark:hover:text-slate-200 text-[14px] leading-none">✕</button>
        </div>
      </div>

      <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-300 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-[12px]">
            <thead>
              <tr class="bg-slate-800 dark:bg-slate-950 text-white text-left">
                <th class="px-3 py-2.5 font-semibold">Código</th>
                <th class="px-3 py-2.5 font-semibold">Apellidos</th>
                <th class="px-3 py-2.5 font-semibold">Nombres</th>
                <th v-for="c in filtros.campos" :key="c" class="px-3 py-2.5 font-semibold">{{ etiqueta(c) }}</th>
                <th class="px-3 py-2.5 font-semibold">Títulos registrados</th>
                <th class="px-3 py-2.5 font-semibold text-center">Detalle</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(row, i) in datosFiltrados" :key="row.CODIGO"
                class="border-t border-gray-200 dark:border-slate-700 text-gray-800 dark:text-slate-200 align-top transition-colors hover:bg-sky-100/70 dark:hover:bg-sky-500/10"
                :class="i % 2 === 1 ? 'bg-sky-50 dark:bg-slate-700/40' : 'bg-white dark:bg-slate-800'">
                <td class="px-3 py-2">{{ row.CODIGO }}</td>
                <td class="px-3 py-2">{{ row.APELLIDOS }}</td>
                <td class="px-3 py-2">{{ row.NOMBRES }}</td>
                <td v-for="c in filtros.campos" :key="c" class="px-3 py-2">{{ celda(row, c) }}</td>
                <td class="px-3 py-2 min-w-[260px]">
                  <TitulosCelda :titulos="row.titulos" :extras="filtros.campos_titulo" />
                </td>
                <td class="px-3 py-2 text-center">
                  <button @click="verTitulosDocente(row)"
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-blue-300 bg-blue-50 hover:bg-blue-100 text-blue-800 dark:border-blue-500/40 dark:bg-blue-500/15 dark:hover:bg-blue-500/25 dark:text-blue-300 font-semibold whitespace-nowrap transition-colors">
                    Ver títulos
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                  </button>
                </td>
              </tr>

              <tr v-if="!datosFiltrados.length">
                <td :colspan="5 + filtros.campos.length"
                  class="px-3 py-8 text-center text-gray-500 dark:text-slate-400">
                  Ningún resultado coincide con la búsqueda.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div v-else-if="buscoAlMenosUnaVez && !loading"
      class="text-center py-10 text-[13px] text-gray-800 dark:text-slate-300">
      No se encontraron docentes con carga en ese periodo.
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useReporteDocente } from '../composables/useReporteDocente'
import { usePeriodoTitulos } from '../composables/usePeriodoTitulos'
import { norm, fmtFecha } from '../composables/utilsTitulo'
import TituloResumen from '../components/TituloResumen.vue'
import TitulosCelda from '../components/TitulosCelda.vue'

const router = useRouter()

// Del composable antiguo solo usamos tipos de título y el Excel
const { tiposTitulo, cargarTiposTitulo, descargarExcel, error: errorExcel } = useReporteDocente()
const { datos, loading, error, buscar } = usePeriodoTitulos()

const filtros = reactive({
  anio: new Date().getFullYear(),
  periodo: '1',
  tipo_titulo: '',
  campos: ['TITULO'],
  campos_titulo: [],
})

const camposDocenteDisponibles = [
  { value: 'CI', label: 'CI' },
  { value: 'FECHA_NAC', label: 'Fecha de nacimiento' },
  { value: 'SEXO', label: 'Sexo' },
  { value: 'TITULO', label: 'Título (abrev.)' },
  { value: 'FECHA_NOMBRAMIENTO', label: 'Fecha de nombramiento' },
]

// El nombre del título siempre se muestra, por eso no es opción
const camposTituloDisponibles = [
  { value: 'UNIVERSIDAD', label: 'Universidad' },
  { value: 'PAIS', label: 'País' },
  { value: 'FECHA_TITULO', label: 'Fecha del título' },
  { value: 'NUMERO', label: 'Número' },
]

const etiqueta = (campo) =>
  camposDocenteDisponibles.find(c => c.value === campo)?.label || campo

function celda(row, c) {
  if (c === 'FECHA_NAC' || c === 'FECHA_NOMBRAMIENTO') return fmtFecha(row[c])
  return row[c] ?? ''
}

const buscoAlMenosUnaVez = ref(false)
const generandoExcel = ref(false)
const tituloSeleccionado = ref(null)
const busqueda = ref('')

async function onBuscar() {
  buscoAlMenosUnaVez.value = true
  tituloSeleccionado.value = null
  busqueda.value = ''
  await buscar({ anio: filtros.anio, periodo: filtros.periodo, tipo_titulo: filtros.tipo_titulo })
}

async function onGenerarExcel() {
  generandoExcel.value = true
  try {
    await descargarExcel({
      ...filtros,
      campos_titulo: [...new Set(['NOMBRE_TITULO', ...filtros.campos_titulo])],
    })
  } catch (e) {
    // el error queda en errorExcel
  } finally {
    generandoExcel.value = false
  }
}

// ── Chips ──
const resumenTitulos = computed(() => {
  const mapa = {}
  for (const r of datos.value) {
    if (!r.titulos.length) continue
    const clave = r.TITULO || 'Sin abrev.'
    mapa[clave] = (mapa[clave] || 0) + 1
  }
  return Object.entries(mapa)
    .map(([titulo, docentes]) => ({ titulo, docentes }))
    .sort((a, b) => b.docentes - a.docentes)
})

const sinTituloCount = computed(() => datos.value.filter(r => !r.titulos.length).length)

// ── Filtro por chip + buscador ──
const datosFiltrados = computed(() => {
  let lista = datos.value

  if (tituloSeleccionado.value === '__SIN__') {
    lista = lista.filter(r => !r.titulos.length)
  } else if (tituloSeleccionado.value) {
    lista = lista.filter(r => r.titulos.length && (r.TITULO || 'Sin abrev.') === tituloSeleccionado.value)
  }

  const q = norm(busqueda.value)
  if (q) {
    lista = lista.filter(r =>
      [r.CODIGO, r.APELLIDOS, r.NOMBRES, r.TITULO, ...r.titulos.map(t => t.nombre_titulo), ...r.titulos.map(t => t.tipo_titulo)]
        .map(norm).join(' ').includes(q)
    )
  }
  return lista
})

function verTitulosDocente(row) {
  router.push({
    name: 'DocenteTitulos',
    params: { codigo: row.CODIGO },
    query: { anio: filtros.anio, periodo: filtros.periodo },
  })
}

onMounted(() => {
  cargarTiposTitulo()
})
</script>