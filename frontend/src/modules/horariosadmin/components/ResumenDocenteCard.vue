<template>
  <div
    class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm mb-6 break-inside-avoid"
  >
    <!-- Header Docente -->
    <div
      class="flex justify-between items-center px-5 py-4 bg-gradient-to-r from-slate-800 to-blue-600 text-white"
    >
      <div>
        <span class="text-[11px] opacity-70 tracking-wider">
          {{ docente.docente }}
        </span>
        <h2 class="text-[15px] font-bold">
          {{ docente.apellidos }} {{ docente.nombres }}
        </h2>
      </div>

      <div class="flex items-center gap-3">
        <div
          class="bg-white/15 border border-white/30 rounded-lg px-3 py-1 text-center"
        >
          <span class="block text-2xl font-extrabold leading-none">
            {{ docente.total_ch }}
          </span>
          <span class="block text-[10px] opacity-80">
            hrs/sem
          </span>
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
      <table class="w-full border-collapse text-xs">
        <thead>
          <tr class="bg-slate-50 border-b-2 border-slate-200">
            <th
              class="px-2 py-2 text-center text-[11px] font-bold uppercase tracking-wider text-slate-600 min-w-[70px]"
            >
              Carrera
            </th>
            <th
              class="px-3 py-2 text-left text-[11px] font-bold uppercase tracking-wider text-slate-600 min-w-[60px]"
            >
              Código
            </th>
            <th
              class="px-3 py-2 text-left text-[11px] font-bold uppercase tracking-wider text-slate-600 min-w-[220px]"
            >
              Materia
            </th>
            <th
              class="px-2 py-2 text-center text-[11px] font-bold uppercase tracking-wider text-slate-600"
            >
              Grp
            </th>
            <th
              class="px-2 py-2 text-center text-[11px] font-bold uppercase tracking-wider text-slate-600"
            >
              Niv
            </th>
            <th
              class="px-2 py-2 text-center text-[11px] font-bold uppercase tracking-wider text-slate-600"
            >
              CH
            </th>
            <th
              class="px-2 py-2 text-center text-[11px] font-bold uppercase tracking-wider text-slate-600"
            >
              C
            </th>
            <th
              class="px-2 py-2 text-left text-[11px] font-bold uppercase tracking-wider text-slate-600 min-w-[160px]"
            >
              Compartido
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
            <!-- Carrera -->
            <td class="px-2 py-2 text-center">
              <span
                class="inline-block px-2 py-1 rounded-full text-[11px] font-bold border"
                :style="{
                  background: colorCarrera(mat.CARRERA).bg,
                  color: colorCarrera(mat.CARRERA).text,
                  borderColor: colorCarrera(mat.CARRERA).border
                }"
              >
                {{ mat.CARRERA }}
              </span>
            </td>

            <!-- Código -->
            <td class="px-3 py-2 text-[10px] font-semibold text-slate-400">
              {{ mat.MATERIA }}
            </td>

            <!-- Nombre materia -->
            <td class="px-3 py-2 font-semibold text-slate-800 leading-snug">
              {{ mat.NOMBRE }}
            </td>

            <!-- Grupo -->
            <td class="text-center px-2 py-2 font-semibold text-slate-600">
              {{ mat.GRUPO }}
            </td>

            <!-- Nivel -->
            <td class="text-center px-2 py-2 font-semibold text-slate-600">
              {{ mat.NIVEL }}
            </td>

            <!-- CH -->
            <td class="text-center px-2 py-2">
              <span
                class="inline-block px-2 py-1 rounded-md bg-blue-50 text-blue-700 border border-blue-200 font-bold"
              >
                {{ mat.CARGA_HORARIA }}
              </span>
            </td>

            <!-- C flag -->
            <td class="text-center px-2 py-2">
              <span
                v-if="mat.COMP"
                class="inline-block px-2 py-1 text-[10px] font-semibold rounded bg-amber-100 text-amber-800 border border-amber-300"
                :title="mat.COMPARTIDO"
              >
                {{ mat.COMP }}
              </span>
              <span v-else class="text-slate-300">—</span>
            </td>

            <!-- Compartido -->
            <td class="px-2 py-2">
              <span
                v-if="mat.COMPARTIDO"
                class="text-[10px] font-semibold text-orange-700"
              >
                {{ mat.COMPARTIDO }}
              </span>
              <span v-else class="text-slate-300">—</span>
            </td>
          </tr>
        </tbody>

        <tfoot>
          <tr class="bg-slate-50 border-t-2 border-slate-200">
            <td
              colspan="5"
              class="text-right px-4 py-3 text-xs font-bold uppercase tracking-wider text-slate-600"
            >
              TOTAL CARGA HORARIA SEMANAL
            </td>
            <td
              colspan="3"
              class="text-center text-lg font-extrabold text-slate-800"
            >
              {{ docente.total_ch }} hrs
            </td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</template>

<script setup>
import { useHorarioResumen } from '../composables/useHorarioResumen'

const { colorCarrera } = useHorarioResumen()

const props = defineProps({
  docente: { type: Object, required: true },
})
</script>