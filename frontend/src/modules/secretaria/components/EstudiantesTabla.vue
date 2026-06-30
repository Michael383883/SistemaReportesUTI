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
      class="rounded-2xl shadow-sm ring-1 ring-slate-200 overflow-hidden bg-white"
    >
      <!-- Header del grupo -->
      <div class="flex items-center justify-between px-6 py-4 bg-slate-800">
        <div class="flex items-center gap-3">
          <div class="h-8 w-8 rounded-lg bg-white/20 flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2" />
            </svg>
          </div>
          <div>
            <div class="text-white font-bold text-[15px] tracking-wide">
              {{ grupo.nombreMateria || grupo.materia }}
            </div>
            <div class="text-slate-400 text-[13px] mt-0.5">
              Grupo {{ grupo.grupo }} · {{ grupo.siglaPlan }} · Nivel {{ grupo.nivel }}
            </div>
          </div>
        </div>
        <span class="rounded-full bg-white/20 text-white text-xs font-semibold px-3 py-1">
          {{ grupo.estudiantes.length }} inscritos
        </span>
      </div>

      <!-- Info docente -->
      <div v-if="grupo.docente" class="px-6 py-2.5 bg-blue-50 border-b border-blue-100 flex items-center gap-2 text-blue-700 text-xs">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        <span class="font-medium">Docente:</span>
        <span>{{ grupo.docente }}</span>
      </div>

      <!-- Tabla -->
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-100">
              <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-6 py-3 w-10">#</th>
              <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Nombre del Estudiante</th>
              <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Carrera</th>
              <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Nivel</th>
              <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Grupo</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50">
            <tr
              v-for="(est, idx) in grupo.estudiantes"
              :key="est.codEstudiante"
              class="hover:bg-blue-50/40 transition-colors group"
            >
              <td class="px-6 py-3 text-slate-400 text-xs">{{ idx + 1 }}</td>

              <td class="px-4 py-3">
                <div class="flex items-center gap-2.5">
                  <div class="h-7 w-7 rounded-full bg-blue-100 text-blue-500 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                      <path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/>
                      <path d="M5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/>
                    </svg>
                  </div>
                  <span class="text-slate-800 font-medium">{{ est.estudiante }}</span>
                  <span class="text-slate-500"> - </span>
                  <span class="text-slate-800 font-medium">{{ est.codEstudiante }}</span>
                </div>
              </td>

              <td class="px-4 py-3">
                <span
                  :class="colorPlan(est.siglaPlan)"
                  class="inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold whitespace-nowrap"
                >
                  {{ est.siglaPlan }}
                </span>
              </td>

              <td class="px-4 py-3 text-slate-600 text-xs font-medium">{{ est.nivel }}</td>

              <td class="px-4 py-3 text-slate-600 text-xs font-medium">{{ est.grupo }}</td>
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