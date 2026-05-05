<template>
  <div>
    <!-- ───────────────────────────────────────────────── -->
    <!-- VISOR DE CÁMARA                                  -->
    <!-- ───────────────────────────────────────────────── -->
    <div v-if="camaraActiva" class="bg-white rounded-xl border border-gray-200 overflow-hidden">

      <!-- Header -->
      <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
            <svg class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
          </div>
          <div>
            <p class="text-sm font-semibold text-gray-800">
              Hoja {{ fotosTomadas.length + 1 }} de {{ TOTAL_FOTOS }}
            </p>
            <p class="text-xs text-gray-400">{{ instruccionActual }}</p>
          </div>
        </div>
        <button @click="cerrarCamara" class="text-gray-400 hover:text-gray-600 transition-colors p-1">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Video stream -->
      <div class="relative bg-black" style="aspect-ratio: 4/3;">
        <video ref="videoEl" autoplay playsinline muted class="w-full h-full object-cover" />

        <!-- Overlay procesando (después de disparar, antes de mostrar preview) -->
        <div
          v-if="procesandoRecorte"
          class="absolute inset-0 bg-black/60 flex flex-col items-center justify-center gap-3"
        >
          <svg class="w-8 h-8 text-blue-400 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
          <p class="text-white text-sm font-medium">Recortando documento...</p>
        </div>

        <!-- Guías de encuadre -->
        <div v-if="!procesandoRecorte" class="absolute inset-0 pointer-events-none">
          <!-- Marco punteado de la hoja guía -->
          <div class="absolute inset-6 border border-dashed border-white/30 rounded-sm" />
          <!-- Esquinas vivas -->
          <div class="absolute top-6 left-6 w-8 h-8 border-t-2 border-l-2 border-blue-400 rounded-tl" />
          <div class="absolute top-6 right-6 w-8 h-8 border-t-2 border-r-2 border-blue-400 rounded-tr" />
          <div class="absolute bottom-6 left-6 w-8 h-8 border-b-2 border-l-2 border-blue-400 rounded-bl" />
          <div class="absolute bottom-6 right-6 w-8 h-8 border-b-2 border-r-2 border-blue-400 rounded-br" />
          <!-- Tip -->
          <div class="absolute top-3 inset-x-0 text-center">
            <span class="text-xs text-white/70 bg-black/40 px-2 py-0.5 rounded-full">
              Centra la hoja en el encuadre
            </span>
          </div>
        </div>

        <!-- Flash -->
        <div
          v-if="flashActivo"
          class="absolute inset-0 bg-white pointer-events-none"
          style="animation: flashOut 0.25s ease-out forwards;"
        />

        <!-- Dots de progreso -->
        <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2">
          <div
            v-for="n in TOTAL_FOTOS"
            :key="n"
            class="w-2.5 h-2.5 rounded-full transition-all duration-300"
            :class="n - 1 < fotosTomadas.length ? 'bg-green-400 scale-110' : 'bg-white/40'"
          />
        </div>
      </div>

      <!-- Canvas oculto para procesamiento -->
      <canvas ref="canvasEl" class="hidden" />

      <!-- Controles cámara -->
      <div class="flex items-center justify-between px-5 py-4 bg-gray-50 border-t border-gray-100">
        <button
          v-if="fotosTomadas.length > 0"
          @click="deshacerUltimaFoto"
          class="flex items-center gap-2 text-sm text-gray-500 hover:text-red-500 transition-colors"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
          </svg>
          Deshacer
        </button>
        <div v-else />

        <!-- Disparador -->
        <button
          @click="disparar"
          :disabled="procesandoRecorte"
          class="w-16 h-16 rounded-full border-4 border-white shadow-lg flex items-center justify-center transition-transform active:scale-90 disabled:opacity-50"
          :class="procesandoRecorte ? 'bg-gray-400' : 'bg-blue-600 hover:bg-blue-700'"
        >
          <div class="w-10 h-10 rounded-full bg-white/25" />
        </button>

        <span class="text-xs text-gray-400 w-20 text-right">
          {{ fotosTomadas.length }}/{{ TOTAL_FOTOS }}
        </span>
      </div>
    </div>

    <!-- ───────────────────────────────────────────────── -->
    <!-- GRID DE MINIATURAS (después de capturar)         -->
    <!-- ───────────────────────────────────────────────── -->
    <div v-else>
      <div class="grid grid-cols-3 gap-4 mb-5">
        <div
          v-for="n in TOTAL_FOTOS"
          :key="n"
          class="relative rounded-xl overflow-hidden border-2 transition-all duration-300"
          :class="fotosTomadas[n - 1]
            ? 'border-green-400 shadow-md'
            : 'border-dashed border-gray-300 bg-white'"
          style="aspect-ratio: 3/4;"
        >
          <!-- Foto capturada (recortada) -->
          <img
            v-if="fotosTomadas[n - 1]"
            :src="fotosTomadas[n - 1]"
            class="w-full h-full object-cover"
          />

          <!-- Slot vacío -->
          <div v-else class="w-full h-full flex flex-col items-center justify-center gap-2 text-gray-300">
            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <span class="text-xs font-medium">Foto {{ n }}</span>
          </div>

          <!-- Badge verde -->
          <div
            v-if="fotosTomadas[n - 1]"
            class="absolute top-2 right-2 w-6 h-6 bg-green-500 rounded-full flex items-center justify-center shadow"
          >
            <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
          </div>

          <!-- Etiqueta hoja -->
          <div v-if="fotosTomadas[n - 1]" class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-black/60 to-transparent py-2 px-2">
            <span class="text-xs text-white font-medium">Hoja {{ n }}</span>
          </div>

          <!-- Botón retomar foto individual -->
          <button
            v-if="fotosTomadas[n - 1]"
            @click="retomarFoto(n - 1)"
            class="absolute top-2 left-2 w-6 h-6 bg-black/50 rounded-full flex items-center justify-center hover:bg-black/75 transition-colors"
            title="Retomar esta foto"
          >
            <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Acciones -->
      <div class="flex items-center justify-between">
        <button
          @click="abrirCamara()"
          class="flex items-center gap-2 px-5 py-2.5 border border-gray-300 hover:border-blue-400 text-gray-600 hover:text-blue-600 text-sm font-medium rounded-lg transition-all"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          {{ fotosTomadas.length === 0 ? 'Abrir cámara' : 'Continuar tomando' }}
        </button>

        <button
          @click="convertirAPdf"
          :disabled="fotosTomadas.length < TOTAL_FOTOS || convirtiendo"
          class="flex items-center gap-2 px-6 py-2.5 text-sm font-semibold rounded-lg transition-all duration-200"
          :class="fotosTomadas.length < TOTAL_FOTOS || convirtiendo
            ? 'bg-gray-200 text-gray-400 cursor-not-allowed'
            : 'bg-blue-600 hover:bg-blue-700 text-white shadow-sm'"
        >
          <svg v-if="convirtiendo" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
          </svg>
          <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          {{ convirtiendo ? 'Generando PDF...' : 'Convertir a PDF' }}
        </button>
      </div>

      <!-- Hint -->
      <p v-if="fotosTomadas.length < TOTAL_FOTOS" class="text-xs text-amber-600 mt-3 text-center">
        Faltan {{ TOTAL_FOTOS - fotosTomadas.length }} foto(s) para continuar
      </p>

      <!-- Error -->
      <div v-if="errorLocal" class="mt-3 flex items-center gap-2 p-3 bg-red-50 border border-red-200 rounded-lg text-red-600 text-sm">
        <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
        </svg>
        {{ errorLocal }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onUnmounted } from 'vue'

// ────────────────────────────────────────────────────────────
// Props y emits
// ────────────────────────────────────────────────────────────
const emit = defineEmits(['pdf-listo', 'error'])

// ────────────────────────────────────────────────────────────
// Constantes
// ────────────────────────────────────────────────────────────
const TOTAL_FOTOS = 3

const INSTRUCCIONES = [
  'Fotografía la carátula (Hoja 1)',
  'Fotografía la segunda hoja — materias y docentes',
  'Fotografía la tercera hoja — datos restantes',
]

// ────────────────────────────────────────────────────────────
// Estado
// ────────────────────────────────────────────────────────────
const camaraActiva    = ref(false)
const videoEl         = ref(null)
const canvasEl        = ref(null)
const fotosTomadas    = ref([])      // dataURLs ya recortadas
const procesandoRecorte = ref(false)
const flashActivo     = ref(false)
const convirtiendo    = ref(false)
const errorLocal      = ref('')
const indiceRetoma    = ref(null)    // número de índice si se retoma foto específica

let streamActual = null

// ────────────────────────────────────────────────────────────
// Computed
// ────────────────────────────────────────────────────────────
const instruccionActual = computed(() => {
  if (indiceRetoma.value !== null) return `Retomando Hoja ${indiceRetoma.value + 1}`
  return INSTRUCCIONES[fotosTomadas.value.length] ?? 'Listo'
})

// ────────────────────────────────────────────────────────────
// Cámara
// ────────────────────────────────────────────────────────────
async function abrirCamara() {
  errorLocal.value = ''
  try {
    streamActual = await navigator.mediaDevices.getUserMedia({
      video: {
        facingMode: { ideal: 'environment' },
        width:  { ideal: 1920 },
        height: { ideal: 1080 },
      },
      audio: false,
    })
    camaraActiva.value = true
    await new Promise(r => setTimeout(r, 60))
    if (videoEl.value) videoEl.value.srcObject = streamActual
  } catch {
    errorLocal.value = 'No se pudo acceder a la cámara. Verifica los permisos del navegador.'
    emit('error', errorLocal.value)
  }
}

function cerrarCamara() {
  detenerStream()
  camaraActiva.value = false
  indiceRetoma.value = null
}

function detenerStream() {
  streamActual?.getTracks().forEach(t => t.stop())
  streamActual = null
}

onUnmounted(detenerStream)

// ────────────────────────────────────────────────────────────
// Disparo y recorte
// ────────────────────────────────────────────────────────────
async function disparar() {
  if (procesandoRecorte.value) return

  // Flash visual
  flashActivo.value = true
  await new Promise(r => setTimeout(r, 120))
  flashActivo.value = false

  procesandoRecorte.value = true
  try {
    const dataUrl = capturarFrame()
    const recortada = await recortarHoja(dataUrl)

    if (indiceRetoma.value !== null) {
      const arr = [...fotosTomadas.value]
      arr[indiceRetoma.value] = recortada
      fotosTomadas.value = arr
      indiceRetoma.value = null
    } else {
      fotosTomadas.value = [...fotosTomadas.value, recortada]
    }

    if (fotosTomadas.value.length >= TOTAL_FOTOS) {
      cerrarCamara()
    }
  } finally {
    procesandoRecorte.value = false
  }
}

/** Dibuja el frame actual del video en el canvas oculto y retorna dataURL */
function capturarFrame() {
  const video  = videoEl.value
  const canvas = canvasEl.value
  canvas.width  = video.videoWidth
  canvas.height = video.videoHeight
  canvas.getContext('2d').drawImage(video, 0, 0)
  return canvas.toDataURL('image/jpeg', 0.95)
}

// ────────────────────────────────────────────────────────────
// Recorte automático de la hoja
// ────────────────────────────────────────────────────────────
/**
 * Algoritmo de detección de hoja / recorte automático:
 *
 * 1. Escala la imagen a un tamaño pequeño para procesar rápido.
 * 2. Convierte a escala de grises.
 * 3. Aplica umbral (threshold) para binarizar.
 * 4. Busca la región blanca más grande (la hoja).
 * 5. Calcula el bounding box de esa región con un pequeño margen.
 * 6. Recorta la imagen original en alta resolución.
 *
 * Si no detecta una hoja claramente, devuelve la imagen original.
 */
async function recortarHoja(dataUrl) {
  return new Promise(resolve => {
    const img = new Image()
    img.onload = () => {
      // ── 1. Escalar para procesar ──────────────────────────
      const SCALE  = 0.15          // resolución de análisis
      const W      = Math.round(img.naturalWidth  * SCALE)
      const H      = Math.round(img.naturalHeight * SCALE)

      const small  = document.createElement('canvas')
      small.width  = W
      small.height = H
      const sCtx   = small.getContext('2d')
      sCtx.drawImage(img, 0, 0, W, H)

      const { data } = sCtx.getImageData(0, 0, W, H)

      // ── 2. Grises + umbral ────────────────────────────────
      // Determinamos el umbral adaptativo: mediana de la luminosidad
      const lumas = new Uint8Array(W * H)
      for (let i = 0; i < W * H; i++) {
        const r = data[i * 4], g = data[i * 4 + 1], b = data[i * 4 + 2]
        lumas[i] = Math.round(0.299 * r + 0.587 * g + 0.114 * b)
      }
      const sorted = [...lumas].sort((a, b) => a - b)
      // Umbral = percentil 70 (las hojas blancas están por encima)
      const threshold = sorted[Math.floor(sorted.length * 0.70)]

      // Máscara binaria: 1 = blanco/hoja, 0 = fondo
      const mask = new Uint8Array(W * H)
      for (let i = 0; i < lumas.length; i++) {
        mask[i] = lumas[i] >= threshold ? 1 : 0
      }

      // ── 3. Bounding box de píxeles "hoja" ─────────────────
      let minX = W, maxX = 0, minY = H, maxY = 0
      let count = 0
      for (let y = 0; y < H; y++) {
        for (let x = 0; x < W; x++) {
          if (mask[y * W + x]) {
            if (x < minX) minX = x
            if (x > maxX) maxX = x
            if (y < minY) minY = y
            if (y > maxY) maxY = y
            count++
          }
        }
      }

      // Si la región blanca es < 20% del total, devolver original
      const coverageRatio = count / (W * H)
      if (coverageRatio < 0.20 || maxX <= minX || maxY <= minY) {
        resolve(dataUrl)
        return
      }

      // ── 4. Expandir a resolución original con margen ──────
      const MARGIN = 0.01          // 1% de margen en cada borde
      const scaleX = img.naturalWidth  / W
      const scaleY = img.naturalHeight / H
      const mxPx   = Math.round(img.naturalWidth  * MARGIN)
      const myPx   = Math.round(img.naturalHeight * MARGIN)

      const cropX  = Math.max(0, Math.round(minX * scaleX) - mxPx)
      const cropY  = Math.max(0, Math.round(minY * scaleY) - myPx)
      const cropW  = Math.min(img.naturalWidth,  Math.round((maxX - minX + 1) * scaleX) + mxPx * 2)
      const cropH  = Math.min(img.naturalHeight, Math.round((maxY - minY + 1) * scaleY) + myPx * 2)

      // ── 5. Recortar en alta resolución ─────────────────────
      const out    = document.createElement('canvas')
      out.width    = cropW
      out.height   = cropH
      out.getContext('2d').drawImage(img, cropX, cropY, cropW, cropH, 0, 0, cropW, cropH)

      resolve(out.toDataURL('image/jpeg', 0.93))
    }
    img.src = dataUrl
  })
}

// ────────────────────────────────────────────────────────────
// Deshacer / retomar
// ────────────────────────────────────────────────────────────
function deshacerUltimaFoto() {
  if (fotosTomadas.value.length > 0) {
    fotosTomadas.value = fotosTomadas.value.slice(0, -1)
  }
}

async function retomarFoto(idx) {
  indiceRetoma.value = idx
  await abrirCamara()
}

// ────────────────────────────────────────────────────────────
// Convertir imágenes → PDF y emitir el File al padre
// ────────────────────────────────────────────────────────────
async function convertirAPdf() {
  if (fotosTomadas.value.length < TOTAL_FOTOS) return
  convirtiendo.value = true
  errorLocal.value   = ''

  try {
    // Cargar jsPDF si no está disponible
    if (!window.jspdf?.jsPDF && !window.jsPDF) {
      await cargarScript('https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js')
    }
    const jsPDFClass = window.jspdf?.jsPDF || window.jsPDF
    if (!jsPDFClass) throw new Error('No se pudo cargar jsPDF.')

    const pdf   = new jsPDFClass({ orientation: 'portrait', unit: 'mm', format: 'a4' })
    const pageW = pdf.internal.pageSize.getWidth()
    const pageH = pdf.internal.pageSize.getHeight()

    for (let i = 0; i < fotosTomadas.value.length; i++) {
      if (i > 0) pdf.addPage()
      const { width, height } = await getDims(fotosTomadas.value[i])
      const ratio     = width / height
      const pageRatio = pageW / pageH
      let dW, dH, dX, dY
      if (ratio > pageRatio) {
        dW = pageW; dH = pageW / ratio; dX = 0; dY = (pageH - dH) / 2
      } else {
        dH = pageH; dW = pageH * ratio; dX = (pageW - dW) / 2; dY = 0
      }
      pdf.addImage(fotosTomadas.value[i], 'JPEG', dX, dY, dW, dH)
    }

    const blob = pdf.output('blob')
    const file = new File([blob], 'resolucion_camara.pdf', { type: 'application/pdf' })
    emit('pdf-listo', file)
  } catch (e) {
    errorLocal.value = e.message || 'Error al generar el PDF.'
    emit('error', errorLocal.value)
  } finally {
    convirtiendo.value = false
  }
}

// ────────────────────────────────────────────────────────────
// Helpers
// ────────────────────────────────────────────────────────────
function getDims(dataUrl) {
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
    s.src = src
    s.onload  = resolve
    s.onerror = () => reject(new Error(`No se pudo cargar: ${src}`))
    document.head.appendChild(s)
  })
}

// ────────────────────────────────────────────────────────────
// Exponer método para reset desde el padre si hace falta
// ────────────────────────────────────────────────────────────
function reset() {
  detenerStream()
  camaraActiva.value    = false
  fotosTomadas.value    = []
  indiceRetoma.value    = null
  errorLocal.value      = ''
  procesandoRecorte.value = false
  convirtiendo.value    = false
}

defineExpose({ reset })
</script>

<style scoped>
@keyframes flashOut {
  0%   { opacity: 0.85; }
  100% { opacity: 0;    }
}
</style>