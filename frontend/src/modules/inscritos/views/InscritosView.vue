<template>
  <div class="min-h-screen bg-slate-100">

    <!-- HEADER -->
    <div class="flex items-start justify-between mb-3">
      <h1 class="text-xl font-bold text-black tracking-tight m-0 mb-0.5">
        Lista de Inscritos
      </h1>
      <span class="text-xs text-black/70">
        Reporte completo · Gestión {{ filtros.anio }}/{{ filtros.periodo }}
      </span>
    </div>

    <!-- FILTROS -->
    <div class="border-b border-slate-700 px-8 flex flex-wrap gap-3 items-end">

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
          <option :value="1">1</option>
          <option :value="2">2</option>
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

      <!-- Botón PDF con menú desplegable -->
      <div class="flex flex-col" ref="pdfDropdownRef">
        <label class="text-[0.68rem] invisible">PDF</label>

        <div class="relative">

          <!-- Botón partido: ambos abren el menú -->
          <div
            class="inline-flex rounded-lg overflow-visible border border-red-700/40 shadow-lg shadow-red-900/20"
            :class="(loadingPdf || data.length === 0) ? 'opacity-40 pointer-events-none' : ''"
          >
            <!-- Botón principal -->
            <button
              @click.stop="mostrarMenuPdf = !mostrarMenuPdf"
              class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold
                     bg-red-700 hover:bg-red-600 active:bg-red-800 text-white
                     transition-all duration-150"
            >
              <svg
                :class="loadingPdf ? 'animate-spin' : ''"
                width="15"
                height="15"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
              >
                <template v-if="loadingPdf">
                  <circle cx="12" cy="12" r="9" />
                  <path d="M12 3a9 9 0 0 1 9 9" stroke-linecap="round" />
                </template>
                <template v-else>
                  <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                  <polyline points="14 2 14 8 20 8" />
                </template>
              </svg>

              Generar PDF
            </button>

            <!-- Flecha -->
            <button
              @click.stop="mostrarMenuPdf = !mostrarMenuPdf"
              class="px-2.5 py-2 bg-red-700 hover:bg-red-600 active:bg-red-800 text-white
                     border-l border-red-600/60 transition-all duration-150"
              aria-label="Más opciones de PDF"
            >
              <svg
                width="14"
                height="14"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2.5"
                :style="mostrarMenuPdf ? 'transform: rotate(180deg);' : ''"
                style="transition: transform 0.15s"
              >
                <polyline points="6 9 12 15 18 9" />
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
            enter-active-class="transition-all duration-150 ease-out"
            enter-from-class="opacity-0 scale-95 -translate-y-1"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition-all duration-100 ease-in"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 -translate-y-1"
          >
            <div
              v-if="mostrarMenuPdf"
              class="absolute right-0 top-full mt-1.5 z-50
                     bg-white border border-slate-200 rounded-xl
                     shadow-xl overflow-hidden w-64"
            >
              <!-- Lista completa -->
              <div class="px-4 pt-3 pb-1">
                <p class="text-[0.65rem] font-semibold tracking-widest uppercase text-slate-400">
                  Lista completa de estudiantes
                </p>
              </div>

              <!-- Ver PDF Lista -->
              <button
                @click="generarPDFLista('ver'); mostrarMenuPdf = false"
                class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700
                       hover:bg-slate-50 transition-colors text-left"
              >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" class="text-slate-400 shrink-0">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                  <circle cx="12" cy="12" r="3" />
                </svg>
                <div>
                  <div class="font-medium leading-tight">Ver PDF Lista</div>
                  <div class="text-xs text-slate-400 mt-0.5">Abrir en nueva pestaña</div>
                </div>
              </button>

              <!-- Descargar PDF Lista -->
              <button
                @click="generarPDFLista('descargar'); mostrarMenuPdf = false"
                class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700
                       hover:bg-slate-50 transition-colors text-left"
              >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" class="text-slate-400 shrink-0">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                  <polyline points="7 10 12 15 17 10" />
                  <line x1="12" y1="15" x2="12" y2="3" />
                </svg>
                <div>
                  <div class="font-medium leading-tight">Descargar PDF Lista</div>
                  <div class="text-xs text-slate-400 mt-0.5">Guardar en tu equipo</div>
                </div>
              </button>

              <div class="border-t border-slate-100 mx-4"></div>

              <!-- Resumen de totales -->
              <div class="px-4 pt-3 pb-1">
                <p class="text-[0.65rem] font-semibold tracking-widest uppercase text-slate-400">
                  Solo totales por docente
                </p>
              </div>

              <!-- Ver PDF Totales -->
              <button
                @click="generarPDFTotales('ver'); mostrarMenuPdf = false"
                class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700
                       hover:bg-slate-50 transition-colors text-left"
              >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" class="text-slate-400 shrink-0">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                  <circle cx="12" cy="12" r="3" />
                </svg>
                <div>
                  <div class="font-medium leading-tight">Ver PDF Totales</div>
                  <div class="text-xs text-slate-400 mt-0.5">Abrir en nueva pestaña</div>
                </div>
              </button>

              <!-- Descargar PDF Totales -->
              <button
                @click="generarPDFTotales('descargar'); mostrarMenuPdf = false"
                class="w-full flex items-center gap-3 px-4 py-2.5 pb-3 text-sm text-slate-700
                       hover:bg-slate-50 transition-colors text-left"
              >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" class="text-slate-400 shrink-0">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                  <polyline points="7 10 12 15 17 10" />
                  <line x1="12" y1="15" x2="12" y2="3" />
                </svg>
                <div>
                  <div class="font-medium leading-tight">Descargar PDF Totales</div>
                  <div class="text-xs text-slate-400 mt-0.5">Guardar en tu equipo</div>
                </div>
              </button>
            </div>
          </Transition>

        </div>
      </div>

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
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import DocenteInscritosCard from '../components/DocenteInscritosCard.vue'
import { useInscritos } from '../composables/useInscritos'
import { useReporteInscritosLista } from '../composables/useReporteInscritosLista'
import { useReporteInscritosTotales } from '../composables/useReporteInscritosTotales'

// ─── Sin onMounted: no carga automática al entrar ───────────────────────────
const { data, loading, error, fetchInscritos } = useInscritos()
const { generandoLista, exportarListaCompleta } = useReporteInscritosLista()
const { generandoResumen, exportarResumenTotales } = useReporteInscritosTotales()

const filtros = ref({
  anio: new Date().getFullYear(),
  periodo: 1,
})
const busqueda = ref('')

const mostrarMenuPdf = ref(false)
const pdfDropdownRef = ref(null)

// El botón muestra "cargando" si cualquiera de los dos PDF se está generando
const loadingPdf = computed(() => generandoLista.value || generandoResumen.value)

const totalGlobal = computed(() =>
  data.value.reduce((s, d) => s + (d.total_inscritos ?? 0), 0)
)

const fechaActual = computed(() =>
  new Date().toLocaleString('es-BO', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
)

// Cierra el menú al hacer click fuera
function onClickFuera(e) {
  if (pdfDropdownRef.value && !pdfDropdownRef.value.contains(e.target)) {
    mostrarMenuPdf.value = false
  }
}
onMounted(() => document.addEventListener('click', onClickFuera))
onBeforeUnmount(() => document.removeEventListener('click', onClickFuera))

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
    await fetchInscritos(filtros.value.anio, filtros.value.periodo)
    data.value = data.value.filter(d => d.cod_docente === q)
  } else {
    await fetchInscritos(filtros.value.anio, filtros.value.periodo)
    data.value = data.value.filter(d =>
      `${d.apellidos} ${d.nombres}`.toLowerCase().includes(q.toLowerCase())
    )
  }
}

async function generarPDFLista(modo = 'descargar') {
  mostrarMenuPdf.value = false
  let ventana = null
  if (modo === 'ver') {
    ventana = window.open('', '_blank')
  }
  await exportarListaCompleta(data.value, filtros.value.anio, filtros.value.periodo, modo, ventana)
}

async function generarPDFTotales(modo = 'descargar') {
  mostrarMenuPdf.value = false
  let ventana = null
  if (modo === 'ver') {
    ventana = window.open('', '_blank')
  }
  await exportarResumenTotales(data.value, filtros.value.anio, filtros.value.periodo, modo, ventana)
}
</script>