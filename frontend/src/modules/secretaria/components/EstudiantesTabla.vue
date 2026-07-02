<template>
  <div class="flex flex-col gap-5">
    <div v-if="cargando" class="text-center py-10 text-slate-400 text-sm">
      Cargando estudiantes...
    </div>

    <div v-else-if="grupos.length === 0" class="text-center py-10 text-slate-400 text-sm">
      No se encontraron estudiantes con los filtros seleccionados.
    </div>

    <div
      v-else
      v-for="grupo in grupos"
      :key="grupo.clave"
      class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 overflow-hidden"
    >
      <!-- Header del grupo -->
      <div class="flex items-center justify-between px-6 py-4 bg-slate-800 rounded-t-xl">

        <div class="flex items-center gap-4 text-white">

          <h2 class="font-bold text-sm uppercase">
            Materia: {{ grupo.nombreMateria || grupo.materia }}
          </h2>

          <span v-if="grupo.docente" class="text-slate-400">•</span>

          <span v-if="grupo.docente" class="text-sm text-slate-100">
            {{ grupo.docente }}
          </span>

        </div>

        <div class="flex gap-2">

          <span class="rounded-full bg-slate-700 px-3 py-1 text-xs text-white">
            Grupo {{ grupo.grupo }}
          </span>

          <span class="rounded-full bg-blue-600/20 px-3 py-1 text-xs text-blue-300">
            Examen Normal
          </span>

          <span class="rounded-full bg-green-600/20 px-3 py-1 text-xs text-green-300 font-semibold">
            {{ grupo.estudiantes.length }} inscritos
          </span>

        </div>

      </div>

      <!-- Tabla -->
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-100">
              <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-6 py-3 w-10">Nro</th>
              <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Codigo</th>
              <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Nombre</th>
              <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Carrera</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50">
            <tr
              v-for="(est, idx) in grupo.estudiantes"
              :key="est.codEstudiante"
              class="hover:bg-blue-50/40 transition-colors group"
            >
              <!-- Nro -->
              <td class="px-6 py-3 text-slate-800 text-xs">{{ idx + 1 }}</td>

              <!-- Codigo -->
              <td class="px-6 py-3 text-slate-800 font-medium">{{ est.codEstudiante }}</td>

              <!-- Nombre -->
              <td class="px-4 py-3">
                <div class="flex items-center gap-2.5">
                  <span class="text-slate-800 font-medium">{{ est.estudiante }}</span>
                </div>
              </td>

              <!-- Carrera -->
              <td class="px-4 py-3">
                <span
                  :class="colorPlan(est.siglaPlan)"
                  class="inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold whitespace-nowrap"
                >
                  {{ est.siglaPlan }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  estudiantes: {
    type: Array,
    required: true,
    default: () => [],
  },
  cargando: {
    type: Boolean,
    default: false,
  },
})

// Agrupa la lista plana de estudiantes en grupos por Materia + Grupo
const grupos = computed(() => {
  const mapa = new Map()

  for (const est of props.estudiantes) {
    const clave = `${est.materia}-${est.grupo}`

    if (!mapa.has(clave)) {
      mapa.set(clave, {
        clave,
        materia: est.materia,
        nombreMateria: est.nombreMateria,
        grupo: est.grupo,
        nivel: est.nivel,
        siglaPlan: est.siglaPlan,
        nombrePlan: est.nombrePlan,
        docente: est.docente,
        estudiantes: [],
      })
    }

    mapa.get(clave).estudiantes.push(est)
  }

  return Array.from(mapa.values()).sort((a, b) => {
    if (a.materia !== b.materia) return a.materia.localeCompare(b.materia)
    return String(a.grupo).localeCompare(String(b.grupo))
  })
})

// Colores de badge por carrera (alineados a los del listado de talleres)
const COLORES = {
  'ADM': 'bg-blue-100 text-blue-700',
  'COM': 'bg-emerald-100 text-emerald-700',
  'CPB': 'bg-orange-100 text-orange-700',
  'FIN': 'bg-violet-100 text-violet-700',
  'ECO': 'bg-rose-100 text-rose-700',
}

const colorPlan = sigla => COLORES[sigla] || 'bg-slate-100 text-slate-700'
</script>