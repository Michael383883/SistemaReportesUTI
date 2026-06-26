<template>
  <div class="relative rounded-xl border border-slate-700 bg-slate-800 overflow-hidden mb-6">
    <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-amber-400 to-amber-600 rounded-l-xl"/>
    
    <div class="flex items-center gap-4 py-4 pr-6 pl-7">
      <h2 class="text-base font-semibold text-slate-100 m-0 tracking-tight whitespace-nowrap">
        {{ reporte.docente?.nombre }}
      </h2>

      <span class="inline-flex items-center px-2 py-0.5 rounded text-[0.72rem] font-semibold bg-indigo-500/15 text-indigo-300 whitespace-nowrap">
        SIS: {{ reporte.docente?.codigo }}
      </span>

      <!-- Desde: calculado del mínimo ANIO en materias -->
      <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white/[0.04] border border-slate-700 text-xs text-slate-400 whitespace-nowrap">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/>
          <line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
        </svg>
        Desde {{ anioDesde }}
      </div>

      <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-amber-500/10 border border-amber-500/20 text-xs text-amber-400 font-semibold whitespace-nowrap">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
        </svg>
        {{ reporte.total }} materia{{ reporte.total !== 1 ? 's' : '' }}
      </div>

      <div class="flex-1"/>

      <button
        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium border border-slate-700 text-slate-400 bg-transparent hover:bg-white/5 hover:text-slate-200 transition-all duration-150 cursor-pointer whitespace-nowrap"
        @click="$emit('volver')"
      >
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <polyline points="15 18 9 12 15 6"/>
        </svg>
        Volver
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  reporte: { type: Object, required: true },
})
defineEmits(['volver'])

const anioDesde = computed(() => {
  const materias = props.reporte.materias || []
  if (!materias.length) return '—'
  return materias.reduce((min, m) => {
    const anio = parseInt(m.ANIO)
    return anio < min ? anio : min
  }, Infinity)
})
</script>