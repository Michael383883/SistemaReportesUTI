<template>
  <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">

    <!-- ── Cabecera del docente ──────────────────────────── -->
    <div class="flex items-center gap-3 px-5 py-4 bg-gradient-to-r from-slate-800 to-slate-700 text-white">
      <div class="flex-1 min-w-0">
       
        <h2 class="font-bold text-base truncate leading-tight mt-0.5">
          {{ docente.apellidos }} {{ docente.nombres }} - {{ docente.cod_docente }} 
        </h2>
        
      </div>
      
      
    </div>

    <!-- ── Grilla de carreras ─────────────────────────────── -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-px bg-slate-100">
      <div
        v-for="carrera in docente.carreras"
        :key="carrera.plan + carrera.carrera"
        class="bg-white"
      >
        <!-- Cabecera carrera (solo muestra total, no desplegable) -->
        <div class="px-4 py-3 flex items-center gap-2">
          <span
            class="text-xs font-extrabold tracking-wider px-2 py-0.5 rounded-md shrink-0"
            :class="colorClasses[carrera.carrera.toLowerCase()]?.badge ?? 'bg-slate-100 text-slate-600'"
          >
            {{ carrera.carrera }}
          </span>
          <span class="text-[11px] text-slate-400 flex-1 truncate">Codigo: {{ carrera.plan }}</span>
          <span
            class="text-lg font-extrabold tabular-nums"
            :class="colorClasses[carrera.carrera.toLowerCase()]?.num ?? 'text-slate-700'"
          >
            {{ carrera.subtotal }}
          </span>
          <span
            v-if="carrera.subtotal_examen_mesa"
            class="text-[11px] font-bold text-amber-600 tabular-nums"
            title="Examen de mesa"
          >
            +{{ carrera.subtotal_examen_mesa }}
          </span>
        </div>

        <!-- ── Materias dentro de la carrera ─────────────── -->
        <div class="border-t border-slate-100">
          <div
            v-for="materia in carrera.materias"
            :key="materia.cod_materia + materia.grupo"
          >
            <!-- Botón materia -->
            <button
              class="w-full text-left px-4 py-2.5 flex items-center gap-2 hover:bg-slate-50 transition-colors border-b border-slate-50"
              @click="toggleMateria(carrera.plan + materia.cod_materia + materia.grupo)"
            >
              <div class="flex-1 min-w-0">
                <p class="text-[11px] font-semibold text-slate-700 truncate leading-tight">
                  {{ materia.nom_materia }}
                </p>
                <p class="text-[10px] text-slate-400 mt-0.5">
                  Grupo {{ materia.grupo }} · cód. {{ materia.cod_materia }}
                </p>
              </div>

              <!-- Contador inscritos regulares de esta materia -->
              <span
                class="text-sm font-extrabold tabular-nums shrink-0"
                :class="colorClasses[carrera.carrera.toLowerCase()]?.num ?? 'text-slate-700'"
              >
                {{ materia.subtotal }}
              </span>

              <!-- Contador examen de mesa (si tiene) -->
              <span
                v-if="materia.subtotal_examen_mesa"
                class="text-[11px] font-bold text-amber-600 tabular-nums shrink-0"
                title="Examen de mesa"
              >
                +{{ materia.subtotal_examen_mesa }}
              </span>

              <!-- Chevron -->
              <svg
                class="w-3.5 h-3.5 text-slate-400 shrink-0 transition-transform duration-200"
                :class="{ 'rotate-180': abiertos.has(carrera.plan + materia.cod_materia + materia.grupo) }"
                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"
              >
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
              </svg>
            </button>

            <!-- Lista inscritos de la materia -->
            <Transition
              enter-active-class="transition-all duration-200 ease-out"
              enter-from-class="opacity-0 max-h-0"
              enter-to-class="opacity-100 max-h-96"
              leave-active-class="transition-all duration-150 ease-in"
              leave-from-class="opacity-100 max-h-96"
              leave-to-class="opacity-0 max-h-0"
            >
              <div
                v-if="abiertos.has(carrera.plan + materia.cod_materia + materia.grupo)"
                class="overflow-hidden bg-slate-50 border-b border-slate-100"
              >
                <!-- Inscritos regulares -->
                <p class="px-5 pt-2 pb-1 text-[10px] font-bold uppercase tracking-wide text-slate-400">
                  Regulares ({{ materia.subtotal }})
                </p>
                <ul class="divide-y divide-slate-100 max-h-60 overflow-y-auto">
                  <li
                    v-for="(est, idx) in materia.inscritos"
                    :key="'r-' + est.codigo"
                    class="flex items-center gap-2 px-5 py-2 text-xs hover:bg-white"
                  >
                    <span class="text-slate-300 w-4 text-right shrink-0 tabular-nums">{{ idx + 1 }}</span>
                    <span class="font-mono text-slate-400 w-20 shrink-0">{{ est.codigo }}</span>
                    <span class="text-slate-700 font-medium truncate">{{ est.nombre }}</span>
                  </li>
                  <li v-if="!materia.inscritos.length" class="px-5 py-2 text-xs text-slate-400 italic">
                    Sin inscritos regulares
                  </li>
                </ul>

                <!-- Examen de mesa -->
                <template v-if="materia.subtotal_examen_mesa">
                  <p class="px-5 pt-3 pb-1 text-[10px] font-bold uppercase tracking-wide text-amber-600 border-t border-slate-200 mt-1">
                    Examen de mesa ({{ materia.subtotal_examen_mesa }})
                  </p>
                  <ul class="divide-y divide-slate-100 max-h-60 overflow-y-auto">
                    <li
                      v-for="(est, idx) in materia.inscritos_examen_mesa"
                      :key="'e-' + est.codigo"
                      class="flex items-center gap-2 px-5 py-2 text-xs hover:bg-white bg-amber-50/40"
                    >
                      <span class="text-slate-300 w-4 text-right shrink-0 tabular-nums">{{ idx + 1 }}</span>
                      <span class="font-mono text-slate-400 w-20 shrink-0">{{ est.codigo }}</span>
                      <span class="text-slate-700 font-medium truncate">{{ est.nombre }}</span>
                    </li>
                  </ul>
                </template>
              </div>
            </Transition>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Fila de totales ────────────────────────────────── -->
    <div class="flex flex-wrap items-center justify-between gap-2 px-5 py-3 bg-slate-50 border-t border-slate-200">
      <div class="flex flex-wrap gap-2">
        <span
          v-for="carrera in docente.carreras"
          :key="'tot-' + carrera.carrera + carrera.plan"
          class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full"
          :class="colorClasses[carrera.carrera.toLowerCase()]?.pill ?? 'bg-slate-100 text-slate-600'"
        >
          {{ carrera.carrera }}
          <span class="font-extrabold">{{ carrera.subtotal }}</span>
        </span>
      </div>
      <div class="flex items-center gap-2">
        <span class="text-[10px] font-bold tracking-widest uppercase text-slate-400">Total</span>
        <span class="text-xl font-extrabold bg-blue-100 text-blue-800 px-4 py-0.5 rounded-full tabular-nums">
          {{ docente.total_inscritos }}
        </span>
        <span
          v-if="docente.total_examen_mesa"
          class="text-sm font-extrabold bg-amber-100 text-amber-700 px-3 py-0.5 rounded-full tabular-nums"
          title="Examen de mesa"
        >
          +{{ docente.total_examen_mesa }}
        </span>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  docente: { type: Object, required: true },
})

// Ahora el key es: plan + cod_materia + grupo  (nivel materia, no carrera)
const abiertos = ref(new Set())

function toggleMateria(key) {
  const next = new Set(abiertos.value)
  if (next.has(key)) next.delete(key)
  else next.add(key)
  abiertos.value = next
}

const colorClasses = {
  adm: { badge: 'bg-blue-100 text-blue-700',       num: 'text-blue-600',    pill: 'bg-blue-100 text-blue-700'       },
  eco: { badge: 'bg-emerald-100 text-emerald-700', num: 'text-emerald-600', pill: 'bg-emerald-100 text-emerald-700' },
  ccp: { badge: 'bg-purple-100 text-purple-700',   num: 'text-purple-600',  pill: 'bg-purple-100 text-purple-700'   },
  com: { badge: 'bg-orange-100 text-orange-700',   num: 'text-orange-600',  pill: 'bg-orange-100 text-orange-700'   },
  fin: { badge: 'bg-yellow-100 text-yellow-700',   num: 'text-yellow-600',  pill: 'bg-yellow-100 text-yellow-700'   },
  nn:  { badge: 'bg-slate-100 text-slate-600',     num: 'text-slate-500',   pill: 'bg-slate-100 text-slate-600'     },
}
</script>