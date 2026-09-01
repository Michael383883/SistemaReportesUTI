<template>
  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden shadow-sm mb-3">

    <!-- ── Cabecera del docente ──────────────────────────── -->
    <div class="flex items-center justify-between gap-3 px-5 py-1 bg-slate-800 dark:bg-slate-950 text-white">
      <div class="min-w-0">
        <h2 class="font-semibold text-sm truncate leading-tight">
          {{ docente.cod_docente }} - {{ docente.apellidos }} {{ docente.nombres }}
        </h2>
      </div>

      <div class="flex flex-wrap gap-1.5 justify-end shrink-0">
        <span
          v-for="carrera in docente.carreras"
          :key="'badge-' + carrera.plan + carrera.carrera"
          class="text-xs font-semibold px-2.5 py-1 rounded-md"
          :class="colorClasses[carrera.carrera.toLowerCase()]?.pill ?? 'bg-slate-100 text-slate-700'"
        >
          {{ carrera.carrera }} {{ carrera.subtotal }}
        </span>
      </div>
    </div>

    <!-- ── Tabla de materias ──────────────────────────────── -->
    <table class="w-full text-sm table-fixed">
      <colgroup>
        <col class="w-[58%]" />
        <col class="w-[12%]" />
        <col class="w-[16%]" />
        <col class="w-[16%]" />
        <col class="w-[18%]" />
      </colgroup>
      <thead>
        <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800">
          <th class="text-left font-medium text-slate-800 dark:text-slate-400 text-xs px-4 py-2">Materia</th>
          <th class="text-left font-medium text-slate-800 dark:text-slate-400 text-xs px-2 py-2">Plan</th>
          <th class="text-right font-medium text-slate-800 dark:text-slate-400 text-xs px-2 py-2">Regular</th>
          <th class="text-right font-medium text-slate-800 dark:text-slate-400 text-xs px-2 py-2">Mesa</th>
          <th class="text-right font-medium text-slate-800 dark:text-slate-400 text-xs px-4 py-2">Total</th>
        </tr>
      </thead>
      <tbody>
        <template
          v-for="(item, idx) in materiasFlat"
          :key="item.key"
        >
          <!-- Fila materia (clic para expandir) -->
          <tr
            class="border-b cursor-pointer transition-colors"
            :class="[
              idx % 2 === 0
                ? 'bg-white dark:bg-slate-900 border-slate-100 dark:border-slate-800'
                : 'bg-slate-50 dark:bg-slate-800/60 border-slate-100 dark:border-slate-800',
              abiertos.has(item.key) ? 'bg-blue-50 dark:bg-slate-700/60' : 'hover:bg-slate-100 dark:hover:bg-slate-700/40'
            ]"
            @click="toggleMateria(item.key)"
          >
            <td class="px-4 py-2.5">
              <div class="flex items-center gap-1.5 min-w-0">
                <svg
                  class="w-3.5 h-3.5 text-slate-400 shrink-0 transition-transform duration-150"
                  :class="{ 'rotate-180': abiertos.has(item.key) }"
                  fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
                <div class="min-w-0">
                  <p class="font-medium text-slate-700 dark:text-slate-200 truncate leading-tight text-[13px]">
                    {{ item.materia.cod_materia }} - {{ item.materia.nom_materia }} - GRP {{ item.materia.grupo }}
                  </p>
                </div>
              </div>
            </td>
            <td class="px-2 py-2.5">
              <span
                class="text-[11px] font-semibold px-2 py-0.5 rounded-md"
                :class="colorClasses[item.carrera.carrera.toLowerCase()]?.badge ?? 'bg-slate-100 text-slate-600'"
              >
                {{ item.carrera.carrera }}
              </span>
            </td>
            <td class="text-right px-2 py-2.5 tabular-nums text-slate-700 dark:text-slate-200">
              {{ item.materia.subtotal }}
            </td>
            <td class="text-right px-2 py-2.5 tabular-nums text-slate-500 dark:text-slate-400">
              {{ item.materia.subtotal_examen_mesa || 0 }}
            </td>
            <td class="text-right px-4 py-2.5 tabular-nums font-semibold text-slate-800 dark:text-slate-100">
              {{ item.materia.subtotal + (item.materia.subtotal_examen_mesa || 0) }}
            </td>
          </tr>

          <!-- Fila expandida: lista de estudiantes -->
          <tr v-if="abiertos.has(item.key)" class="border-b border-slate-200 dark:border-slate-700">
            <td colspan="5" class="p-0 bg-slate-100 dark:bg-slate-800">
              <div class="px-4 pl-10 py-2 max-h-72 overflow-y-auto">

                <table class="w-full text-xs">
                  <thead>
                    <tr class="text-slate-400 dark:text-slate-500">
                      <th class="text-left font-medium py-1 pr-2 w-16">Código</th>
                      <th class="text-left font-medium py-1 pr-2">Nombre</th>
                      <th class="text-right font-medium py-1 w-20">Modalidad</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="(est, i) in item.materia.inscritos"
                      :key="'r-' + est.codigo"
                      class="border-t border-slate-200 dark:border-slate-700"
                      :class="i % 2 === 0 ? 'bg-white dark:bg-slate-900' : 'bg-slate-50 dark:bg-slate-800/60'"
                    >
                      <td class="py-1.5 pr-2 font-mono text-slate-500 dark:text-slate-400">{{ est.codigo }}</td>
                      <td class="py-1.5 pr-2 text-slate-700 dark:text-slate-200 truncate">{{ est.nombre }}</td>
                      <td class="py-1.5 text-right">
                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-md"
                              :class="colorClasses[item.carrera.carrera.toLowerCase()]?.badge ?? 'bg-slate-100 text-slate-600'">
                          Regular
                        </span>
                      </td>
                    </tr>
                    <tr
                      v-for="(est, i) in item.materia.inscritos_examen_mesa"
                      :key="'e-' + est.codigo"
                      class="border-t border-slate-200 dark:border-slate-700"
                      :class="i % 2 === 0 ? 'bg-amber-50/60 dark:bg-amber-900/20' : 'bg-amber-50/30 dark:bg-amber-900/10'"
                    >
                      <td class="py-1.5 pr-2 font-mono text-slate-500 dark:text-slate-400">{{ est.codigo }}</td>
                      <td class="py-1.5 pr-2 text-slate-700 dark:text-slate-200 truncate">{{ est.nombre }}</td>
                      <td class="py-1.5 text-right">
                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-md bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300">
                          Mesa
                        </span>
                      </td>
                    </tr>
                    <tr v-if="!item.materia.inscritos?.length && !item.materia.inscritos_examen_mesa?.length">
                      <td colspan="3" class="py-2 text-center text-slate-400 dark:text-slate-500 italic">
                        Sin inscritos
                      </td>
                    </tr>
                  </tbody>
                </table>

              </div>
            </td>
          </tr>
        </template>
      </tbody>

      <!-- ── Fila de totales ────────────────────────────────── -->
      <tfoot>
        <tr class="bg-slate-100 dark:bg-slate-950 border-t-2 border-slate-200 dark:border-slate-700">
          <td class="px-4 py-2.5 font-semibold text-slate-700 dark:text-slate-200 text-xs" colspan="2">Total</td>
          <td class="text-right px-2 py-2.5 font-semibold tabular-nums text-slate-700 dark:text-slate-200">
            {{ docente.total_inscritos }}
          </td>
          <td class="text-right px-2 py-2.5 font-semibold tabular-nums text-slate-500 dark:text-slate-400">
            {{ docente.total_examen_mesa || 0 }}
          </td>
          <td class="text-right px-4 py-2.5 font-bold tabular-nums text-slate-900 dark:text-white">
            {{ docente.total_inscritos + (docente.total_examen_mesa || 0) }}
          </td>
        </tr>
      </tfoot>
    </table>

  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  docente: { type: Object, required: true },
})

// key único por materia dentro de la carrera (para controlar expandido/colapsado)
const abiertos = ref(new Set())

function materiaKey(carrera, materia) {
  return `${carrera.plan}-${materia.cod_materia}-${materia.grupo}`
}

// Aplana carreras -> materias en un solo array para poder alternar colores de fila
// de forma consistente (idx % 2) sin que las filas expandidas rompan el patrón.
const materiasFlat = computed(() =>
  props.docente.carreras.flatMap((carrera) =>
    carrera.materias.map((materia) => ({
      carrera,
      materia,
      key: materiaKey(carrera, materia),
    }))
  )
)

function toggleMateria(key) {
  const next = new Set(abiertos.value)
  if (next.has(key)) next.delete(key)
  else next.add(key)
  abiertos.value = next
}

const colorClasses = {
  adm: { badge: 'bg-blue-100 text-blue-700',       pill: 'bg-blue-100 text-blue-700' },
  eco: { badge: 'bg-emerald-100 text-emerald-700', pill: 'bg-emerald-100 text-emerald-700' },
  ccp: { badge: 'bg-purple-100 text-purple-700',   pill: 'bg-purple-100 text-purple-700' },
  com: { badge: 'bg-orange-100 text-orange-700',   pill: 'bg-orange-100 text-orange-700' },
  fin: { badge: 'bg-yellow-100 text-yellow-700',   pill: 'bg-yellow-100 text-yellow-700' },
  nn:  { badge: 'bg-slate-100 text-slate-600',     pill: 'bg-slate-100 text-slate-600' },
}
</script>