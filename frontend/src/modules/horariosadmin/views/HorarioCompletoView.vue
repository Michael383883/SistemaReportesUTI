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
            <p class="px-4 pt-3 pb-1 text-[0.65rem] font-semibold tracking-widest uppercase text-slate-400">
              Formato clásico
            </p>
            <PdfMenuItem icon="ver" titulo="Ver PDF" subtitulo="Abrir en nueva pestaña"
              @click="generarPDFCompleto('ver'); cerrar()" />
            <PdfMenuItem icon="descargar" titulo="Descargar PDF" subtitulo="Guardar en tu equipo"
              @click="generarPDFCompleto('descargar'); cerrar()" />

            <div class="border-t border-slate-100 mx-4"></div>

            <p class="px-4 pt-3 pb-1 text-[0.65rem] font-semibold tracking-widest uppercase text-slate-400">
              Formato nuevo
            </p>
            <PdfMenuItem icon="ver" titulo="Ver PDF" subtitulo="Abrir en nueva pestaña"
              @click="generarPDFResumen('ver'); cerrar()" />
            <PdfMenuItem icon="descargar" titulo="Descargar PDF" subtitulo="Guardar en tu equipo"
              @click="generarPDFResumen('descargar'); cerrar()" />
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
import PdfMenuItem from '../components/PdfMenuItem.vue'
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
</script>