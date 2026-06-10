<template>
  <div
    class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm mb-6 break-inside-avoid"
  >
    <!-- Header Docente -->
    <div
      class="flex justify-between items-center px-5 py-3 bg-gradient-to-r from-slate-800 to-blue-600 text-white"
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
          <span class="block text-[10px] opacity-80">hrs/sem</span>
        </div>
        <div
          class="text-xs bg-white/10 border border-white/20 rounded-md px-3 py-1"
        >
          {{ materiasAgrupadas.length }} materias
        </div>
      </div>
    </div>

    <!-- Tabla -->
    <div class="overflow-x-auto">
      <table class="w-full border-collapse text-xs">
        <thead>
          <tr class="bg-slate-50 border-b-2 border-slate-200">
            <th class="px-2 py-2 text-center text-[11px] font-bold uppercase tracking-wider text-slate-600 min-w-[60px]">
              Plan
            </th>
            <th class="px-2 py-2 text-left text-[11px] font-bold uppercase tracking-wider text-slate-600 min-w-[200px]">
              Materia
            </th>
            <th class="px-2 py-2 text-center text-[11px] font-bold uppercase tracking-wider text-slate-600">
              Grp
            </th>
            <th class="px-2 py-2 text-center text-[11px] font-bold uppercase tracking-wider text-slate-600">
              Niv
            </th>
            <th
              v-for="dia in DIAS_ORDEN"
              :key="dia"
              class="px-1 py-2 text-center text-[11px] font-bold uppercase tracking-wider text-slate-600"
              :class="tieneSesionesEnDia(dia) ? 'min-w-[110px]' : 'min-w-[36px] w-[36px]'"
            >
              {{ DIAS_LABEL[dia]?.slice(0, 2) ?? dia }}
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
            v-for="(mat, i) in materiasAgrupadas"
            :key="i"
            class="border-b border-slate-100 hover:bg-slate-50"
            :class="{ 'bg-amber-50': mat.compartido }"
          >
            <!-- Plan (antes Carrera) -->
            <td class="px-2 py-2 text-center">
              <span
                class="inline-block px-2 py-1 rounded-full text-[11px] font-bold border"
                :style="{
                  background: colorCarrera(mat.carrera).bg,
                  color: colorCarrera(mat.carrera).text,
                  borderColor: colorCarrera(mat.carrera).border
                }"
              >
                {{ mat.carrera }}
              </span>
            </td>

            <!-- Materia -->
            <td class="px-3 py-2">
              <span class="block text-[10px] font-semibold text-slate-400">
                {{ mat.materia }}
              </span>
              <span class="block font-semibold text-slate-800 leading-snug">
                {{ mat.nombre }}
              </span>
            </td>

            <!-- Grupo -->
            <td class="text-center px-2 py-2 font-semibold text-slate-600">
              {{ mat.grupo }}
            </td>

            <!-- Nivel -->
            <td class="text-center px-2 py-2 font-semibold text-slate-600">
              {{ mat.nivel }}
            </td>

            <!-- Dias -->
            <td
              v-for="dia in DIAS_ORDEN"
              :key="dia"
              class="py-1 align-middle"
              :class="tieneSesionesEnDia(dia) ? 'px-1' : 'px-0 text-center'"
            >
              <!-- Tiene sesión ese día -->
              <template v-if="mat.sesiones.filter(x => x.dia === dia).length > 0">
                <div
                  v-for="s in mat.sesiones.filter(x => x.dia === dia)"
                  :key="`${s.horario}-${s.ambiente}`"
                  class="rounded px-2 py-1 mb-0.5 border-l-4"
                  :style="{
                    background: colorCarrera(mat.carrera).bg,
                    borderLeftColor: colorCarrera(mat.carrera).border
                  }"
                >
                  <span class="block text-[10px] font-bold text-slate-800 whitespace-nowrap">
                    {{ s.horario }}
                  </span>
                  <span class="block text-[9px] text-slate-500">
                    {{ s.ambiente }}
                  </span>
                </div>
              </template>

              <!-- Sin sesión: celda vacía compacta -->
              <template v-else>
                <div
                  v-if="tieneSesionesEnDia(dia)"
                  class="h-8 rounded bg-slate-50 border border-dashed border-slate-200"
                />
                <!-- Si ningún docente tiene clase ese día, solo muestra nada -->
              </template>
            </td>

            <!-- CH -->
            <td class="text-center px-2 py-2">
              <span
                class="inline-block px-2 py-1 rounded-md bg-blue-50 text-blue-700 border border-blue-200 font-bold"
              >
                {{ mat.carga }}
              </span>
            </td>

            <!-- Inscritos -->
            <td class="text-center px-2 py-2 font-semibold text-slate-600">
              {{ mat.inscritos }}
            </td>

            <!-- Compartido -->
            <td class="text-center px-2 py-2">
              <span
                v-if="mat.comp"
                class="inline-block px-2 py-1 text-[10px] font-semibold rounded bg-amber-100 text-amber-800 border border-amber-300"
                :title="mat.compartido"
              >
                {{ mat.comp }}
              </span>
              <span v-else class="text-slate-300">—</span>
            </td>
          </tr>
        </tbody>

        <tfoot>
          <tr class="bg-slate-50 border-t-2 border-slate-200">
            <td
              colspan="8"
              class="text-right px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-slate-600"
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
import { computed } from 'vue'
import { useHorarioAdmin } from '../composables/useHorarioAdmin'

const { colorCarrera, agruparPorMateriaGrupo, DIAS_ORDEN, DIAS_LABEL } = useHorarioAdmin()

const props = defineProps({
  docente: { type: Object, required: true },
})

const materiasAgrupadas = computed(() => agruparPorMateriaGrupo(props.docente.horarios))

// Determina si al menos UNA materia tiene sesión en ese día
// Así los días sin ninguna clase colapsan la columna
function tieneSesionesEnDia(dia) {
  return materiasAgrupadas.value.some(mat =>
    mat.sesiones.some(s => s.dia === dia)
  )
}
</script>