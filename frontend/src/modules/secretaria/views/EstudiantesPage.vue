<template>
  <div class="min-h-screen bg-slate-50">

    <!-- ===== HEADER ===== -->
    <div class="bg-white border-b border-slate-200 ">
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

          <!-- ===== Botón EXPORTAR (Ver / Descargar) ===== -->
          <div class="relative" ref="exportarDropdownRef">
            <div
              class="inline-flex rounded-full overflow-hidden border border-emerald-200 shadow-sm"
              :class="(cargando || exportando) ? 'opacity-40 pointer-events-none' : ''"
            >
              <button
                type="button"
                @click.stop="mostrarMenuExportar = !mostrarMenuExportar"
                title="Exportar lista de estudiantes"
                class="inline-flex items-center gap-1.5 px-4 py-1.5 text-sm font-semibold
                       bg-emerald-50 hover:bg-emerald-100 active:bg-emerald-200 text-emerald-700
                       transition"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 14h18M10 3v18M6 3h12a1 1 0 011 1v16a1 1 0 01-1 1H6a1 1 0 01-1-1V4a1 1 0 011-1z" />
                </svg>
                Exportar
              </button>

              <button
                type="button"
                @click.stop="mostrarMenuExportar = !mostrarMenuExportar"
                class="px-2.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 active:bg-emerald-200 text-emerald-700
                       border-l border-emerald-200 transition"
                aria-label="Más opciones"
              >
                <svg
                  width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                  :style="mostrarMenuExportar ? 'transform: rotate(180deg);' : ''"
                  style="transition: transform 0.15s"
                >
                  <polyline points="6 9 12 15 18 9" />
                </svg>
              </button>
            </div>

            <div v-if="mostrarMenuExportar" class="fixed inset-0 z-40" @click="mostrarMenuExportar = false" />

            <Transition
              enter-active-class="transition-all duration-150 ease-out"
              enter-from-class="opacity-0 scale-95 -translate-y-1"
              enter-to-class="opacity-100 scale-100 translate-y-0"
              leave-active-class="transition-all duration-100 ease-in"
              leave-from-class="opacity-100 scale-100 translate-y-0"
              leave-to-class="opacity-0 scale-95 -translate-y-1"
            >
              <div
                v-if="mostrarMenuExportar"
                class="absolute right-0 top-full mt-1.5 z-50 bg-white border border-slate-200 rounded-xl shadow-xl overflow-hidden w-72"
              >
                <button
                  @click="verLista(); mostrarMenuExportar = false"
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

                <button
                  @click="descargar(); mostrarMenuExportar = false"
                  class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors text-left"
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
          <!-- ===== FIN Botón EXPORTAR ===== -->

        </div>
      </div>

      <!-- Barra de progreso de descarga (solo visible mientras exporta) -->
      <div v-if="exportando" class="max-w-7xl mx-auto pb-3">
        <div class="flex items-center gap-3 text-xs text-slate-500">
          <div class="flex-1 h-1.5 bg-slate-100 rounded-full overflow-hidden">
            <div
              class="h-full bg-emerald-500 transition-all duration-200"
              :style="{ width: progresoPorcentaje + '%' }"
            />
          </div>
          <span class="shrink-0 tabular-nums">
            {{ progreso.cargados.toLocaleString() }} / {{ progreso.total.toLocaleString() }}
          </span>
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

import { ref, computed, onMounted, watch } from 'vue'
import EstudiantesFiltros from '../components/EstudiantesFiltros.vue'
import EstudiantesTabla from '../components/EstudiantesTabla.vue'
import estudiantesInscritosService, {
  ANIO_ACTUAL,
  PERIODO_ACTUAL,
} from '../services/estudiantesInscritosService.js'
import {
  obtenerVistaPrevia,
  descargarCsvCompleto,
  LIMITE_PREVIEW,
  UMBRAL_ADVERTENCIA,
} from '../services/reporteExcelEstudiantes.js'

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

// Menú desplegable del botón "Exportar"
const mostrarMenuExportar = ref(false)
const exportarDropdownRef = ref(null)

// Estado de la exportación (descarga por lotes)
const exportando = ref(false)
const progreso = ref({ cargados: 0, total: 0 })
const progresoPorcentaje = computed(() =>
  progreso.value.total ? Math.round((progreso.value.cargados / progreso.value.total) * 100) : 0
)

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

/**
 * "Ver": trae solo una muestra acotada (nunca el dataset completo) y
 * la muestra en una pestaña nueva. Si el total supera el límite,
 * avisa y sugiere filtrar por nivel/carrera.
 */
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

  const encabezados = ['Carrera', 'Nivel', 'Materia', 'Grupo', 'Codigo', 'Estudiante']
  const filasHtml = filas
    .map((e) => {
      const valores = [e.siglaPlan, e.nivel, e.nombreMateria, e.grupo, e.codEstudiante, e.estudiante]
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
      <title>Vista previa – Estudiantes Inscritos ${filtros.value.periodo}/${filtros.value.anio}</title>
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
      <h1>Estudiantes Inscritos – Período ${filtros.value.periodo}/${filtros.value.anio}</h1>
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

/**
 * "Descargar": trae el dataset completo en lotes (ver
 * reporteExcelEstudiantes.js) y arma el CSV, mostrando progreso.
 */
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