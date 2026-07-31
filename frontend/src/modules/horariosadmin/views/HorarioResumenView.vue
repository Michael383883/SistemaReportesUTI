<template>
  <div class="min-h-screen bg-slate-100 pb-12">
    <div class="flex items-start justify-between mb-3">
      <h1 class="text-xl font-bold text-black-400 tracking-tight m-0 mb-0.5">
        Reportes de horarios resumen
      </h1>
    </div>

    <ReporteToolbar
      v-model:anio="anio" v-model:periodo="periodo" v-model:busqueda="busqueda"
      :loading="loading" @buscar="ejecutarBusqueda"
    >
      <template #pdf>
        <PdfMenuButton :disabled="loadingPdf || docentes.length === 0" :loading="loadingPdf" menu-width="w-64">
          <template #default="{ cerrar }">

            <p class="px-4 pt-3 pb-1.5 text-[0.8rem] font-semibold text-slate-800">
              Reporte resumen
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
                @click="generarPDF('ver'); cerrar()"
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
                @click="generarPDF('imprimir'); cerrar()"
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
                @click="generarPDF('descargar'); cerrar()"
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
      loadingLabel="Cargando resumen..."
      emptyLabel="Busca un docente o carga todos para ver el resumen."
    />

    <div v-if="!loading && !error && docentes.length > 0" id="reporte-imprimible" class="px-8 py-4">
      <div class="bg-white border border-slate-200 rounded-lg px-5 py-2.5 flex justify-between items-center mb-3 text-sm text-slate-500">
        <span>
          Generado: <strong class="text-slate-700">{{ fechaActual }}</strong>
          <em class="text-slate-400 ml-2">"La carga horaria incluye Grupos Compartidos."</em>
        </span>
        <span class="text-slate-300 mx-2">|</span>
        <span>Total docentes: <strong class="text-slate-700">{{ docentes.length }}</strong></span>
      </div>

      <ResumenDocenteCard v-for="doc in docentes" :key="doc.docente" :docente="doc" />

      <div class="text-center text-slate-400 text-xs mt-8 pt-4 border-t border-slate-200">
        Procesado UTI – Facultad de Ciencias Económicas · Los inscritos incluyen ambos grupos de materias compartidas.
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import ReporteToolbar from '../components/ReporteToolbar.vue'
import PdfMenuButton from '../components/PdfMenuButton.vue'
import ReporteEstados from '../components/ReporteEstados.vue'
import ResumenDocenteCard from '../components/ResumenDocenteCard.vue'

import { useHorarioResumen } from '../composables/useHorarioResumen'
import { useReporteHorario } from '../composables/useReporteHorario'
import { generarPDFResumen } from '../composables/useGenerarPDFResumen'
import { usePeriodoActual } from '../composables/usePeriodoActual'

const { anio, periodo } = usePeriodoActual()

const {
  docentes, loading, error,
  busqueda, terminoBuscado, fechaActual,
  ejecutarBusqueda,
} = useReporteHorario(useHorarioResumen(), { anio, periodo })

const loadingPdf = ref(false)

async function generarPDF(modo = 'descargar') {
  loadingPdf.value = true
  try {
    await generarPDFResumen(docentes.value, { anio: anio.value, periodo: periodo.value, modo })
  } catch (err) {
    console.error(err)
  } finally {
    loadingPdf.value = false
  }
}
</script>