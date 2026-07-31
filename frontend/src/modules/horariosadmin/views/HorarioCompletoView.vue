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

            <!-- ===== Formato clásico ===== -->
            <p class="px-4 pt-3 pb-1.5 text-[0.8rem] font-semibold text-slate-800">
              Reporte estándar
            </p>
            <div class="px-4 pb-3 flex items-center gap-2">
              <!-- Ver -->
              <button
                class="
                  flex-1 flex items-center justify-center h-9 rounded-lg
                  text-slate-600 bg-slate-100 border border-slate-200
                  hover:bg-slate-200 hover:text-slate-800
                  active:bg-slate-300
                  transition-colors duration-100 cursor-pointer outline-none
                "
                title="Ver PDF"
                @click="generarPDFCompleto('ver'); cerrar()"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/>
                  <circle cx="12" cy="12" r="3"/>
                </svg>
              </button>

              <!-- Imprimir -->
              <button
                class="
                  flex-1 flex items-center justify-center h-9 rounded-lg
                  text-slate-600 bg-slate-100 border border-slate-200
                  hover:bg-slate-200 hover:text-slate-800
                  active:bg-slate-300
                  transition-colors duration-100 cursor-pointer outline-none
                "
                title="Imprimir"
                @click="imprimirPDFCompleto(); cerrar()"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="6 9 6 2 18 2 18 9"/>
                  <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                  <rect x="6" y="14" width="12" height="8"/>
                </svg>
              </button>

              <!-- Descargar -->
              <button
                class="
                  flex-1 flex items-center justify-center h-9 rounded-lg
                  text-amber-700 bg-amber-100 border border-amber-300
                  hover:bg-amber-200 hover:text-amber-800
                  active:bg-amber-300
                  transition-colors duration-100 cursor-pointer outline-none
                "
                title="Descargar PDF"
                @click="generarPDFCompleto('descargar'); cerrar()"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                  <polyline points="7 10 12 15 17 10"/>
                  <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
              </button>
            </div>

            <div class="border-t border-slate-100 mx-4"></div>

            <!-- ===== Formato nuevo ===== -->
            <p class="px-4 pt-3 pb-1.5 text-[0.8rem] font-semibold text-slate-800">
              Reporte nuevo formato
            </p>
            <div class="px-4 pb-3 flex items-center gap-2">
              <!-- Ver -->
              <button
                class="
                  flex-1 flex items-center justify-center h-9 rounded-lg
                  text-slate-600 bg-slate-100 border border-slate-200
                  hover:bg-slate-200 hover:text-slate-800
                  active:bg-slate-300
                  transition-colors duration-100 cursor-pointer outline-none
                "
                title="Ver PDF"
                @click="generarPDFResumen('ver'); cerrar()"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/>
                  <circle cx="12" cy="12" r="3"/>
                </svg>
              </button>

              <!-- Imprimir -->
              <button
                class="
                  flex-1 flex items-center justify-center h-9 rounded-lg
                  text-slate-600 bg-slate-100 border border-slate-200
                  hover:bg-slate-200 hover:text-slate-800
                  active:bg-slate-300
                  transition-colors duration-100 cursor-pointer outline-none
                "
                title="Imprimir"
                @click="imprimirPDFResumen(); cerrar()"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="6 9 6 2 18 2 18 9"/>
                  <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                  <rect x="6" y="14" width="12" height="8"/>
                </svg>
              </button>

              <!-- Descargar -->
              <button
                class="
                  flex-1 flex items-center justify-center h-9 rounded-lg
                  text-amber-700 bg-amber-100 border border-amber-300
                  hover:bg-amber-200 hover:text-amber-800
                  active:bg-amber-300
                  transition-colors duration-100 cursor-pointer outline-none
                "
                title="Descargar PDF"
                @click="generarPDFResumen('descargar'); cerrar()"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                  <polyline points="7 10 12 15 17 10"/>
                  <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
              </button>
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
import { ref } from 'vue'
import ReporteToolbar from '../components/ReporteToolbar.vue'
import PdfMenuButton from '../components/PdfMenuButton.vue'
import ReporteEstados from '../components/ReporteEstados.vue'
import HorarioDocenteCard from '../components/HorarioDocenteCard.vue'

import { useHorarioAdmin } from '../composables/useHorarioAdmin'
import { useReporteHorario } from '../composables/useReporteHorario'
import { generarPDFCargaHoraria } from '../composables/useGenerarPDFCargaHoraria'
import { generarPDFResumenDos } from '../composables/usePdfResumenDos'
import { usePeriodoActual } from '../composables/usePeriodoActual'

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

// Imprime el PDF usando un iframe oculto (más confiable que window.open + print,
// porque el visor de PDF del navegador no siempre dispara 'onload' en la pestaña)
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
        // Fallback: si el navegador bloquea el print del iframe, abrimos el PDF
        window.open(url, '_blank')
      }
    }

    document.body.appendChild(iframe)

    // Limpiamos el iframe y el blob URL después de un tiempo prudente
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

async function generarPDFCompleto(modo = 'descargar') {
  await ejecutarPDF(
    () => generarPDFCargaHoraria(docentes.value, { anio: anio.value, periodo: periodo.value }),
    modo
  )
}

async function generarPDFResumen(modo = 'descargar') {
  await ejecutarPDF(
    () => generarPDFResumenDos(docentes.value, { anio: anio.value, periodo: periodo.value }),
    modo
  )
}

async function imprimirPDFCompleto() {
  await imprimirPDF(() => generarPDFCargaHoraria(docentes.value, { anio: anio.value, periodo: periodo.value }))
}

async function imprimirPDFResumen() {
  await imprimirPDF(() => generarPDFResumenDos(docentes.value, { anio: anio.value, periodo: periodo.value }))
}
</script>