<template>
  <div class="min-h-screen bg-slate-100 pb-12">
    <div class="flex items-start justify-between mb-3">
      <h1 class="text-xl font-bold text-black-400 tracking-tight m-0 mb-0.5">
        Reportes de horarios completo
      </h1>
    </div>

    <ReporteToolbar
      v-model:anio="anio" v-model:periodo="periodo" v-model:busqueda="busqueda"
      :loading="loading" @buscar="ejecutarBusqueda"
    >
      <template #pdf>
        <PdfMenuButton :disabled="loadingPdf || docentes.length === 0" :loading="loadingPdf" menu-width="w-64">
          <template #default="{ cerrar }">

            <div v-for="(sec, idx) in seccionesPdf" :key="sec.titulo">
              <div v-if="idx > 0" class="border-t border-slate-100 mx-4"></div>

              <p class="px-4 pt-3 pb-1.5 text-[0.8rem] font-semibold text-slate-800">
                {{ sec.titulo }}
              </p>

              <div class="px-4 pb-3 flex items-center gap-2">
                <button
                  v-for="b in botonesMenu"
                  :key="b.accion"
                  class="flex-1 flex items-center justify-center h-9 rounded-lg border transition-colors duration-100 cursor-pointer outline-none"
                  :class="b.clase"
                  :title="b.title"
                  @click="sec.ejecutar(b.accion); cerrar()"
                >
                  <svg v-if="b.accion === 'ver'" xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/>
                    <circle cx="12" cy="12" r="3"/>
                  </svg>
                  <svg v-else-if="b.accion === 'imprimir'" xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 6 2 18 2 18 9"/>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                    <rect x="6" y="14" width="12" height="8"/>
                  </svg>
                  <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                  </svg>
                </button>
              </div>
            </div>

          </template>
        </PdfMenuButton>
      </template>
    </ReporteToolbar>

    <ReporteEstados
      :error="error" :loading="loading" :total="docentes.length" :terminoBuscado="terminoBuscado"
      loadingLabel="Cargando horarios..."
      emptyLabel="Busca un docente o carga todos para ver los horarios."
    />

    <div v-if="!loading && !error && docentes.length > 0" id="reporte-imprimible" class="px-8 py-4">
      <div class="bg-white border border-slate-200 rounded-lg px-5 py-2.5 flex justify-between items-center mb-4 text-sm text-slate-500">
        <span>Generado: <strong class="text-slate-700">{{ fechaActual }}</strong></span>
        <em class="text-slate-400 ml-2">"La carga horaria incluye Grupos Compartidos."</em>
        <span class="text-slate-300 mx-2">|</span>
        <span>Total docentes: <strong class="text-slate-700">{{ docentes.length }}</strong></span>
      </div>

      <HorarioDocenteCard v-for="doc in docentes" :key="doc.docente" :docente="doc" />

      <div class="text-center text-slate-400 text-xs mt-8 pt-4 border-t border-slate-200">
        Procesado UTI – Facultad de Ciencias Económicas · La carga horaria incluye Grupos Compartidos.
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import ReporteToolbar from '../components/ReporteToolbar.vue'
import PdfMenuButton from '../components/PdfMenuButton.vue'
import ReporteEstados from '../components/ReporteEstados.vue'
import HorarioDocenteCard from '../components/HorarioDocenteCard.vue'

import { useHorarioAdmin } from '../composables/useHorarioAdmin'
import { useReporteHorario } from '../composables/useReporteHorario'
import { generarPDFCargaHoraria } from '../composables/useGenerarPDFCargaHoraria'
import { generarPDFResumenDos } from '../composables/usePdfResumenDos'
import { usePeriodoActual } from '../composables/usePeriodoActual'

const route = useRoute()

const { anio, periodo } = usePeriodoActual()

const {
  docentes, loading, error,
  busqueda, terminoBuscado, fechaActual,
  ejecutarBusqueda,
} = useReporteHorario(useHorarioAdmin(), { anio, periodo })

const loadingPdf = ref(false)

async function ejecutarPDF(generador, modo = 'descargar') {
  loadingPdf.value = true
  try {
    const { url, filename } = generador()
    if (modo === 'ver') {
      window.open(url, '_blank')
      setTimeout(() => URL.revokeObjectURL(url), 60_000)
    } else {
      const a = document.createElement('a')
      a.href = url
      a.download = filename
      document.body.appendChild(a)
      a.click()
      document.body.removeChild(a)
      URL.revokeObjectURL(url)
    }
  } catch (err) {
    console.error(err)
  } finally {
    loadingPdf.value = false
  }
}

// Imprime el PDF usando un iframe oculto
async function imprimirPDF(generador) {
  loadingPdf.value = true
  try {
    const { url } = generador()

    const iframe = document.createElement('iframe')
    iframe.style.position = 'fixed'
    iframe.style.right = '0'
    iframe.style.bottom = '0'
    iframe.style.width = '0'
    iframe.style.height = '0'
    iframe.style.border = '0'
    iframe.src = url

    iframe.onload = () => {
      try {
        iframe.contentWindow.focus()
        iframe.contentWindow.print()
      } catch (e) {
        console.error('No se pudo imprimir automáticamente', e)
        window.open(url, '_blank')
      }
    }

    document.body.appendChild(iframe)

    setTimeout(() => {
      document.body.removeChild(iframe)
      URL.revokeObjectURL(url)
    }, 60_000)
  } catch (err) {
    console.error(err)
  } finally {
    loadingPdf.value = false
  }
}

const generarCompleto = () =>
  generarPDFCargaHoraria(docentes.value, { anio: anio.value, periodo: periodo.value })

const generarResumen = () =>
  generarPDFResumenDos(docentes.value, { anio: anio.value, periodo: periodo.value })

async function generarPDFCompleto(modo = 'descargar') {
  await ejecutarPDF(generarCompleto, modo)
}

async function generarPDFResumen(modo = 'descargar') {
  await ejecutarPDF(generarResumen, modo)
}

async function imprimirPDFCompleto() {
  await imprimirPDF(generarCompleto)
}

async function imprimirPDFResumen() {
  await imprimirPDF(generarResumen)
}

// ─── Menú: dos secciones × 3 botones (Ver / Imprimir / Descargar) ───
const botonesMenu = [
  { accion: 'ver', title: 'Ver PDF',
    clase: 'text-slate-600 bg-slate-100 border-slate-200 hover:bg-slate-200 hover:text-slate-800 active:bg-slate-300' },
  { accion: 'imprimir', title: 'Imprimir',
    clase: 'text-slate-600 bg-slate-100 border-slate-200 hover:bg-slate-200 hover:text-slate-800 active:bg-slate-300' },
  { accion: 'descargar', title: 'Descargar PDF',
    clase: 'text-amber-700 bg-amber-100 border-amber-300 hover:bg-amber-200 hover:text-amber-800 active:bg-amber-300' },
]

const seccionesPdf = [
  {
    titulo: 'Reporte estándar',
    ejecutar: (accion) => accion === 'imprimir' ? imprimirPDFCompleto() : generarPDFCompleto(accion),
  },
  {
    titulo: 'Reporte nuevo formato',
    ejecutar: (accion) => accion === 'imprimir' ? imprimirPDFResumen() : generarPDFResumen(accion),
  },
]

// ─── Llegó desde el menú del docente: autocompleta y busca ───
// URL: ?buscar=APELLIDOS NOMBRES&anio=2026&periodo=2
onMounted(async () => {
  if (!route.query.buscar) return

  if (route.query.anio)    anio.value = Number(route.query.anio)
  if (route.query.periodo) periodo.value = Number(route.query.periodo)
  busqueda.value = String(route.query.buscar)

  await ejecutarBusqueda()
})
</script>