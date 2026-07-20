<template>
  <div class="min-h-screen bg-slate-100 pb-12">
    <div class="flex items-start justify-between mb-3">
      <h1 class="text-xl font-bold text-black-400 tracking-tight m-0 mb-0.5">
        Reportes de horarios resumen Segunda versión
      </h1>
    </div>

    <ReporteToolbar
      v-model:anio="anio" v-model:periodo="periodo" v-model:busqueda="busqueda"
      :loading="loading" @buscar="ejecutarBusqueda"
    >
      <template #pdf>
        <PdfMenuButton :disabled="loading || docentes.length === 0">
          <template #default="{ cerrar }">
            <PdfMenuItem icon="ver" titulo="Ver PDF" subtitulo="Abre en nueva pestaña"
              @click="generarPDF('ver'); cerrar()" />
            <div class="border-t border-slate-100"></div>
            <PdfMenuItem icon="descargar" titulo="Descargar PDF" subtitulo="Guarda en tu equipo"
              @click="generarPDF('descargar'); cerrar()" />
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

      <ResumenDocenteCardDos v-for="doc in docentes" :key="doc.docente" :docente="doc" />

      <div class="text-center text-slate-400 text-xs mt-8 pt-4 border-t border-slate-200">
        Procesado UTI – Facultad de Ciencias Económicas · Los inscritos incluyen ambos grupos de materias compartidas.
      </div>
    </div>
  </div>
</template>

<script setup>
import ReporteToolbar from '../components/ReporteToolbar.vue'
import PdfMenuButton from '../components/PdfMenuButton.vue'
import PdfMenuItem from '../components/PdfMenuItem.vue'
import ReporteEstados from '../components/ReporteEstados.vue'
import ResumenDocenteCardDos from '../components/Resumendocentecarddos.vue'

import { useHorarioResumen } from '../composables/useHorarioResumen'
import { useReporteHorario } from '../composables/useReporteHorario'
import { generarPDFResumenDos } from '../composables/usepdf2prueba'
import { usePeriodoActual } from '../composables/usePeriodoActual'

const { anio, periodo } = usePeriodoActual()

const {
  docentes, loading, error,
  busqueda, terminoBuscado, fechaActual,
  ejecutarBusqueda,
} = useReporteHorario(useHorarioResumen(), { anio, periodo })

async function generarPDF(modo = 'descargar') {
  await generarPDFResumenDos(docentes.value, { anio: anio.value, periodo: periodo.value, modo })
}
</script>