<template>
  <div
    class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm mb-6 break-inside-avoid"
  >
    <!-- Header Docente: código + nombre en una sola línea (igual al completo) -->
     <div
  class="flex justify-between items-center px-5 py-3 bg-slate-800 text-white"
>
      <div class="flex flex-col min-w-0">
        <h2 class="text-[15px] font-bold truncate">
          {{ docente.apellidos }} {{ docente.nombres }} - {{ docente.docente }}
        </h2>
        
      </div>

      <div class="flex items-center gap-3 shrink-0 ml-4">
        <div
          class="bg-white/15 border border-white/30 rounded-lg px-3 py-1 text-center"
        >
          <span class="block text-2xl font-extrabold leading-none">
            {{ totalChReal }}
          </span>
          <span class="block text-[10px] opacity-80">hrs/sem</span>
        </div>
        <div
          class="text-xs bg-white/10 border border-white/20 rounded-md px-3 py-1"
        >
          {{ docente.materias?.length ?? 0 }} materias
        </div>
      </div>
    </div>

    <!-- Tabla resumen (sin columnas de día/hora/aula) -->
    <div class="overflow-x-auto">
      <table class="w-full border-collapse text-xs" style="table-layout: auto;">
        <thead>
          <tr class="bg-slate-50 border-b-2 border-slate-200">
            <!-- Plan / Niv combinados (igual al completo) -->
            <th class="px-2 py-2 text-center text-[11px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
              Plan - Niv
            </th>
            <th class="px-2 py-2 text-left text-[11px] font-bold uppercase tracking-wider text-slate-600 min-w-[160px]">
              Materia
            </th>
            <th class="px-2 py-2 text-center text-[11px] font-bold uppercase tracking-wider text-slate-600">
              Grp
            </th>
            <th class="px-2 py-2 text-center text-[11px] font-bold uppercase tracking-wider text-slate-600">
              CH
            </th>
            <th class="px-2 py-2 text-center text-[11px] font-bold uppercase tracking-wider text-slate-600">
              Ins.
            </th>
            <th class="px-2 py-2 text-center text-[11px] font-bold uppercase tracking-wider text-slate-600">
              Comp.
            </th>
          </tr>
        </thead>

        <tbody>
          <tr
            v-for="(mat, i) in docente.materias"
            :key="i"
            class="border-b border-slate-100 hover:bg-slate-50"
            :class="{ 'bg-amber-50': mat.COMPARTIDO }"
          >
            <!-- Plan + Nivel juntos como badge (igual al completo) -->
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

            <!-- Materia: nombre arriba, código abajo (igual al completo) -->
            <td class="px-3 py-1.5">
              <span class="block font-semibold text-slate-800 leading-snug text-[12px]">
                {{ mat.NOMBRE }}
              </span>
              <span class="block text-[10px] font-semibold text-slate-400">
                {{ mat.MATERIA }}
              </span>
            </td>

            <!-- Grupo -->
            <td class="text-center px-2 py-1.5 font-semibold text-slate-600">
              {{ mat.GRUPO }}
            </td>

            <!-- CH -->
            <td class="text-center px-2 py-1.5">
              <span
                class="inline-block px-2 py-1 rounded-md bg-blue-50 text-blue-700 border border-blue-200 font-bold"
              >
                {{ mat.CARGA_HORARIA }}
              </span>
            </td>

            <!-- Inscritos -->
            <td class="text-center px-2 py-1.5 font-semibold text-slate-600">
              {{ mat.TOTAL_NORMAL ?? '—' }}
            </td>

            <!-- Compartido (igual al completo) -->
            <td class="text-center px-2 py-1.5">
              <span
                v-if="mat.COMP"
                class="inline-block px-2 py-1 text-[10px] font-semibold rounded bg-amber-100 text-amber-800 border border-amber-300"
                :title="mat.COMPARTIDO"
              >
                {{ mat.COMP }}
              </span>
              <span v-else class="text-slate-300">—</span>
            </td>
          </tr>
        </tbody>

        <tfoot>
          <tr class="bg-slate-50 border-t-2 border-slate-200">
            <td
              colspan="3"
              class="text-right px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-slate-600"
            >
              Total
            </td>
            <!-- CH igual al completo -->
            <td class="text-center py-2.5">
              <span class="inline-block px-3 py-1 rounded-md bg-blue-100 text-blue-800 border border-blue-300 text-sm font-extrabold">
                {{ totalChReal }} CH
              </span>
            </td>
            <!-- Total inscritos -->
            <td class="text-center py-2.5">
              <span class="inline-block px-3 py-1 rounded-md bg-slate-100 text-slate-700 border border-slate-300 text-sm font-extrabold">
                {{ totalInscritos }}
              </span>
            </td>
            <td />
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

// Misma lógica de CH real que el componente completo:
// compartidas cuentan UNA sola vez, el resto suma normal.
const totalChReal = computed(() => {
  let compartidaContada = false
  let chCompartida = 0
  let chNormal = 0

  for (const mat of props.docente.materias ?? []) {
    const esCompartida = mat.COMPARTIDO !== undefined && mat.COMPARTIDO !== null && mat.COMPARTIDO !== ''
    if (esCompartida) {
      if (!compartidaContada) {
        chCompartida = Number(mat.CARGA_HORARIA) || 0
        compartidaContada = true
      }
    } else {
      chNormal += Number(mat.CARGA_HORARIA) || 0
    }
  }

  return chNormal + chCompartida
})

// Total inscritos
const totalInscritos = computed(() => {
  return (props.docente.materias ?? []).reduce(
    (acc, mat) => acc + (Number(mat.TOTAL_NORMAL) || 0),
    0
  )
})
</script>