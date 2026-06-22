<template>
  <div class="rounded-xl border border-slate-700 bg-slate-800 overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-700 flex items-center justify-between">
      <div>
        <h3 class="text-sm font-semibold text-slate-100 m-0">3. Confirmá la asignación</h3>
        <p class="text-xs text-slate-400 m-0 mt-0.5">Materias marcadas para esta resolución</p>
      </div>
      <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/20">
        {{ materias.length }} materia{{ materias.length !== 1 ? 's' : '' }}
      </span>
    </div>

    <div v-if="materias.length === 0" class="flex flex-col items-center gap-2 py-10 text-slate-500">
      <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path d="M9 17v-2m3 2v-4m3 4v-6M3 3h18M3 8h18M3 13h9"/>
      </svg>
      <p class="text-sm">Todavía no marcaste ninguna materia.</p>
    </div>

    <div v-else class="overflow-x-auto">
      <table class="w-full text-xs">
        <thead class="bg-slate-900/40 border-b border-slate-700">
          <tr>
            <th class="px-4 py-2.5 text-left text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400">Docente</th>
            <th class="px-4 py-2.5 text-left text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400">Plan</th>
            <th class="px-4 py-2.5 text-left text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400">Materia</th>
            <th class="px-4 py-2.5 text-left text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400">Grupo</th>
            <th class="px-4 py-2.5 text-left text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400">Observación</th>
            <th class="px-4 py-2.5 text-center text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400">Quitar</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-700/60">
          <tr v-for="m in materias" :key="m.key" class="hover:bg-white/[0.025] transition-colors">
            <td class="px-4 py-2.5 text-slate-200 font-medium">
              {{ m.docente.nombres ?? m.docente.NOMBRES }} {{ m.docente.apellidos ?? m.docente.APELLIDOS }}
            </td>
            <td class="px-4 py-2.5 text-slate-400 font-mono">{{ m.cod_plan }}</td>
            <td class="px-4 py-2.5 text-slate-300 font-mono">{{ m.cod_materia }}</td>
            <td class="px-4 py-2.5 text-slate-400">{{ m.grupo || '—' }}</td>
            <td class="px-4 py-2.5">
              <span v-if="m.observacion" class="inline-flex items-center px-2 py-0.5 rounded-full text-[0.68rem] font-medium bg-amber-500/15 text-amber-300">
                {{ m.observacion }}
              </span>
              <span v-else class="text-slate-600">—</span>
            </td>
            <td class="px-4 py-2.5 text-center">
              <button type="button" class="text-slate-500 hover:text-red-400 transition-colors" title="Quitar" @click="$emit('quitar', m.key)">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <p v-if="error" class="px-5 py-2.5 text-xs text-red-400 border-t border-slate-700">{{ error }}</p>

    <!-- Footer -->
    <div class="flex items-center justify-between px-5 py-4 border-t border-slate-700 bg-slate-900/30">
      <button
        type="button"
        class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-400 hover:text-slate-200 transition-colors"
        :disabled="materias.length === 0"
        @click="$emit('limpiar-todo')"
      >
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
        </svg>
        Limpiar todo
      </button>

      <button
        type="button"
        :disabled="materias.length === 0 || guardando"
        class="inline-flex items-center gap-2 px-5 py-2 rounded-lg text-sm font-semibold
               bg-amber-500 hover:bg-amber-400 active:bg-amber-600 text-slate-900
               transition-all duration-150 cursor-pointer border-none
               disabled:opacity-40 disabled:cursor-not-allowed
               shadow-lg shadow-amber-500/20"
        @click="$emit('terminar')"
      >
        <svg v-if="guardando" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
        <svg v-else width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <polyline points="20 6 9 17 4 12"/>
        </svg>
        {{ guardando ? 'Guardando...' : 'Terminar' }}
      </button>
    </div>
  </div>
</template>

<script setup>
defineProps({
  materias: { type: Array, default: () => [] },
  guardando: { type: Boolean, default: false },
  error: { type: String, default: '' },
})

defineEmits(['quitar', 'limpiar-todo', 'terminar'])
</script>