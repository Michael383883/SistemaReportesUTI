<template>
  <div class="bg-gray-50 pb-4">
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm w-full h-[calc(100vh-6rem)] min-h-[620px] flex flex-col">

      <!-- ── Encabezado ── -->
      <div class="flex items-center justify-between gap-3 px-6 py-3.5 border-b border-gray-100">
        <div class="min-w-0">
          <div class="flex items-center gap-2 flex-wrap">
            <h3 class="text-base font-semibold text-gray-900">Orden de prelación por materia</h3>
            <span v-if="etiquetaPeriodo" class="px-2 py-0.5 text-[11px] font-semibold rounded-full bg-indigo-100 text-indigo-700">
              {{ etiquetaPeriodo }}
            </span>
            <span v-if="estaActualizando" class="inline-flex items-center gap-1.5 px-2 py-0.5 text-[11px] font-medium rounded-full bg-blue-50 text-blue-600">
              <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
              </svg>
              Actualizando…
            </span>
          </div>
          <p class="text-xs text-gray-500 mt-0.5">
            Así se verá el archivo. Los cambios se aplican automáticamente.
          </p>
        </div>

        <div class="flex items-center gap-2">
          <button
            @click="restablecerFiltros"
            :disabled="estaActualizando"
            class="hidden sm:inline-flex px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors disabled:opacity-50"
            title="Vuelve a los filtros originales"
          >
            Restablecer filtros
          </button>
          <button
            @click="panelOpciones = !panelOpciones"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
          >
            {{ panelOpciones ? 'Ocultar opciones' : 'Mostrar opciones' }}
            <svg class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-180': !panelOpciones }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/>
            </svg>
          </button>
          <button
            @click="volver"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
            title="Volver al listado"
          >
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Volver
          </button>
        </div>
      </div>

      <!-- ── Panel de opciones (3 bloques numerados) ── -->
      <div v-show="panelOpciones" class="px-6 py-4 bg-slate-50 border-b border-gray-100">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

          <!-- 1 · Qué materias incluir -->
          <section class="bg-white rounded-xl border border-gray-200 p-4">
            <header class="flex items-center gap-2 mb-3">
              <span class="w-5 h-5 rounded-full bg-blue-600 text-white text-[11px] font-bold flex items-center justify-center">1</span>
              <h4 class="text-sm font-semibold text-gray-800">¿Qué materias incluir?</h4>
            </header>

            <div class="grid grid-cols-3 gap-2 mb-2">
              <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-0.5">Año</label>
                <input v-model.number="filtros.anio" type="number" min="1990" max="2100" placeholder="2026"
                  class="w-full px-2.5 py-1.5 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  :class="anioValido ? 'border-gray-300' : 'border-red-300'" />
              </div>
              <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-0.5">Periodo</label>
                <select v-model.number="filtros.periodo"
                  class="w-full px-2.5 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                  <option v-for="p in PERIODOS" :key="p" :value="p">{{ p }}</option>
                </select>
              </div>
              <div class="col-span-3 sm:col-span-1">
                <label class="block text-[11px] font-medium text-gray-500 mb-0.5">Materia</label>
                <input v-model="filtros.materia" type="text" placeholder="Nombre o código"
                  class="w-full px-2.5 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
              </div>
            </div>
            <p v-if="!anioValido" class="text-[11px] text-red-500 mb-2">Escribe un año de 4 dígitos para actualizar la tabla.</p>

            <label class="block text-[11px] font-medium text-gray-500 mb-1">Carrera</label>
            <div class="flex flex-wrap gap-1.5">
              <button
                v-for="c in CARRERAS"
                :key="c.sigla"
                type="button"
                :title="c.nombre"
                :aria-pressed="filtros.carreras.includes(c.sigla)"
                @click="alternarCarrera(c.sigla)"
                class="px-2.5 py-1 text-xs font-medium rounded-full border transition-colors"
                :class="filtros.carreras.includes(c.sigla)
                  ? 'bg-blue-600 border-blue-600 text-white'
                  : 'bg-white border-gray-300 text-gray-600 hover:bg-blue-50'"
              >
                {{ c.sigla }}
              </button>
              <button
                v-if="filtros.carreras.length"
                type="button"
                @click="filtros.carreras = []"
                class="px-2 py-1 text-[11px] text-gray-500 hover:text-red-500"
              >
                Quitar todas
              </button>
            </div>
            <p class="text-[11px] text-gray-400 mt-1.5">
              {{ filtros.carreras.length ? filtros.carreras.join(', ') : 'Todas las carreras' }}
            </p>
          </section>

          <!-- 2 · Cómo mostrarlos -->
          <section class="bg-white rounded-xl border border-gray-200 p-4">
            <header class="flex items-center gap-2 mb-3">
              <span class="w-5 h-5 rounded-full bg-blue-600 text-white text-[11px] font-bold flex items-center justify-center">2</span>
              <h4 class="text-sm font-semibold text-gray-800">¿Cómo mostrarlos?</h4>
            </header>

            <!-- Alcance -->
            <label class="block text-[11px] font-medium text-gray-500 mb-1">Docentes a considerar</label>
            <div class="grid grid-cols-2 gap-1 p-0.5 mb-2 bg-gray-100 rounded-lg" role="radiogroup">
              <button
                v-for="a in alcances"
                :key="a.valor"
                type="button"
                role="radio"
                :aria-checked="filtros.alcance === a.valor"
                @click="filtros.alcance = a.valor"
                class="px-2 py-1.5 text-xs font-medium rounded-md transition-colors"
                :class="filtros.alcance === a.valor ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
              >
                {{ a.titulo }}
              </button>
            </div>
            <p class="text-xs text-gray-500 leading-snug mb-3">
              {{ alcances.find(a => a.valor === filtros.alcance)?.desc }}
            </p>

            <div class="space-y-1.5">
              <button
                v-for="t in interruptores"
                :key="t.id"
                type="button"
                role="switch"
                :aria-checked="t.activo"
                @click="t.accion()"
                class="w-full flex items-start gap-3 px-3 py-2 rounded-lg border text-left transition-colors"
                :class="t.activo ? 'bg-blue-50/60 border-blue-200' : 'bg-white border-gray-200 hover:bg-gray-50'"
              >
                <span class="mt-0.5 relative inline-flex h-5 w-9 flex-shrink-0 rounded-full transition-colors"
                  :class="t.activo ? 'bg-blue-600' : 'bg-gray-300'">
                  <span class="absolute top-0.5 left-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform"
                    :class="t.activo ? 'translate-x-4' : ''"></span>
                </span>
                <span class="min-w-0">
                  <span class="block text-sm font-medium text-gray-800">{{ t.titulo }}</span>
                  <span class="block text-xs text-gray-500 leading-snug">{{ t.desc }}</span>
                </span>
              </button>
            </div>
          </section>

          <!-- 3 · Materias fuera del listado -->
          <section class="bg-white rounded-xl border border-gray-200 p-4 flex flex-col min-h-0">
            <header class="flex items-center gap-2 mb-3">
              <span class="w-5 h-5 rounded-full bg-blue-600 text-white text-[11px] font-bold flex items-center justify-center">3</span>
              <h4 class="text-sm font-semibold text-gray-800">Materias fuera del listado</h4>
            </header>

            <p v-if="!filtros.soloListado" class="text-xs text-gray-500 leading-snug">
              Estás viendo todas las materias que se dictan en el periodo. Activa “Solo materias del listado” para ocultar las que no están en el PDF histórico.
            </p>
            <template v-else>
              <p class="text-xs text-gray-500 leading-snug mb-2">
                Se dictan en {{ etiquetaPeriodo || 'el periodo' }} pero no están en el listado, por eso no aparecen en la tabla.
                Si falta alguna, agrégala a <span class="font-mono text-[11px] text-gray-600">MATERIAS_PERMITIDAS</span>.
              </p>
              <ul v-if="materiasFueraDelListado.length"
                class="flex-1 max-h-40 overflow-y-auto rounded-lg border border-gray-100 divide-y divide-gray-50 text-xs text-gray-700">
                <li v-for="m in materiasFueraDelListado" :key="m" class="px-2.5 py-1.5">{{ m }}</li>
              </ul>
              <p v-else class="text-xs text-emerald-600">✓ Todas las materias del periodo están en el listado.</p>
            </template>
          </section>
        </div>
      </div>

      <!-- ── Franja de resumen ── -->
      <div class="flex flex-wrap items-center gap-2 px-6 py-2.5 border-b border-gray-100 text-xs">
        <template v-if="!loading && !error">
          <span class="px-2.5 py-1 rounded-full bg-slate-900 text-white font-semibold">
            {{ totalMaterias }} materias
          </span>
          <span class="px-2.5 py-1 rounded-full bg-gray-100 text-gray-600">
            {{ totalFilas }} docentes en total
          </span>
          <span class="px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 font-medium">
            {{ filasConDocumento }} con documento
          </span>
          <span class="px-2.5 py-1 rounded-full bg-orange-50 text-orange-700 font-medium">
            {{ filasSinDocumento }} sin documento
          </span>
          <span v-if="filtros.soloListado && materiasFueraDelListado.length"
            class="px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 font-medium">
            {{ materiasFueraDelListado.length }} fuera del listado
          </span>
          <span v-if="etiquetaPeriodo" class="text-gray-400 ml-auto">
            Prelación vigente desde el semestre {{ etiquetaPeriodo }}
          </span>
        </template>
      </div>

      <!-- ── Tabla ── -->
      <div class="relative flex-1 overflow-auto px-6 pb-3">

        <!-- Velo de carga: mantiene la tabla anterior atenuada -->
        <div v-if="estaActualizando && hayDatos"
          class="absolute inset-0 z-20 bg-white/60 flex items-start justify-center pt-24">
          <div class="flex items-center gap-2 px-4 py-2 bg-white rounded-full shadow-lg border border-gray-100 text-sm text-gray-600">
            <svg class="w-4 h-4 animate-spin text-blue-500" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            Actualizando vista previa…
          </div>
        </div>

        <div v-if="(loading || iniciando) && !hayDatos" class="text-center py-16 text-sm text-gray-400">
          <svg class="w-6 h-6 mx-auto animate-spin text-blue-500 mb-2" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
          Cargando vista previa…
        </div>

        <div v-else-if="error" class="text-center py-16 text-sm text-red-500">
          {{ error }}
          <div class="mt-2">
            <button @click="programarConsulta(true)" class="text-blue-500 hover:underline">Reintentar</button>
          </div>
        </div>

        <table v-else-if="hayDatos" class="w-full text-xs border-collapse">
          <thead class="sticky top-0 z-10 bg-white shadow-[0_1px_0_0_#e5e7eb]">
            <tr>
              <th class="text-center font-semibold text-gray-500 px-2 py-2.5 uppercase tracking-wider w-10">Nº</th>
              <th class="text-left font-semibold text-gray-500 px-2 py-2.5 uppercase tracking-wider">Materia</th>
              <th class="text-center font-semibold text-gray-500 px-2 py-2.5 uppercase tracking-wider">Carrera</th>
              <th class="text-center font-semibold text-gray-500 px-2 py-2.5 uppercase tracking-wider w-12" title="Posición dentro de la materia">Orden</th>
              <th class="text-left font-semibold text-gray-500 px-2 py-2.5 uppercase tracking-wider">Docente</th>
              <th class="text-left font-semibold text-gray-500 px-2 py-2.5 uppercase tracking-wider">Categoría</th>
              <th class="text-left font-semibold text-gray-500 px-2 py-2.5 uppercase tracking-wider">Observaciones</th>
              <th class="text-center font-semibold text-gray-500 px-2 py-2.5 uppercase tracking-wider">PDF</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(item, i) in data"
              :key="i"
              class="border-b border-gray-50"
              :class="[
                item.FIN_GRUPO ? 'border-b-2 border-b-gray-300' : '',
                item.SIN_DOCUMENTO ? 'bg-orange-50/60' : ''
              ]"
            >
              <td v-if="item.INICIO_GRUPO" :rowspan="item.FILAS_GRUPO"
                class="px-2 py-2 text-center align-middle border-r border-gray-100 font-medium text-gray-700">
                {{ item.N }}
              </td>
              <td v-if="item.INICIO_GRUPO" :rowspan="item.FILAS_GRUPO"
                class="px-2 py-2 align-middle border-r border-gray-100 font-semibold text-gray-800">
                {{ item.MATERIA }}
              </td>
              <td v-if="item.INICIO_GRUPO" :rowspan="item.FILAS_GRUPO"
                class="px-2 py-2 text-center align-middle border-r border-gray-100 text-gray-600 whitespace-nowrap">
                {{ item.CARRERA || '—' }}
              </td>

              <td class="px-2 py-2 text-center tabular-nums text-gray-500">{{ item.ORDEN }}</td>

              <td class="px-2 py-2" :class="item.SIN_DOCUMENTO ? 'text-gray-500' : 'text-gray-800'">
                <div class="font-medium">{{ item.DOCENTE }}</div>
                <div v-if="!item.DOCENTE_DICTA_ESTA_MATERIA || item.OTROS_DOCUMENTOS" class="flex flex-wrap gap-1 mt-0.5">
                  <span v-if="!item.DOCENTE_DICTA_ESTA_MATERIA"
                    class="px-1.5 py-0.5 text-[10px] font-medium rounded bg-gray-100 text-gray-500"
                    title="Tiene documento para esta materia pero hoy no la dicta">
                    No la dicta este periodo
                  </span>
                  <span v-if="item.OTROS_DOCUMENTOS"
                    class="px-1.5 py-0.5 text-[10px] font-medium rounded bg-indigo-50 text-indigo-600"
                    title="Se muestra solo su mejor documento para esta materia">
                    +{{ item.OTROS_DOCUMENTOS }} documento(s)
                  </span>
                </div>
              </td>

              <td class="px-2 py-2 text-gray-600">{{ item.CATEGORIA || '—' }}</td>
              <td class="px-2 py-2 text-gray-600 max-w-md" :class="item.SIN_DOCUMENTO ? 'text-gray-500' : ''">
                {{ item.OBSERVACIONES || '—' }}
              </td>

              <td class="px-2 py-2 text-center">
                <button
                  v-if="item.ID_DOCUMENTO && item.NOMBRE_ARCHIVO"
                  @click="verPdf(item.ID_DOCUMENTO)"
                  :disabled="abriendoPdfId === item.ID_DOCUMENTO"
                  class="inline-flex items-center gap-1 px-2 py-1 bg-blue-800 hover:bg-blue-900 text-white text-[11px] font-semibold rounded-md shadow-sm transition-colors disabled:opacity-60"
                  title="Ver el PDF del documento"
                >
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h3m5-13v4a1 1 0 001 1h4m-5-5H8a2 2 0 00-2 2v14a2 2 0 002 2h8a2 2 0 002-2V8l-5-5z"/>
                  </svg>
                  {{ abriendoPdfId === item.ID_DOCUMENTO ? '...' : 'PDF' }}
                </button>
                <span v-else class="text-gray-300">—</span>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-else class="text-center py-16 text-sm text-gray-400">
          No hay materias con los filtros actuales. Prueba quitando alguna carrera, borrando el texto de la materia o desactivando “Solo materias del listado”.
        </div>
      </div>

      <!-- ── Pie ── -->
      <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-3.5 border-t border-gray-100">
        <p class="text-xs text-gray-400">
          El Excel se genera con los mismos filtros que ves en pantalla.
        </p>
        <div class="flex items-center gap-3">
          <button
            @click="volver"
            class="px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
          >
            Volver
          </button>
          <button
            @click="descargar"
            :disabled="!hayDatos || estaActualizando || descargando"
            class="inline-flex items-center gap-2 px-5 py-2 bg-green-600 hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-semibold rounded-lg transition-colors"
          >
            <svg v-if="descargando" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H6a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            {{ descargando ? 'Generando…' : 'Descargar Excel' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import {
  useReportePrelacionMateria,
  CARRERAS,
  PERIODOS,
} from '../composables/useReportePrelacionMateria'
import { useClasificacion } from '../composables/useClasificacion'

const router = useRouter()
const clasificacion = useClasificacion()

const {
  filtros,
  loading,
  descargando,
  error,
  data,
  totalMaterias,
  totalFilas,
  filasSinDocumento,
  filasConDocumento,
  materiasFueraDelListado,
  etiquetaPeriodo,
  hayDatos,
  previsualizar,
  descargarExcel,
  alternarCarrera,
  restablecerFiltros,
  reset,
} = useReportePrelacionMateria()

// ═══════════════════════════════════════════════════════════════
// ESTADO DE LA VISTA
// ═══════════════════════════════════════════════════════════════
const panelOpciones = ref(true)
const iniciando = ref(true)   // true hasta que termina la primera consulta
const pendiente = ref(false)  // true mientras espera el debounce

const estaActualizando = computed(() => loading.value || pendiente.value)

const anioValido = computed(() => {
  const a = filtros.value.anio
  return Number.isInteger(a) && a >= 1990 && a <= 2100
})

const alcances = [
  {
    valor: 'docente',
    titulo: 'Activos en el periodo',
    desc: 'Entra todo docente que dicta alguna materia en el periodo y tiene documento para la materia, aunque hoy no la dicte.',
  },
  {
    valor: 'materia',
    titulo: 'Solo esta materia',
    desc: 'Entra únicamente quien dicta esa materia en el periodo.',
  },
]

const interruptores = computed(() => [
  {
    id: 'sinDoc',
    titulo: 'Incluir docentes sin documento',
    desc: 'Agrega a quienes dictan la materia pero no tienen documento válido, al final y marcados “SIN PRELACIÓN”.',
    activo: filtros.value.incluirSinDocumento,
    accion: () => { filtros.value.incluirSinDocumento = !filtros.value.incluirSinDocumento },
  },
  {
    id: 'refs',
    titulo: 'Mostrar referencias',
    desc: 'Agrega el número de referencia del documento dentro de Observaciones.',
    activo: filtros.value.mostrarReferencias,
    accion: () => { filtros.value.mostrarReferencias = !filtros.value.mostrarReferencias },
  },
  {
    id: 'listado',
    titulo: 'Solo materias del listado',
    desc: 'Limita el reporte a las materias del PDF histórico. Apagado: entran todas las que se dictan.',
    activo: filtros.value.soloListado,
    accion: () => { filtros.value.soloListado = !filtros.value.soloListado },
  },
])

// ═══════════════════════════════════════════════════════════════
// CONSULTA AUTOMÁTICA (con espera para no consultar en cada tecla)
// ═══════════════════════════════════════════════════════════════
let timer = null

function programarConsulta(inmediato = false) {
  clearTimeout(timer)
  if (!anioValido.value) {
    pendiente.value = false
    return
  }
  pendiente.value = true
  timer = setTimeout(async () => {
    try {
      await previsualizar()
    } finally {
      pendiente.value = false
      iniciando.value = false
    }
  }, inmediato ? 0 : 400)
}

watch(filtros, () => programarConsulta(), { deep: true })

// ═══════════════════════════════════════════════════════════════
// ACCIONES
// ═══════════════════════════════════════════════════════════════
const abriendoPdfId = ref(null)

async function verPdf(idDocumento) {
  if (abriendoPdfId.value) return
  abriendoPdfId.value = idDocumento
  try {
    await clasificacion.verPdf(idDocumento, 'inline')
  } catch (e) {
    alert(e?.response?.status === 401
      ? 'Tu sesión expiró. Vuelve a iniciar sesión.'
      : 'No se pudo abrir el PDF'
    )
  } finally {
    abriendoPdfId.value = null
  }
}

async function descargar() {
  try {
    await descargarExcel()
  } catch (e) {
    alert(e?.message || 'No se pudo descargar el Excel')
  }
}

function volver() {
  clearTimeout(timer)
  if (window.history.length > 1) router.back()
  else router.push('/')
}

onMounted(() => programarConsulta(true))

onBeforeUnmount(() => {
  clearTimeout(timer)
  reset() // cancela la consulta en vuelo y limpia el estado
})
</script>