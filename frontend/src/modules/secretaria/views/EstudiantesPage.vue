<template>
  <div class="min-h-screen bg-slate-50">

    <!-- ===== HEADER ===== -->
    <div class="bg-white border-b border-slate-200 px-6 py-5">
      <div class="max-w-7xl mx-auto flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">

        <!-- Título -->
        <div>
          <h1 class="text-xl font-bold text-slate-800 tracking-tight">
            Estudiantes Inscritos
          </h1>
          <p class="text-sm text-slate-500 mt-0.5">
            Gestión · Período {{ filtros.periodo }} / {{ filtros.anio }}
          </p>
        </div>

        <!-- Zona derecha: badge + botón exportación -->
        <div class="flex flex-wrap items-center gap-2">

          <!-- Badge total -->
          <div class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-4 py-1.5 text-blue-700 text-sm font-semibold border border-blue-100">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m9-4a4 4 0 11-8 0 4 4 0 018 0zm6 4a2 2 0 11-4 0 2 2 0 014 0zM5 16a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            {{ total }} estudiante{{ total !== 1 ? 's' : '' }}
          </div>

          <!-- Botón Excel -->
          <button
            type="button"
            :disabled="cargando"
            @click="exportarExcel"
            title="Exportar lista a Excel"
            class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 hover:bg-emerald-100 active:bg-emerald-200 border border-emerald-200 text-emerald-700 text-sm font-semibold px-4 py-1.5 transition disabled:opacity-40 disabled:cursor-not-allowed"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 14h18M10 3v18M6 3h12a1 1 0 011 1v16a1 1 0 01-1 1H6a1 1 0 01-1-1V4a1 1 0 011-1z" />
            </svg>
            Excel
          </button>

        </div>
      </div>
    </div>

    <!-- ===== CONTENIDO PRINCIPAL ===== -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-6">

      <!-- Error -->
      <p v-if="error" class="bg-red-50 text-red-700 px-4 py-3 rounded-xl text-sm mb-4 border border-red-100">
        {{ error }}
      </p>

      <!-- Filtros -->
      <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 px-6 py-4 mb-6">
        <EstudiantesFiltros v-model="filtros" @limpiar="limpiarFiltros" />
      </div>

      <!-- Tabla -->
      <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 overflow-hidden">
        <EstudiantesTabla :estudiantes="estudiantesFiltrados" :cargando="cargando" />
      </div>

      <!-- Paginación -->
      <div v-if="totalPages > 1" class="flex items-center justify-center gap-4 mt-6">
        <button
          class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-600 text-sm font-medium px-4 py-2 transition disabled:opacity-50 disabled:cursor-not-allowed"
          :disabled="page === 1"
          @click="irPaginaAnterior"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
          </svg>
          Anterior
        </button>
        <span class="text-sm text-slate-500 font-medium">Página {{ page }} de {{ totalPages }}</span>
        <button
          class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-600 text-sm font-medium px-4 py-2 transition disabled:opacity-50 disabled:cursor-not-allowed"
          :disabled="page === totalPages"
          @click="irPaginaSiguiente"
        >
          Siguiente
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
          </svg>
        </button>
      </div>

    </div>
  </div>
</template>

<script setup>
defineOptions({ name: 'EstudiantesInscritosPage' })

import { ref, onMounted, watch } from 'vue'
import EstudiantesFiltros from '../components/EstudiantesFiltros.vue'
import EstudiantesTabla from '../components/EstudiantesTabla.vue'
import estudiantesInscritosService, {
  ANIO_ACTUAL,
  PERIODO_ACTUAL,
} from '../services/estudiantesInscritosService.js'

const filtros = ref({
  anio: ANIO_ACTUAL,
  periodo: PERIODO_ACTUAL,
  plan: '',
  nivel: '',
  busqueda: '',
})

const estudiantes = ref([])
const total = ref(0)
const cargando = ref(false)
const error = ref(null)

const page = ref(1)
const perPage = ref(100)
const totalPages = ref(0)

async function cargarEstudiantes() {
  cargando.value = true
  error.value = null

  try {
    const resp = await estudiantesInscritosService.getInscritos({
      anio: filtros.value.anio,
      periodo: filtros.value.periodo,
      plan: filtros.value.plan || null,
      nivel: filtros.value.nivel || null,
      page: page.value,
      perPage: perPage.value,
    })

    estudiantes.value = resp.data
    total.value = resp.total
    totalPages.value = resp.totalPages
  } catch (e) {
    error.value = e.message || 'Ocurrió un error al cargar los estudiantes.'
    estudiantes.value = []
    total.value = 0
  } finally {
    cargando.value = false
  }
}

const estudiantesFiltrados = ref([])

function aplicarBusquedaLocal() {
  const termino = filtros.value.busqueda.trim().toLowerCase()

  if (!termino) {
    estudiantesFiltrados.value = estudiantes.value
    return
  }

  estudiantesFiltrados.value = estudiantes.value.filter((est) =>
    est.estudiante.toLowerCase().includes(termino) ||
    String(est.codEstudiante).toLowerCase().includes(termino)
  )
}

watch(estudiantes, aplicarBusquedaLocal)
watch(() => filtros.value.busqueda, aplicarBusquedaLocal)

watch(
  () => [filtros.value.anio, filtros.value.periodo, filtros.value.plan, filtros.value.nivel],
  () => {
    page.value = 1
    cargarEstudiantes()
  }
)

watch(page, cargarEstudiantes)

function irPaginaAnterior() {
  if (page.value > 1) page.value -= 1
}

function irPaginaSiguiente() {
  if (page.value < totalPages.value) page.value += 1
}

function limpiarFiltros() {
  filtros.value = {
    anio: ANIO_ACTUAL,
    periodo: PERIODO_ACTUAL,
    plan: '',
    nivel: '',
    busqueda: '',
  }
}

async function exportarExcel() {
  cargando.value = true
  try {
    const resp = await estudiantesInscritosService.getInscritosCompleto({
      anio: filtros.value.anio,
      periodo: filtros.value.periodo,
      plan: filtros.value.plan || null,
      nivel: filtros.value.nivel || null,
    })

    const filas = resp.data.map((e) => ({
      Carrera: e.siglaPlan,
      Nivel: e.nivel,
      Materia: e.nombreMateria,
      Grupo: e.grupo,
      Codigo: e.codEstudiante,
      Estudiante: e.estudiante,
    }))

    descargarComoCsv(filas, `estudiantes_inscritos_${filtros.value.anio}_${filtros.value.periodo}.csv`)
  } catch (e) {
    error.value = e.message || 'Ocurrió un error al exportar.'
  } finally {
    cargando.value = false
  }
}

function descargarComoCsv(filas, nombreArchivo) {
  if (!filas.length) return

  const encabezados = Object.keys(filas[0])
  const lineas = [
    encabezados.join(','),
    ...filas.map((fila) =>
      encabezados.map((campo) => `"${String(fila[campo] ?? '').replace(/"/g, '""')}"`).join(',')
    ),
  ]

  const blob = new Blob([lineas.join('\n')], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const enlace = document.createElement('a')
  enlace.href = url
  enlace.download = nombreArchivo
  enlace.click()
  URL.revokeObjectURL(url)
}

onMounted(cargarEstudiantes)
</script>