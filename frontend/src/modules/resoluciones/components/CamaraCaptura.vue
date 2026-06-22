<template>
  <div class="w-full font-sans">

    <!-- ══════════════════════════════════════════════════════
         PASO 1 · PERMISO
    ══════════════════════════════════════════════════════ -->
    <div v-if="paso === 'permiso'"
      class="flex flex-col items-center gap-4 px-6 py-8 bg-white border border-gray-200 rounded-2xl text-center">

      <div class="w-18 h-18 flex items-center justify-center bg-blue-50 rounded-full p-4 text-blue-600">
        <svg class="w-10 h-10" viewBox="0 0 48 48" fill="none">
          <circle cx="24" cy="24" r="23" stroke="currentColor" stroke-width="1.5" stroke-dasharray="4 3"/>
          <path d="M14 20a3 3 0 013-3h.93a3 3 0 002.497-1.336l.812-1.218A3 3 0 0123.736 13h.528a3 3 0 012.497 1.336l.812 1.218A3 3 0 0030.07 17H31a3 3 0 013 3v11a3 3 0 01-3 3H17a3 3 0 01-3-3V20z" stroke="currentColor" stroke-width="1.5"/>
          <circle cx="24" cy="26" r="4" stroke="currentColor" stroke-width="1.5"/>
          <circle cx="30.5" cy="21.5" r="1" fill="currentColor"/>
        </svg>
      </div>

      <h3 class="text-lg font-bold text-gray-900 m-0">Escanear documento</h3>
      <p class="text-sm text-gray-500 max-w-xs leading-relaxed m-0">
        Para digitalizar la resolución de manera óptima necesitamos acceso a tu cámara.
        Las fotos <strong class="text-gray-700">no se almacenan</strong> en ningún servidor externo.
      </p>

      <ul class="list-none p-0 m-0 flex flex-col gap-1.5 text-left">
        <li v-for="item in ['Detección automática de bordes', 'Mejora de contraste y nitidez', 'PDF comprimido listo para guardar']"
          :key="item" class="flex items-center gap-2 text-xs text-gray-600">
          <span class="text-green-600 font-bold text-sm">✓</span>
          {{ item }}
        </li>
      </ul>

      <button @click="solicitarPermiso"
        class="inline-flex items-center justify-center gap-2 w-full max-w-xs px-7 py-3 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl cursor-pointer border-0 shadow-sm transition-colors duration-150">
        <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
          <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
          <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
        </svg>
        Permitir cámara y comenzar
      </button>

      <div v-if="errorPermiso"
        class="flex items-start gap-2.5 w-full bg-red-50 border border-red-200 rounded-xl p-3 text-red-600 text-left">
        <svg class="w-4 h-4 flex-shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
        </svg>
        <div>
          <strong class="text-xs font-semibold block">No se pudo acceder a la cámara</strong>
          <p class="text-xs mt-0.5 leading-snug m-0">{{ errorPermiso }}</p>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         PASO 2 · CÁMARA ACTIVA
    ══════════════════════════════════════════════════════ -->
    <div v-else-if="paso === 'camara'" class="bg-white border border-gray-200 rounded-2xl overflow-hidden">

      <!-- Header -->
      <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
        <div class="flex flex-col gap-0.5">
          <div class="inline-flex items-center gap-1.5 bg-blue-50 text-blue-700 text-xs font-semibold px-2.5 py-1 rounded-md w-fit">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <rect x="3" y="3" width="18" height="18" rx="2"/>
              <path d="M8 12h8M8 8h8M8 16h5"/>
            </svg>
            Hoja {{ indiceActivo + 1 }} / {{ TOTAL_HOJAS }}
          </div>
          <span class="text-xs text-gray-400">{{ instruccionActiva }}</span>
        </div>
        <button @click="cerrarCamara"
          class="w-7 h-7 flex items-center justify-center rounded-full bg-gray-100 hover:bg-gray-200 text-gray-500 hover:text-gray-700 border-0 cursor-pointer transition-colors duration-150">
          <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
          </svg>
        </button>
      </div>

      <!-- Visor -->
      <div class="relative bg-black" style="aspect-ratio:4/3">
        <video ref="videoEl" autoplay playsinline muted class="w-full h-full object-cover block" />

        <!-- Encuadre overlay -->
        <div v-if="!procesando" class="absolute inset-0 pointer-events-none">
          <!-- Marco con esquinas -->
          <div class="absolute inset-5 transition-colors duration-300"
            :class="encuadreBueno ? 'border border-dashed border-blue-400/50' : 'border border-dashed border-white/25'">
            <!-- Esquinas TL -->
            <span class="absolute -top-px -left-px w-6 h-6 border-t-2 border-l-2 border-blue-400 rounded-tl" />
            <span class="absolute -top-px -right-px w-6 h-6 border-t-2 border-r-2 border-blue-400 rounded-tr" />
            <span class="absolute -bottom-px -left-px w-6 h-6 border-b-2 border-l-2 border-blue-400 rounded-bl" />
            <span class="absolute -bottom-px -right-px w-6 h-6 border-b-2 border-r-2 border-blue-400 rounded-br" />
            <!-- Grid de tercios -->
            <div class="absolute left-1/3 top-0 bottom-0 w-px bg-white/7" />
            <div class="absolute left-2/3 top-0 bottom-0 w-px bg-white/7" />
            <div class="absolute top-1/3 left-0 right-0 h-px bg-white/7" />
            <div class="absolute top-2/3 left-0 right-0 h-px bg-white/7" />
          </div>

          <!-- Tip superior -->
          <div class="absolute top-2.5 left-1/2 -translate-x-1/2 flex items-center gap-1 bg-black/50 text-white/80 text-xs px-3 py-1 rounded-full whitespace-nowrap">
            <svg class="w-3 h-3 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            Alinea la hoja con las esquinas azules
          </div>

          <!-- Indicador de luz -->
          <div class="absolute bottom-14 right-3 flex items-center gap-1.5 bg-black/55 text-white text-xs px-2.5 py-1 rounded-full backdrop-blur-sm">
            <span class="w-2 h-2 rounded-full flex-shrink-0"
              :class="nivelLuz >= 110 ? 'bg-green-400' : nivelLuz >= 70 ? 'bg-yellow-400' : 'bg-red-400'" />
            {{ nivelLuz >= 110 ? 'Iluminación buena' : nivelLuz >= 70 ? 'Iluminación aceptable' : 'Poca luz' }}
          </div>
        </div>

        <!-- Overlay procesando -->
        <Transition enter-active-class="transition-opacity duration-200" leave-active-class="transition-opacity duration-200"
          enter-from-class="opacity-0" leave-to-class="opacity-0">
          <div v-if="procesando" class="absolute inset-0 bg-black/65 flex items-center justify-center backdrop-blur-sm">
            <div class="bg-slate-800 rounded-xl px-7 py-6 flex flex-col items-center gap-3 min-w-44">
              <svg class="w-8 h-8 text-blue-400 animate-spin" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
              </svg>
              <p class="text-slate-300 text-xs text-center m-0">{{ mensajeProcesando }}</p>
              <div class="w-full h-1 bg-slate-700 rounded-full overflow-hidden">
                <div class="h-full bg-blue-500 rounded-full transition-all duration-300 ease-out"
                  :style="{ width: progresoProcesado + '%' }" />
              </div>
            </div>
          </div>
        </Transition>

        <!-- Flash -->
        <div v-if="flash" class="absolute inset-0 bg-white pointer-events-none animate-[flashOut_0.22s_ease-out_forwards]" />

        <!-- Dots progreso -->
        <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-1.5">
          <div v-for="n in TOTAL_HOJAS" :key="n"
            class="w-2.5 h-2.5 rounded-full flex items-center justify-center transition-all duration-250"
            :class="capturas[n-1] ? 'bg-green-400 scale-110' : n-1 === indiceActivo ? 'bg-white/80' : 'bg-white/30'">
            <svg v-if="capturas[n-1]" class="w-2 h-2" viewBox="0 0 12 12" fill="none">
              <path d="M10 3L5 8.5 2 5.5" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
        </div>
      </div>

      <!-- Canvas oculto -->
      <canvas ref="canvasEl" class="hidden" />

      <!-- Controles -->
      <div class="flex items-center justify-between px-5 py-3.5 bg-gray-50 border-t border-gray-100">
        <button v-if="capturas.some(Boolean)" @click="deshacerUltima" :disabled="procesando"
          class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-red-500 border-0 bg-transparent cursor-pointer disabled:opacity-40 transition-colors duration-150 p-0">
          <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M7.707 3.293a1 1 0 010 1.414L5.414 7H11a7 7 0 017 7v2a1 1 0 11-2 0v-2a5 5 0 00-5-5H5.414l2.293 2.293a1 1 0 11-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/>
          </svg>
          Deshacer
        </button>
        <div v-else />

        <!-- Disparador -->
        <button @click="disparar" :disabled="procesando"
          class="w-16 h-16 rounded-full border-4 border-white flex items-center justify-center flex-shrink-0 cursor-pointer transition-transform duration-100 active:scale-90 disabled:opacity-50 disabled:cursor-not-allowed"
          :class="procesando ? 'bg-gray-400 shadow-none' : 'bg-blue-600 hover:bg-blue-700 shadow-[0_0_0_2px_#2563eb,0_4px_12px_rgba(37,99,235,0.35)]'">
          <div class="w-10 h-10 rounded-full bg-white/20" />
        </button>

        <div class="flex items-baseline gap-0.5 min-w-10 justify-end">
          <span class="text-lg font-bold text-blue-700">{{ capturas.filter(Boolean).length }}</span>
          <span class="text-sm text-gray-300">/</span>
          <span class="text-sm text-gray-400">{{ TOTAL_HOJAS }}</span>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         PASO 3 · REVISIÓN
    ══════════════════════════════════════════════════════ -->
    <div v-else-if="paso === 'revision'" class="flex flex-col gap-4">

      <!-- Header revisión -->
      <div class="flex items-center justify-between">
        <div>
          <h3 class="text-base font-bold text-gray-900 m-0">Revisar documento</h3>
          <p class="text-xs text-gray-400 mt-1 m-0">
            {{ capturas.filter(Boolean).length }} de {{ TOTAL_HOJAS }} hojas capturadas
          </p>
        </div>
        <div class="flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold"
          :class="todasListas ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-800'">
          <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor">
            <path v-if="todasListas" fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            <path v-else fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
          </svg>
          {{ todasListas ? 'Listo para exportar' : `Faltan ${TOTAL_HOJAS - capturas.filter(Boolean).length} hoja${TOTAL_HOJAS - capturas.filter(Boolean).length > 1 ? 's' : ''}` }}
        </div>
      </div>

      <!-- Grid miniaturas -->
      <div class="grid grid-cols-3 gap-2.5">
        <div v-for="n in TOTAL_HOJAS" :key="n"
          class="relative rounded-xl overflow-hidden transition-all duration-250"
          :class="capturas[n-1]
            ? 'border-2 border-green-400 shadow-[0_2px_8px_rgba(74,222,128,0.2)]'
            : 'border-2 border-dashed border-gray-300 bg-gray-50'"
          style="aspect-ratio:3/4">

          <img v-if="capturas[n-1]" :src="capturas[n-1]" :alt="`Hoja ${n}`"
            class="w-full h-full object-cover block" />

          <div v-else class="w-full h-full flex flex-col items-center justify-center gap-2 text-gray-300">
            <svg class="w-9 h-9" viewBox="0 0 48 48" fill="none">
              <rect x="8" y="4" width="32" height="40" rx="3" stroke="currentColor" stroke-width="1.5" stroke-dasharray="4 3"/>
              <path d="M16 18h16M16 24h16M16 30h10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
            <span class="text-xs font-medium text-gray-400">Hoja {{ n }}</span>
          </div>

          <!-- Label inferior -->
          <div v-if="capturas[n-1]"
            class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-black/60 to-transparent py-1.5 px-2">
            <span class="text-xs text-white font-semibold">Hoja {{ n }}</span>
          </div>

          <!-- Check -->
          <div v-if="capturas[n-1]"
            class="absolute top-1.5 right-1.5 w-5 h-5 bg-green-500 rounded-full flex items-center justify-center shadow">
            <svg class="w-2.5 h-2.5" viewBox="0 0 12 12" fill="none">
              <path d="M2.5 6l2.5 2.5L9.5 3.5" stroke="white" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>

          <!-- Retomar -->
          <button v-if="capturas[n-1]" @click="retomarHoja(n-1)"
            class="absolute top-1.5 left-1.5 w-5 h-5 bg-black/55 hover:bg-black/75 rounded-full flex items-center justify-center border-0 cursor-pointer transition-colors duration-150"
            title="Retomar esta foto">
            <svg class="w-2.5 h-2.5 text-white" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/>
            </svg>
          </button>
        </div>
      </div>

      <!-- Acciones -->
      <div class="flex items-center justify-between gap-2.5">
        <button @click="continuarCapturando"
          class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-white text-gray-600 hover:text-blue-600 text-xs font-medium border border-gray-300 hover:border-blue-400 rounded-xl cursor-pointer transition-colors duration-150">
          <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor">
            <path d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z"/>
          </svg>
          {{ capturas.filter(Boolean).length === 0 ? 'Abrir cámara' : 'Seguir escaneando' }}
        </button>

        <button @click="exportarPDF" :disabled="!todasListas || exportando"
          class="inline-flex items-center gap-1.5 px-5 py-2.5 text-sm font-semibold rounded-xl border-0 cursor-pointer transition-all duration-150 flex-shrink-0"
          :class="!todasListas || exportando
            ? 'bg-gray-200 text-gray-400 cursor-not-allowed'
            : 'bg-blue-600 hover:bg-blue-700 text-white shadow-sm'">
          <svg v-if="exportando" class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
          <svg v-else class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
          </svg>
          {{ exportando ? 'Generando PDF…' : 'Guardar resolución en PDF' }}
        </button>
      </div>

      <!-- Error -->
      <div v-if="error"
        class="flex items-start gap-2.5 bg-red-50 border border-red-200 rounded-xl p-3 text-red-600">
        <svg class="w-4 h-4 flex-shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
        </svg>
        <div>
          <strong class="text-xs font-semibold block">Error</strong>
          <p class="text-xs mt-0.5 leading-snug m-0">{{ error }}</p>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         PASO 4 · ÉXITO
    ══════════════════════════════════════════════════════ -->
    <div v-else-if="paso === 'exito'"
      class="flex flex-col items-center gap-3 px-6 py-8 bg-white border border-gray-200 rounded-2xl text-center">
      <svg class="w-14 h-14" viewBox="0 0 48 48" fill="none">
        <circle cx="24" cy="24" r="22" stroke="#22c55e" stroke-width="2"/>
        <path d="M14 24l7 7 13-14" stroke="#22c55e" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      <h3 class="text-lg font-bold text-gray-900 m-0">Resolución guardada</h3>
      <p class="text-sm text-gray-500 max-w-xs leading-relaxed m-0">
        El PDF se generó correctamente con {{ TOTAL_HOJAS }} hojas y está listo para continuar.
      </p>
      <div class="flex items-center gap-3 bg-green-50 border border-green-200 rounded-lg px-3.5 py-2 text-xs text-green-800 font-medium">
        <span>📄 resolucion_{{ fechaArchivo }}.pdf</span>
        <span class="text-green-400">·</span>
        <span>{{ tamañoPdfKb }} KB</span>
      </div>
      <button @click="reiniciar"
        class="inline-flex items-center gap-1.5 mt-1 px-4 py-2.5 bg-white text-gray-600 hover:text-blue-600 text-sm font-medium border border-gray-300 hover:border-blue-400 rounded-xl cursor-pointer transition-colors duration-150">
        Escanear otro documento
      </button>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onUnmounted } from 'vue'

const emit = defineEmits(['pdf-listo', 'error'])

const TOTAL_HOJAS = 3
const INSTRUCCIONES = [
  'Fotografía la carátula — Hoja 1',
  'Segunda hoja — materias y docentes',
  'Tercera hoja — datos restantes',
]
const LUZ_BUENA     = 110
const LUZ_ACEPTABLE = 70

// Estado
const paso              = ref('permiso')
const errorPermiso      = ref('')
const error             = ref('')
const videoEl           = ref(null)
const canvasEl          = ref(null)
const capturas          = ref(Array(TOTAL_HOJAS).fill(null))
const indiceActivo      = ref(0)
const indiceRetoma      = ref(null)
const procesando        = ref(false)
const mensajeProcesando = ref('Procesando…')
const progresoProcesado = ref(0)
const flash             = ref(false)
const encuadreBueno     = ref(false)
const nivelLuz          = ref(255)
const exportando        = ref(false)
const tamañoPdfKb       = ref(0)
const fechaArchivo      = ref('')

let streamActual = null
let rafId        = null

// Computed
const todasListas = computed(() => capturas.value.every(Boolean))

const instruccionActiva = computed(() => {
  if (indiceRetoma.value !== null) return `Retomando Hoja ${indiceRetoma.value + 1}`
  return INSTRUCCIONES[indiceActivo.value] ?? '¡Todas las hojas capturadas!'
})

// PASO 1 · Permiso
async function solicitarPermiso() {
  errorPermiso.value = ''
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false })
    stream.getTracks().forEach(t => t.stop())
    await abrirCamara()
  } catch (e) {
    if (e.name === 'NotAllowedError' || e.name === 'PermissionDeniedError') {
      errorPermiso.value = 'Permiso denegado. Ve a Configuración > Privacidad > Cámara y habilita el acceso para este sitio.'
    } else if (e.name === 'NotFoundError') {
      errorPermiso.value = 'No se encontró ninguna cámara en este dispositivo.'
    } else {
      errorPermiso.value = `Error inesperado: ${e.message}`
    }
    emit('error', errorPermiso.value)
  }
}

// PASO 2 · Cámara
async function abrirCamara() {
  error.value = ''
  try {
    streamActual = await navigator.mediaDevices.getUserMedia({
      video: {
        facingMode: { ideal: 'environment' },
        width:  { ideal: 3840 },
        height: { ideal: 2160 },
        focusMode: { ideal: 'continuous' },
        exposureMode: { ideal: 'continuous' },
        whiteBalanceMode: { ideal: 'continuous' },
      },
      audio: false,
    })
    paso.value = 'camara'
    await new Promise(r => setTimeout(r, 80))
    if (videoEl.value) {
      videoEl.value.srcObject = streamActual
      videoEl.value.addEventListener('loadedmetadata', iniciarAnalisisContinuo, { once: true })
    }
  } catch (e) {
    error.value = 'No se pudo acceder a la cámara. Verifica los permisos.'
    paso.value  = 'revision'
    emit('error', error.value)
  }
}

function cerrarCamara() {
  detenerStream()
  cancelAnimationFrame(rafId)
  paso.value         = 'revision'
  indiceRetoma.value = null
}

function detenerStream() {
  streamActual?.getTracks().forEach(t => t.stop())
  streamActual = null
}

function iniciarAnalisisContinuo() {
  const analizarFrame = () => {
    if (!videoEl.value || !canvasEl.value || paso.value !== 'camara') return
    const video = videoEl.value, canvas = canvasEl.value
    const W = 80, H = 60
    canvas.width = W; canvas.height = H
    const ctx = canvas.getContext('2d')
    ctx.drawImage(video, 0, 0, W, H)
    const { data } = ctx.getImageData(0, 0, W, H)
    let sum = 0
    for (let i = 0; i < W * H; i++)
      sum += 0.299 * data[i*4] + 0.587 * data[i*4+1] + 0.114 * data[i*4+2]
    nivelLuz.value      = Math.round(sum / (W * H))
    encuadreBueno.value = nivelLuz.value >= LUZ_ACEPTABLE
    rafId = requestAnimationFrame(analizarFrame)
  }
  rafId = requestAnimationFrame(analizarFrame)
}

onUnmounted(() => { detenerStream(); cancelAnimationFrame(rafId) })

// Disparo
async function disparar() {
  if (procesando.value) return
  flash.value = true
  await new Promise(r => setTimeout(r, 150))
  flash.value = false
  procesando.value = true; progresoProcesado.value = 0
  mensajeProcesando.value = 'Capturando frame…'
  try {
    const original = capturarFrameAltaRes()
    progresoProcesado.value = 25; mensajeProcesando.value = 'Detectando bordes…'
    await tick()
    const recortada = await recortarYEnhancer(original)
    progresoProcesado.value = 70; mensajeProcesando.value = 'Mejorando contraste…'
    await tick()
    const mejorada = await mejorarImagen(recortada)
    progresoProcesado.value = 95; mensajeProcesando.value = 'Guardando hoja…'
    await tick()
    const idx = indiceRetoma.value !== null ? indiceRetoma.value : indiceActivo.value
    const arr = [...capturas.value]
    arr[idx] = mejorada
    capturas.value     = arr
    indiceRetoma.value = null
    const sig = arr.findIndex(c => !c)
    if (sig !== -1) { indiceActivo.value = sig } else { cerrarCamara() }
    progresoProcesado.value = 100
  } catch (e) {
    error.value = e.message
    emit('error', e.message)
  } finally {
    await new Promise(r => setTimeout(r, 200))
    procesando.value = false
  }
}

const tick = () => new Promise(r => setTimeout(r, 30))

function capturarFrameAltaRes() {
  const video = videoEl.value, canvas = canvasEl.value
  canvas.width = video.videoWidth; canvas.height = video.videoHeight
  canvas.getContext('2d').drawImage(video, 0, 0)
  return canvas.toDataURL('image/jpeg', 1.0)
}

function recortarYEnhancer(dataUrl) {
  return new Promise((resolve, reject) => {
    const img = new Image()
    img.onerror = () => reject(new Error('No se pudo cargar la imagen'))
    img.onload = () => {
      const OW = img.naturalWidth, OH = img.naturalHeight
      const SCALE = 0.12
      const W = Math.round(OW * SCALE), H = Math.round(OH * SCALE)
      const mini = document.createElement('canvas')
      mini.width = W; mini.height = H
      mini.getContext('2d').drawImage(img, 0, 0, W, H)
      const { data } = mini.getContext('2d').getImageData(0, 0, W, H)
      const lumas = new Uint8Array(W * H)
      for (let i = 0; i < W * H; i++)
        lumas[i] = Math.round(0.299 * data[i*4] + 0.587 * data[i*4+1] + 0.114 * data[i*4+2])
      const sorted = [...lumas].sort((a, b) => a - b)
      const thresh = sorted[Math.floor(sorted.length * 0.65)]
      let minX = W, maxX = 0, minY = H, maxY = 0, count = 0
      for (let y = 0; y < H; y++)
        for (let x = 0; x < W; x++)
          if (lumas[y * W + x] >= thresh) {
            if (x < minX) minX = x; if (x > maxX) maxX = x
            if (y < minY) minY = y; if (y > maxY) maxY = y
            count++
          }
      if (count / (W * H) < 0.15 || maxX <= minX || maxY <= minY) { resolve(dataUrl); return }
      const mxPx = Math.round(OW * 0.015), myPx = Math.round(OH * 0.015)
      const sX = OW / W, sY = OH / H
      const cropX = Math.max(0, Math.round(minX * sX) - mxPx)
      const cropY = Math.max(0, Math.round(minY * sY) - myPx)
      const cropW = Math.min(OW - cropX, Math.round((maxX - minX + 1) * sX) + mxPx * 2)
      const cropH = Math.min(OH - cropY, Math.round((maxY - minY + 1) * sY) + myPx * 2)
      const out = document.createElement('canvas')
      out.width = cropW; out.height = cropH
      out.getContext('2d').drawImage(img, cropX, cropY, cropW, cropH, 0, 0, cropW, cropH)
      resolve(out.toDataURL('image/jpeg', 0.97))
    }
    img.src = dataUrl
  })
}

function mejorarImagen(dataUrl) {
  return new Promise((resolve, reject) => {
    const img = new Image()
    img.onerror = () => reject(new Error('Error al mejorar la imagen'))
    img.onload = () => {
      const W = img.naturalWidth, H = img.naturalHeight
      const c = document.createElement('canvas')
      c.width = W; c.height = H
      const ctx = c.getContext('2d')
      ctx.drawImage(img, 0, 0)
      const imageData = ctx.getImageData(0, 0, W, H), d = imageData.data
      let minL = 255, maxL = 0
      for (let i = 0; i < d.length; i += 4) {
        const l = Math.round(0.299 * d[i] + 0.587 * d[i+1] + 0.114 * d[i+2])
        if (l < minL) minL = l; if (l > maxL) maxL = l
      }
      const range = maxL - minL || 1
      const contrast = Math.min(1.35, 255 / range)
      const brightness = -minL * contrast + 8
      const lut = new Uint8Array(256)
      for (let i = 0; i < 256; i++) lut[i] = Math.max(0, Math.min(255, Math.round(i * contrast + brightness)))
      for (let i = 0; i < d.length; i += 4) { d[i] = lut[d[i]]; d[i+1] = lut[d[i+1]]; d[i+2] = lut[d[i+2]] }
      ctx.putImageData(imageData, 0, 0)
      const c2 = document.createElement('canvas')
      c2.width = W; c2.height = H
      const ctx2 = c2.getContext('2d')
      ctx2.filter = 'contrast(1.04) saturate(0.82)'
      ctx2.drawImage(c, 0, 0)
      resolve(c2.toDataURL('image/jpeg', 0.94))
    }
    img.src = dataUrl
  })
}

function deshacerUltima() {
  for (let i = capturas.value.length - 1; i >= 0; i--) {
    if (capturas.value[i]) {
      const arr = [...capturas.value]; arr[i] = null
      capturas.value = arr; indiceActivo.value = i; break
    }
  }
}

async function retomarHoja(idx) {
  indiceRetoma.value = idx; await abrirCamara()
}

function continuarCapturando() {
  const primerVacio = capturas.value.findIndex(c => !c)
  indiceActivo.value = primerVacio !== -1 ? primerVacio : 0
  abrirCamara()
}

async function exportarPDF() {
  if (!todasListas.value || exportando.value) return
  exportando.value = true; error.value = ''
  try {
    if (!window.jspdf?.jsPDF && !window.jsPDF)
      await cargarScript('https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js')
    const jsPDFClass = window.jspdf?.jsPDF || window.jsPDF
    if (!jsPDFClass) throw new Error('No se pudo cargar el generador de PDF.')
    const pdf = new jsPDFClass({ orientation: 'portrait', unit: 'mm', format: 'a4', compress: true })
    pdf.setProperties({ title: 'Resolución escaneada', subject: 'Documento digitalizado', creator: 'Sistema de resoluciones' })
    const pageW = pdf.internal.pageSize.getWidth(), pageH = pdf.internal.pageSize.getHeight()
    for (let i = 0; i < capturas.value.length; i++) {
      if (i > 0) pdf.addPage()
      const { width: iW, height: iH } = await obtenerDimensiones(capturas.value[i])
      const ratio = iW / iH, pageRatio = pageW / pageH
      let dW, dH, dX, dY
      if (ratio > pageRatio) { dW = pageW; dH = pageW / ratio; dX = 0; dY = (pageH - dH) / 2 }
      else { dH = pageH; dW = pageH * ratio; dX = (pageW - dW) / 2; dY = 0 }
      pdf.addImage(capturas.value[i], 'JPEG', dX, dY, dW, dH, `hoja_${i+1}`, 'FAST')
    }
    const blob = pdf.output('blob')
    const fecha = new Date()
    const nombreFecha = `${fecha.getFullYear()}${String(fecha.getMonth()+1).padStart(2,'0')}${String(fecha.getDate()).padStart(2,'0')}`
    fechaArchivo.value = nombreFecha
    tamañoPdfKb.value  = Math.round(blob.size / 1024)
    const file = new File([blob], `resolucion_${nombreFecha}.pdf`, { type: 'application/pdf' })
    emit('pdf-listo', file)
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url; link.download = file.name; link.click()
    setTimeout(() => URL.revokeObjectURL(url), 5000)
    paso.value = 'exito'
  } catch (e) {
    error.value = e.message || 'No se pudo generar el PDF.'
    emit('error', error.value)
  } finally {
    exportando.value = false
  }
}

function obtenerDimensiones(dataUrl) {
  return new Promise(resolve => {
    const img = new Image()
    img.onload = () => resolve({ width: img.naturalWidth, height: img.naturalHeight })
    img.src = dataUrl
  })
}

function cargarScript(src) {
  return new Promise((resolve, reject) => {
    if (document.querySelector(`script[src="${src}"]`)) { resolve(); return }
    const s = document.createElement('script')
    s.src = src; s.onload = resolve; s.onerror = () => reject(new Error(`No se pudo cargar: ${src}`))
    document.head.appendChild(s)
  })
}

function reiniciar() {
  capturas.value = Array(TOTAL_HOJAS).fill(null)
  indiceActivo.value = 0; indiceRetoma.value = null
  error.value = ''; paso.value = 'permiso'
}

defineExpose({ reiniciar })
</script>

<style scoped>
@keyframes flashOut { from { opacity: 0.9 } to { opacity: 0 } }
</style>