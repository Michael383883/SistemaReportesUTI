<template>
  <div v-if="categorias.length" class="mt-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm border-collapse">
        <thead>
          <tr class="bg-slate-900 dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800">
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100 w-10">Nº</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100 w-24">Gestión</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100">Tipo de Documento</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100">Detalle General</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100">Categoría</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100 w-28">Documento</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="6" class="px-4 py-6 text-center text-slate-400 dark:text-slate-500 text-xs">Cargando documentos…</td>
          </tr>
          <tr v-else-if="!documentos.length">
            <td colspan="6" class="px-4 py-6 text-center text-slate-400 dark:text-slate-500 text-xs">Sin documentos en las categorías seleccionadas</td>
          </tr>
          <tr
            v-for="(doc, i) in documentos" :key="doc.ID_DOCUMENTO"
            class="border-b border-slate-100 dark:border-slate-800 transition-colors hover:bg-slate-100 dark:hover:bg-slate-700/40"
            :class="i % 2 === 0 ? 'bg-white dark:bg-slate-900' : 'bg-sky-100 dark:bg-sky-500/15'"
          >
            <td class="px-4 py-3 text-slate-800 dark:text-slate-500 font-medium text-[13px] tabular-nums">{{ doc.nro }}</td>
            <td class="px-4 py-3 text-slate-700 dark:text-slate-300 font-semibold text-xs whitespace-nowrap">
              {{ doc.GESTION }}{{ doc.PERIODO ? '/' + doc.PERIODO : '' }}
            </td>
            <td class="px-4 py-3 text-slate-700 dark:text-slate-300 text-xs">{{ doc.TIPO_DOCUMENTO || '—' }}</td>
            <td class="px-4 py-3 text-slate-600 dark:text-slate-400 text-xs">{{ doc.DETALLE_GENERAL || '—' }}</td>
            <td class="px-4 py-3 text-xs">
              <span class="inline-flex items-center px-2 py-0.5 rounded text-[0.68rem] font-semibold bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">
                {{ doc.CATEGORIA }}
              </span>
            </td>
            <td class="px-4 py-3">
              <div v-if="doc.tiene_archivo" class="flex items-center gap-1.5">
                <button
                  @click="$emit('ver-pdf', doc.ID_DOCUMENTO, false)"
                  class="inline-flex items-center gap-1 px-2 py-1 rounded text-[0.68rem] font-medium transition-colors bg-blue-50 text-blue-600 hover:bg-blue-100 dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-500/20"
                >Ver</button>
                <button
                  @click="$emit('ver-pdf', doc.ID_DOCUMENTO, true)"
                  class="inline-flex items-center gap-1 px-2 py-1 rounded text-[0.68rem] font-medium transition-colors bg-emerald-50 text-emerald-600 hover:bg-emerald-100 dark:bg-emerald-500/10 dark:text-emerald-400 dark:hover:bg-emerald-500/20"
                >PDF</button>
              </div>
              <span v-else class="text-slate-400 dark:text-slate-600 text-xs">—</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="px-4 py-2.5 border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/30 text-xs text-slate-500 dark:text-slate-500 text-right">
      {{ documentos.length }} documento{{ documentos.length !== 1 ? 's' : '' }} · {{ categorias.join(', ') }}
    </div>
  </div>
</template>

<script setup>
defineProps({
  documentos: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  // ── cambia de "categoria" (string) a "categorias" (array) ──
  categorias: { type: Array, default: () => [] },
})
defineEmits(['ver-pdf'])
</script>