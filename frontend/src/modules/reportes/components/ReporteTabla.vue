<template>
  <div class="rounded-xl border border-slate-700 bg-slate-800 overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm border-collapse">
        <thead>
          <tr class="border-b border-slate-700 bg-slate-900/60">
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400 w-10">Nº</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400">Gestión</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400 w-16">Plan</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400">Materia</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400 w-28">Compartido</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400 w-14">GRP</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400">Resolución</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400">Designación</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="(m, i) in materias"
            :key="m.nro"
            class="border-b border-slate-700/60 transition-colors hover:bg-white/[0.025]"
            :class="i % 2 === 0 ? 'bg-transparent' : 'bg-slate-900/20'"
          >
            <!-- Nº -->
            <td class="px-4 py-3 text-slate-500 font-medium text-xs tabular-nums">{{ m.nro }}</td>

            <!-- Gestión -->
            <td class="px-4 py-3">
              <span
                class="inline-flex items-center gap-1.5 text-xs font-semibold px-2 py-0.5 rounded"
                :class="tipoGestion(m.gestion).class"
              >
                <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="tipoGestion(m.gestion).dot"/>
                {{ m.gestion }}
              </span>
            </td>

            <!-- Plan -->
            <td class="px-4 py-3">
              <span class="inline-flex items-center px-2 py-0.5 rounded text-[0.68rem] font-bold bg-slate-700/60 text-slate-300 tracking-wide">
                {{ m.plan }}
              </span>
            </td>

            <!-- Materia -->
            <td class="px-4 py-3 text-slate-200 font-medium">{{ m.materia }}</td>

            <!-- Compartido -->
            <td class="px-4 py-3">
              <span
                v-if="m.compartido"
                class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[0.68rem] font-semibold bg-violet-500/15 text-violet-300"
              >
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/>
                  <polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/>
                </svg>
                Compartido
              </span>
              <span v-else class="text-slate-600 text-xs">—</span>
            </td>

            <!-- GRP -->
            <td class="px-4 py-3 tabular-nums text-slate-300 font-semibold text-xs">{{ m.grp }}</td>

            <!-- Resolución -->
            <td class="px-4 py-3">
              <span v-if="m.resolucion" class="text-xs text-emerald-400 font-medium">{{ m.resolucion }}</span>
              <span v-else class="text-slate-600 text-xs">—</span>
            </td>

            <!-- Designación -->
            <td class="px-4 py-3 max-w-xs">
              <span v-if="m.designacion" class="text-xs text-slate-400 leading-relaxed line-clamp-2" :title="m.designacion">
                {{ m.designacion }}
              </span>
              <span v-else class="text-slate-600 text-xs">—</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Footer -->
    <div class="px-4 py-2.5 border-t border-slate-700 bg-slate-900/30 text-xs text-slate-500 text-right">
      {{ materias.length }} registro{{ materias.length !== 1 ? 's' : '' }}
    </div>
  </div>
</template>

<script setup>
defineProps({
  materias: { type: Array, default: () => [] },
})

const tipoGestion = (gestion) => {
  if (gestion?.includes('Verano'))
    return { class: 'bg-orange-500/10 text-orange-400', dot: 'bg-orange-400' }
  if (gestion?.includes('Invierno'))
    return { class: 'bg-sky-500/10 text-sky-400', dot: 'bg-sky-400' }
  return { class: 'bg-slate-700/60 text-slate-300', dot: 'bg-slate-400' }
}
</script>