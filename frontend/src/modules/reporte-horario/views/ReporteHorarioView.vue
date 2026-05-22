<template>
  <div class="px-9 py-8 max-w-7xl">

    <!-- Header de página -->
    <div class="flex items-start justify-between mb-7">
      <div>
        <h1 class="text-2xl font-bold text-slate-100 tracking-tight m-0 mb-1">Reporte de Carga Horaria</h1>
        <p class="text-xs text-slate-400 m-0">Carga horaria docentes registrada en el SISS – gestión actual</p>
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

    <!-- Filtros siempre visibles -->
    <div class="mb-5">
      <HorarioFiltros
        v-model:docente="docenteFiltro"
        :loading="loading"
        :horario="horario"
        @generar="onGenerar"
      />
    </div>

    <!-- Loading skeleton -->
    <div v-if="loading" class="space-y-3 animate-pulse">
      <div class="h-24 rounded-xl bg-slate-800 border border-slate-700"/>
      <div class="h-10 rounded-lg bg-slate-800/60 border border-slate-700/50"/>
      <div class="h-96 rounded-xl bg-slate-800 border border-slate-700"/>
    </div>

    <!-- Horario cargado -->
    <template v-else-if="horario">
      <!-- Header resumen -->
      <HorarioHeader :horario="horario" />

      <!-- Tabla -->
      <HorarioTabla :docentes="horario.docentes || []" />
    </template>

    <!-- Empty state inicial -->
    <div v-else-if="!loading && !error" class="flex flex-col items-center justify-center py-20 text-center text-slate-400">
      <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" class="mb-3">
        <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/>
        <line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
      </svg>
      <p class="text-sm font-medium">No hay datos cargados</p>
      <p class="text-xs mt-1">Presioná <strong class="text-slate-300">Generar</strong> para cargar la carga horaria actual</p>
    </div>

  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useHorario } from '../composables/useHorario'
import HorarioHeader  from '../components/HorarioHeader.vue'
import HorarioFiltros from '../components/HorarioFiltros.vue'
import HorarioTabla   from '../components/HorarioTabla.vue'

const { horario, loading, error, generarHorario } = useHorario()

const docenteFiltro = ref(null)

// Al montar carga todos los docentes automáticamente
onMounted(async () => {
  await generarHorario(null)
})

const onGenerar = async ({ docente }) => {
  docenteFiltro.value = docente
  await generarHorario(docente)
}
</script>