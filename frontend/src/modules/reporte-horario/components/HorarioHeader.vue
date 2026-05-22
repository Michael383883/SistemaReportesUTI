<template>
  <div class="relative rounded-xl border border-slate-700 bg-slate-800 overflow-hidden mb-6">
    <!-- Accent bar -->
    <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-amber-400 to-amber-600 rounded-l-xl"/>

    <div class="flex flex-col sm:flex-row sm:items-center gap-4 py-5 pr-6 pl-7">
      <!-- Icono -->
      <div
        class="w-14 h-14 rounded-xl bg-gradient-to-br from-amber-600 to-orange-500
               text-white text-xl flex items-center justify-center shrink-0"
      >
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
          <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/>
          <line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
          <line x1="9" y1="15" x2="15" y2="15"/>
        </svg>
      </div>

      <!-- Info -->
      <div class="flex-1 min-w-0">
        <div class="flex flex-wrap items-center gap-2 mb-1">
          <h2 class="text-base font-semibold text-slate-100 m-0 tracking-tight">
            Carga Horaria Docentes
          </h2>
          <span class="inline-flex items-center px-2 py-0.5 rounded text-[0.72rem] font-semibold bg-amber-500/15 text-amber-300">
            {{ horario.gestion }}
          </span>
        </div>

        <div class="flex flex-wrap gap-2 mt-2">
          <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white/[0.04] border border-slate-700 text-xs text-slate-400">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
              <circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
            {{ horario.docentes?.length ?? 0 }} docente{{ (horario.docentes?.length ?? 0) !== 1 ? 's' : '' }}
          </div>
          <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-amber-500/10 border border-amber-500/20 text-xs text-amber-400 font-semibold">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
            </svg>
            {{ totalMaterias }} materia{{ totalMaterias !== 1 ? 's' : '' }} en total
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  horario: { type: Object, required: true },
})

const totalMaterias = computed(() =>
  (props.horario.docentes || []).reduce((sum, d) => sum + (d.materias?.length ?? 0), 0)
)
</script>