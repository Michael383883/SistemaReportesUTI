
<template>
  <div class="bg-gray-50 pb-4">

    <!-- Header compacto -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
      <div>
        <h1 class="text-xl font-semibold text-gray-900">Documentos de Docentes</h1>
        <p class="text-sm text-gray-500">Listado de documentos registrados</p>
      </div>
      <div class="flex items-center gap-2 flex-wrap">
        <!-- Botón para generar reporte Excel -->
        <button
          @click="generarReporteExcel"
          class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors whitespace-nowrap"
          title="Generar reporte en Excel"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
          </svg>
          Reporte Excel
        </button>

        <router-link
          :to="{ name: 'clasificaciones-nueva' }"
          class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors whitespace-nowrap"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
          </svg>
          Nueva
        </router-link>
      </div>
    </div>

    <!-- Barra de búsqueda + botón de filtros agrupado -->
    <div class="bg-white rounded-xl border border-gray-200 p-3 mb-2 flex flex-wrap items-center gap-2">
      <div class="flex-1 min-w-[150px] relative">
        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input
          v-model="filtroNombre"
          type="text"
          placeholder="Buscar docente..."
          class="w-full pl-9 pr-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        />
      </div>

      <!-- Botón único que despliega el panel de filtros -->
      <button
        @click="mostrarFiltros = !mostrarFiltros"
        class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium rounded-lg border transition-colors"
        :class="mostrarFiltros || filtrosActivosCount > 0
          ? 'bg-blue-50 border-blue-200 text-blue-700'
          : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
        </svg>
        Filtros
        <span
          v-if="filtrosActivosCount > 0"
          class="inline-flex items-center justify-center w-5 h-5 bg-blue-600 text-white text-xs font-semibold rounded-full"
        >
          {{ filtrosActivosCount }}
        </span>
        <svg
          class="w-4 h-4 transition-transform"
          :class="{ 'rotate-180': mostrarFiltros }"
          fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
        >
          <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
        </svg>
      </button>
    </div>

    <!-- Panel de filtros desplegable -->
    <div
      v-if="mostrarFiltros"
      class="bg-white rounded-xl border border-gray-200 p-3 mb-4 flex flex-wrap items-center gap-2"
    >
      <select
        v-model="filtros.categoria"
        @change="cargar"
        class="px-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
      >
        <option value="">Todos</option>
        <option value="Docentes Titulares">Titulares</option>
        <option value="Docentes Temporales">Temporales</option>
      </select>
      <select
        v-model="filtros.nivel"
        @change="cargar"
        class="px-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
      >
        <option value="">Todos los niveles</option>
        <option value="PRIMER NIVEL">PRIMER NIVEL</option>
        <option value="SEGUNDO NIVEL">SEGUNDO NIVEL</option>
        <option value="TERCER NIVEL">TERCER NIVEL</option>
      </select>
      <input
        v-model="filtros.gestion"
        @change="cargar"
        type="text"
        placeholder="Gestión"
        class="w-28 px-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
      />
      <button
        @click="limpiarFiltros"
        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm text-gray-400 hover:text-gray-600 rounded-lg transition-colors"
        title="Limpiar filtros"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
        Limpiar
      </button>
    </div>

    <!-- Estados -->
    <div v-if="clasificacion.loading.value" class="text-center py-12 text-sm text-gray-400">
      <svg class="w-8 h-8 mx-auto animate-spin text-blue-500 mb-3" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
      </svg>
      Cargando...
    </div>

    <div v-else-if="clasificacion.error.value" class="bg-white rounded-xl border border-red-200 p-8 text-center text-sm text-red-500">
      {{ clasificacion.error.value }}
    </div>

    <div v-else-if="!listadoFiltrado.length" class="bg-white rounded-xl border border-gray-200 p-12 text-center text-sm text-gray-400">
      <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
      </svg>
      No hay clasificaciones registradas
    </div>

    <!-- Tabla compacta -->
    <div v-else class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-100 bg-gray-50/80">
              <th class="text-left font-medium text-gray-500 px-4 py-3 text-xs uppercase tracking-wider">Docente</th>
              <th class="text-left font-medium text-gray-500 px-4 py-3 text-xs uppercase tracking-wider">Categoria</th>
              <th class="text-left font-medium text-gray-500 px-4 py-3 text-xs uppercase tracking-wider">Nivel</th>
              <th class="text-left font-medium text-gray-500 px-4 py-3 text-xs uppercase tracking-wider">Gestión</th>
              <th class="text-left font-medium text-gray-500 px-4 py-3 text-xs uppercase tracking-wider">Periodo</th>
              <th class="text-left font-medium text-gray-500 px-4 py-3 text-xs uppercase tracking-wider">PDF</th>
              <th class="text-right font-medium text-gray-500 px-4 py-3 text-xs uppercase tracking-wider">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="c in listadoFiltrado"
              :key="c.COD_DOCENTE ?? c.NOMBRE_DOCENTE"
              class="border-b border-gray-50 last:border-0 hover:bg-gray-50/50 transition-colors"
            >
              <!-- Docente -->
              <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                  <span class="font-medium text-gray-800 text-base whitespace-normal break-words">
                    {{ c.NOMBRE_DOCENTE }}
                  </span>
                  <span
                    v-if="c._totalClasificaciones > 1"
                    class="inline-flex items-center justify-center px-1.5 py-0.5 min-w-[22px] bg-gray-100 text-gray-500 text-xs font-semibold rounded-full flex-shrink-0"
                    title="Cantidad de clasificaciones registradas"
                  >
                    {{ c._totalClasificaciones }}
                  </span>
                </div>
              </td>

              <!-- Categoria -->
              <td class="px-4 py-3">
                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium" :class="badgeCategoria(c.CATEGORIA)">
                  {{ c.CATEGORIA }}
                </span>
              </td>

              <!-- Nivel - TEXTO NORMAL SIN COLORES -->
              <td class="px-4 py-3">
                <span class="text-sm font-medium text-gray-700">
                  {{ c.NIVEL || '—' }}
                </span>
              </td>

              <!-- Gestión -->
              <td class="px-4 py-3 text-gray-600 text-sm font-medium">{{ c.GESTION || '—' }}</td>

              <!-- Periodo -->
              <td class="px-4 py-3 text-gray-600 text-sm font-medium">{{ c.PERIODO || '—' }}</td>

              <!-- PDF -->
              <td class="px-4 py-3">
                <a
                  v-if="c.NOMBRE_ARCHIVO"
                  :href="clasificacion.urlPdf(c.ID_CLASIFICACION, 'inline')"
                  target="_blank"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h3m5-13v4a1 1 0 001 1h4m-5-5H8a2 2 0 00-2 2v14a2 2 0 002 2h8a2 2 0 002-2V8l-5-5z"/>
                  </svg>
                  Ver PDF
                </a>
                <span v-else class="text-gray-300 text-sm">—</span>
              </td>

              <!-- Acciones -->
              <td class="px-4 py-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <router-link
                    v-if="c.COD_DOCENTE"
                    :to="{ name: 'clasificaciones-docente', params: { cod_docente: c.COD_DOCENTE } }"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium shadow-sm transition-colors"
                    title="Ver documentos adjuntados"
                  >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span class="hidden sm:inline">Documentos adjuntados</span>
                  </router-link>

                  <!-- Botón eliminar -->
                  <button
                    @click="confirmarEliminar(c)"
                    class="inline-flex items-center p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                    title="Eliminar clasificación"
                  >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Contador de registros -->
      <div class="px-4 py-2.5 bg-gray-50/80 border-t border-gray-100 text-xs text-gray-400 flex justify-between">
        <span>Mostrando {{ listadoFiltrado.length }} docentes</span>
        <span v-if="clasificacion.listado.value.length !== listadoFiltrado.length">
          ({{ clasificacion.listado.value.length }} clasificaciones en total)
        </span>
      </div>
    </div>

    <!-- Modal de Confirmación para Eliminar -->
    <div v-if="mostrarModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" @click.self="cerrarModal">
      <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4 p-6">
        <div class="flex items-center justify-center mb-4">
          <div class="w-14 h-14 rounded-full bg-red-100 flex items-center justify-center">
            <svg class="w-7 h-7 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
          </div>
        </div>
        <h3 class="text-center text-lg font-semibold text-gray-900 mb-2">
          ¿Eliminar clasificación?
        </h3>
        <p class="text-center text-sm text-gray-500 mb-6">
          ¿Estás seguro de eliminar la clasificación de <br>
          <span class="font-medium text-gray-700">"{{ itemAEliminar?.NOMBRE_DOCENTE || 'Sin docente' }}"</span>?
          <br>
          <span v-if="itemAEliminar?._totalClasificaciones > 1" class="text-sm text-amber-600">
            Este docente tiene {{ itemAEliminar._totalClasificaciones }} clasificaciones registradas. Solo se eliminará la más reciente mostrada en el listado.
          </span>
          <span v-else class="text-sm text-red-500">
            Esta acción eliminará todas las materias y referencias asociadas.
          </span>
        </p>
        <div class="flex gap-3">
          <button
            @click="cerrarModal"
            class="flex-1 px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
          >
            Cancelar
          </button>
          <button
            @click="eliminarClasificacion"
            :disabled="eliminando"
            class="flex-1 px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed rounded-lg transition-colors flex items-center justify-center gap-2"
          >
            <svg v-if="eliminando" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            {{ eliminando ? 'Eliminando...' : 'Sí, eliminar' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useClasificacion } from '../composables/useClasificacion'

const router = useRouter()
const clasificacion = useClasificacion()
const API_BASE = import.meta.env.VITE_API_URL ?? ''

// Filtros
const filtroNombre = ref('')
const filtros = ref({
  categoria: '',
  nivel: '',
  gestion: '',
})

// Panel de filtros colapsable
const mostrarFiltros = ref(false)
const filtrosActivosCount = computed(() => {
  let count = 0
  if (filtros.value.categoria) count++
  if (filtros.value.nivel) count++
  if (filtros.value.gestion) count++
  return count
})

// Modal de eliminación
const mostrarModal = ref(false)
const itemAEliminar = ref(null)
const eliminando = ref(false)

// ─── Cargar datos ───
async function cargar() {
  try {
    await clasificacion.listar({
      categoria: filtros.value.categoria || undefined,
      nivel: filtros.value.nivel || undefined,
      gestion: filtros.value.gestion || undefined,
    })
  } catch (e) {
    console.error('Error cargando listado de clasificaciones:', e)
  }
}

function limpiarFiltros() {
  filtroNombre.value = ''
  filtros.value.categoria = ''
  filtros.value.nivel = ''
  filtros.value.gestion = ''
  cargar()
}

// ─── Agrupado por docente ───
const listadoPorDocente = computed(() => {
  const mapa = new Map()

  for (const c of clasificacion.listado.value) {
    const clave = c.COD_DOCENTE ?? c.NOMBRE_DOCENTE

    if (!mapa.has(clave)) {
      mapa.set(clave, { ...c, _totalClasificaciones: 1 })
      continue
    }

    const existente = mapa.get(clave)
    const total = existente._totalClasificaciones + 1

    const fechaExistente = existente.FECHA_REGISTRO ? new Date(existente.FECHA_REGISTRO) : null
    const fechaActual = c.FECHA_REGISTRO ? new Date(c.FECHA_REGISTRO) : null

    if (fechaActual && (!fechaExistente || fechaActual > fechaExistente)) {
      mapa.set(clave, { ...c, _totalClasificaciones: total })
    } else {
      mapa.set(clave, { ...existente, _totalClasificaciones: total })
    }
  }

  return Array.from(mapa.values())
})

// ─── Filtrado en cliente ───
const listadoFiltrado = computed(() => {
  const term = filtroNombre.value.trim().toLowerCase()
  if (!term) return listadoPorDocente.value
  return listadoPorDocente.value.filter(c =>
    (c.NOMBRE_DOCENTE || '').toLowerCase().includes(term)
  )
})

// ─── Badge de categoria ───
function badgeCategoria(categoria) {
  if (categoria === 'Docentes Titulares')  return 'bg-emerald-50 text-emerald-700'
  if (categoria === 'Docentes Temporales') return 'bg-amber-50 text-amber-700'
  return 'bg-gray-50 text-gray-600'
}

// ─── Generar Reporte Excel ───
function generarReporteExcel() {
  const gestion = filtros.value.gestion || 'I/2023'
  const version = '4ta Versión'

  const url = `${API_BASE}/api/reportes/docentes-clasificados/excel?gestion=${encodeURIComponent(gestion)}&version=${encodeURIComponent(version)}`
  window.open(url, '_blank')
}

// ─── Eliminar clasificación ───
function confirmarEliminar(item) {
  itemAEliminar.value = item
  mostrarModal.value = true
}

function cerrarModal() {
  mostrarModal.value = false
  itemAEliminar.value = null
}

async function eliminarClasificacion() {
  if (!itemAEliminar.value) return

  eliminando.value = true

  try {
    const result = await clasificacion.eliminar(itemAEliminar.value.ID_CLASIFICACION)

    if (result?.ok) {
      await cargar()
      cerrarModal()
    } else {
      alert(result?.error || 'Error al eliminar la clasificación')
    }
  } catch (e) {
    console.error('Error al eliminar:', e)
    alert('Error al eliminar la clasificación')
  } finally {
    eliminando.value = false
  }
}

// ─── Debounce para búsqueda ───
let timeoutId = null
watch(filtroNombre, () => {
  clearTimeout(timeoutId)
  timeoutId = setTimeout(() => {}, 300)
})

onMounted(cargar)
</script>