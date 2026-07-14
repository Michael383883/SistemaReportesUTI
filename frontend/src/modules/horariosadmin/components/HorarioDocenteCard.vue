<template>
  <div
    class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm mb-6 break-inside-avoid"
  >
    <!-- Header Docente: código + nombre en una sola línea -->
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
          class="text-xs bg-white/10 border border-white/20 rounded-md px-3 py-1"
        >
          {{ materiasAgrupadas.length }} materias
        </div>
      </div>
    </div>

    <!-- Tabla -->
    <div class="overflow-x-auto">
      <table class="w-full border-collapse text-xs" style="table-layout: auto;">
        <thead>
          <tr class="bg-slate-50 border-b-2 border-slate-200">
            <!-- Plan / Niv combinados -->
            <th class="px-2 py-2 text-center text-[11px] font-bold uppercase tracking-wider text-slate-600 whitespace-nowrap">
              Plan - Niv
            </th>
            <th class="px-2 py-2 text-left text-[11px] font-bold uppercase tracking-wider text-slate-600 min-w-[160px]">
              Materia
            </th>
            <th class="px-2 py-2 text-center text-[11px] font-bold uppercase tracking-wider text-slate-600">
              Grp
            </th>
            <th
              v-for="dia in DIAS_ORDEN"
              :key="dia"
              v-show="tieneSesionesEnDia(dia)"
              class="px-1 py-2 text-center text-[11px] font-bold uppercase tracking-wider text-slate-600"
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
              :class="{ 'ring-1 ring-inset ring-amber-300': mat.compartido }"
            >
            <!-- Plan + Nivel juntos como "ADM - F" en un solo badge -->
            <td class="px-2 py-1.5 text-center">
              <span
                class="inline-block px-2 py-0.5 rounded-full text-[11px] font-bold border whitespace-nowrap"
                :style="{
                  background: colorCarrera(mat.carrera).bg,
                  color: colorCarrera(mat.carrera).text,
                  borderColor: colorCarrera(mat.carrera).border
                }"
              >
                {{ mat.carrera }} - {{ mat.nivel }}
              </span>
            </td>

            <!-- Materia: nombre arriba, código abajo -->
            <td class="px-3 py-1.5">
              <span class="block font-semibold text-slate-800 leading-snug text-[12px]">
                {{ mat.nombre }}
              </span>
              <span class="block text-[10px] font-semibold text-slate-400">
                {{ mat.materia }}
              </span>
            </td>

            <!-- Grupo -->
            <td class="text-center px-2 py-1.5 font-semibold text-slate-600">
              {{ mat.grupo }}
            </td>

            <!-- Días: solo los que tienen sesiones -->
            <td
              v-for="dia in DIAS_ORDEN"
              :key="dia"
              v-show="tieneSesionesEnDia(dia)"
              class="py-1 px-1 align-middle"
            >
              <template v-if="mat.sesiones.filter(x => x.dia === dia).length > 0">
  <div
    v-for="s in mat.sesiones.filter(x => x.dia === dia)"
    :key="`${s.horario}-${s.ambiente}`"
    class="rounded px-1.5 py-1 mb-0.5 border-l-4 bg-white"
    :style="{ borderLeftColor: colorCarrera(mat.carrera).border }"
  >
    <span class="block text-[10px] font-bold whitespace-nowrap text-slate-800 dark-color-carrera"
      :style="{ '--carrera-color': colorCarrera(mat.carrera).border }">
      {{ s.horario }}
    </span>
    <span class="block text-[9px] text-slate-500 dark-color-carrera"
      :style="{ '--carrera-color': colorCarrera(mat.carrera).border }">
      {{ s.ambiente }}
    </span>
  </div>
</template>
              <template v-else>
                <div class="h-8 rounded bg-slate-50 border border-dashed border-slate-200 min-w-[80px]" />
              </template>
            </td>

            <!-- CH -->
            <td class="text-center px-2 py-1.5">
              <span
                class="inline-block px-2 py-1 rounded-md bg-blue-50 text-blue-700 border border-blue-200 font-bold"
              >
                {{ mat.carga }}
              </span>
            </td>

            <!-- Inscritos -->
            <td class="text-center px-2 py-1.5 font-semibold text-slate-600">
              {{ mat.inscritos }}
            </td>

            <!-- Compartido -->
            <td class="text-center px-2 py-1.5">
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
              :colspan="3 + diasConSesiones.length"
              class="text-right px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-slate-600"
            >
              Total
            </td>
            <!-- CH real: grupos compartidos suman 1 -->
            <td class="text-center py-2.5">
              <span class="inline-block px-3 py-1 rounded-md bg-blue-100 text-blue-800 border border-blue-300 text-sm font-extrabold">
                {{ totalChReal }} CH
              </span>
            </td>
            <!-- Total inscritos alineado a la columna Ins. -->
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
import { useHorarioAdmin } from '../composables/useHorarioAdmin'

const { colorCarrera, agruparPorMateriaGrupo, DIAS_ORDEN, DIAS_LABEL } = useHorarioAdmin()

const props = defineProps({
  docente: { type: Object, required: true },
})

const materiasAgrupadas = computed(() => agruparPorMateriaGrupo(props.docente.horarios))

// Solo los días que tienen al menos una sesión (para ocultar columnas vacías)
function tieneSesionesEnDia(dia) {
  return materiasAgrupadas.value.some(mat =>
    mat.sesiones.some(s => s.dia === dia)
  )
}

const diasConSesiones = computed(() => DIAS_ORDEN.filter(d => tieneSesionesEnDia(d)))

// Total CH: materias compartidas (mismo valor en mat.comp) cuentan UNA sola vez con su CH real.
// Materias no compartidas suman su carga normalmente.
// Total CH: cualquier materia con comp (compartida) se cuenta UNA sola vez en total.
// Se toma el CH de la primera compartida encontrada, el resto se ignora.
const totalChReal = computed(() => {
  let compartidaContada = false
  let chCompartida = 0
  let chNormal = 0

  for (const mat of materiasAgrupadas.value) {
    // mat.compartido es el identificador real del grupo compartido
    // mat.comp es el valor display (puede ser 0, que es falsy)
    const esCompartida = mat.compartido !== undefined && mat.compartido !== null && mat.compartido !== ''
    if (esCompartida) {
      if (!compartidaContada) {
        chCompartida = Number(mat.carga) || 0
        compartidaContada = true
      }
    } else {
      chNormal += Number(mat.carga) || 0
    }
  }

  return chNormal + chCompartida
})

// Total inscritos: forzar Number para evitar concatenación si vienen como string
const totalInscritos = computed(() => {
  return materiasAgrupadas.value.reduce((acc, mat) => acc + (Number(mat.inscritos) || 0), 0)
})
</script>