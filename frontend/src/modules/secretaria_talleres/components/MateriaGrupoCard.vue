<template>
  <div>
    <!-- Header materia (clickable para desplegar/colapsar) -->
    <button
      @click="abierto = !abierto"
      class="w-full flex items-center justify-between px-6 py-2.5 hover:bg-slate-50 transition-colors text-left"
    >
      <div class="flex items-center gap-3 min-w-0">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="h-4 w-4 text-slate-400 shrink-0 transition-transform duration-200"
          :class="{ 'rotate-90': abierto }"
          fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
        >
          <polyline points="9 18 15 12 9 6" />
        </svg>
        <span class="font-semibold text-sm text-slate-700 truncate">{{ abreviarPlanCodigo(plan) }}</span>
        <span class="font-semibold text-sm text-slate-700 truncate">{{ codigoMateria }}-{{ materia }}</span>
        <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs text-slate-600 shrink-0">
          Grupo {{ grupo }}
        </span>
      </div>

      <span class="rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700 shrink-0">
        {{ estudiantes.length }} inscrito{{ estudiantes.length !== 1 ? 's' : '' }}
      </span>
    </button>

    <!-- Tabla de estudiantes (solo si está abierto) -->
    <Transition
      enter-active-class="transition-all duration-200 ease-out"
      leave-active-class="transition-all duration-150 ease-in"
      enter-from-class="opacity-0"
      leave-to-class="opacity-0"
    >
      <div v-if="abierto" class="overflow-x-auto border-t border-slate-100 bg-slate-50/40">
        <table class="w-full text-sm">
          <thead>
            <tr class="bg-slate-50 border-b border-slate-100">
              <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-6 py-3 w-10">Nro</th>
              <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Codigo</th>
              <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Nombre</th>
              <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Carrera</th>
              <th class="text-center text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Contacto</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50 bg-white">
            <tr
              v-for="(est, idx) in estudiantes"
              :key="est.cod_estudiante"
              class="hover:bg-blue-50/40 transition-colors"
            >
              <td class="px-6 py-3 text-slate-800 text-xs">{{ idx + 1 }}</td>
              <td class="px-6 py-3 text-slate-800 font-medium">{{ est.codigo }}</td>
              <td class="px-4 py-3">
                <span class="text-slate-800 font-medium">{{ est.nom_estudiante }}</span>
              </td>
              <td class="px-4 py-3">
                <span
                  :class="colorPlan(est.plan)"
                  class="inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold whitespace-nowrap"
                >
                  {{ abreviarPlan(est.plan) }}
                </span>
              </td>
              <td class="px-4 py-3 text-center">
                <button
                  @click="$emit('ver-contacto', est)"
                  class="inline-flex items-center gap-1 rounded-lg bg-blue-800 hover:bg-blue-700 active:bg-blue-900 text-white text-xs font-semibold px-3 py-1.5 transition-colors shadow-sm"
                  title="Ver tarjeta de contacto"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0" />
                  </svg>
                  Ver
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </Transition>
  </div>
</template>

<script setup>
defineOptions({ name: 'MateriaGrupoCard' })

import { ref } from 'vue'
import { abreviarPlan, colorPlan } from './utils/planStyles'

const props = defineProps({
  plan:               { type: [String, Number], default: '' },
  materia:            { type: String, default: '' },
  codigoMateria:      { type: [String, Number], default: '' },
  grupo:              { type: [String, Number], default: '' },
  estudiantes:        { type: Array, default: () => [] },
  // Permite que el padre decida si esta materia arranca abierta o cerrada.
  abiertoPorDefecto:  { type: Boolean, default: false },
})

defineEmits(['ver-contacto'])

const abierto = ref(props.abiertoPorDefecto)

// Mapeo de código de plan -> abreviación
const PLAN_ABREV = {
  '059801': 'ECO',
  '109401': 'ADM',
  '089801': 'CCP',
  '125091': 'COM',
  '126091': 'FIN',
}

// Traduce el código de plan a su abreviación; si no está mapeado, muestra el código tal cual
function abreviarPlanCodigo(codigo) {
  return PLAN_ABREV[codigo] ?? codigo
}
</script>