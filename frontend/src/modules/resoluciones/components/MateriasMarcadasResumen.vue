<template>
  <div class="relative">
    <!-- Overlay de carga: aparece al confirmar la asignación -->
    <div
      v-if="guardando"
      class="absolute inset-0 z-20 flex flex-col items-center justify-center gap-3 bg-white/85 backdrop-blur-[1px]"
    >
      <svg class="w-8 h-8 text-amber-500 animate-spin" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
      </svg>
      <p class="text-sm font-medium text-gray-600 m-0">Guardando asignación…</p>
    </div>

    <div v-if="materias.length === 0" class="flex flex-col items-center gap-2 py-10 text-gray-400">
      <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path d="M9 17v-2m3 2v-4m3 4v-6M3 3h18M3 8h18M3 13h9"/>
      </svg>
      <p class="text-sm m-0">Todavía no marcaste ninguna materia.</p>
    </div>

    <div v-else class="overflow-x-auto">
      <table class="w-full text-xs">
        <thead class="bg-slate-800 border-b border-gray-100">
          <tr>
            <th class="px-4 py-2.5 text-left text-[0.68rem] font-semibold tracking-widest uppercase text-gray-100">Docente</th>
            <th class="px-4 py-2.5 text-left text-[0.68rem] font-semibold tracking-widest uppercase text-gray-100">Plan</th>
            <th class="px-4 py-2.5 text-left text-[0.68rem] font-semibold tracking-widest uppercase text-gray-100">Materia</th>
            <th class="px-4 py-2.5 text-left text-[0.68rem] font-semibold tracking-widest uppercase text-gray-100">Grupo</th>
            <th class="px-4 py-2.5 text-left text-[0.68rem] font-semibold tracking-widest uppercase text-gray-100">Observación</th>
            <th class="text-center px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-gray-100 w-24">Tipo de ingreso</th>
            <th class="px-4 py-2.5 text-center text-[0.68rem] font-semibold tracking-widest uppercase text-gray-100">Quitar</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="m in materias" :key="m.key" class="hover:bg-gray-50 transition-colors">
            <td class="px-4 py-2.5 text-gray-800 font-medium">
             {{ m.docente.apellidos ?? m.docente.APELLIDOS }} {{ m.docente.nombres ?? m.docente.NOMBRES }} 
            </td>
            <td class="px-4 py-2.5 text-gray-900 font-mono">{{ m.cod_plan }}</td>
            <td class="px-4 py-2.5 text-gray-900 font-mono">{{ m.cod_materia }}</td>
            <td class="px-4 py-2.5 text-gray-900">{{ m.grupo || '—' }}</td>
            <td class="px-4 py-2.5">
              <span v-if="m.observacion" class="inline-flex items-center px-2 py-0.5 rounded-full text-[0.68rem] font-medium bg-amber-100 text-amber-700">
                {{ m.observacion }}
              </span>
              <span v-else class="text-gray-300">—</span>
            </td>
            <td class="px-4 py-2.5 text-gray-900 font-mono">{{ m.tipo_ingreso }}</td>
            <td class="px-4 py-2.5 text-center">
              <button
                type="button"
                :disabled="guardando"
                class="text-gray-400 hover:text-red-500 transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                title="Quitar"
                @click="$emit('quitar', m.key)"
              >
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <p v-if="error" class="px-5 py-2.5 text-xs text-red-600 border-t border-gray-100 m-0">{{ error }}</p>

    <!-- Footer -->
    <div class="flex items-center justify-between px-5 py-4 border-t border-gray-100 bg-gray-50">
      <button
        type="button"
        class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-500 hover:text-gray-700 transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
        :disabled="materias.length === 0 || guardando"
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
        class="inline-flex items-center gap-2 px-5 py-2 rounded-lg text-sm font-semibold min-w-[130px] justify-center
               bg-amber-500 hover:bg-amber-400 active:bg-amber-600 text-white
               transition-colors duration-150 cursor-pointer border-none
               disabled:opacity-40 disabled:cursor-not-allowed"
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