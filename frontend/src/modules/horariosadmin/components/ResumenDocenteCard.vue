<template>
  <div
    class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-xl overflow-hidden shadow-sm mb-6 break-inside-avoid"
  >
    <!-- Header Docente -->
    <div
      class="flex justify-between items-center px-5 py-1 bg-slate-800 text-white"
    >
      <div class="flex flex-col min-w-0">
        <h2 class="text-[12px] font-bold truncate">
         {{ docente.docente }} - {{ docente.apellidos }} {{ docente.nombres }} 
        </h2>
      </div>

      <div class="flex items-center gap-3 shrink-0 ml-4">
        <div
          class="text-xs bg-white/10 border border-white/20 rounded-md px-3 py-1"
        >
          {{ docente.materias?.length ?? 0 }} materias
        </div>
      </div>
    </div>

    <!-- Tabla resumen -->
    <div class="overflow-x-auto">
      <table class="w-full border-collapse text-xs" style="table-layout: auto;">
        <thead>
          <tr class="bg-slate-50 dark:bg-slate-800 border-b-2 border-slate-200 dark:border-slate-700">
            <th class="px-2 py-2 text-center text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 whitespace-nowrap">
              Plan - Niv
            </th>
            <th class="px-2 py-2 text-left text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 min-w-[160px]">
              Materia
            </th>
            <th class="px-2 py-2 text-center text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">
              Grp
            </th>
            <th class="px-2 py-2 text-center text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">
              CH
            </th>
            <th class="px-2 py-2 text-center text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">
              Ins.
            </th>
            <th class="px-2 py-2 text-center text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">
              C
            </th>
            <th class="px-3 py-2 text-left text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 min-w-[150px]">
              Compartido
            </th>
          </tr>
        </thead>

        <tbody>
          <tr
            v-for="(mat, i) in docente.materias"
            :key="i"
            class="border-b border-slate-200 dark:border-slate-700 transition-colors"
            :class="filaClase(mat, i)"
          >
            <!-- Plan + Nivel -->
            <td class="px-2 py-1.5 text-center">
              <span
                class="inline-block px-2 py-0.5 rounded-full text-[11px] font-bold border whitespace-nowrap"
                :style="{
                  background: colorCarrera(mat.CARRERA).bg,
                  color: colorCarrera(mat.CARRERA).text,
                  borderColor: colorCarrera(mat.CARRERA).border
                }"
              >
                {{ mat.CARRERA }} - {{ mat.NIVEL }}
              </span>
            </td>

            <!-- Materia -->
            <td class="px-3 py-1.5">
              <span class="text-slate-800 dark:text-slate-100 font-medium truncate">
               {{ mat.MATERIA }} - {{ mat.NOMBRE }}
              </span>
            </td>

            <!-- Grupo -->
            <td class="text-center px-2 py-1.5 font-semibold text-slate-600 dark:text-slate-300">
              {{ mat.GRUPO }}
            </td>

            <!-- CH -->
            <td class="text-center px-2 py-1.5">
              <span
                class="inline-block px-2 py-1 rounded-md bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-700 font-bold"
              >
                {{ chMostrada(mat) }}
              </span>
            </td>

            <!-- Inscritos -->
            <td class="text-center px-2 py-1.5 font-semibold text-slate-600 dark:text-slate-300">
              {{ mat.TOTAL_NORMAL ?? '—' }}
            </td>

            <!-- C: badge origen (0) / derivada (1) -->
            <td class="text-center px-2 py-1.5">
              <span
                v-if="esCompartido(mat)"
                class="inline-flex items-center justify-center w-5 h-5 rounded-full text-[10px] font-extrabold border"
                :class="esOrigen(mat)
                  ? 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-900/40 dark:text-amber-300 dark:border-amber-600'
                  : 'bg-purple-100 text-purple-800 border-purple-300 dark:bg-purple-900/40 dark:text-purple-300 dark:border-purple-600'"
              >
                {{ mat.COMP }}
              </span>
              <span v-else class="text-slate-300 dark:text-slate-600">—</span>
            </td>

            <!-- Compartido: texto descriptivo -->
            <td class="px-3 py-1.5">
              <span
                v-if="esCompartido(mat)"
                class="text-[10.5px] font-semibold"
                :class="esOrigen(mat)
                  ? 'text-amber-700 dark:text-amber-300'
                  : 'text-purple-700 dark:text-purple-300'"
              >
                {{ mat.COMPARTIDO }}
              </span>
              <span v-else class="text-slate-300 dark:text-slate-600">—</span>
            </td>
          </tr>
        </tbody>

        <tfoot>
          <tr class="bg-slate-100 dark:bg-slate-800 border-t-2 border-slate-200 dark:border-slate-700">
            <td
              colspan="3"
              class="text-right px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300"
            >
              Total
            </td>
            <td class="text-center py-2.5">
              <span class="inline-block px-3 py-1 rounded-md bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-300 border border-blue-300 dark:border-blue-700 text-sm font-extrabold">
                {{ totalChReal }} 
              </span>
              <span
                translate="no"
                class="inline-block px-3 py-1 rounded-md bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-300 border border-green-300 dark:border-green-700 text-sm font-extrabold"
              >
                Mes({{ totalChReal * 4 }})
              </span>
            </td>
            <td class="text-center py-2.5">
              <span class="inline-block px-3 py-1 rounded-md bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-600 text-sm font-extrabold">
                {{ totalInscritos }}
              </span>
            </td>
            <td colspan="2" />
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useHorarioResumen } from '../composables/useHorarioResumen'

const { colorCarrera } = useHorarioResumen()

const props = defineProps({
  docente: { type: Object, required: true },
})

function norm(v) {
  if (v === null || v === undefined) return ''
  return String(v).trim()
}

function esCompartido(mat) {
  return norm(mat.COMPARTIDO) !== ''
}

// Origen = 0 (materia "madre" que reparte carga a otra carrera)
// Derivada = 1 (materia que recibe/comparte carga desde el origen)
function esOrigen(mat) {
  return norm(mat.COMP) === '0'
}

// CH a mostrar en la fila: si es "derivada" (COMP = 1) se muestra 0,
// porque su carga horaria ya está contabilizada en la fila "origen"
// (COMP = 0) del mismo grupo compartido. El resto muestra su CH real.
function chMostrada(mat) {
  if (norm(mat.COMP) === '1') return 0
  return mat.CARGA_HORARIA
}

// Clase de fila:
// - Si la materia es compartida (origen/derivada), se conserva el acento
//   ámbar/morado con borde lateral, tal como ya lo tenía esta tabla.
// - Si NO es compartida, se aplica el mismo intercalado blanco/gris
//   (zebra striping) que usa la tabla base, en modo claro y oscuro.
function filaClase(mat, i) {
  if (esCompartido(mat)) {
    if (esOrigen(mat)) {
      return 'bg-amber-50/70 dark:bg-amber-900/10 border-l-4 border-l-amber-400 dark:border-l-amber-500 hover:bg-amber-100/70 dark:hover:bg-amber-900/20'
    }
    return 'bg-purple-50/70 dark:bg-purple-900/10 border-l-4 border-l-purple-400 dark:border-l-purple-500 hover:bg-purple-100/70 dark:hover:bg-purple-900/20'
  }

  return i % 2 === 0
    ? 'bg-white dark:bg-slate-900 hover:bg-blue-50 dark:hover:bg-slate-700/60'
    : 'bg-gray-100 dark:bg-slate-800 hover:bg-blue-50 dark:hover:bg-slate-700/60'
}

// CH real: se suma exactamente lo mismo que se muestra en la columna CH
// de cada fila (chMostrada). Es decir: las filas "derivada" (COMP = 1)
// aportan 0 (su carga ya está contada en la fila "origen" del grupo
// compartido) y el resto suma su CARGA_HORARIA real.
const totalChReal = computed(() => {
  return (props.docente.materias ?? []).reduce(
    (acc, mat) => acc + (Number(chMostrada(mat)) || 0),
    0
  )
})

// Total inscritos: suma directa de todas las filas, sin filtrar
const totalInscritos = computed(() => {
  return (props.docente.materias ?? []).reduce(
    (acc, mat) => acc + (Number(mat.TOTAL_NORMAL) || 0),
    0
  )
})
</script>