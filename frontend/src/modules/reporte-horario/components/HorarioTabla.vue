<template>
  <div class="rounded-xl border border-slate-700 bg-slate-800 overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm border-collapse">
        <thead>
          <tr class="border-b border-slate-700 bg-slate-900/60">
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400 w-44">Docente / Plan</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400">Materia</th>
            <th class="text-center px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400 w-16">GRP</th>
            <th class="text-center px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400 w-14">CH</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400 w-52">Compartido</th>
          </tr>
        </thead>
        <tbody>
          <template v-for="(doc, di) in docentes" :key="doc.codigo">

            <!-- Fila cabecera del docente -->
            <tr class="bg-slate-700/40 border-b border-slate-600">
              <td colspan="5" class="px-4 py-2">
                <div class="flex items-center gap-3">
                  <!-- Avatar -->
                  <div
                    class="w-7 h-7 rounded-lg bg-gradient-to-br from-blue-700 to-violet-600
                           text-white text-[0.65rem] font-bold flex items-center justify-center shrink-0"
                  >
                    {{ initials(doc.nombre) }}
                  </div>
                  <span class="text-slate-100 font-semibold text-sm tracking-tight">
                    {{ doc.codigo }} &nbsp;{{ doc.nombre }}
                  </span>
                </div>
              </td>
            </tr>

            <!-- Filas de materias -->
            <tr
              v-for="(m, mi) in doc.materias"
              :key="`${doc.codigo}-${mi}`"
              class="border-b border-slate-700/50 transition-colors hover:bg-white/[0.025]"
              :class="mi % 2 === 0 ? 'bg-transparent' : 'bg-slate-900/20'"
            >
              <!-- Plan -->
              <td class="px-4 py-2.5">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[0.68rem] font-bold bg-slate-700/60 text-slate-300 tracking-wide">
                  {{ m.plan }}
                </span>
              </td>

              <!-- Materia -->
              <td class="px-4 py-2.5 text-slate-200 font-medium text-xs">{{ m.materia }}</td>

              <!-- GRP -->
              <td class="px-4 py-2.5 text-center tabular-nums text-slate-300 font-semibold text-xs">{{ m.grp }}</td>

              <!-- CH -->
              <td class="px-4 py-2.5 text-center tabular-nums text-slate-300 text-xs">{{ m.ch }}</td>

              <!-- Compartido -->
              <td class="px-4 py-2.5">
                <template v-if="m.compartido">
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[0.68rem] font-semibold bg-violet-500/15 text-violet-300">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                      <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/>
                      <polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/>
                    </svg>
                    {{ m.compartido }}
                  </span>
                </template>
                <span v-else class="text-slate-600 text-xs">—</span>
              </td>
            </tr>

            <!-- Fila TOTAL -->
            <tr class="border-b border-slate-600 bg-slate-900/30">
              <td class="px-4 py-2" colspan="2"/>
              <td class="px-4 py-2 text-right text-[0.7rem] font-bold text-slate-400 uppercase tracking-widest">TOTAL</td>
              <td class="px-4 py-2 text-center font-bold text-amber-400 tabular-nums text-sm">{{ doc.total_ch }}</td>
              <td class="px-4 py-2"/>
            </tr>

          </template>
        </tbody>
      </table>
    </div>

    <!-- Footer -->
    <div class="px-4 py-2.5 border-t border-slate-700 bg-slate-900/30 text-xs text-slate-500 flex items-center justify-between">
      <span>{{ docentes.length }} docente{{ docentes.length !== 1 ? 's' : '' }}</span>
      <span>{{ totalMaterias }} materia{{ totalMaterias !== 1 ? 's' : '' }}</span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  docentes: { type: Array, default: () => [] },
})

const totalMaterias = computed(() =>
  props.docentes.reduce((sum, d) => sum + (d.materias?.length ?? 0), 0)
)

const initials = (nombre = '') => {
  const parts = nombre.trim().split(/\s+/)
  if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase()
  return nombre.slice(0, 2).toUpperCase()
}
</script>