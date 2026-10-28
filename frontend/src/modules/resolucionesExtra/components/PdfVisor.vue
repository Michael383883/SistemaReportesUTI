<template>
  <div class="flex flex-col h-full bg-white rounded-xl border border-gray-200 overflow-hidden">

    <!-- Cabecera -->
    <div class="flex items-center justify-between gap-2 px-4 py-2 bg-slate-900">
      <span class="text-[13px] font-semibold text-white truncate">{{ nombre }}</span>
      <div class="flex items-center gap-2 flex-shrink-0">
        <button
          type="button"
          class="hidden lg:inline-flex px-2 py-0.5 text-[11px] font-semibold rounded bg-slate-700 hover:bg-slate-600 text-white transition-colors"
          @click="$emit('toggle-ancho')"
        >
          {{ ampliado ? 'Reducir panel' : 'Ampliar panel' }}
        </button>
        <button
          type="button"
          class="text-gray-400 hover:text-red-400 transition-colors"
          title="Cerrar visor"
          @click="$emit('cerrar')"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
    </div>

    <!-- Barra de herramientas -->
    <div class="flex items-center flex-wrap gap-1.5 px-3 py-1.5 bg-slate-100 border-b border-gray-200 text-[12px] text-slate-700">
      <button
        type="button"
        class="w-7 h-7 rounded bg-white border border-gray-300 hover:bg-gray-50 font-bold disabled:opacity-40"
        :disabled="zoom <= ZOOM_MIN"
        title="Alejar"
        @click="cambiarZoom(-ZOOM_PASO)"
      >−</button>

      <span class="w-12 text-center font-semibold">{{ Math.round(zoom * 100) }}%</span>

      <button
        type="button"
        class="w-7 h-7 rounded bg-white border border-gray-300 hover:bg-gray-50 font-bold disabled:opacity-40"
        :disabled="zoom >= ZOOM_MAX"
        title="Acercar"
        @click="cambiarZoom(ZOOM_PASO)"
      >+</button>

      <button
        type="button"
        class="px-2 h-7 rounded bg-white border border-gray-300 hover:bg-gray-50"
        @click="ajustarAncho"
      >Ajustar al ancho</button>

      <button
        type="button"
        class="px-2 h-7 rounded bg-white border border-gray-300 hover:bg-gray-50"
        @click="ajustarPagina"
      >Página completa</button>

      <span class="ml-auto text-[11px] text-slate-500 hidden xl:inline">
        ✋ Arrastra para mover · Ctrl + rueda para acercar
      </span>
      <span v-if="numPaginas" class="text-[11px] font-medium text-slate-600">
        {{ numPaginas }} {{ numPaginas === 1 ? 'página' : 'páginas' }}
      </span>
    </div>

    <!-- Área del documento: scroll en ambos ejes + mano para arrastrar -->
    <div
      ref="contenedor"
      class="relative flex-1 overflow-auto bg-slate-300 select-none"
      :class="arrastrando ? 'cursor-grabbing' : 'cursor-grab'"
      @mousedown="iniciarArrastre"
      @wheel="onWheel"
    >
      <div v-if="cargando" class="absolute inset-0 flex items-center justify-center text-[13px] text-slate-600 pointer-events-none">
        Cargando PDF...
      </div>
      <div v-if="errorMsg" class="absolute inset-0 flex items-center justify-center p-6 text-center text-[13px] text-red-600">
        {{ errorMsg }}
      </div>

      <!-- width:max-content + min-width:100% evita que se corte el lado izquierdo al hacer zoom -->
      <div
        ref="paginas"
        class="flex flex-col items-center gap-3 p-3"
        style="width: max-content; min-width: 100%;"
      ></div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'
import * as pdfjsLib from 'pdfjs-dist'
// Con pdfjs-dist 3.x el worker es 'pdf.worker.min.js'; con 4.x/5.x es '.mjs'.
import workerUrl from 'pdfjs-dist/build/pdf.worker.min.mjs?url'

pdfjsLib.GlobalWorkerOptions.workerSrc = workerUrl

const props = defineProps({
  file:     { type: File, default: null },
  nombre:   { type: String, default: '' },
  ampliado: { type: Boolean, default: false },
})

defineEmits(['cerrar', 'toggle-ancho'])

// ─── Zoom (1 = 100% = ajustado al ancho del panel) ───
const ZOOM_MIN  = 0.25
const ZOOM_MAX  = 4
const ZOOM_PASO = 0.25

const zoom        = ref(1)
const numPaginas  = ref(0)
const cargando    = ref(false)
const errorMsg    = ref('')
const arrastrando = ref(false)

const contenedor = ref(null)
const paginas    = ref(null)

// pdf.js NO debe pasar por el proxy reactivo de Vue: se guarda como variable normal.
let pdfDoc = null
let loadingTask = null // en pdfjs-dist recientes se destruye la tarea, no el documento
let ancho1 = 1   // ancho de la página 1 a escala 1
let alto1  = 1   // alto de la página 1 a escala 1
let token  = 0   // invalida renders viejos cuando llega uno nuevo
let anchoRenderizado = 0
let timerZoom = null
let observer = null

const MARGEN = 24 // padding horizontal/vertical del contenedor

function limitar(v) {
  return Math.min(ZOOM_MAX, Math.max(ZOOM_MIN, Math.round(v * 100) / 100))
}

function cambiarZoom(delta) {
  zoom.value = limitar(zoom.value + delta)
}

function ajustarAncho() {
  zoom.value = 1
}

function ajustarPagina() {
  const cont = contenedor.value
  if (!cont || !alto1) return
  const fit = (cont.clientWidth - MARGEN) / ancho1
  zoom.value = limitar((cont.clientHeight - MARGEN) / (alto1 * fit))
}

// Libera la memoria de pdf.js sin que un error aquí rompa el desmontaje del componente
function liberarPdf() {
  try { loadingTask?.destroy() } catch (e) { /* se ignora */ }
  loadingTask = null
  pdfDoc = null
}

// Render que nunca deja una promesa sin atrapar (p. ej. si se cierra el visor a mitad de dibujo)
function renderizarSeguro() {
  renderizar().catch((e) => {
    if (e?.name !== 'RenderingCancelledException') console.warn('Render PDF:', e)
  })
}

// ─── Carga del PDF ───
async function cargar() {
  if (!props.file) return
  cargando.value = true
  errorMsg.value = ''
  try {
    const data = await props.file.arrayBuffer()
    liberarPdf()
    // verbosity ERRORS: oculta los "Warning: TT: ..." de fuentes TrueType con
    // instrucciones raras (inofensivos, el texto se dibuja igual)
    loadingTask = pdfjsLib.getDocument({
      data,
      verbosity: pdfjsLib.VerbosityLevel.ERRORS,
    })
    pdfDoc = await loadingTask.promise
    numPaginas.value = pdfDoc.numPages

    const p1 = await pdfDoc.getPage(1)
    const v = p1.getViewport({ scale: 1 })
    ancho1 = v.width
    alto1  = v.height

    paginas.value?.replaceChildren()
    zoom.value = 1
    await nextTick()
    await renderizar()
  } catch (e) {
    console.error('Error cargando PDF:', e)
    errorMsg.value = 'No se pudo mostrar el PDF. Revisa que el archivo no esté dañado.'
  } finally {
    cargando.value = false
  }
}

// ─── Render de todas las páginas en canvas ───
async function renderizar() {
  const cont = contenedor.value
  if (!pdfDoc || !paginas.value || !cont) return

  const miToken = ++token
  const fit   = (cont.clientWidth - MARGEN) / ancho1
  const scale = fit * zoom.value
  const dpr   = window.devicePixelRatio || 1
  anchoRenderizado = cont.clientWidth

  // Guardar posición relativa para mantener el "centro" al hacer zoom
  const hayContenido = paginas.value.children.length > 0
  const rx = cont.scrollWidth  ? (cont.scrollLeft + cont.clientWidth  / 2) / cont.scrollWidth  : 0
  const ry = cont.scrollHeight ? (cont.scrollTop  + cont.clientHeight / 2) / cont.scrollHeight : 0

  const nuevos = []
  for (let n = 1; n <= pdfDoc.numPages; n++) {
    const page = await pdfDoc.getPage(n)
    if (miToken !== token) return

    const vpCss = page.getViewport({ scale })
    const vpImg = page.getViewport({ scale: scale * dpr }) // nítido en pantallas HiDPI

    const canvas = document.createElement('canvas')
    canvas.width  = Math.floor(vpImg.width)
    canvas.height = Math.floor(vpImg.height)
    canvas.style.width  = `${Math.floor(vpCss.width)}px`
    canvas.style.height = `${Math.floor(vpCss.height)}px`
    canvas.className = 'bg-white shadow-md flex-shrink-0'

    await page.render({ canvasContext: canvas.getContext('2d'), viewport: vpImg }).promise
    if (miToken !== token) return

    if (hayContenido) {
      nuevos.push(canvas)          // re-render: se cambia todo junto (sin parpadeo)
    } else {
      paginas.value.appendChild(canvas) // primera carga: se ve página a página
    }
  }

  if (hayContenido) {
    paginas.value.replaceChildren(...nuevos)
    await nextTick()
    cont.scrollLeft = rx * cont.scrollWidth  - cont.clientWidth  / 2
    cont.scrollTop  = ry * cont.scrollHeight - cont.clientHeight / 2
  }
}

// Re-render con un pequeño retraso para no repetirlo en cada click de zoom
watch(zoom, () => {
  clearTimeout(timerZoom)
  timerZoom = setTimeout(renderizarSeguro, 150)
})

watch(() => props.file, () => cargar())

// ─── Ctrl + rueda = zoom (rueda sola = scroll normal) ───
function onWheel(e) {
  if (!e.ctrlKey) return
  e.preventDefault()
  cambiarZoom(e.deltaY < 0 ? ZOOM_PASO / 2 : -ZOOM_PASO / 2)
}

// ─── Mano: arrastrar para recorrer el documento ───
let inicioX = 0, inicioY = 0, scrollX0 = 0, scrollY0 = 0

function iniciarArrastre(e) {
  if (e.button !== 0) return
  const cont = contenedor.value
  if (!cont) return
  arrastrando.value = true
  inicioX  = e.clientX
  inicioY  = e.clientY
  scrollX0 = cont.scrollLeft
  scrollY0 = cont.scrollTop
  window.addEventListener('mousemove', moverArrastre)
  window.addEventListener('mouseup', terminarArrastre)
  e.preventDefault()
}

function moverArrastre(e) {
  const cont = contenedor.value
  if (!cont) return
  cont.scrollLeft = scrollX0 - (e.clientX - inicioX)
  cont.scrollTop  = scrollY0 - (e.clientY - inicioY)
}

function terminarArrastre() {
  arrastrando.value = false
  window.removeEventListener('mousemove', moverArrastre)
  window.removeEventListener('mouseup', terminarArrastre)
}

// ─── Si cambia el ancho del panel (ampliar/reducir, resize), se reajusta ───
onMounted(() => {
  cargar()
  observer = new ResizeObserver(() => {
    const cont = contenedor.value
    if (!cont || !pdfDoc) return
    if (Math.abs(cont.clientWidth - anchoRenderizado) < 4) return
    clearTimeout(timerZoom)
    timerZoom = setTimeout(renderizarSeguro, 200)
  })
  if (contenedor.value) observer.observe(contenedor.value)
})

onBeforeUnmount(() => {
  terminarArrastre()
  clearTimeout(timerZoom)
  observer?.disconnect()
  token++
  liberarPdf()
})
</script>