<template>
  <div class="min-h-screen bg-slate-100 pb-12">
    <!-- HEADER -->
    <div class="flex items-start justify-between mb-3">
      <h1 class="text-xl font-bold text-black-400 tracking-tight m-0 mb-0.5">
        Reportes de horarios completo
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
          v-model.number="anio" type="number" min="2020" max="2030"
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
            v-model="busqueda" type="text" placeholder="Código o apellidos..."
            @keyup.enter="buscarDocente"
            class="flex-1 bg-slate-800 border border-slate-700 rounded-lg text-slate-100 text-sm px-3 py-2 outline-none
                   placeholder-slate-500 transition-all duration-150
                   focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20"
          />
          <button
            @click="buscarDocente" :disabled="loading"
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
            @click="verTodos" :disabled="loading"
            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium
                   border border-slate-700 text-slate-400 bg-transparent
                   hover:bg-white/5 hover:text-slate-200
                   transition-all duration-150 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Ver todos
          </button>
        </div>
      </div>

      <!-- Botón PDF con menú desplegable -->
      <div class="flex flex-col">
        <label class="text-[0.68rem] invisible">PDF</label>
        <div class="relative">
          <div class="inline-flex rounded-lg overflow-hidden border border-red-700/40 shadow-lg shadow-red-900/20">
            <button
              @click="generarPDFCompleto"
              :disabled="loading || docentes.length === 0"
              class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold
                     bg-red-700 hover:bg-red-600 active:bg-red-800 text-white
                     transition-all duration-150 border-none
                     disabled:opacity-40 disabled:cursor-not-allowed"
            >
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
              </svg>
              Generar PDF
            </button>
            <button
              @click.stop="mostrarMenuPdf = !mostrarMenuPdf"
              :disabled="loading || docentes.length === 0"
              class="inline-flex items-center px-2.5 py-2
                     bg-red-800 hover:bg-red-700 text-white
                     border-l border-red-900/50
                     transition-all duration-150
                     border-t-0 border-b-0 border-r-0
                     disabled:opacity-40 disabled:cursor-not-allowed"
              aria-label="Opciones de PDF"
            >
              <svg
                width="13" height="13" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2.5"
                :class="['transition-transform duration-200', mostrarMenuPdf ? 'rotate-180' : '']"
              >
                <polyline points="6 9 12 15 18 9"/>
              </svg>
            </button>
          </div>

          <!-- Backdrop -->
          <div
            v-if="mostrarMenuPdf"
            class="fixed inset-0 z-40"
            @click="mostrarMenuPdf = false"
          />

          <!-- Menú desplegable -->
          <Transition
            enter-active-class="transition ease-out duration-150"
            enter-from-class="opacity-0 translate-y-1 scale-95"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 translate-y-1 scale-95"
          >
            <div
              v-if="mostrarMenuPdf"
              class="absolute right-0 mt-1.5 w-56 rounded-xl
                     bg-slate-800 border border-slate-700
                     shadow-2xl shadow-black/40 z-50 overflow-hidden"
            >
              <button
                @click="generarPDFCompleto"
                class="w-full flex items-center gap-2.5 px-3.5 py-2.5
                       border-b border-slate-700
                       text-xs font-medium text-slate-300
                       hover:bg-white/[0.06] hover:text-slate-100
                       transition-colors bg-transparent text-left
                       border-t-0 border-r-0 border-l-0"
              >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-slate-500 shrink-0">
                  <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                  <polyline points="14 2 14 8 20 8"/>
                </svg>
                <span class="flex flex-col">
                  <span class="font-semibold">PDF Formato clásico</span>
                  <span class="text-[11px] text-slate-500">Detalle de horarios por docente</span>
                </span>
              </button>
              <button
                @click="generarPDFResumen"
                class="w-full flex items-center gap-2.5 px-3.5 py-2.5
                       text-xs font-medium text-slate-300
                       hover:bg-white/[0.06] hover:text-slate-100
                       transition-colors bg-transparent text-left border-none"
              >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-slate-500 shrink-0">
                  <line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/>
                  <line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/>
                  <line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>
                </svg>
                <span class="flex flex-col">
                  <span class="font-semibold">PDF Formato nuevo</span>
                  <span class="text-[11px] text-slate-500">Reporte según vista actual</span>
                </span>
              </button>
            </div>
          </Transition>
        </div>
      </div>
    </div>

    <!-- ERROR -->
    <div v-if="error" class="mx-8 mt-4 bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg">
      ⚠️ {{ error }}
    </div>

    <!-- LOADING -->
    <div v-if="loading" class="flex flex-col items-center gap-3 py-20 text-slate-500">
      <div class="w-10 h-10 border-4 border-slate-200 border-t-blue-600 rounded-full animate-spin" />
      <span>Cargando horarios...</span>
    </div>

    <!-- EMPTY -->
    <div v-else-if="!loading && docentes.length === 0 && !error" class="flex flex-col items-center gap-3 py-24 text-slate-400">
      <span class="text-6xl">📋</span>
      <p>Busca un docente o carga todos para ver los horarios.</p>
    </div>

    <!-- CONTENIDO -->
    <div v-else id="reporte-imprimible" class="px-8 py-4">
      <div class="bg-white border border-slate-200 rounded-lg px-5 py-2.5 flex justify-between items-center mb-4 text-sm text-slate-500">
        <span>Generado: <strong class="text-slate-700">{{ fechaActual }}</strong></span>
        <span class="text-slate-300 mx-2">|</span>
        <span>Total docentes: <strong class="text-slate-700">{{ docentes.length }}</strong></span>
      </div>

      <HorarioDocenteCard v-for="doc in docentes" :key="doc.docente" :docente="doc" />

      <div class="text-center text-slate-400 text-xs mt-8 pt-4 border-t border-slate-200">
        Procesado UTI – Facultad de Ciencias Económicas · La carga horaria incluye Grupos Compartidos.
      </div>
    </div>

    <!-- MODALES -->
    <Teleport to="body">

      <!-- Generando PDF -->
      <div
        v-if="generandoPdf"
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50"
      >
        <div class="bg-white rounded-2xl p-8 shadow-2xl flex flex-col items-center gap-4 min-w-[320px]">
          <div class="w-16 h-16 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
          <h3 class="text-lg font-bold text-slate-800">Generando PDF...</h3>
          <p class="text-sm text-slate-500 text-center">Procesando horarios de docentes</p>
        </div>
      </div>

      <!-- PDF Generado -->
      <div
        v-if="pdfGenerado"
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50"
      >
        <div class="bg-white rounded-2xl p-8 shadow-2xl flex flex-col items-center gap-4 min-w-[320px]">
          <div class="w-20 h-20 rounded-full bg-green-100 flex items-center justify-center text-5xl animate-bounce">✅</div>
          <h3 class="text-lg font-bold text-green-700">PDF Generado</h3>
          <p class="text-sm text-slate-500 text-center">El documento fue creado correctamente</p>
        </div>
      </div>

      <!-- Vista previa PDF -->
      <div
        v-if="pdfPreviewUrl"
        class="fixed inset-0 z-50 flex flex-col bg-black/70 backdrop-blur-sm"
        @keydown.esc="cerrarPreview"
        tabindex="-1"
      >
        <div class="flex items-center justify-between bg-slate-900 px-6 py-3 shrink-0">
          <div class="flex items-center gap-3">
            <span class="text-white font-bold text-sm">📄 Vista previa — {{ pdfFilename }}</span>
            <span class="text-slate-400 text-xs">Gestión {{ anio }}/{{ periodo }} · {{ docentes.length }} docentes</span>
          </div>
          <div class="flex items-center gap-2">
            <a
              :href="pdfPreviewUrl"
              :download="pdfFilename"
              target="_blank"
              class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-1.5 rounded-lg font-semibold text-sm flex items-center gap-1.5"
            >
              <span>⬇️</span> Descargar
            </a>
            <button
              @click="cerrarPreview"
              class="bg-slate-700 hover:bg-slate-600 text-white px-4 py-1.5 rounded-lg font-semibold text-sm"
            >
              ✕ Cerrar
            </button>
          </div>
        </div>
        <iframe :src="pdfPreviewUrl" class="flex-1 w-full border-0" type="application/pdf" />
      </div>

    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onUnmounted } from 'vue'
import HorarioDocenteCard from '../components/HorarioDocenteCard.vue'
import { useHorarioAdmin } from '../composables/useHorarioAdmin'
import { generarPDFCargaHoraria } from '../composables/useGenerarPDFCargaHoraria'
import { generarPDFResumenDos } from '../composables/usePdfResumenDos'
import { usePeriodoActual } from '../composables/usePeriodoActual'

const { anio, periodo } = usePeriodoActual()

const generandoPdf = ref(false)
const pdfGenerado = ref(false)
const mostrarMenuPdf = ref(false)

const {
  docentes, loading, error,
  cargarTodos, cargarDocente, colorCarrera,
} = useHorarioAdmin()

const busqueda = ref('')

// ── Estado del modal ──────────────────────────────────────────────────────────
const pdfPreviewUrl = ref(null)
const pdfFilename = ref('')

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

async function verTodos() {
  await cargarTodos(anio.value, periodo.value)
}

// ── Generación de PDF ──────────────────────────────────────────────────────────
// Helper común: muestra los estados de "generando" / "generado" y abre la
// vista previa, sin importar qué generador de PDF se use.
async function ejecutarGeneracionPDF(generador) {
  generandoPdf.value = true
  pdfGenerado.value = false

  try {
    // Simular espera visual mínima
    await new Promise(resolve => setTimeout(resolve, 1000))

    if (pdfPreviewUrl.value) {
      URL.revokeObjectURL(pdfPreviewUrl.value)
      pdfPreviewUrl.value = null
    }

    const { url, filename } = generador()

    pdfPreviewUrl.value = url
    pdfFilename.value = filename

    generandoPdf.value = false
    pdfGenerado.value = true

    setTimeout(() => {
      pdfGenerado.value = false
    }, 3000)

  } catch (error) {
    generandoPdf.value = false
    console.error(error)
  }
}

// Opción 1: el PDF original (igual que antes)
async function generarPDFCompleto() {
  mostrarMenuPdf.value = false
  await ejecutarGeneracionPDF(() =>
    generarPDFCargaHoraria(docentes.value, {
      anio: anio.value,
      periodo: periodo.value,
    })
  )
}

// Opción 2: el nuevo PDF resumen (usePdfResumenDos)
async function generarPDFResumen() {
  mostrarMenuPdf.value = false
  await ejecutarGeneracionPDF(() =>
    generarPDFResumenDos(docentes.value, {
      anio: anio.value,
      periodo: periodo.value,
    })
  )
}

function cerrarPreview() {
  URL.revokeObjectURL(pdfPreviewUrl.value)
  pdfPreviewUrl.value = null
  pdfFilename.value = ''
}

// Limpiar al desmontar el componente
onUnmounted(() => {
  if (pdfPreviewUrl.value) URL.revokeObjectURL(pdfPreviewUrl.value)
})
</script>