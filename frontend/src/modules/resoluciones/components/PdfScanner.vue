<template>
  <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">

    <!-- Header -->
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
          <svg class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
        </div>
        <div>
          <p class="text-sm font-semibold text-gray-800">{{ file?.name }}</p>
          <p class="text-xs text-gray-400">{{ fileSizeMB }} MB · {{ totalPages }} pagina(s)</p>
        </div>
      </div>
      <span
        class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full"
        :class="{
          'bg-green-100 text-green-700':   scanStatus === 'ok',
          'bg-yellow-100 text-yellow-700': scanStatus === 'scanning',
          'bg-gray-100 text-gray-500':     scanStatus === 'idle',
        }"
      >
        <span
          class="w-1.5 h-1.5 rounded-full"
          :class="{
            'bg-green-500':                scanStatus === 'ok',
            'bg-yellow-400 animate-pulse': scanStatus === 'scanning',
            'bg-gray-400':                 scanStatus === 'idle',
          }"
        />
        {{ statusLabel }}
      </span>
    </div>

    <!-- Previews -->
    <div class="p-6">

      <!-- Spinner inicial mientras carga el PDF completo -->
      <div v-if="cargandoPdf" class="flex flex-col items-center justify-center py-16 gap-3">
        <svg class="w-7 h-7 animate-spin text-blue-400" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
        <span class="text-sm text-gray-400">Cargando paginas del PDF...</span>
      </div>

      <!-- Miniaturas de paginas -->
      <div v-else class="flex gap-4 overflow-x-auto pb-2">
        <div
          v-for="(page, i) in pages"
          :key="i"
          class="flex-shrink-0 flex flex-col items-center gap-2"
        >
          <div
            class="relative rounded-lg overflow-hidden border-2 transition-all duration-500 bg-gray-50"
            :class="[
              page.scanned ? 'border-green-400 shadow-lg shadow-green-100' : 'border-gray-200',
              activePageIndex === i ? 'ring-2 ring-blue-400 ring-offset-2' : '',
            ]"
            style="width: 160px; height: 226px;"
          >
            <!-- Imagen de la pagina renderizada -->
            <img
              v-if="page.imgSrc"
              :src="page.imgSrc"
              class="w-full h-full object-contain bg-white"
              draggable="false"
            />

            <!-- Spinner por pagina mientras renderiza -->
            <div v-else class="absolute inset-0 flex items-center justify-center">
              <svg class="w-5 h-5 text-gray-300 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
              </svg>
            </div>

            <!-- Linea de escaneo animada -->
            <div
              v-if="activePageIndex === i && scanStatus === 'scanning'"
              class="absolute inset-0 pointer-events-none"
            >
              <div
                class="absolute left-0 right-0 h-0.5 bg-blue-400 opacity-80"
                style="box-shadow: 0 0 8px 2px rgba(59,130,246,0.5);"
                :style="{ top: scanLineY + '%' }"
              />
              <div class="absolute top-1.5 left-1.5 w-5 h-5 border-t-2 border-l-2 border-blue-500 rounded-tl" />
              <div class="absolute top-1.5 right-1.5 w-5 h-5 border-t-2 border-r-2 border-blue-500 rounded-tr" />
              <div class="absolute bottom-1.5 left-1.5 w-5 h-5 border-b-2 border-l-2 border-blue-500 rounded-bl" />
              <div class="absolute bottom-1.5 right-1.5 w-5 h-5 border-b-2 border-r-2 border-blue-500 rounded-br" />
            </div>

            <!-- Check de pagina escaneada -->
            <div
              v-if="page.scanned"
              class="absolute top-2 right-2 w-6 h-6 bg-green-500 rounded-full flex items-center justify-center shadow"
            >
              <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
              </svg>
            </div>

            <!-- Badge pagina 1 -->
            <div
              v-if="i === 0"
              class="absolute bottom-0 inset-x-0 bg-gray-800/70 py-1 text-center"
            >
              <span class="text-xs text-gray-300">Se omitira</span>
            </div>
          </div>

          <span
            class="text-xs font-medium"
            :class="page.scanned ? 'text-green-600' : 'text-gray-400'"
          >
            Pag. {{ i + 1 }}
          </span>
        </div>
      </div>

      <!-- Nota informativa -->
      <div class="mt-5 flex items-start gap-2 p-3 bg-amber-50 border border-amber-200 rounded-lg">
        <svg class="w-4 h-4 text-amber-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd"
            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
            clip-rule="evenodd"
          />
        </svg>
        <p class="text-xs text-amber-700">
          La <strong>pagina 1</strong> (caratula) sera omitida.
          Se procesaran las paginas 2 y 3 con los datos de materias y docentes.
        </p>
      </div>
    </div>

    <!-- Barra de progreso -->
    <div v-if="scanStatus === 'scanning'" class="px-6 pb-4">
      <div class="flex justify-between text-xs text-gray-500 mb-1">
        <span>Analizando documento...</span>
        <span>{{ Math.round(scanProgress) }}%</span>
      </div>
      <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
        <div
          class="h-full bg-blue-500 rounded-full transition-all duration-300"
          :style="{ width: scanProgress + '%' }"
        />
      </div>
    </div>

    <!-- Acciones -->
    <div class="flex items-center justify-between px-6 py-4 border-t border-gray-100 bg-gray-50">
      <button
        @click="$emit('back')"
        class="flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 transition-colors"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        Volver
      </button>

      <button
        @click="confirmScan"
        :disabled="scanStatus !== 'ok'"
        class="flex items-center gap-2 px-5 py-2 text-sm font-medium rounded-lg transition-all duration-200"
        :class="scanStatus === 'ok'
          ? 'bg-blue-600 hover:bg-blue-700 text-white'
          : 'bg-gray-200 text-gray-400 cursor-not-allowed'"
      >
        Procesar documento
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
      </button>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

const props = defineProps({
  file: { type: File, default: null },
})
const emit = defineEmits(['scanned', 'back'])

const totalPages      = ref(0)
const pages           = ref([])   // [{ imgSrc: string|null, scanned: bool }]
const activePageIndex = ref(-1)
const scanLineY       = ref(0)
const scanProgress    = ref(0)
const scanStatus      = ref('idle')
const cargandoPdf     = ref(true)

const fileSizeMB = computed(() =>
  props.file ? (props.file.size / 1024 / 1024).toFixed(2) : '0'
)
const statusLabel = computed(() => {
  if (scanStatus.value === 'ok')       return 'Listo para procesar'
  if (scanStatus.value === 'scanning') return 'Escaneando...'
  return 'Esperando'
})

onMounted(async () => {
  if (!props.file) return
  try {
    const lib = await getPdfjsLib()
    await renderPdf(lib)
  } catch (e) {
    console.error('PdfScanner error:', e)
    setupPages(3)
    cargandoPdf.value = false
    startScanAnimation(3)
  }
})

// ── Carga pdfjs desde CDN si no esta disponible ───────────
const PDFJS_JS     = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js'
const PDFJS_WORKER = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js'

async function getPdfjsLib () {
  let lib = window['pdfjs-dist/build/pdf'] || window.pdfjsLib
  if (!lib) {
    await loadScript(PDFJS_JS)
    lib = window['pdfjs-dist/build/pdf'] || window.pdfjsLib
  }
  if (!lib) throw new Error('pdfjs no disponible')
  if (!lib.GlobalWorkerOptions.workerSrc) {
    lib.GlobalWorkerOptions.workerSrc = PDFJS_WORKER
  }
  return lib
}

function loadScript (src) {
  return new Promise((resolve, reject) => {
    if (document.querySelector(`script[src="${src}"]`)) { resolve(); return }
    const s = document.createElement('script')
    s.src     = src
    s.onload  = resolve
    s.onerror = () => reject(new Error('No se pudo cargar ' + src))
    document.head.appendChild(s)
  })
}

// ── Renderizar PDF -> array de imgSrc ─────────────────────
async function renderPdf (lib) {
  const buf = await props.file.arrayBuffer()
  const pdf = await lib.getDocument({ data: buf }).promise
  const n   = pdf.numPages

  setupPages(n)
  cargandoPdf.value = false   // mostrar miniaturas (con spinners por pagina)

  for (let i = 0; i < n; i++) {
    const pdfPage  = await pdf.getPage(i + 1)
    const viewport = pdfPage.getViewport({ scale: 1 })

    // Escalar para caber en 160x226
    const scale   = Math.min(160 / viewport.width, 226 / viewport.height)
    const scaled  = pdfPage.getViewport({ scale })

    const offscreen       = document.createElement('canvas')
    offscreen.width       = scaled.width
    offscreen.height      = scaled.height
    const ctx             = offscreen.getContext('2d')
    ctx.fillStyle         = '#ffffff'
    ctx.fillRect(0, 0, offscreen.width, offscreen.height)

    await pdfPage.render({ canvasContext: ctx, viewport: scaled }).promise

    pages.value[i].imgSrc = offscreen.toDataURL('image/jpeg', 0.92)
  }

  startScanAnimation(n)
}

function setupPages (n) {
  totalPages.value = n
  pages.value = Array.from({ length: n }, () => ({ imgSrc: null, scanned: false }))
}

// ── Animacion de escaneo ─────────────────────────────────
function startScanAnimation (numPages) {
  scanStatus.value   = 'scanning'
  scanProgress.value = 0

  function scanPage (idx) {
    if (idx >= numPages) {
      scanStatus.value      = 'ok'
      scanProgress.value    = 100
      activePageIndex.value = -1
      return
    }
    activePageIndex.value = idx
    const duration = 700
    const start    = performance.now()

    function step (now) {
      const elapsed = now - start
      const pos     = Math.min((elapsed / duration) * 100, 100)
      scanLineY.value    = pos
      scanProgress.value = ((idx / numPages) + (elapsed / duration) / numPages) * 100
      if (pos < 100) {
        requestAnimationFrame(step)
      } else {
        pages.value[idx].scanned = true
        setTimeout(() => scanPage(idx + 1), 180)
      }
    }
    requestAnimationFrame(step)
  }

  setTimeout(() => scanPage(0), 350)
}

function confirmScan () {
  if (scanStatus.value === 'ok') emit('scanned')
}
</script>