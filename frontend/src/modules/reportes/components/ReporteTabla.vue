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
            <!-- Nueva columna -->
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-400 w-28">Documento</th>
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
              <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2 py-0.5 rounded"
                    :class="tipoGestion(m.gestion).class">
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
              <span v-if="m.compartido"
                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[0.68rem] font-semibold bg-violet-500/15 text-violet-300">
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
              <span v-if="m.designacion"
                    class="text-xs text-slate-400 leading-relaxed line-clamp-2"
                    :title="m.designacion">
                {{ m.designacion }}
              </span>
              <span v-else class="text-slate-600 text-xs">—</span>
            </td>

            <!-- ── Nueva columna: Documento PDF ── -->
            <td class="px-4 py-3">
              <template v-if="m.resolucion && m.designacion">
                <div class="flex items-center gap-1.5">

                  <!-- Botón Ver -->
                  <button
                    :disabled="loadingPdf[m.nro]"
                    @click="handleVer(m)"
                    class="inline-flex items-center gap-1 px-2 py-1 rounded text-[0.68rem] font-medium transition-colors
                           bg-blue-500/10 text-blue-400 hover:bg-blue-500/20 disabled:opacity-40 disabled:cursor-not-allowed"
                    title="Ver PDF"
                  >
                    <svg v-if="loadingPdf[m.nro]"
                         class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <svg v-else class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                      <circle cx="12" cy="12" r="3"/>
                    </svg>
                    Ver
                  </button>

                  <!-- Botón Descargar -->
                  <button
                    :disabled="loadingPdf[m.nro]"
                    @click="handleDescargar(m)"
                    class="inline-flex items-center gap-1 px-2 py-1 rounded text-[0.68rem] font-medium transition-colors
                           bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20 disabled:opacity-40 disabled:cursor-not-allowed"
                    title="Descargar PDF"
                  >
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    PDF
                  </button>

                </div>
              </template>
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
import { ref } from 'vue'
import { useReporte } from '../composables/useReporte'

defineProps({
  materias: { type: Array, default: () => [] },
})

const { verPdfResolucion } = useReporte()

// Controla el spinner por fila (usando m.nro como key)
const loadingPdf = ref({})

async function handleVer(m) {
  loadingPdf.value[m.nro] = true
  try {
    await verPdfResolucion(m.resolucion, false)
  } finally {
    loadingPdf.value[m.nro] = false
  }
}

async function handleDescargar(m) {
  loadingPdf.value[m.nro] = true
  try {
    await verPdfResolucion(m.resolucion, true)
  } finally {
    loadingPdf.value[m.nro] = false
  }
}

const tipoGestion = (gestion) => {
  if (gestion?.includes('Verano'))
    return { class: 'bg-orange-500/10 text-orange-400', dot: 'bg-orange-400' }
  if (gestion?.includes('Invierno'))
    return { class: 'bg-sky-500/10 text-sky-400', dot: 'bg-sky-400' }
  return { class: 'bg-slate-700/60 text-slate-300', dot: 'bg-slate-400' }
}
</script>