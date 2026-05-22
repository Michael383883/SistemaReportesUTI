<template>
  <div class="px-9 py-0 max-w-6xl00">

    <!-- Header de página -->
    <div class="flex items-start justify-between mb-7">
      <div>
        <h1 class="text-2xl font-bold text-slate-100 tracking-tight m-0 mb-1">Reporte de Docente</h1>
        <p class="text-xs text-slate-400 m-0">Materias dictadas registradas en el SISS a partir de 2001</p>
      </div>
    </div>

    <!-- Error -->
    <div
      v-if="error"
      class="flex items-center gap-2 px-3.5 py-2.5 bg-red-500/10 border border-red-500/20 rounded-lg text-red-400 text-sm mb-5"
    >
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
      </svg>
      {{ error }}
    </div>

    <!-- Loading skeleton -->
    <div v-if="loading" class="space-y-3 animate-pulse">
      <div class="h-24 rounded-xl bg-slate-800 border border-slate-700"/>
      <div class="h-10 rounded-lg bg-slate-800/60 border border-slate-700/50"/>
      <div class="h-64 rounded-xl bg-slate-800 border border-slate-700"/>
    </div>

    <!-- Reporte cargado -->
    <template v-else-if="reporte">
      <!-- Header del docente -->
      <ReporteHeader :reporte="reporte" @volver="$router.back()" />

      <!-- Filtros — se pasa :reporte para que el botón PDF tenga acceso a los datos -->
      <div class="mb-5">
        <ReporteFiltros
          v-model:anio="anioFiltro"
          v-model:materia="materiaFiltro"   
          v-model:grupo="grupoFiltro"       
          :loading="loading"
          :reporte="reporte"
          @generar="reGenerar"
        />
      </div>

      <!-- Tabla -->
      <ReporteTabla :materias="reporte.materias" />
    </template>

    <!-- Empty -->
    <div v-else-if="!loading && !error" class="flex flex-col items-center justify-center py-20 text-center text-slate-400">
      <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" class="mb-3">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
        <polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/>
        <line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>
      </svg>
      <p class="text-sm font-medium">No hay reporte disponible</p>
      <p class="text-xs mt-1">Seleccioná un docente desde la lista</p>
    </div>

  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useReporte } from '../composables/useReporte'
import ReporteHeader  from '../components/ReporteHeader.vue'
import ReporteFiltros from '../components/ReporteFiltros.vue'
import ReporteTabla   from '../components/ReporteTabla.vue'

const route  = useRoute()
const router = useRouter()

const { reporte, loading, error, generarReporte } = useReporte()

const anioFiltro = ref(null)
const materiaFiltro = ref(null)   // AÑADIR
const grupoFiltro   = ref(null)   // AÑADIR


onMounted(async () => {
  const codigo = route.query.codigo
  const anio   = route.query.anio ? Number(route.query.anio) : null
  if (!codigo) { router.replace({ name: 'docentes' }); return }
  anioFiltro.value = anio
  await generarReporte(codigo, anio)
})

const reGenerar = async ({ anio, materia, grupo }) => {
  const codigo = route.query.codigo
  if (!codigo) return

  router.replace({
    query: {
      codigo,
      ...(anio    ? { anio }    : {}),
      ...(materia ? { materia } : {}),
      ...(grupo   ? { grupo }   : {}),
    }
  })

  await generarReporte(codigo, anio, materia, grupo)  // AÑADIR materia, grupo
}

</script>