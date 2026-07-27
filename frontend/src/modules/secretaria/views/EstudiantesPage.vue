<template>
  <div class="min-h-screen bg-slate-50">

    <!-- ===== HEADER ===== -->
    <div class="border-b border-slate-200 bg-white">
      <div class="w-full px-2 sm:px-2 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">

        <!-- Título -->
        <div>
          <h1 class="text-xl font-bold text-slate-800 tracking-tight">
            Estudiantes de la Carrera
          </h1>
          
            <p class="text-sm text-slate-500 mt-0.5">
  Gestión · Período {{ PERIODOS_MAP[filtros.periodo] || filtros.periodo }} / {{ filtros.anio }}
</p>
          
        </div>

        <!-- Zona derecha: badge + botón Generar -->
        <div class="flex flex-wrap items-center gap-2">

          <!-- Badge total -->
          <div class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-4 py-1.5 text-blue-700 text-sm font-semibold border border-blue-100">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m9-4a4 4 0 11-8 0 4 4 0 018 0zm6 4a2 2 0 11-4 0 2 2 0 014 0zM5 16a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            {{ total.toLocaleString() }} estudiante{{ total !== 1 ? 's' : '' }}
          </div>

          <!-- ===== Botón GENERAR (Ver lista / Descargar CSV completo) ===== -->
          <div class="relative" ref="generarDropdownRef">

            <div
              class="inline-flex rounded-full overflow-hidden border border-emerald-700/30 shadow-sm shadow-emerald-900/10"
              :class="(cargando || exportando) ? 'opacity-40 pointer-events-none' : ''"
            >
              <button
                type="button"
                @click.stop="mostrarMenuGenerar = !mostrarMenuGenerar"
                class="inline-flex items-center gap-1.5 px-4 py-1.5 text-sm font-semibold
                       bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white
                       transition-all duration-150"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Generar
              </button>

              <button
                type="button"
                @click.stop="mostrarMenuGenerar = !mostrarMenuGenerar"
                class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white
                       border-l border-emerald-500/50 transition-all duration-150"
                aria-label="Más opciones"
              >
                <svg
                  width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                  :style="mostrarMenuGenerar ? 'transform: rotate(180deg);' : ''"
                  style="transition: transform 0.15s"
                >
                  <polyline points="6 9 12 15 18 9" />
                </svg>
              </button>
            </div>

            <div v-if="mostrarMenuGenerar" class="fixed inset-0 z-40" @click="mostrarMenuGenerar = false" />

            <Transition
              enter-active-class="transition-all duration-150 ease-out"
              enter-from-class="opacity-0 scale-95 -translate-y-1"
              enter-to-class="opacity-100 scale-100 translate-y-0"
              leave-active-class="transition-all duration-100 ease-in"
              leave-from-class="opacity-100 scale-100 translate-y-0"
              leave-to-class="opacity-0 scale-95 -translate-y-1"
            >
              <div
                v-if="mostrarMenuGenerar"
                class="absolute right-0 top-full mt-1.5 z-50
                       bg-white border border-slate-200 rounded-xl
                       shadow-xl overflow-hidden w-72"
              >
                <!-- ── Lista de estudiantes (vista previa) ── -->
                <div class="px-4 pt-3 pb-1">
                  <p class="text-[0.65rem] font-semibold tracking-widest uppercase text-slate-400">
                    Lista de estudiantes
                  </p>
                </div>

                <button
                  @click="verLista(); mostrarMenuGenerar = false"
                  class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors text-left"
                >
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-slate-400 shrink-0">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                    <circle cx="12" cy="12" r="3" />
                  </svg>
                  <div>
                    <div class="font-medium leading-tight">Ver lista</div>
                    <div class="text-xs text-slate-400 mt-0.5">Muestra hasta {{ LIMITE_PREVIEW }} filas</div>
                  </div>
                </button>

                <div class="border-t border-slate-100 mx-4"></div>

                <!-- ── Descarga completa ── -->
                <div class="px-4 pt-3 pb-1">
                  <p class="text-[0.65rem] font-semibold tracking-widest uppercase text-slate-400">
                    Descarga completa
                  </p>
                </div>

                <button
                  @click="descargar(); mostrarMenuGenerar = false"
                  class="w-full flex items-center gap-3 px-4 py-2.5 pb-3 text-sm text-slate-700 hover:bg-slate-50 transition-colors text-left"
                >
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-slate-400 shrink-0">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                    <polyline points="7 10 12 15 17 10" />
                    <line x1="12" y1="15" x2="12" y2="3" />
                  </svg>
                  <div>
                    <div class="font-medium leading-tight">Descargar CSV completo</div>
                    <div class="text-xs text-slate-400 mt-0.5">
                      {{ total > UMBRAL_ADVERTENCIA ? 'Dataset grande: puede tardar unos segundos' : 'Nombre, código, materia y grupo' }}
                    </div>
                  </div>
                </button>
              </div>
            </Transition>

          </div>
          <!-- ===== FIN Botón GENERAR ===== -->

        </div>
      </div>

      <!-- Barra de progreso de descarga -->
      <div v-if="exportando" class="max-w-7xl mx-auto px-4 sm:px-6 pb-3">
        <div class="flex items-center gap-3 text-xs text-slate-500">
          <div class="flex-1 h-1.5 bg-slate-100 rounded-full overflow-hidden">
            <div class="h-full bg-emerald-500 transition-all duration-200" :style="{ width: progresoPorcentaje + '%' }" />
          </div>
          <span class="shrink-0 tabular-nums">
            {{ progreso.cargados.toLocaleString() }} / {{ progreso.total.toLocaleString() }}
          </span>
        </div>
      </div>
    </div>

    <!-- =======================================================
     BUSCADOR + FILTROS
      ======================================================== -->
    <div >

      <div class="flex items-center gap-3">

        <!-- Selector de gestión (año/periodo) -->
        <div class="flex items-center gap-1.5 shrink-0">
           <select
            v-model="filtros.periodo"
            class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-500 transition"
            title="Periodo académico"
          >
            <option v-for="(nombre, codigo) in PERIODOS_MAP" :key="codigo" :value="codigo">
              {{ nombre }}
            </option>
          </select>

            <select
              v-model="filtros.anio"
              class="h-10 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-500 transition"
              title="Año"
            >
              <option v-for="a in aniosDisponibles" :key="a" :value="a">
                {{ a }}
              </option>
            </select>

            <button
              v-if="!gestionEsAutomatica"
              @click="volverAGestionActual"
              title="Volver a la gestión actual detectada por el sistema"
              class="h-10 px-3 rounded-xl border border-amber-200 bg-amber-50 text-amber-700 text-xs font-semibold hover:bg-amber-100 transition shrink-0"
            >
              Hoy
            </button>
          </div>

        <!-- Buscador -->
        <div class="relative flex-1">
          <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0z" />
          </svg>
          <input
            v-model="filtros.busqueda"
            type="text"
            placeholder="Buscar estudiante o código..."
            class="w-full h-10 rounded-xl border border-slate-200 bg-white pl-11 pr-4 text-sm text-slate-700 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-500 transition"
          />
        </div>

        <!-- Botón filtros -->
        <button
          @click="mostrarFiltros = !mostrarFiltros"
          class="flex items-center gap-2 h-10 px-4 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 transition shrink-0"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 01.8 1.6L15 12v6l-6 2v-8L3.2 4.6A1 1 0 013 4z" />
          </svg>
          <span class="text-sm font-medium text-slate-700">Filtros</span>
          <span
            v-if="filtros.plan || filtros.nivel"
            class="flex items-center justify-center min-w-[20px] h-5 px-1 rounded-full bg-blue-600 text-white text-[11px] font-semibold"
          >
            {{ [filtros.plan, filtros.nivel].filter(Boolean).length }}
          </span>
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-500 transition duration-200" :class="{ 'rotate-180': mostrarFiltros }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>

      </div>

      <!-- Panel filtros -->
      <Transition
        enter-active-class="transition-all duration-200"
        leave-active-class="transition-all duration-150"
        enter-from-class="opacity-0 -translate-y-2"
        leave-to-class="opacity-0 -translate-y-2"
      >
        <div v-if="mostrarFiltros" class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-4">

          <div class="flex items-center justify-between mb-3">
            <span class="text-sm font-semibold text-slate-700">Filtros avanzados</span>
            <button
              v-if="filtros.plan || filtros.nivel"
              @click="limpiarFiltrosAvanzados"
              class="text-xs font-medium text-blue-600 hover:text-blue-700 transition"
            >
              Restablecer
            </button>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">

            <!-- Carrera -->
            <div>
              <label class="block text-xs font-medium text-slate-500 mb-1">Carrera</label>
              <select v-model="filtros.plan" class="w-full h-10 rounded-lg border border-slate-200 bg-white px-3 text-sm">
                <option value="">Todas las carreras</option>
                <option v-for="(nombre, codigo) in PLANES" :key="codigo" :value="codigo">{{ nombre }}</option>
              </select>
            </div>

            <!-- Nivel -->
            <div>
              <label class="block text-xs font-medium text-slate-500 mb-1">Nivel</label>
              <select v-model="filtros.nivel" class="w-full h-10 rounded-lg border border-slate-200 bg-white px-3 text-sm">
                <option value="">Todos los niveles</option>
                <option v-for="n in NIVELES" :key="n" :value="n">Nivel {{ n }}</option>
              </select>
            </div>

          </div>
        </div>
      </Transition>

    </div>

    <!-- ===== CONTENIDO PRINCIPAL ===== -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-6">

      <!-- Error -->
      <p v-if="error" class="bg-red-50 text-red-700 px-4 py-3 rounded-xl text-sm mb-4 border border-red-100">
        {{ error }}
      </p>

      <!-- Estado cargando -->
      <div v-if="cargando" class="flex items-center justify-center py-24">
        <div class="flex flex-col items-center gap-3 text-slate-400">
          <svg class="animate-spin h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
          </svg>
          <span class="text-sm">Cargando estudiantes...</span>
        </div>
      </div>

    <!-- Sin resultados -->
<div v-else-if="!gruposFiltrados.length" class="flex flex-col items-center justify-center py-24 text-slate-400">
  <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mb-3 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
    <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
  </svg>
  <p class="text-base font-medium">Sin resultados</p>
  <p class="text-sm mt-1">Intenta ajustar los filtros de búsqueda.</p>
  <button @click="limpiarFiltros" class="mt-3 text-sm font-medium text-blue-600 hover:text-blue-700 transition">
    Limpiar filtros
  </button>
</div>
      <!-- Tabla -->
       <div v-else class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 overflow-hidden">
  <EstudiantesTabla :grupos="gruposFiltrados" :cargando="cargando" />
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
defineOptions({ name: 'EstudiantesPage' })

import { ref, computed, onMounted, watch } from 'vue'
import EstudiantesTabla from '../components/EstudiantesTabla.vue'
import estudiantesInscritosService, {
  ANIO_ACTUAL,
  PERIODO_ACTUAL,
  PERIODOS,
  PLANES,
  NIVELES,
} from '../services/estudiantesInscritosService.js'
import {
  obtenerVistaPrevia,
  descargarCsvCompleto,
  LIMITE_PREVIEW,
  UMBRAL_ADVERTENCIA,
} from '../services/reporteExcelEstudiantes.js'

// ─────────────────────────────────────────────
// Estado
// ─────────────────────────────────────────────
console.log('PERIODOS:', JSON.stringify(PERIODOS))
//const PERIODOS_LABEL = Object.fromEntries(PERIODOS.map(p => [p.value, p.label.replace('Periodo ', '')]))

const filtros = ref({
  anio: ANIO_ACTUAL,
  periodo: PERIODO_ACTUAL,
  plan: '',
  nivel: '',
  busqueda: '',
})

const grupos  = ref([])
const total = ref(0)
const cargando = ref(false)
const error = ref(null)

const page = ref(1)
const perPage = ref(300)
const totalPages = ref(0)

const mostrarFiltros = ref(false)
const mostrarMenuGenerar = ref(false)
const generarDropdownRef = ref(null)

const exportando = ref(false)
const progreso = ref({ cargados: 0, total: 0 })
const progresoPorcentaje = computed(() =>
  progreso.value.total ? Math.round((progreso.value.cargados / progreso.value.total) * 100) : 0
)

const aniosDisponibles = computed(() => {
  const actual = new Date().getFullYear()
  const desde = actual - 5
  const anios = []
  for (let a = actual + 1; a >= desde; a--) anios.push(String(a))
  return anios
})

// ─────────────────────────────────────────────
// Carga de datos
// ─────────────────────────────────────────────
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

    grupos.value = resp.data
    total.value = resp.total
    totalPages.value = resp.totalPages
  } catch (e) {
    error.value = e.message || 'Ocurrió un error al cargar los estudiantes.'
    grupos.value = []
    total.value = 0
  } finally {
    cargando.value = false
  }
}

const gruposFiltrados = ref([])


function aplicarBusquedaLocal() {
  const termino = filtros.value.busqueda.trim().toLowerCase()

  if (!termino) {
    gruposFiltrados.value = grupos.value
    return
  }

  gruposFiltrados.value = grupos.value
    .map((g) => {
      // Coincide si el termino esta en la materia o el docente...
      const coincideGrupo =
        g.nombreMateria?.toLowerCase().includes(termino) ||
        g.docente?.docente?.toLowerCase().includes(termino)

      // ...o filtra solo los estudiantes que coincidan dentro del grupo
      const estudiantesCoincidentes = g.estudiantes.filter((est) =>
        est.estudiante.toLowerCase().includes(termino) ||
        String(est.codEstudiante).toLowerCase().includes(termino)
      )

      if (coincideGrupo) return g
      if (estudiantesCoincidentes.length) return { ...g, estudiantes: estudiantesCoincidentes }
      return null
    })
    .filter(Boolean)
}

watch(grupos, aplicarBusquedaLocal)
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

function limpiarFiltrosAvanzados() {
  filtros.value.plan = ''
  filtros.value.nivel = ''
}

// ─────────────────────────────────────────────
// Acciones – exportación
// ─────────────────────────────────────────────
async function verLista() {
  error.value = null
  try {
    const { filas, total: totalReal, truncado } = await obtenerVistaPrevia(filtros.value)
    abrirVistaPrevia(filas, totalReal, truncado)
  } catch (e) {
    error.value = e.message || 'Ocurrió un error al generar la vista previa.'
  }
}

function abrirVistaPrevia(filas, totalReal, truncado) {
  const ventana = window.open('', '_blank')

  if (!ventana) {
    error.value = 'Tu navegador bloqueó la ventana de vista previa. Habilita las ventanas emergentes e inténtalo de nuevo.'
    return
  }

  if (!filas.length) {
    ventana.document.write('<p style="font-family: Arial, sans-serif; padding: 24px;">No hay estudiantes para mostrar con los filtros actuales.</p>')
    ventana.document.close()
    return
  }

  const encabezados = ['Carrera', 'Nivel', 'Materia', 'Grupo', 'Cod Docente', 'Docente', 'Codigo', 'Estudiante']
  const filasHtml = filas
    .map((e) => {
      const valores = [e.siglaPlan, e.nivel, e.nombreMateria, e.grupo, e.codDocente, e.docente, e.codEstudiante, e.estudiante]
      return `<tr>${valores.map((v) => `<td>${escapeHtml(v)}</td>`).join('')}</tr>`
    })
    .join('')

  const avisoTruncado = truncado
    ? `<p class="aviso">Mostrando ${filas.length.toLocaleString()} de ${totalReal.toLocaleString()} estudiantes.
       Para ver el listado completo, filtra por nivel o carrera, o usa "Descargar CSV completo".</p>`
    : ''

  ventana.document.write(`
    <!DOCTYPE html>
    <html lang="es">
    <head>
      <meta charset="utf-8" />
      <title>Vista previa – Estudiantes ${filtros.value.periodo}/${filtros.value.anio}</title>
      <style>
        body { font-family: Arial, Helvetica, sans-serif; padding: 24px; color: #1e293b; }
        h1 { font-size: 15px; margin: 0 0 8px; }
        .aviso { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; padding: 10px 14px; border-radius: 8px; font-size: 12.5px; margin-bottom: 16px; }
        table { border-collapse: collapse; width: 100%; font-size: 12px; }
        td, th { border: 1px solid #e2e8f0; padding: 6px 10px; text-align: left; white-space: nowrap; }
        th { background: #f1f5f9; font-weight: 600; }
        .toolbar { margin: 16px 0; }
        .toolbar button {
          background: #059669; color: #fff; border: none; border-radius: 8px;
          padding: 8px 16px; font-size: 13px; font-weight: 600; cursor: pointer;
        }
        .toolbar button:hover { background: #047857; }
      </style>
    </head>
    <body>
      <h1>Estudiantes de la Carrera – Período ${filtros.value.periodo}/${filtros.value.anio}</h1>
      ${avisoTruncado}
      <div class="toolbar">
        <button onclick="window.print()">Imprimir / Guardar como PDF</button>
      </div>
      <table>
        <thead><tr>${encabezados.map((h) => `<th>${h}</th>`).join('')}</tr></thead>
        <tbody>${filasHtml}</tbody>
      </table>
    </body>
    </html>
  `)
  ventana.document.close()
}

function escapeHtml(valor) {
  return String(valor ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
}



// ─────────────────────────────────────────────
// Gestión automática vs manual
// ─────────────────────────────────────────────
const gestionEsAutomatica = computed(() =>
  filtros.value.anio === ANIO_ACTUAL && filtros.value.periodo === PERIODO_ACTUAL
)

function volverAGestionActual() {
  filtros.value.anio = ANIO_ACTUAL
  filtros.value.periodo = PERIODO_ACTUAL
}

// Objeto plano { '1': 'I', '2': 'II' } — igual que en Talleres
// Objeto plano { '1': 'I', '2': 'II' } — conversión manual a números romanos
const PERIODOS_MAP = {
  '1': 'I',
  '2': 'II',
}

async function descargar() {
  error.value = null
  exportando.value = true
  progreso.value = { cargados: 0, total: total.value || 0 }

  try {
    await descargarCsvCompleto(filtros.value, (cargados, totalReal) => {
      progreso.value = { cargados, total: totalReal }
    })
  } catch (e) {
    error.value = e.message || 'Ocurrió un error al exportar.'
  } finally {
    exportando.value = false
  }
}

onMounted(cargarEstudiantes)
</script>