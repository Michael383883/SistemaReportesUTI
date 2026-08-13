
<template>
  <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-sm border-collapse">
        <thead>
          <tr class="bg-slate-900 dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800">
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100 w-10">Nº</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100">Gestión</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100 w-16">Plan</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100">Materia</th>
            <th v-if="!agruparCompartidos" class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100 w-28">Compartido</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100 w-14">GRP</th>
            <th v-if="agruparCompartidos" class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100 min-w-[320px]">Comparte</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100">Resolución</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100">Designación</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100 w-28">Modalidad de ingreso</th>
            <th class="text-left px-4 py-3 text-[0.68rem] font-semibold tracking-widest uppercase text-slate-100 w-28">Documento</th>
          </tr>
        </thead>
        <tbody>
          <tr
  v-for="(fila, i) in filas"
  :key="fila.principal.nro ?? i"
  class="border-b border-slate-100 dark:border-slate-800 transition-colors hover:bg-slate-100 dark:hover:bg-slate-700/40"
  :class="i % 2 === 0 ? 'bg-white dark:bg-slate-900' : 'bg-sky-100 dark:bg-sky-500/15'"
>
            <!-- Nº -->
            <td class="px-4 py-3 text-slate-800 dark:text-slate-500 font-medium text-[13px] tabular-nums">{{ fila.nro }}</td>

            <!-- Gestión -->
            <td class="px-4 py-3">
              <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2 py-0.5 rounded" :class="tipoGestion(fila.principal.gestion).class">
                <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="tipoGestion(fila.principal.gestion).dot"/>
                {{ fila.principal.gestion }}
              </span>
            </td>

            <!-- Plan -->
            <td class="px-4 py-3">
              <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[0.68rem] font-bold tracking-wide" :class="tipoGrp(fila.principal.plan).class">
                <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="tipoGrp(fila.principal.plan).dot"/>
                {{ tipoGrp(fila.principal.plan).label }}
              </span>
            </td>

            <!-- Materia -->
            <td class="px-4 py-3 text-slate-800 dark:text-slate-200 font-medium">{{ fila.principal.materia }}</td>

            <!-- Compartido (modo plano) -->
            <td v-if="!agruparCompartidos" class="px-4 py-3">
              <span v-if="fila.esHija" class="inline-flex items-center px-2 py-0.5 rounded text-[0.68rem] font-semibold bg-violet-50 text-violet-600 dark:bg-violet-500/15 dark:text-violet-300">
                Compartido
              </span>
              <span v-else class="text-slate-400 dark:text-slate-600 text-xs">—</span>
            </td>

            <!-- GRP -->
            <td class="px-4 py-3 tabular-nums text-slate-700 dark:text-slate-300 font-semibold text-xs">{{ fila.principal.grp }}</td>

            <!-- Comparte (modo agrupado) -->
            <td v-if="agruparCompartidos" class="px-4 py-3">
              <div v-if="fila.hermanas?.length" class="flex flex-col gap-1">
                <div v-for="(h, hi) in fila.hermanas" :key="hi" class="flex items-center gap-1.5 flex-wrap text-[11px] leading-snug">
                  <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[0.65rem] font-bold tracking-wide" :class="tipoGrp(h.plan).class">
                    <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="tipoGrp(h.plan).dot"/>
                    {{ tipoGrp(h.plan).label }}
                  </span>
                  <span class="text-slate-800 dark:text-slate-300 font-medium truncate">{{ h.materia }}</span>
                  <span class="text-slate-500 whitespace-nowrap">· Grp {{ h.grp }}</span>
                </div>
              </div>
              <span v-else class="text-slate-400 dark:text-slate-600 text-xs">—</span>
            </td>

            <!-- Resolución -->
            <td class="px-4 py-3">
              <span v-if="fila.principal.resolucion" class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">{{ fila.principal.resolucion }}</span>
              <span v-else class="text-slate-400 dark:text-slate-600 text-xs">—</span>
            </td>

            <!-- Designación -->
            <td class="px-4 py-3 max-w-xs">
              <span v-if="fila.principal.designacion" class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed line-clamp-2" :title="fila.principal.designacion">
                {{ fila.principal.designacion }}
              </span>
              <span v-else class="text-slate-400 dark:text-slate-600 text-xs">—</span>
            </td>

            <!-- Modalidad de ingreso -->
            <td class="px-4 py-3 max-w-xs">
              <span v-if="fila.principal.tipo_ingreso" class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed line-clamp-2" :title="fila.principal.tipo_ingreso">
                {{ fila.principal.tipo_ingreso }}
              </span>
              <span v-else class="text-slate-400 dark:text-slate-600 text-xs">—</span>
            </td>

            <!-- Documento PDF -->
            <td class="px-4 py-3">
              <template v-if="fila.principal.resolucion && fila.principal.designacion">
                <div class="flex items-center gap-1.5">
                  <button
                    :disabled="loadingPdf[fila.principal.nro]"
                    @click="handleVer(fila.principal, codDocente)"
                    class="inline-flex items-center gap-1 px-2 py-1 rounded text-[0.68rem] font-medium transition-colors
                           bg-blue-50 text-blue-600 hover:bg-blue-100 dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-500/20 disabled:opacity-40 disabled:cursor-not-allowed"
                    title="Ver PDF"
                  >
                    <svg v-if="loadingPdf[fila.principal.nro]" class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <svg v-else class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                      <circle cx="12" cy="12" r="3"/>
                    </svg>
                    Ver
                  </button>

                  <button
                    :disabled="loadingPdf[fila.principal.nro]"
                    @click="handleDescargar(fila.principal, codDocente)"
                    class="inline-flex items-center gap-1 px-2 py-1 rounded text-[0.68rem] font-medium transition-colors
                           bg-emerald-50 text-emerald-600 hover:bg-emerald-100 dark:bg-emerald-500/10 dark:text-emerald-400 dark:hover:bg-emerald-500/20 disabled:opacity-40 disabled:cursor-not-allowed"
                    title="Descargar PDF"
                  >
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    PDF
                  </button>
                </div>
              </template>
              <span v-else class="text-slate-400 dark:text-slate-600 text-xs">—</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Footer -->
    <div class="px-4 py-2.5 border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/30 text-xs text-slate-500 dark:text-slate-500 text-right">
      {{ filas.length }} registro{{ filas.length !== 1 ? 's' : '' }}
    </div>
  </div>
</template>

<script setup>
import { computed, toRef } from 'vue'
import { useReporte } from '../composables/useReporte'
import { useReporteCom } from '../composables/useReporteCom'
import { useTablaFormato } from '../composables/reporte/useTablaFormato'
import { useAgrupacionCompartidos } from '../composables/reporte/useAgrupacionCompartidos'
import { usePdfMateria } from '../composables/reporte/usePdfMateria'

const props = defineProps({
  materias: { type: Array, default: () => [] },
  codDocente: { type: [String, Number], default: null },
  // false (default) → tabla plana, badge "Compartido" en la fila hija
  // true             → hija colgada de la fila del padre, columna "Comparte"
  agruparCompartidos: { type: Boolean, default: false },
})

const { tipoGestion, tipoGrp } = useTablaFormato()

const materiasRef = toRef(props, 'materias')
const { hijasIndices, filasAgrupadas } = useAgrupacionCompartidos(materiasRef)

// Arma un array uniforme de "filas" para el template sea cual sea el modo
const filas = computed(() => {
  if (props.agruparCompartidos) return filasAgrupadas.value

  return props.materias.map((m, i) => ({
    nro: m.nro,
    principal: m,
    esHija: hijasIndices.value.has(i),
    hermanas: [],
  }))
})

// El endpoint de PDF depende de qué versión del reporte se está mostrando
const { verPdfResolucion: verPdfNormal }     = useReporte()
const { verPdfResolucion: verPdfCompartido } = useReporteCom()
const verPdfActivo = computed(() => (props.agruparCompartidos ? verPdfCompartido : verPdfNormal))

const { loadingPdf, handleVer, handleDescargar } = usePdfMateria(
  (nro, descargar) => verPdfActivo.value(nro, descargar)
)
</script>